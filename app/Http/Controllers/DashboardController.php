<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Services\ProjectWorkspaceService;
use App\Services\ProjectAccessService;
use App\Services\WorkspaceModeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ProjectWorkspaceService $workspaceService, ProjectAccessService $projectAccess, WorkspaceModeService $workspaceMode): View
    {
        $currentWorkspaceMode = $workspaceMode->current($request);
        $projectQuery = $workspaceMode->applyMode($projectAccess->visibleProjectsQuery($request->user()), $currentWorkspaceMode);
        $projects = (clone $projectQuery)->latest()->limit(6)->get();
        $visibleProjectIds = $workspaceMode->applyMode($projectAccess->visibleProjectsQuery($request->user()), $currentWorkspaceMode)->pluck('id');
        $auditQuery = AuditLog::query();
        if (! $request->user()->isAdmin()) {
            $auditQuery->where(function ($query) use ($visibleProjectIds): void {
                $query->whereNull('project_id')->orWhereIn('project_id', $visibleProjectIds);
            });
        }
        $latestAudit = (clone $auditQuery)->with(['user', 'project'])->latest()->limit(8)->get();
        $currentProject = $this->currentProject($request, $projectAccess, $workspaceMode);

        return view('dashboard.index', [
            'projectCount' => (clone $projectQuery)->count(),
            'activeProjectCount' => (clone $projectQuery)->where('is_active', true)->count(),
            'activeProjects' => (clone $projectQuery)->where('is_active', true)->count(),
            'auditCount' => (clone $auditQuery)->count(),
            'latestProjects' => $projects,
            'projects' => $projects,
            'latestAuditLogs' => $latestAudit,
            'latestAudit' => $latestAudit,
            'currentProject' => $currentProject,
            'workspaceSummary' => $workspaceService->summary($currentProject),
        ]);
    }

    private function currentProject(Request $request, ProjectAccessService $projectAccess, WorkspaceModeService $workspaceMode): ?Project
    {
        $currentWorkspaceMode = $workspaceMode->current($request);
        $projectQuery = $workspaceMode->applyMode($projectAccess->visibleProjectsQuery($request->user()), $currentWorkspaceMode);
        $projectId = $request->session()->get('current_project_id');

        if ($projectId) {
            $project = (clone $projectQuery)->find($projectId);

            if ($project) {
                return $project;
            }

            $request->session()->forget('current_project_id');
        }

        return (clone $projectQuery)->latest()->first();
    }
}
