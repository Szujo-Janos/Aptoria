<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectWorkspaceService;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function show(string $module, ProjectWorkspaceService $workspaceService): View
    {
        $this->guardModule($module);

        return view('placeholders.module', [
            'module' => $module,
            'project' => null,
            'modules' => $workspaceService->modules(),
        ]);
    }

    public function project(Project $project, string $module, ProjectWorkspaceService $workspaceService): View
    {
        $this->guardModule($module);

        return view('placeholders.module', [
            'module' => $module,
            'project' => $project,
            'modules' => $workspaceService->modules(),
        ]);
    }

    private function guardModule(string $module): void
    {
        $allowed = [
            'environments', 'auth-profiles', 'qa-cockpit', 'endpoint-inventory', 'safe-scan', 'assertions', 'snapshots', 'findings',
            'evidence', 'release-readiness', 'release-gates', 'reports', 'calendar', 'project-settings', 'settings', 'client-portal',
        ];

        abort_unless(in_array($module, $allowed, true), 404);
    }
}
