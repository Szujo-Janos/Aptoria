<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\ProjectAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectContextController extends Controller
{
    public function switch(Request $request, Project $project, AuditLogger $auditLogger, ProjectAccessService $projectAccess): RedirectResponse
    {
        abort_unless($projectAccess->can($request->user(), $project, 'project.view'), 403, __('messages.project_members.access_denied'));

        $request->session()->put('current_project_id', $project->id);
        $auditLogger->record('selected', __('messages.audit_messages.project_selected'), $project, [], 'workspace');

        return redirect()->route('projects.show', $project)->with('status', __('messages.workspace.project_selected', ['project' => $project->name]));
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget('current_project_id');

        return redirect()->route('dashboard')->with('status', __('messages.workspace.project_context_cleared'));
    }
}
