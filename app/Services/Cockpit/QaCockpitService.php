<?php

namespace App\Services\Cockpit;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Models\ReportVersion;
use App\Models\RiskAcceptance;
use App\Models\ScanRun;
use App\Services\BlindSpots\QaBlindSpotDetectorService;
use App\Services\ReleaseReadinessService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class QaCockpitService
{
    public const STALE_SCAN_DAYS = 14;
    public const STALE_REPORT_DAYS = 14;
    public const EXPIRING_RISK_DAYS = 14;

    public function __construct(
        private readonly ReleaseReadinessService $readiness,
        private readonly QaBlindSpotDetectorService $blindSpots,
    ) {
    }

    /** @return array<string, mixed> */
    public function summarize(Project $project): array
    {
        $readiness = $this->readiness->summarize($project);
        $blindSpotData = $this->blindSpots->summarize($project);
        $latestScan = $project->scanRuns()->latest()->first();
        $latestApprovedReport = $project->reportVersions()->where('status', ReportVersion::STATUS_APPROVED)->latest('approved_at')->latest()->first();
        $latestDecision = $project->releaseDecisions()->latest()->first();
        $latestGate = $project->qaReleaseGates()->latest()->first();

        $blockers = $this->blockers($project);
        $fixesWaitingForRetest = $this->fixesWaitingForRetest($project);
        $expiringRisks = $this->expiringRisks($project);
        $staleScans = $this->staleScanItems($project, $latestScan);
        $staleReports = $this->staleReportItems($project, $latestApprovedReport);
        $endpointsWithoutEvidence = $this->endpointsWithoutEvidence($project);
        $releaseCandidates = $this->releaseCandidatesNeedingDecision($project, $latestScan, $latestApprovedReport, $latestDecision, $latestGate, $readiness);
        $monitorAlerts = $this->monitorAlerts($project);
        $topBlindSpots = collect($blindSpotData['top_items'])->take(5)->values();
        $recentRiskEndpoints = $this->recentRiskEndpoints($project);

        $priorityQueue = $this->priorityQueue([
            'blockers' => $blockers,
            'fixes_waiting_for_retest' => $fixesWaitingForRetest,
            'accepted_risks_expiring' => $expiringRisks,
            'stale_scan_evidence' => $staleScans,
            'stale_reports' => $staleReports,
            'endpoints_without_evidence' => $endpointsWithoutEvidence,
            'release_candidates_needing_decision' => $releaseCandidates,
            'monitor_alerts' => $monitorAlerts,
        ]);

        return [
            'generated_at' => now(),
            'readiness' => $readiness,
            'blind_spots' => $blindSpotData,
            'metrics' => [
                'open_blockers' => $blockers->count(),
                'fixes_waiting_for_retest' => $fixesWaitingForRetest->count(),
                'accepted_risks_expiring' => $expiringRisks->count(),
                'stale_scans' => $staleScans->count(),
                'stale_reports' => $staleReports->count(),
                'endpoints_without_evidence' => $endpointsWithoutEvidence->count(),
                'release_candidates_needing_decision' => $releaseCandidates->count(),
                'monitor_alerts' => $monitorAlerts->count(),
                'top_blind_spots' => $topBlindSpots->count(),
                'recent_risk_endpoints' => $recentRiskEndpoints->count(),
            ],
            'queues' => [
                'priority' => $priorityQueue,
                'blockers' => $blockers,
                'fixes_waiting_for_retest' => $fixesWaitingForRetest,
                'accepted_risks_expiring' => $expiringRisks,
                'stale_scan_evidence' => $staleScans,
                'stale_reports' => $staleReports,
                'endpoints_without_evidence' => $endpointsWithoutEvidence,
                'release_candidates_needing_decision' => $releaseCandidates,
                'monitor_alerts' => $monitorAlerts,
                'top_blind_spots' => $topBlindSpots,
                'recent_risk_endpoints' => $recentRiskEndpoints,
            ],
            'latest' => [
                'scan' => $latestScan,
                'approved_report' => $latestApprovedReport,
                'release_decision' => $latestDecision,
                'release_gate' => $latestGate,
            ],
            'quick_actions' => $this->quickActions($project),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function blockers(Project $project): Collection
    {
        $blockingStatuses = [
            Finding::STATUS_OPEN,
            Finding::STATUS_CONFIRMED,
            Finding::STATUS_TRIAGED,
            Finding::STATUS_IN_PROGRESS,
            Finding::STATUS_REOPENED,
        ];

        return $project->findings()
            ->with(['endpoint', 'owner'])
            ->where(function ($query) use ($blockingStatuses): void {
                $query->where('status', Finding::STATUS_RETEST_FAILED)
                    ->orWhere(function ($subQuery) use ($blockingStatuses): void {
                        $subQuery->whereIn('status', $blockingStatuses)
                            ->whereIn('severity', [Finding::SEVERITY_CRITICAL, Finding::SEVERITY_HIGH]);
                    });
            })
            ->latest('detected_at')
            ->limit(15)
            ->get()
            ->map(fn (Finding $finding): array => [
                'kind' => 'finding',
                'label' => $finding->title,
                'meta' => trim(($finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path.' · ' : '').$finding->status_label),
                'severity' => $finding->severity,
                'css' => $finding->status === Finding::STATUS_RETEST_FAILED ? 'danger' : $finding->severity_css,
                'due_at' => $finding->due_date,
                'url' => route('projects.findings.show', [$project, $finding]),
                'score' => $this->priorityScore($finding->severity, $finding->due_date, 100),
                'object' => $finding,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function fixesWaitingForRetest(Project $project): Collection
    {
        return $project->findings()
            ->with(['endpoint', 'owner'])
            ->where(function ($query): void {
                $query->where('status', Finding::STATUS_READY_FOR_RETEST)
                    ->orWhere(function ($subQuery): void {
                        $subQuery->where('status', Finding::STATUS_FIXED)
                            ->where(function ($inner): void {
                                $inner->whereNull('verification_status')
                                    ->orWhere('verification_status', '!=', Finding::VERIFICATION_VERIFIED);
                            });
                    });
            })
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->map(fn (Finding $finding): array => [
                'kind' => 'retest',
                'label' => $finding->title,
                'meta' => trim(($finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path.' · ' : '').$finding->verification_status_label),
                'severity' => $finding->severity,
                'css' => $finding->is_overdue ? 'danger' : 'warning',
                'due_at' => $finding->due_date,
                'url' => route('projects.findings.show', [$project, $finding]),
                'score' => $this->priorityScore($finding->severity, $finding->due_date, 80),
                'object' => $finding,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function expiringRisks(Project $project): Collection
    {
        return $project->riskAcceptances()
            ->with(['finding.endpoint', 'acceptedBy'])
            ->where('status', RiskAcceptance::STATUS_ACTIVE)
            ->get()
            ->filter(fn (RiskAcceptance $risk): bool => $risk->accepted_until === null || $risk->is_expired || $risk->expires_soon || $risk->accepted_until->lte(now()->addDays(self::EXPIRING_RISK_DAYS)))
            ->sortBy(fn (RiskAcceptance $risk): string => ($risk->accepted_until?->format('YmdHis') ?? '00000000000000').sprintf('%06d', $risk->id))
            ->take(15)
            ->values()
            ->map(fn (RiskAcceptance $risk): array => [
                'kind' => 'risk',
                'label' => $risk->finding?->title ?: __('messages.risk_acceptances.title'),
                'meta' => $risk->accepted_until ? __('messages.qa_cockpit.expires_on', ['date' => $risk->accepted_until->format('Y-m-d')]) : __('messages.qa_cockpit.no_expiry'),
                'severity' => $risk->is_expired || $risk->accepted_until === null ? 'high' : 'medium',
                'css' => $risk->is_expired || $risk->accepted_until === null ? 'danger' : 'warning',
                'due_at' => $risk->accepted_until,
                'url' => route('projects.risk-acceptances.index', $project),
                'score' => $this->priorityScore($risk->is_expired ? 'critical' : 'high', $risk->accepted_until, 70),
                'object' => $risk,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function staleScanItems(Project $project, ?ScanRun $latestScan): Collection
    {
        if (! $project->endpoints()->exists()) {
            return collect();
        }

        if (! $latestScan) {
            return collect([[
                'kind' => 'scan',
                'label' => __('messages.qa_cockpit.items.no_scan_evidence'),
                'meta' => __('messages.qa_cockpit.actions.run_safe_scan'),
                'severity' => 'high',
                'css' => 'danger',
                'due_at' => null,
                'url' => route('projects.scans.create', $project),
                'score' => 75,
                'object' => null,
            ]]);
        }

        $evidenceDate = $latestScan->finished_at ?: $latestScan->created_at;
        if (! $evidenceDate instanceof CarbonInterface || $evidenceDate->gte(now()->subDays(self::STALE_SCAN_DAYS))) {
            return collect();
        }

        return collect([[
            'kind' => 'scan',
            'label' => __('messages.qa_cockpit.items.stale_scan_evidence'),
            'meta' => __('messages.qa_cockpit.days_old', ['days' => (int) $evidenceDate->diffInDays(now())]),
            'severity' => 'high',
            'css' => 'warning',
            'due_at' => $evidenceDate,
            'url' => route('projects.scans.index', $project),
            'score' => 65,
            'object' => $latestScan,
        ]]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function staleReportItems(Project $project, ?ReportVersion $latestApprovedReport): Collection
    {
        if (! $project->endpoints()->exists() && ! $project->scanRuns()->exists()) {
            return collect();
        }

        if (! $latestApprovedReport) {
            return collect([[
                'kind' => 'report',
                'label' => __('messages.qa_cockpit.items.no_approved_report'),
                'meta' => __('messages.qa_cockpit.actions.approve_report'),
                'severity' => 'medium',
                'css' => 'warning',
                'due_at' => null,
                'url' => route('projects.report-versions.index', $project),
                'score' => 55,
                'object' => null,
            ]]);
        }

        $reportDate = $latestApprovedReport->approved_at ?: $latestApprovedReport->generated_at ?: $latestApprovedReport->created_at;
        if (! $reportDate instanceof CarbonInterface || $reportDate->gte(now()->subDays(self::STALE_REPORT_DAYS))) {
            return collect();
        }

        return collect([[
            'kind' => 'report',
            'label' => __('messages.qa_cockpit.items.stale_approved_report'),
            'meta' => __('messages.qa_cockpit.days_old', ['days' => (int) $reportDate->diffInDays(now())]),
            'severity' => 'medium',
            'css' => 'warning',
            'due_at' => $reportDate,
            'url' => route('projects.report-versions.index', $project),
            'score' => 50,
            'object' => $latestApprovedReport,
        ]]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function endpointsWithoutEvidence(Project $project): Collection
    {
        return $project->endpoints()
            ->withCount(['scanResults', 'assertionRules'])
            ->where('is_active', true)
            ->where('excluded_from_scan', false)
            ->orderBy('method')
            ->orderBy('path')
            ->get()
            ->filter(fn (Endpoint $endpoint): bool => (int) $endpoint->scan_results_count === 0 || (int) $endpoint->assertion_rules_count === 0)
            ->take(15)
            ->values()
            ->map(fn (Endpoint $endpoint): array => [
                'kind' => 'endpoint',
                'label' => $endpoint->method.' '.$endpoint->path,
                'meta' => (int) $endpoint->scan_results_count === 0
                    ? __('messages.qa_cockpit.items.endpoint_without_scan')
                    : __('messages.qa_cockpit.items.endpoint_without_assertion'),
                'severity' => in_array($endpoint->risk_level, [Endpoint::RISK_CRITICAL, Endpoint::RISK_HIGH], true) ? 'high' : 'medium',
                'css' => $endpoint->risk_css,
                'due_at' => null,
                'url' => route('projects.endpoints.show', [$project, $endpoint]),
                'score' => in_array($endpoint->risk_level, [Endpoint::RISK_CRITICAL, Endpoint::RISK_HIGH], true) ? 62 : 42,
                'object' => $endpoint,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function releaseCandidatesNeedingDecision(Project $project, ?ScanRun $latestScan, ?ReportVersion $latestReport, ?ReleaseDecision $latestDecision, mixed $latestGate, array $readiness): Collection
    {
        $latestEvidenceDate = collect([
            $latestScan?->finished_at ?: $latestScan?->created_at,
            $latestReport?->approved_at ?: $latestReport?->generated_at ?: $latestReport?->created_at,
            $latestGate?->created_at,
        ])->filter(fn ($date): bool => $date instanceof CarbonInterface)->sortDesc()->first();

        if (! $latestEvidenceDate instanceof CarbonInterface && ! $project->endpoints()->exists()) {
            return collect();
        }

        $needsDecision = ! $latestDecision;
        if (! $needsDecision && $latestEvidenceDate instanceof CarbonInterface) {
            $decisionDate = $latestDecision->decided_at ?: $latestDecision->created_at;
            $needsDecision = ! $decisionDate instanceof CarbonInterface || $decisionDate->lt($latestEvidenceDate);
        }

        if (! $needsDecision) {
            return collect();
        }

        return collect([[
            'kind' => 'release',
            'label' => __('messages.qa_cockpit.items.release_needs_decision'),
            'meta' => __('messages.release_readiness.score').': '.($readiness['score'] ?? 0).' / 100 · '.__('messages.release_readiness.blocking_issues').': '.(is_countable($readiness['blocking_issues'] ?? null) ? count($readiness['blocking_issues']) : 0),
            'severity' => ((int) (is_countable($readiness['blocking_issues'] ?? null) ? count($readiness['blocking_issues']) : 0)) > 0 ? 'high' : 'medium',
            'css' => ((int) (is_countable($readiness['blocking_issues'] ?? null) ? count($readiness['blocking_issues']) : 0)) > 0 ? 'danger' : 'warning',
            'due_at' => $latestEvidenceDate,
            'url' => route('projects.release-decisions.index', $project),
            'score' => ((int) (is_countable($readiness['blocking_issues'] ?? null) ? count($readiness['blocking_issues']) : 0)) > 0 ? 72 : 48,
            'object' => $latestDecision,
        ]]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function monitorAlerts(Project $project): Collection
    {
        return $project->monitorAlertEvents()
            ->with('monitor')
            ->whereNull('acknowledged_at')
            ->latest('delivered_at')
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($alert): array => [
                'kind' => 'monitor_alert',
                'label' => $alert->message ?: __('messages.qa_cockpit.items.monitor_alert'),
                'meta' => $alert->monitor?->name ?: __('messages.nav.monitors'),
                'severity' => $alert->severity ?: 'medium',
                'css' => in_array($alert->severity, ['critical', 'high'], true) ? 'danger' : 'warning',
                'due_at' => $alert->delivered_at ?: $alert->created_at,
                'url' => route('projects.monitors.index', $project),
                'score' => in_array($alert->severity, ['critical', 'high'], true) ? 68 : 45,
                'object' => $alert,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function recentRiskEndpoints(Project $project): Collection
    {
        return $project->endpoints()
            ->with('latestScanResult')
            ->whereIn('risk_level', [Endpoint::RISK_CRITICAL, Endpoint::RISK_HIGH, Endpoint::RISK_REVIEW])
            ->orderByRaw("CASE risk_level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'review' THEN 3 ELSE 4 END")
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (Endpoint $endpoint): array => [
                'kind' => 'endpoint_risk',
                'label' => $endpoint->method.' '.$endpoint->path,
                'meta' => $endpoint->risk_label,
                'severity' => $endpoint->risk_level,
                'css' => $endpoint->risk_css,
                'due_at' => $endpoint->updated_at,
                'url' => route('projects.endpoints.show', [$project, $endpoint]),
                'score' => match ($endpoint->risk_level) {
                    Endpoint::RISK_CRITICAL => 60,
                    Endpoint::RISK_HIGH => 45,
                    default => 25,
                },
                'object' => $endpoint,
            ]);
    }

    /** @param array<string, Collection<int, array<string, mixed>>> $queues */
    private function priorityQueue(array $queues): Collection
    {
        return collect($queues)
            ->flatMap(fn (Collection $items): Collection => $items)
            ->sortByDesc(fn (array $item): int => (int) ($item['score'] ?? 0))
            ->take(12)
            ->values();
    }

    private function priorityScore(string $severity, mixed $dueAt, int $base): int
    {
        $severityBoost = match ($severity) {
            Finding::SEVERITY_CRITICAL, 'critical' => 30,
            Finding::SEVERITY_HIGH, 'high' => 20,
            Finding::SEVERITY_MEDIUM, 'medium' => 10,
            default => 0,
        };

        $dueBoost = 0;
        if ($dueAt instanceof CarbonInterface) {
            if ($dueAt->isPast()) {
                $dueBoost = 25;
            } elseif ($dueAt->lte(now()->addDays(3))) {
                $dueBoost = 15;
            } elseif ($dueAt->lte(now()->addDays(7))) {
                $dueBoost = 8;
            }
        }

        return $base + $severityBoost + $dueBoost;
    }

    /** @return array<int, array<string, string>> */
    private function quickActions(Project $project): array
    {
        return [
            ['label' => __('messages.qa_cockpit.quick_actions.release_workflow'), 'icon' => 'road', 'url' => route('projects.release-workflow.index', $project)],
            ['label' => __('messages.qa_cockpit.quick_actions.blind_spots'), 'icon' => 'eye-slash', 'url' => route('projects.blind-spots.index', $project)],
            ['label' => __('messages.qa_cockpit.quick_actions.release_readiness'), 'icon' => 'check-circle', 'url' => route('projects.release-readiness.show', $project)],
            ['label' => __('messages.qa_cockpit.quick_actions.release_decision'), 'icon' => 'gavel', 'url' => route('projects.release-decisions.index', $project)],
            ['label' => __('messages.qa_cockpit.quick_actions.report_approvals'), 'icon' => 'check-square-o', 'url' => route('projects.report-versions.index', $project)],
            ['label' => __('messages.qa_cockpit.quick_actions.run_scan'), 'icon' => 'crosshairs', 'url' => route('projects.scans.create', $project)],
            ['label' => __('messages.qa_cockpit.quick_actions.findings'), 'icon' => 'bug', 'url' => route('projects.findings.index', $project)],
        ];
    }
}
