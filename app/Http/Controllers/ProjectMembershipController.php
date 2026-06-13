<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Services\Access\ProjectAccessService;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectMembershipController extends Controller
{
    public function index(Project $project, ProjectAccessService $access, SettingService $settings): View
    {
        $access->authorize($project, request()->user(), 'project.view', __('messages.project_members.audit.members_page_access_denied'));

        $itemsPerPage = $settings->integer('app.items_per_page', 25);

        $memberships = $project->memberships()
            ->with(['user', 'invitedBy'])
            ->orderByRaw("CASE role WHEN 'project_admin' THEN 1 WHEN 'qa_engineer' THEN 2 WHEN 'reviewer' THEN 3 WHEN 'release_approver' THEN 4 ELSE 5 END")
            ->latest()
            ->paginate($itemsPerPage);

        $memberUserIds = $project->memberships()->pluck('user_id')->filter()->values();
        $userSearch = trim((string) request('user_q', ''));

        $availableUsersQuery = User::query()
            ->when($memberUserIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $memberUserIds))
            ->when($userSearch !== '', function ($query) use ($userSearch): void {
                $query->where(function ($inner) use ($userSearch): void {
                    $inner->where('name', 'like', '%'.$userSearch.'%')
                        ->orWhere('email', 'like', '%'.$userSearch.'%');
                });
            })
            ->orderBy('name')
            ->orderBy('email');

        $availableUsersCount = (clone $availableUsersQuery)->count();
        $availableUsers = $availableUsersQuery->limit(12)->get();
        $totalUsers = User::query()->count();

        return view('project_memberships.index', [
            'project' => $project,
            'memberships' => $memberships,
            'roleOptions' => ProjectMembership::translatedRoleOptions(),
            'rolePermissions' => ProjectMembership::ROLE_PERMISSIONS,
            'canManageMembers' => $access->can($project, request()->user(), 'members.manage'),
            'currentProjectRoleLabel' => $access->roleLabel($project, request()->user()),
            'currentPermissionMap' => $access->permissionMap($project, request()->user()),
            'availableUsers' => $availableUsers,
            'availableUsersCount' => $availableUsersCount,
            'memberCount' => $project->memberships()->count(),
            'totalUsers' => $totalUsers,
            'userSearch' => $userSearch,
        ]);
    }

    public function store(Request $request, Project $project, ProjectAccessService $access): RedirectResponse
    {
        $access->authorize($project, $request->user(), 'members.manage', __('messages.project_members.audit.member_add_denied'));

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', 'string', Rule::in(ProjectMembership::ROLES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'create_user' => ['nullable', 'boolean'],
            'new_user_name' => ['nullable', 'required_if:create_user,1', 'string', 'max:190'],
            'new_user_password' => ['nullable', 'required_if:create_user,1', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        $createdUser = false;

        if (! $user) {
            if (! $request->boolean('create_user')) {
                throw ValidationException::withMessages([
                    'email' => __('messages.project_members.user_not_found_create_hint'),
                ]);
            }

            $user = User::query()->create([
                'name' => $data['new_user_name'],
                'email' => $data['email'],
                'password' => $data['new_user_password'],
                'role' => 'user',
                'locale' => app()->getLocale(),
                'timezone' => config('app.timezone', 'UTC'),
            ]);
            $createdUser = true;
        }

        $membership = $project->memberships()->where('user_id', $user->id)->first();
        $previousRole = $membership?->role;

        $project->memberships()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'role' => $data['role'],
                'notes' => $data['notes'] ?? null,
                'invited_by_user_id' => $request->user()?->id,
                'joined_at' => $membership?->joined_at ?? now(),
            ]
        );

        $access->recordMembershipEvent(
            $project,
            $request->user(),
            $membership ? 'member_role_changed' : 'member_added',
            $user,
            (string) $data['role'],
            $previousRole
        );

        $message = $membership
            ? __('messages.project_members.updated')
            : ($createdUser ? __('messages.project_members.created_with_user') : __('messages.project_members.created'));

        return redirect()
            ->route('projects.members.index', $project)
            ->with('success', $message);
    }

    public function update(Request $request, Project $project, ProjectMembership $membership, ProjectAccessService $access): RedirectResponse
    {
        $access->authorize($project, $request->user(), 'members.manage', __('messages.project_members.audit.member_role_change_denied'));
        $this->ensureMembershipBelongsToProject($project, $membership);

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in(ProjectMembership::ROLES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $previousRole = (string) $membership->role;
        $membership->update([
            'role' => $data['role'],
            'notes' => $data['notes'] ?? null,
        ]);

        $access->recordMembershipEvent($project, $request->user(), 'member_role_changed', $membership->user, (string) $data['role'], $previousRole);

        return redirect()
            ->route('projects.members.index', $project)
            ->with('success', __('messages.project_members.updated'));
    }

    public function destroy(Request $request, Project $project, ProjectMembership $membership, ProjectAccessService $access): RedirectResponse
    {
        $access->authorize($project, $request->user(), 'members.manage', __('messages.project_members.audit.member_removal_denied'));
        $this->ensureMembershipBelongsToProject($project, $membership);

        if ((int) $project->user_id === (int) $membership->user_id) {
            return back()->withErrors(['membership' => __('messages.project_members.owner_remove_blocked')]);
        }

        $target = $membership->user;
        $role = (string) $membership->role;
        $membership->delete();

        if ($target instanceof User) {
            $access->recordMembershipEvent($project, $request->user(), 'member_removed', $target, $role);
        }

        return redirect()
            ->route('projects.members.index', $project)
            ->with('success', __('messages.project_members.deleted'));
    }

    private function ensureMembershipBelongsToProject(Project $project, ProjectMembership $membership): void
    {
        abort_unless((int) $membership->project_id === (int) $project->id, 404);
    }
}
