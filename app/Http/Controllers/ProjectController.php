<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectSetting;
use App\Services\AuditLogger;
use App\Services\ProjectAccessService;
use App\Services\ProjectWorkspaceService;
use App\Services\WorkspaceModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectAccessService $projectAccess, WorkspaceModeService $workspaceMode): View
    {
        $status = $request->query('status');
        $status = in_array($status, ['active', 'draft', 'paused'], true) ? $status : null;

        $activeWorkspaceMode = $workspaceMode->current($request);
        $projectsQuery = $workspaceMode->applyMode(
            $projectAccess->visibleProjectsQuery($request->user())->with(['owner', 'memberships.user'])->withCount('auditLogs'),
            $activeWorkspaceMode
        )->latest();

        if ($status) {
            $projectsQuery->where('status', $status);
        }

        return view('projects.index', [
            'projects' => $projectsQuery->paginate(12)->withQueryString(),
            'projectCount' => $workspaceMode->applyMode($projectAccess->visibleProjectsQuery($request->user()), $activeWorkspaceMode)->count(),
            'activeProjectCount' => $workspaceMode->applyMode($projectAccess->visibleProjectsQuery($request->user()), $activeWorkspaceMode)->where('status', 'active')->count(),
            'draftProjectCount' => $workspaceMode->applyMode($projectAccess->visibleProjectsQuery($request->user()), $activeWorkspaceMode)->where('status', 'draft')->count(),
            'pausedProjectCount' => $workspaceMode->applyMode($projectAccess->visibleProjectsQuery($request->user()), $activeWorkspaceMode)->where('status', 'paused')->count(),
            'statusFilter' => $status,
            'projectAccess' => $projectAccess,
        ]);
    }

    public function create(Request $request, WorkspaceModeService $workspaceMode): View
    {
        return view('projects.create', ['project' => new Project([
            'status' => 'draft',
            'workspace_type' => $workspaceMode->current($request),
        ])]);
    }

    public function store(Request $request, AuditLogger $auditLogger, ProjectAccessService $projectAccess, WorkspaceModeService $workspaceMode): RedirectResponse
    {
        $data = $this->validated($request);
        $data['workspace_type'] = WorkspaceModeService::normalize($data['workspace_type'] ?? $workspaceMode->current($request));
        $data['user_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = $data['status'] !== 'paused';

        $project = Project::create($data);
        $projectAccess->ensureOwnerMembership($project);
        $this->bootstrapDefaultEnvironment($project);
        $workspaceMode->set($request, $project->workspace_type);
        $request->session()->put('current_project_id', $project->id);
        $auditLogger->record('created', __('messages.audit_messages.project_created'), $project, [
            'base_url' => $project->base_url,
            'status' => $project->status,
            'workspace_type' => $project->workspace_type,
        ], 'workspace');

        return redirect()->route('projects.show', $project)->with('status', __('messages.projects.created'));
    }

    public function show(Project $project, ProjectWorkspaceService $workspaceService, ProjectAccessService $projectAccess, Request $request): View
    {
        return view('projects.show', [
            'project' => $project->load(['owner', 'memberships.user']),
            'workspaceSummary' => $workspaceService->summary($project),
            'currentProjectRole' => $projectAccess->roleLabel($projectAccess->roleFor($request->user(), $project)),
            'canManageProject' => $projectAccess->can($request->user(), $project, 'project.manage'),
            'canManageMembers' => $projectAccess->can($request->user(), $project, 'members.manage'),
        ]);
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', ['project' => $project]);
    }

    public function update(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $project->only(['name', 'base_url', 'environment_label', 'status', 'workspace_type', 'qa_owner', 'release_goal']);
        $data = $this->validated($request);
        $data['workspace_type'] = WorkspaceModeService::normalize($data['workspace_type'] ?? $project->workspace_type);
        $data['is_active'] = $data['status'] !== 'paused';
        $project->update($data);

        $auditLogger->record('updated', __('messages.audit_messages.project_updated'), $project, [
            'before' => $before,
            'after' => $project->only(['name', 'base_url', 'environment_label', 'status', 'workspace_type', 'qa_owner', 'release_goal']),
        ], 'workspace');

        return redirect()->route('projects.show', $project)->with('status', __('messages.projects.updated'));
    }

    public function destroy(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        if ((int) $request->session()->get('current_project_id') === (int) $project->id) {
            $request->session()->forget('current_project_id');
        }

        $auditLogger->record('deleted', __('messages.audit_messages.project_deleted'), $project, [
            'name' => $project->name,
            'base_url' => $project->base_url,
        ], 'workspace', 'warning');
        $project->delete();

        return redirect()->route('projects.index')->with('status', __('messages.projects.deleted'));
    }


    private function bootstrapDefaultEnvironment(Project $project): void
    {
        if (! Schema::hasTable('environments') || blank($project->base_url)) {
            return;
        }

        $label = filled($project->environment_label) ? $project->environment_label : 'Default API';
        $type = str_contains(strtolower($label), 'prod') ? 'production' : 'dev';

        $environment = $project->environments()->create([
            'name' => $label,
            'base_url' => $project->base_url,
            'environment_type' => $type,
            'is_production' => $type === 'production',
            'is_default' => true,
            'notes' => 'Created from project base URL during workspace setup.',
        ]);

        if (Schema::hasTable('project_settings')) {
            ProjectSetting::set($project, 'scan.default_environment_id', $environment->id);
            ProjectSetting::set($project, 'scan.require_confirmation', true);
            ProjectSetting::set($project, 'scan.safe_methods_only', true);
            ProjectSetting::set($project, 'scan.allow_private_networks', false);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_url' => ['nullable', 'url', 'max:500'],
            'environment_label' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'in:draft,active,paused'],
            'workspace_type' => ['nullable', 'in:live,sandbox'],
            'qa_owner' => ['nullable', 'string', 'max:160'],
            'release_goal' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $counter = 2;

        while (Project::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
