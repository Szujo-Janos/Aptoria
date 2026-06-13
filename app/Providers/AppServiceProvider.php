<?php

namespace App\Providers;

use App\Models\ApiMonitor;
use App\Models\AuthProfile;
use App\Models\CalendarEvent;
use App\Models\ClientPortalAccess;
use App\Models\ClientPortalAcknowledgement;
use App\Models\CompareItem;
use App\Models\CompareRun;
use App\Models\ContractValidationResult;
use App\Models\ContractValidationRun;
use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\EndpointPathParameter;
use App\Models\EndpointBehaviorLink;
use App\Models\Environment;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\MonitorAlertEvent;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ProjectSetting;
use App\Models\QaReleaseGate;
use App\Models\QaReleaseGateItem;
use App\Models\ReleaseDecision;
use App\Models\ReleaseWorkflow;
use App\Models\ReleaseWorkflowStep;
use App\Models\ReportVersion;
use App\Models\RiskAcceptance;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\Setting;
use App\Models\Snapshot;
use App\Models\SnapshotItem;
use App\Models\TestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Models\User;
use App\Observers\AuditLogObserver;
use App\Observers\CalendarActivityObserver;
use App\Services\Access\ProjectAccessService;
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
        $configuredUrl = (string) config('app.url');

        if (config('app.env') === 'production' && str_starts_with($configuredUrl, 'https://')) {
            URL::forceScheme('https');
        }

        $this->shareLayoutSettings();

        foreach ($this->calendarAuditedModels() as $modelClass) {
            $modelClass::observe(CalendarActivityObserver::class);
        }

        foreach ($this->auditLoggedModels() as $modelClass) {
            $modelClass::observe(AuditLogObserver::class);
        }
    }


    private function shareLayoutSettings(): void
    {
        View::composer('layouts.app', function ($view): void {
            $settings = app(SettingService::class);
            $currentProject = request()->route('project');
            $currentProject = $currentProject instanceof Project ? $currentProject : null;
            $routeName = request()->route()?->getName() ?? 'dashboard';

            $routeLabel = $this->resolveRouteLabel($routeName);

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
                    'projects.members.*',
                    'projects.assertion-rules.*',
                    'projects.test-suites.*',
                    'projects.test-cases.*',
                    'projects.test-execution.*',
                    'projects.qa-coverage.*',
                    'projects.qa-evidence.*',
                    'projects.qa-cockpit.*',
                    'projects.release-workflow.*',
                    'projects.blind-spots.*',
                    'projects.contract-validations.*',
                    'projects.contract-reality.*',
                    'projects.findings.*',
                    'projects.endpoints.*',
                    'projects.scans.*',
                    'projects.snapshots.*',
                    'projects.monitors.*',
                    'projects.calendar.*',
                    'projects.audit-log.*',
                    'projects.reports.*',
                    'projects.report-versions.*',
                    'projects.client-portal.*',
                    'projects.release-readiness.*',
                    'projects.release-gates.*',
                    'projects.release-decisions.*',
                    'projects.api-behavior.*',
                    'projects.evidence-graph.*',
                    'projects.risk-acceptances.*'
                ),
                'aptoriaProjectMenuActive' => fn (string ...$patterns): string => request()->routeIs(...$patterns) ? 'active' : '',
                'aptoriaCurrentProject' => $currentProject,
                'aptoriaCurrentProjectRoleLabel' => $currentProject ? app(ProjectAccessService::class)->roleLabel($currentProject, request()->user()) : null,
                'aptoriaCurrentProjectPermissions' => $currentProject ? app(ProjectAccessService::class)->permissionMap($currentProject, request()->user()) : [],
                'aptoriaRouteName' => $routeName,
                'aptoriaRouteLabel' => $routeLabel,
                'aptoriaPageTitle' => $routeLabel ?: __('messages.dashboard.title'),
            ]);
        });
    }

    private function resolveRouteLabel(string $routeName): string
    {
        $map = [
            'dashboard' => __('messages.dashboard.title'),
            'projects.members.*' => __('messages.project_members.short_title'),
            'projects.release-workflow.*' => __('messages.release_workflow.short_title'),
            'projects.release-decisions.*' => __('messages.release_decisions.short_title'),
            'projects.release-gates.*' => __('messages.release_gates.short_title'),
            'projects.release-readiness.*' => __('messages.nav.release_readiness_short'),
            'projects.report-versions.*' => __('messages.report_versions.short_title'),
            'projects.client-portal.*' => __('messages.client_portal.short_title'),
            'projects.qa-cockpit.*' => __('messages.qa_cockpit.short_title'),
            'projects.blind-spots.*' => __('messages.blind_spots.short_title'),
            'projects.risk-acceptances.*' => __('messages.risk_acceptances.short_title'),
            'projects.evidence-graph.*' => __('messages.evidence_graph.short_title'),
            'projects.contract-reality.*' => __('messages.contract_reality.short_title'),
            'projects.api-behavior.*' => __('messages.api_behavior.short_title'),
            'projects.qa-evidence.*' => __('messages.qa_evidence.short_title'),
            'projects.audit-log.*' => __('messages.nav.audit_log'),
            'projects.monitors.*' => __('messages.nav.monitors'),
            'projects.calendar.*' => __('messages.nav.calendar'),
            'projects.reports.builder.*' => __('messages.report_builder.short_title'),
            'projects.reports.*' => __('messages.nav.reports'),
        ];

        foreach ($map as $pattern => $label) {
            if (Str::is($pattern, $routeName)) {
                return $label;
            }
        }

        return Str::headline(str_replace(['projects.', '.', '-'], ['', ' ', ' '], $routeName));
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
            ProjectMembership::class,
            QaReleaseGate::class,
            Snapshot::class,
            TestCase::class,
            TestCaseResult::class,
            TestSuite::class,
        ];
    }

    /** @return array<class-string<\Illuminate\Database\Eloquent\Model>> */
    private function auditLoggedModels(): array
    {
        return [
            ApiMonitor::class,
            AuthProfile::class,
            CalendarEvent::class,
            CompareRun::class,
            ContractValidationRun::class,
            Endpoint::class,
            EndpointAssertionRule::class,
            EndpointPathParameter::class,
            EndpointBehaviorLink::class,
            Environment::class,
            Finding::class,
            FindingEvidence::class,
            MonitorAlertEvent::class,
            Project::class,
            ProjectSetting::class,
            ProjectMembership::class,
            QaReleaseGate::class,
            QaReleaseGateItem::class,
            ReleaseDecision::class,
            ReportVersion::class,
            ReleaseWorkflow::class,
            ReleaseWorkflowStep::class,
            RiskAcceptance::class,
            ClientPortalAccess::class,
            ClientPortalAcknowledgement::class,
            ScanRun::class,
            Setting::class,
            Snapshot::class,
            TestCase::class,
            TestCaseResult::class,
            TestSuite::class,
            User::class,
        ];
    }

}
