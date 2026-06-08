<?php

namespace App\Http\Controllers;

use App\Models\ApiMonitor;
use App\Models\AuthProfile;
use App\Models\CalendarEvent;
use App\Models\CompareRun;
use App\Models\Endpoint;
use App\Models\Environment;
use App\Models\Project;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Services\AssertionEvaluationService;
use App\Services\RegressionEvaluationService;
use App\Services\ReleaseReadinessService;
use App\Services\Settings\SettingService;
use App\Services\Settings\SettingsRuntimeService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AssertionEvaluationService $assertions, RegressionEvaluationService $regressions, ReleaseReadinessService $readiness, SettingService $settings, SettingsRuntimeService $runtime): View
    {
        $riskCounts = Endpoint::query()
            ->selectRaw('risk_level, COUNT(*) as total')
            ->groupBy('risk_level')
            ->pluck('total', 'risk_level');

        $methodCounts = Endpoint::query()
            ->selectRaw('method, COUNT(*) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        $recentScans = ScanRun::query()
            ->where('created_at', '>=', now()->subDays($runtime->dashboardRangeDays() - 1)->startOfDay())
            ->get(['created_at']);

        $scanTrendData = collect(range($runtime->dashboardRangeDays() - 1, 1))
            ->map(function (int $daysAgo) use ($recentScans): array {
                $date = now()->subDays($daysAgo)->startOfDay();

                return [
                    'label' => $date->format('M d'),
                    'value' => $recentScans->filter(fn (ScanRun $scan): bool => $scan->created_at instanceof Carbon && $scan->created_at->isSameDay($date))->count(),
                ];
            })
            ->push([
                'label' => now()->format('M d'),
                'value' => $recentScans->filter(fn (ScanRun $scan): bool => $scan->created_at instanceof Carbon && $scan->created_at->isToday())->count(),
            ])
            ->values()
            ->all();

        $riskChartData = [
            ['label' => __('messages.endpoints.risks.critical'), 'value' => (int) ($riskCounts[Endpoint::RISK_CRITICAL] ?? 0)],
            ['label' => __('messages.endpoints.risks.high'), 'value' => (int) ($riskCounts[Endpoint::RISK_HIGH] ?? 0)],
            ['label' => __('messages.endpoints.risks.review'), 'value' => (int) ($riskCounts[Endpoint::RISK_REVIEW] ?? 0)],
            ['label' => __('messages.endpoints.risks.public'), 'value' => (int) ($riskCounts[Endpoint::RISK_PUBLIC] ?? 0)],
            ['label' => __('messages.endpoints.risks.low'), 'value' => (int) ($riskCounts[Endpoint::RISK_LOW] ?? 0)],
        ];

        $methodChartData = collect(Endpoint::METHODS)
            ->map(fn (string $method): array => ['label' => $method, 'value' => (int) ($methodCounts[$method] ?? 0)])
            ->filter(fn (array $item): bool => $item['value'] > 0)
            ->values()
            ->all();

        if ($methodChartData === []) {
            $methodChartData = [['label' => 'GET', 'value' => 0]];
        }

        $topRiskyEndpoints = Endpoint::query()
            ->with(['project', 'latestScanResult'])
            ->whereIn('risk_level', [Endpoint::RISK_CRITICAL, Endpoint::RISK_HIGH, Endpoint::RISK_REVIEW])
            ->orderByRaw("CASE risk_level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'review' THEN 3 ELSE 4 END")
            ->latest()
            ->limit(6)
            ->get();

        $assertionEvaluations = Endpoint::query()
            ->with(['project', 'latestScanResult'])
            ->get()
            ->map(fn (Endpoint $endpoint): array => $assertions->evaluate($endpoint, $endpoint->latestScanResult));

        $regressionDetectedCount = CompareRun::query()
            ->with('items')
            ->latest()
            ->get()
            ->unique('project_id')
            ->sum(fn (CompareRun $compareRun): int => $regressions->evaluateCompare($compareRun)['detected_count']);

        $readinessProjects = Project::query()
            ->with(['endpoints.latestScanResult', 'scanRuns', 'snapshots', 'compareRuns.items', 'apiMonitors'])
            ->withCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'apiMonitors'])
            ->where('is_active', true)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Project $project): array => ['project' => $project, 'readiness' => $readiness->summarize($project)]);

        $readinessFailCount = $readinessProjects->filter(fn (array $row): bool => $row['readiness']['status'] === ReleaseReadinessService::STATUS_FAIL)->count();
        $readinessWarningCount = $readinessProjects->filter(fn (array $row): bool => $row['readiness']['status'] === ReleaseReadinessService::STATUS_WARNING)->count();
        $readinessPassCount = $readinessProjects->filter(fn (array $row): bool => $row['readiness']['status'] === ReleaseReadinessService::STATUS_PASS)->count();
        $averageReadinessScore = $readinessProjects->count() > 0 ? (int) round($readinessProjects->avg(fn (array $row): int => (int) $row['readiness']['score'])) : 0;

        $calendarPreviewStartsAt = now()->startOfDay();
        $calendarPreviewEndsAt = now()->addDays(14)->endOfDay();
        $calendarPreviewEvents = CalendarEvent::query()
            ->with(['project', 'monitor'])
            ->where('starts_at', '<=', $calendarPreviewEndsAt)
            ->where(function ($query) use ($calendarPreviewStartsAt): void {
                $query->where(function ($inner) use ($calendarPreviewStartsAt): void {
                    $inner->whereNull('ends_at')->where('starts_at', '>=', $calendarPreviewStartsAt);
                })->orWhere('ends_at', '>=', $calendarPreviewStartsAt);
            })
            ->orderBy('starts_at')
            ->limit(6)
            ->get();

        $calendarPreviewSummary = [
            'open' => CalendarEvent::query()->whereNull('completed_at')->whereIn('status', [CalendarEvent::STATUS_PLANNED, CalendarEvent::STATUS_IN_PROGRESS])->count(),
            'due_today' => CalendarEvent::query()->whereDate('starts_at', now()->toDateString())->whereIn('status', [CalendarEvent::STATUS_PLANNED, CalendarEvent::STATUS_IN_PROGRESS])->count(),
            'overdue' => CalendarEvent::query()->where('starts_at', '<', now())->whereIn('status', [CalendarEvent::STATUS_PLANNED, CalendarEvent::STATUS_IN_PROGRESS])->count(),
        ];

        return view('dashboard.index', [
            'projectCount' => Project::query()->count(),
            'activeProjectCount' => Project::query()->where('is_active', true)->count(),
            'environmentCount' => Environment::query()->count(),
            'authProfileCount' => AuthProfile::query()->count(),
            'endpointCount' => Endpoint::query()->count(),
            'probeableEndpointCount' => Endpoint::query()
                ->whereIn('method', [Endpoint::METHOD_GET, Endpoint::METHOD_HEAD])
                ->where('is_active', true)
                ->where('excluded_from_scan', false)
                ->count(),
            'criticalEndpointCount' => (int) ($riskCounts[Endpoint::RISK_CRITICAL] ?? 0),
            'highEndpointCount' => (int) ($riskCounts[Endpoint::RISK_HIGH] ?? 0),
            'reviewEndpointCount' => (int) ($riskCounts[Endpoint::RISK_REVIEW] ?? 0),
            'publicEndpointCount' => (int) ($riskCounts[Endpoint::RISK_PUBLIC] ?? 0),
            'lowEndpointCount' => (int) ($riskCounts[Endpoint::RISK_LOW] ?? 0),
            'scanRunCount' => ScanRun::query()->count(),
            'completedScanCount' => ScanRun::query()->where('status', ScanRun::STATUS_COMPLETED)->count(),
            'latestScanRun' => ScanRun::query()->with(['project', 'environment'])->latest()->first(),
            'latestScanRuns' => ScanRun::query()->with(['project', 'environment'])->latest()->limit(5)->get(),
            'snapshotCount' => Snapshot::query()->count(),
            'latestSnapshot' => Snapshot::query()->with(['project', 'environment'])->latest()->first(),
            'compareCount' => CompareRun::query()->count(),
            'latestCompareRun' => CompareRun::query()->with(['project'])->latest()->first(),
            'latestProjects' => Project::query()
                ->withCount(['environments', 'authProfiles', 'endpoints', 'scanRuns'])
                ->latest()
                ->limit(5)
                ->get(),
            'topRiskyEndpoints' => $topRiskyEndpoints,
            'assertionFailCount' => $assertionEvaluations->where('status', AssertionEvaluationService::STATUS_FAIL)->count(),
            'assertionWarningCount' => $assertionEvaluations->where('status', AssertionEvaluationService::STATUS_WARNING)->count(),
            'regressionDetectedCount' => $regressionDetectedCount,
            'monitorCount' => ApiMonitor::query()->count(),
            'enabledMonitorCount' => ApiMonitor::query()->where('is_enabled', true)->count(),
            'monitorAlertCount' => ApiMonitor::query()->where('notify_dashboard', true)->whereIn('last_status', [ApiMonitor::STATUS_WARNING, ApiMonitor::STATUS_REGRESSION, ApiMonitor::STATUS_FAILED])->count(),
            'latestMonitors' => ApiMonitor::query()->with(['project', 'environment', 'lastScanRun', 'lastCompareRun'])->latest('last_run_at')->limit(5)->get(),
            'riskChartData' => $riskChartData,
            'readinessProjects' => $readinessProjects,
            'readinessFailCount' => $readinessFailCount,
            'readinessWarningCount' => $readinessWarningCount,
            'readinessPassCount' => $readinessPassCount,
            'averageReadinessScore' => $averageReadinessScore,
            'methodChartData' => $methodChartData,
            'scanTrendData' => $scanTrendData,
            'calendarPreviewEvents' => $calendarPreviewEvents,
            'calendarPreviewSummary' => $calendarPreviewSummary,
            'showScanSummaryCards' => $settings->boolean('ui.show_scan_summary_cards', true),
            'showDashboardCalendarPreview' => $settings->boolean('ui.show_dashboard_calendar_preview', true),
            'showReleaseReadinessWidget' => $settings->boolean('ui.show_release_readiness_widget', true),
            'showQaEvidenceWidget' => $settings->boolean('ui.show_qa_evidence_widget', true),
        ]);
    }
}
