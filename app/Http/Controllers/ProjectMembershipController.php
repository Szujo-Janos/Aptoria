<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ProjectAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectMembershipController extends Controller
{
    public function index(Project $project, ProjectAccessService $projectAccess): View
    {
        $memberships = $project->memberships()
            ->with(['user', 'invitedBy'])
            ->orderByRaw("case role when 'project_admin' then 1 when 'qa_engineer' then 2 when 'release_approver' then 3 when 'reviewer' then 4 else 5 end")
            ->orderBy('created_at')
            ->get();

        return view('project_memberships.index', [
            'project' => $project->load('owner'),
            'memberships' => $memberships,
            'roles' => ProjectMembership::roles(),
            'statuses' => ProjectMembership::statuses(),
            'currentRole' => $projectAccess->roleLabel($projectAccess->roleFor(auth()->user(), $project)),
            'canManageMembers' => $projectAccess->can(auth()->user(), $project, 'members.manage'),
            'supportedLocales' => config('aptoria.supported_locale_names', ['en' => 'English', 'hu' => 'Magyar']),
            'supportedTimezones' => ['Europe/Budapest', 'UTC', 'Europe/London', 'Europe/Berlin', 'America/New_York', 'America/Los_Angeles'],
        ]);
    }

    public function store(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::exists('users', 'email')],
            'role' => ['required', Rule::in(ProjectMembership::roles())],
        ]);

        $user = User::query()->where('email', $data['email'])->firstOrFail();

        if ((int) $project->user_id === (int) $user->id) {
            return back()->withErrors(['email' => __('messages.project_members.owner_already_admin')])->withInput();
        }

        $membership = ProjectMembership::query()->firstOrNew([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $membership->fill([
            'role' => $data['role'],
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $request->user()->id,
            'added_at' => $membership->exists ? $membership->added_at : now(),
        ]);
        $membership->save();

        $auditLogger->record('updated', __('messages.audit_messages.project_member_added'), $project, [
            'member_user_id' => $user->id,
            'member_email' => $user->email,
            'role' => $membership->role,
        ], 'workspace');

        return redirect()->route('projects.members.index', $project)->with('status', __('messages.project_members.added'));
    }


    public function createUser(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(ProjectMembership::roles())],
            'locale' => ['required', 'string', 'in:en,hu'],
            'timezone' => ['required', 'string', 'in:Europe/Budapest,UTC,Europe/London,Europe/Berlin,America/New_York,America/Los_Angeles'],
        ]);

        $temporaryPassword = $this->generateTemporaryPassword();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'user',
            'locale' => $data['locale'],
            'timezone' => $data['timezone'],
            'password' => Hash::make($temporaryPassword),
            'password_change_required' => true,
        ]);

        $membership = ProjectMembership::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => $data['role'],
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $request->user()->id,
            'added_at' => now(),
        ]);

        $auditLogger->record('created', __('messages.audit_messages.user_created'), $user, [
            'subject_label' => $user->email,
            'managed_user_id' => $user->id,
            'managed_user_email' => $user->email,
            'role' => $user->role,
            'project_id' => $project->id,
            'project_role' => $membership->role,
            'password_change_required' => true,
        ], 'users');

        $auditLogger->record('updated', __('messages.audit_messages.project_member_added'), $project, [
            'member_user_id' => $user->id,
            'member_email' => $user->email,
            'role' => $membership->role,
            'created_from_project_access' => true,
        ], 'workspace');

        return redirect()->route('projects.members.index', $project)
            ->with('status', __('messages.project_members.created_user_and_added'))
            ->with('temporary_user_name', $user->name)
            ->with('temporary_user_email', $user->email)
            ->with('temporary_password', $temporaryPassword);
    }

    public function update(Request $request, Project $project, ProjectMembership $membership, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureMembershipBelongsToProject($project, $membership);
        $this->preventOwnerMembershipChange($project, $membership);

        $data = $request->validate([
            'role' => ['required', Rule::in(ProjectMembership::roles())],
            'status' => ['required', Rule::in(ProjectMembership::statuses())],
        ]);

        if ((int) $request->user()->id === (int) $membership->user_id && $data['status'] !== ProjectMembership::STATUS_ACTIVE) {
            return back()->withErrors(['status' => __('messages.project_members.self_disable_blocked')]);
        }

        $before = $membership->only(['role', 'status']);
        $membership->update($data);

        $auditLogger->record('updated', __('messages.audit_messages.project_member_updated'), $project, [
            'member_user_id' => $membership->user_id,
            'before' => $before,
            'after' => $membership->only(['role', 'status']),
        ], 'workspace');

        return redirect()->route('projects.members.index', $project)->with('status', __('messages.project_members.updated'));
    }

    public function destroy(Request $request, Project $project, ProjectMembership $membership, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureMembershipBelongsToProject($project, $membership);
        $this->preventOwnerMembershipChange($project, $membership);

        if ((int) $request->user()->id === (int) $membership->user_id) {
            return back()->withErrors(['member' => __('messages.project_members.self_remove_blocked')]);
        }

        $memberEmail = $membership->user?->email;
        $memberUserId = $membership->user_id;
        $membership->delete();

        $auditLogger->record('updated', __('messages.audit_messages.project_member_removed'), $project, [
            'member_user_id' => $memberUserId,
            'member_email' => $memberEmail,
        ], 'workspace', 'warning');

        return redirect()->route('projects.members.index', $project)->with('status', __('messages.project_members.removed'));
    }

    private function generateTemporaryPassword(): string
    {
        return 'Temp-'.bin2hex(random_bytes(5)).'!9Aa';
    }

    private function ensureMembershipBelongsToProject(Project $project, ProjectMembership $membership): void
    {
        abort_unless((int) $membership->project_id === (int) $project->id, 404);
    }

    private function preventOwnerMembershipChange(Project $project, ProjectMembership $membership): void
    {
        if ((int) $membership->user_id === (int) $project->user_id) {
            abort(403, __('messages.project_members.owner_locked'));
        }
    }
}
