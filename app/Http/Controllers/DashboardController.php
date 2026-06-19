<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Services\ProjectWorkspaceService;
use App\Services\ProjectAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ProjectWorkspaceService $workspaceService, ProjectAccessService $projectAccess): View
    {
        $projectQuery = $projectAccess->visibleProjectsQuery($request->user());
        $projects = (clone $projectQuery)->latest()->limit(6)->get();
        $visibleProjectIds = $projectAccess->visibleProjectsQuery($request->user())->pluck('id');
        $auditQuery = AuditLog::query();
        if (! $request->user()->isAdmin()) {
            $auditQuery->where(function ($query) use ($visibleProjectIds): void {
                $query->whereNull('project_id')->orWhereIn('project_id', $visibleProjectIds);
            });
        }
        $latestAudit = (clone $auditQuery)->with(['user', 'project'])->latest()->limit(8)->get();
        $currentProject = $this->currentProject($request, $projectAccess);

        return view('dashboard.index', [
            'projectCount' => (clone $projectAccess->visibleProjectsQuery($request->user()))->count(),
            'activeProjectCount' => (clone $projectAccess->visibleProjectsQuery($request->user()))->where('is_active', true)->count(),
            'activeProjects' => (clone $projectAccess->visibleProjectsQuery($request->user()))->where('is_active', true)->count(),
            'auditCount' => (clone $auditQuery)->count(),
            'latestProjects' => $projects,
            'projects' => $projects,
            'latestAuditLogs' => $latestAudit,
            'latestAudit' => $latestAudit,
            'currentProject' => $currentProject,
            'workspaceSummary' => $workspaceService->summary($currentProject),
        ]);
    }

    private function currentProject(Request $request, ProjectAccessService $projectAccess): ?Project
    {
        $projectId = $request->session()->get('current_project_id');

        if ($projectId) {
            $project = $projectAccess->visibleProjectsQuery($request->user())->find($projectId);

            if ($project) {
                return $project;
            }

            $request->session()->forget('current_project_id');
        }

        return $projectAccess->visibleProjectsQuery($request->user())->latest()->first();
    }
}
