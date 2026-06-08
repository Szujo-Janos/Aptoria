<?php

namespace App\Providers;

use App\Models\ApiMonitor;
use App\Models\AuthProfile;
use App\Models\CalendarEvent;
use App\Models\CompareItem;
use App\Models\CompareRun;
use App\Models\ContractValidationResult;
use App\Models\ContractValidationRun;
use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\EndpointPathParameter;
use App\Models\Environment;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\MonitorAlertEvent;
use App\Models\Project;
use App\Models\ProjectSetting;
use App\Models\QaReleaseGate;
use App\Models\QaReleaseGateItem;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\Setting;
use App\Models\Snapshot;
use App\Models\SnapshotItem;
use App\Models\TestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Observers\CalendarActivityObserver;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        $this->shareLayoutSettings();

        foreach ($this->calendarAuditedModels() as $modelClass) {
            $modelClass::observe(CalendarActivityObserver::class);
        }
    }


    private function shareLayoutSettings(): void
    {
        View::composer('layouts.app', function ($view): void {
            $settings = app(SettingService::class);
            $currentProject = request()->route('project');
            $currentProject = $currentProject instanceof Project ? $currentProject : null;
            $routeName = request()->route()?->getName() ?? 'dashboard';

            $routeLabel = Str::headline(str_replace(['projects.', '.', '-'], ['', ' ', ' '], $routeName));

            $view->with([
                'aptoriaUiSettings' => $settings,
                'aptoriaAppName' => $settings->string('app.name', 'Aptoria'),
                'aptoriaShowLogo' => $settings->boolean('ui.show_header_logo', true),
                'aptoriaSidebarState' => $settings->string('ui.default_sidebar_state', 'expanded') ?: 'expanded',
                'aptoriaCompactDashboard' => $settings->boolean('ui.compact_dashboard', false),
                'aptoriaDashboardDensity' => $settings->string('ui.dashboard_density', 'comfortable') ?: 'comfortable',
                'aptoriaTableDensity' => $settings->string('ui.table_density', 'comfortable') ?: 'comfortable',
                'aptoriaTheme' => $settings->string('ui.theme', 'light') ?: 'light',
                'aptoriaEnableSweetalert' => $settings->boolean('ui.enable_sweetalert', true),
                'aptoriaProjectNavActive' => request()->routeIs(
                    'projects.index',
                    'projects.create',
                    'projects.show',
                    'projects.edit',
                    'projects.wizard.*',
                    'projects.environments.*',
                    'projects.auth-profiles.*',
                    'projects.settings.*',
                    'projects.assertion-rules.*',
                    'projects.test-suites.*',
                    'projects.test-cases.*',
                    'projects.test-execution.*',
                    'projects.qa-coverage.*',
                    'projects.qa-evidence.*',
                    'projects.contract-validations.*',
                    'projects.findings.*',
                    'projects.endpoints.*',
                    'projects.scans.*',
                    'projects.snapshots.*',
                    'projects.monitors.*',
                    'projects.calendar.*',
                    'projects.reports.*',
                    'projects.release-readiness.*',
                    'projects.release-gates.*'
                ),
                'aptoriaProjectMenuActive' => fn (string ...$patterns): string => request()->routeIs(...$patterns) ? 'active' : '',
                'aptoriaCurrentProject' => $currentProject,
                'aptoriaRouteName' => $routeName,
                'aptoriaRouteLabel' => $routeLabel,
                'aptoriaPageTitle' => $routeLabel ?: __('messages.dashboard.title'),
            ]);
        });
    }

    /** @return array<class-string<\Illuminate\Database\Eloquent\Model>> */
    private function calendarAuditedModels(): array
    {
        return [
            ApiMonitor::class,
            AuthProfile::class,
            CalendarEvent::class,
            Endpoint::class,
            EndpointAssertionRule::class,
            EndpointPathParameter::class,
            Environment::class,
            Finding::class,
            FindingEvidence::class,
            MonitorAlertEvent::class,
            Project::class,
            ProjectSetting::class,
            QaReleaseGate::class,
            Snapshot::class,
            TestCase::class,
            TestCaseResult::class,
            TestSuite::class,
        ];
    }
}
