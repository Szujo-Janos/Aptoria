<?php

namespace App\Providers;

use App\Models\ProgramSetting;
use App\Services\LicenseGuardService;
use App\Services\ProjectAccessService;
use App\Services\WorkspaceModeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $projectMenuItems = collect();
            $currentProject = null;
            $currentProjectRoleLabel = null;
            $currentProjectPermissions = [];
            /** @var WorkspaceModeService $workspaceModeService */
            $workspaceModeService = app(WorkspaceModeService::class);
            $currentWorkspaceMode = $workspaceModeService->current(request());
            $liveProjectCount = 0;
            $sandboxProjectCount = 0;

            if (Auth::check() && Schema::hasTable('projects')) {
                /** @var ProjectAccessService $projectAccess */
                $projectAccess = app(ProjectAccessService::class);
                $projectMenuItems = $workspaceModeService
                    ->applyMode($projectAccess->visibleProjectsQuery(Auth::user()), $currentWorkspaceMode)
                    ->latest()
                    ->limit(8)
                    ->get();

                $liveProjectCount = $workspaceModeService->applyMode($projectAccess->visibleProjectsQuery(Auth::user()), WorkspaceModeService::LIVE)->count();
                $sandboxProjectCount = $workspaceModeService->applyMode($projectAccess->visibleProjectsQuery(Auth::user()), WorkspaceModeService::SANDBOX)->count();

                $projectId = request()->session()->get('current_project_id');
                $currentProject = $projectId ? $projectAccess->visibleProjectsQuery(Auth::user())->find($projectId) : null;

                if ($projectId && (! $currentProject || ! $workspaceModeService->matches($currentProject, $currentWorkspaceMode))) {
                    request()->session()->forget('current_project_id');
                    $currentProject = null;
                }

                if (! $currentProject && $projectMenuItems->isNotEmpty()) {
                    $currentProject = $projectMenuItems->first();
                }

                if ($currentProject) {
                    $currentProjectRoleLabel = $projectAccess->roleLabel($projectAccess->roleFor(Auth::user(), $currentProject));
                    $currentProjectPermissions = $projectAccess->permissionsFor(Auth::user(), $currentProject);
                }
            }

            $programAppName = ProgramSetting::get('app.name', config('app.name', 'Aptoria'));
            $programTimezone = ProgramSetting::get('app.timezone', config('app.timezone', 'Europe/Budapest'));
            config(['app.timezone' => $programTimezone]);
            date_default_timezone_set($programTimezone);

            $licenseStatus = app(LicenseGuardService::class)->status();

            $view->with('aptoriaVersion', config('aptoria.version'));
            $view->with('licenseStatus', $licenseStatus);
            $view->with('appName', $programAppName);
            $view->with('projectMenuItems', $projectMenuItems);
            $view->with('currentProject', $currentProject);
            $view->with('currentProjectRoleLabel', $currentProjectRoleLabel);
            $view->with('currentProjectPermissions', $currentProjectPermissions);
            $view->with('workspaceModeService', $workspaceModeService);
            $view->with('currentWorkspaceMode', $currentWorkspaceMode);
            $view->with('currentWorkspaceModeLabel', $workspaceModeService->label($currentWorkspaceMode));
            $view->with('currentWorkspaceModeShortLabel', $workspaceModeService->shortLabel($currentWorkspaceMode));
            $view->with('currentWorkspaceModeBadgeClass', $workspaceModeService->badgeClass($currentWorkspaceMode));
            $view->with('currentWorkspaceModeSoftBadgeClass', $workspaceModeService->softBadgeClass($currentWorkspaceMode));
            $view->with('liveProjectCount', $liveProjectCount);
            $view->with('sandboxProjectCount', $sandboxProjectCount);
        });
    }
}
