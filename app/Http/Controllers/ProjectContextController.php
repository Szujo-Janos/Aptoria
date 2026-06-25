<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\ProjectAccessService;
use App\Services\WorkspaceModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectContextController extends Controller
{
    public function switch(Request $request, Project $project, AuditLogger $auditLogger, ProjectAccessService $projectAccess, WorkspaceModeService $workspaceMode): RedirectResponse
    {
        abort_unless($projectAccess->can($request->user(), $project, 'project.view'), 403, __('messages.project_members.access_denied'));

        $workspaceMode->set($request, $workspaceMode->projectType($project));
        $request->session()->put('current_project_id', $project->id);
        $auditLogger->record('selected', __('messages.audit_messages.project_selected'), $project, [], 'workspace');

        return redirect()->route('projects.show', $project)->with('status', __('messages.workspace.project_selected', ['project' => $project->name]));
    }

    public function mode(Request $request, string $mode, AuditLogger $auditLogger, WorkspaceModeService $workspaceMode): RedirectResponse
    {
        $previousMode = $workspaceMode->current($request);
        $selectedMode = $workspaceMode->set($request, $mode);

        if ($previousMode !== $selectedMode) {
            $request->session()->forget('current_project_id');
            $auditLogger->record(
                'workspace_mode_switched_to_'.$selectedMode,
                __('messages.audit_messages.workspace_mode_switched', ['mode' => $workspaceMode->label($selectedMode)]),
                null,
                ['previous_mode' => $previousMode, 'selected_mode' => $selectedMode],
                'workspace'
            );
        }

        return redirect()->route('dashboard')->with('status', __('messages.workspace_mode.switched', ['mode' => $workspaceMode->label($selectedMode)]));
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget('current_project_id');

        return redirect()->route('dashboard')->with('status', __('messages.workspace.project_context_cleared'));
    }
}
