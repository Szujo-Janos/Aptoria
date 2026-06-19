<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\ProgramSetting;
use App\Services\ProjectAccessService;
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

            if (Auth::check() && Schema::hasTable('projects')) {
                /** @var ProjectAccessService $projectAccess */
                $projectAccess = app(ProjectAccessService::class);
                $projectMenuItems = $projectAccess->visibleProjectsQuery(Auth::user())->latest()->limit(8)->get();
                $projectId = request()->session()->get('current_project_id');
                $currentProject = $projectId ? $projectAccess->visibleProjectsQuery(Auth::user())->find($projectId) : null;

                if ($projectId && ! $currentProject) {
                    request()->session()->forget('current_project_id');
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

            $view->with('aptoriaVersion', config('aptoria.version'));
            $view->with('appName', $programAppName);
            $view->with('projectMenuItems', $projectMenuItems);
            $view->with('currentProject', $currentProject);
            $view->with('currentProjectRoleLabel', $currentProjectRoleLabel);
            $view->with('currentProjectPermissions', $currentProjectPermissions);
        });
    }
}
