<?php

namespace App\Services\BlindSpots;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\ScanRun;
use App\Services\AssertionEvaluationService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class QaBlindSpotDetectorService
{
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';

    public const CATEGORY_UNTESTED_ENDPOINT = 'untested_endpoint';
    public const CATEGORY_MISSING_ASSERTION = 'missing_assertion';
    public const CATEGORY_MISSING_AUTH_COMPARISON = 'missing_auth_comparison';
    public const CATEGORY_UNVERIFIED_FIX = 'unverified_fix';
    public const CATEGORY_RISK_WITHOUT_EXPIRY = 'risk_without_expiry';
    public const CATEGORY_EXPIRED_ACCEPTED_RISK = 'expired_accepted_risk';
    public const CATEGORY_STALE_EVIDENCE = 'stale_evidence';
    public const CATEGORY_MISSING_REPORT_CONTEXT = 'missing_report_context';

    public const MODULE_ENDPOINTS = 'endpoints';
    public const MODULE_ASSERTIONS = 'assertions';
    public const MODULE_AUTH = 'auth';
    public const MODULE_FINDINGS = 'findings';
    public const MODULE_RELEASE = 'release';

    public const STALE_SCAN_DAYS = 14;
    public const RECENT_REPORT_DAYS = 14;
    public const EXPIRING_RISK_DAYS = 7;

    public function __construct(private readonly AssertionEvaluationService $assertions)
    {
    }

    /** @return array<string, mixed> */
    public function summarize(Project $project): array
    {
        $items = $this->detect($project);

        $summary = [
            'total' => $items->count(),
            'critical' => $items->where('severity', self::SEVERITY_CRITICAL)->count(),
            'high' => $items->where('severity', self::SEVERITY_HIGH)->count(),
            'medium' => $items->where('severity', self::SEVERITY_MEDIUM)->count(),
            'low' => $items->where('severity', self::SEVERITY_LOW)->count(),
            'release_blockers' => $items->where('release_blocker', true)->count(),
            'stale_evidence' => $items->where('category', self::CATEGORY_STALE_EVIDENCE)->count(),
            'untested_endpoints' => $items->where('category', self::CATEGORY_UNTESTED_ENDPOINT)->count(),
            'missing_assertions' => $items->where('category', self::CATEGORY_MISSING_ASSERTION)->count(),
            'missing_auth_comparisons' => $items->where('category', self::CATEGORY_MISSING_AUTH_COMPARISON)->count(),
            'unverified_fixes' => $items->where('category', self::CATEGORY_UNVERIFIED_FIX)->count(),
            'risk_without_expiry' => $items->where('category', self::CATEGORY_RISK_WITHOUT_EXPIRY)->count(),
            'expired_accepted_risks' => $items->where('category', self::CATEGORY_EXPIRED_ACCEPTED_RISK)->count(),
            'missing_recent_reports' => $items->where('category', self::CATEGORY_MISSING_REPORT_CONTEXT)->count(),
        ];

        return [
            'items' => $items,
            'top_items' => $items->take(10)->values(),
            'summary' => $summary,
            'by_category' => $items->groupBy('category')->map->count()->all(),
            'by_module' => $items->groupBy('module')->map->count()->all(),
            'generated_at' => now(),
            'stale_scan_days' => self::STALE_SCAN_DAYS,
            'recent_report_days' => self::RECENT_REPORT_DAYS,
            'expiring_risk_days' => self::EXPIRING_RISK_DAYS,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function detect(Project $project): Collection
    {
        $items = collect();

        $endpoints = $project->endpoints()
            ->with(['latestScanResult', 'scanResults', 'assertionRules'])
            ->orderBy('method')
            ->orderBy('path')
            ->get();

        foreach ($endpoints as $endpoint) {
            if (! $endpoint->is_active || $endpoint->excluded_from_scan) {
                continue;
            }

            if (! $endpoint->scanResults()->exists()) {
                $items->push($this->item(
                    'endpoint_without_scan_'.$endpoint->id,
                    self::CATEGORY_UNTESTED_ENDPOINT,
                    $this->endpointEvidenceSeverity($endpoint),
                    self::MODULE_ENDPOINTS,
                    __('messages.blind_spots.types.endpoint_without_scan'),
                    $endpoint->method.' '.$endpoint->path,
                    __('messages.blind_spots.reasons.endpoint_without_scan'),
                    __('messages.blind_spots.actions.endpoint_without_scan'),
                    relatedEndpoint: $endpoint,
                    releaseBlocker: in_array($this->endpointEvidenceSeverity($endpoint), [self::SEVERITY_CRITICAL, self::SEVERITY_HIGH], true)
                ));
            }

            if ($this->assertions->effectiveRules($endpoint)->isEmpty()) {
                $items->push($this->item(
                    'endpoint_without_assertion_'.$endpoint->id,
                    self::CATEGORY_MISSING_ASSERTION,
                    $endpoint->risk_level === Endpoint::RISK_CRITICAL ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                    self::MODULE_ASSERTIONS,
                    __('messages.blind_spots.types.endpoint_without_assertion'),
                    $endpoint->method.' '.$endpoint->path,
                    __('messages.blind_spots.reasons.endpoint_without_assertion'),
                    __('messages.blind_spots.actions.endpoint_without_assertion'),
                    relatedEndpoint: $endpoint,
                    releaseBlocker: $endpoint->risk_level === Endpoint::RISK_CRITICAL
                ));
            }

            if ($endpoint->auth_required && ! $this->hasNoAuthComparisonEvidence($endpoint)) {
                $items->push($this->item(
                    'auth_endpoint_without_no_auth_comparison_'.$endpoint->id,
                    self::CATEGORY_MISSING_AUTH_COMPARISON,
                    in_array($endpoint->risk_level, [Endpoint::RISK_CRITICAL, Endpoint::RISK_HIGH], true) ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                    self::MODULE_AUTH,
                    __('messages.blind_spots.types.auth_endpoint_without_no_auth_comparison'),
                    $endpoint->method.' '.$endpoint->path,
                    __('messages.blind_spots.reasons.auth_endpoint_without_no_auth_comparison'),
                    __('messages.blind_spots.actions.auth_endpoint_without_no_auth_comparison'),
                    relatedEndpoint: $endpoint,
                    releaseBlocker: in_array($endpoint->risk_level, [Endpoint::RISK_CRITICAL, Endpoint::RISK_HIGH], true)
                ));
            }
        }

        $fixedFindings = $project->findings()
            ->with(['endpoint', 'evidence'])
            ->where('status', Finding::STATUS_FIXED)
            ->latest('updated_at')
            ->get();

        foreach ($fixedFindings as $finding) {
            if (! $this->hasRetestEvidence($finding)) {
                $items->push($this->item(
                    'fixed_finding_without_retest_'.$finding->id,
                    self::CATEGORY_UNVERIFIED_FIX,
                    in_array($finding->severity, [Finding::SEVERITY_CRITICAL, Finding::SEVERITY_HIGH], true) ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                    self::MODULE_FINDINGS,
                    __('messages.blind_spots.types.fixed_finding_without_retest'),
                    $finding->title,
                    __('messages.blind_spots.reasons.fixed_finding_without_retest'),
                    __('messages.blind_spots.actions.fixed_finding_without_retest'),
                    relatedFinding: $finding,
                    releaseBlocker: in_array($finding->severity, [Finding::SEVERITY_CRITICAL, Finding::SEVERITY_HIGH], true)
                ));
            }
        }

        $acceptedRisks = $project->findings()
            ->with(['endpoint', 'evidence'])
            ->where('status', Finding::STATUS_ACCEPTED_RISK)
            ->latest('updated_at')
            ->get();

        foreach ($acceptedRisks as $finding) {
            $expiresAt = $finding->accepted_risk_expires_at;
            if (! $expiresAt) {
                $items->push($this->item(
                    'accepted_risk_without_expiry_'.$finding->id,
                    self::CATEGORY_RISK_WITHOUT_EXPIRY,
                    self::SEVERITY_HIGH,
                    self::MODULE_FINDINGS,
                    __('messages.blind_spots.types.accepted_risk_without_expiry'),
                    $finding->title,
                    __('messages.blind_spots.reasons.accepted_risk_without_expiry'),
                    __('messages.blind_spots.actions.accepted_risk_without_expiry'),
                    relatedFinding: $finding,
                    releaseBlocker: true
                ));
                continue;
            }

            if ($expiresAt->isPast()) {
                $items->push($this->item(
                    'accepted_risk_expired_'.$finding->id,
                    self::CATEGORY_EXPIRED_ACCEPTED_RISK,
                    self::SEVERITY_CRITICAL,
                    self::MODULE_FINDINGS,
                    __('messages.blind_spots.types.accepted_risk_expired'),
                    $finding->title,
                    __('messages.blind_spots.reasons.accepted_risk_expired', ['date' => $expiresAt->format('Y-m-d')]),
                    __('messages.blind_spots.actions.accepted_risk_expired'),
                    relatedFinding: $finding,
                    releaseBlocker: true
                ));
            } elseif ($expiresAt->lte(now()->addDays(self::EXPIRING_RISK_DAYS))) {
                $items->push($this->item(
                    'accepted_risk_expiring_'.$finding->id,
                    self::CATEGORY_RISK_WITHOUT_EXPIRY,
                    self::SEVERITY_MEDIUM,
                    self::MODULE_FINDINGS,
                    __('messages.blind_spots.types.accepted_risk_expiring'),
                    $finding->title,
                    __('messages.blind_spots.reasons.accepted_risk_expiring', ['date' => $expiresAt->format('Y-m-d')]),
                    __('messages.blind_spots.actions.accepted_risk_expiring'),
                    relatedFinding: $finding,
                    releaseBlocker: false
                ));
            }
        }

        $latestScan = $project->scanRuns()->latest()->first();
        if ($latestScan instanceof ScanRun) {
            $evidenceDate = $latestScan->finished_at ?: $latestScan->created_at;
            if ($evidenceDate instanceof CarbonInterface && $evidenceDate->lt(now()->subDays(self::STALE_SCAN_DAYS))) {
                $items->push($this->item(
                    'stale_scan_evidence_'.$latestScan->id,
                    self::CATEGORY_STALE_EVIDENCE,
                    self::SEVERITY_HIGH,
                    self::MODULE_RELEASE,
                    __('messages.blind_spots.types.stale_scan_evidence'),
                    '#'.$latestScan->id,
                    __('messages.blind_spots.reasons.stale_scan_evidence', ['days' => self::STALE_SCAN_DAYS]),
                    __('messages.blind_spots.actions.stale_scan_evidence'),
                    relatedScanRun: $latestScan,
                    releaseBlocker: true
                ));
            }
        }

        if ($project->endpoints()->exists() || $project->scanRuns()->exists()) {
            $latestReleaseReport = $project->qaReleaseGates()->latest()->first();
            if (! $latestReleaseReport) {
                $items->push($this->item(
                    'release_without_recent_report_missing_'.$project->id,
                    self::CATEGORY_MISSING_REPORT_CONTEXT,
                    self::SEVERITY_HIGH,
                    self::MODULE_RELEASE,
                    __('messages.blind_spots.types.release_without_recent_report'),
                    $project->name,
                    __('messages.blind_spots.reasons.release_without_recent_report_missing'),
                    __('messages.blind_spots.actions.release_without_recent_report'),
                    releaseBlocker: true
                ));
            } elseif ($latestReleaseReport->created_at instanceof CarbonInterface && $latestReleaseReport->created_at->lt(now()->subDays(self::RECENT_REPORT_DAYS))) {
                $items->push($this->item(
                    'release_without_recent_report_stale_'.$latestReleaseReport->id,
                    self::CATEGORY_MISSING_REPORT_CONTEXT,
                    self::SEVERITY_MEDIUM,
                    self::MODULE_RELEASE,
                    __('messages.blind_spots.types.release_without_recent_report'),
                    '#'.$latestReleaseReport->id,
                    __('messages.blind_spots.reasons.release_without_recent_report_stale', ['days' => self::RECENT_REPORT_DAYS]),
                    __('messages.blind_spots.actions.release_without_recent_report'),
                    relatedReleaseGate: $latestReleaseReport,
                    releaseBlocker: false
                ));
            }
        }

        return $items
            ->sortBy(fn (array $item): string => sprintf('%02d-%s-%s', $this->severityRank((string) $item['severity']), (string) $item['module'], (string) $item['related_label']))
            ->values();
    }

    /** @return array<string, mixed> */
    private function item(
        string $key,
        string $category,
        string $severity,
        string $module,
        string $typeLabel,
        string $relatedLabel,
        string $reason,
        string $suggestedAction,
        ?Endpoint $relatedEndpoint = null,
        ?Finding $relatedFinding = null,
        ?ScanRun $relatedScanRun = null,
        mixed $relatedReleaseGate = null,
        bool $releaseBlocker = false
    ): array {
        return [
            'key' => $key,
            'category' => $category,
            'category_label' => __('messages.blind_spots.categories.'.$category),
            'severity' => $severity,
            'severity_label' => __('messages.blind_spots.severities.'.$severity),
            'severity_css' => $this->severityCss($severity),
            'module' => $module,
            'module_label' => __('messages.blind_spots.modules.'.$module),
            'type_label' => $typeLabel,
            'related_label' => $relatedLabel,
            'reason' => $reason,
            'suggested_action' => $suggestedAction,
            'detected_at' => now(),
            'release_blocker' => $releaseBlocker,
            'related_endpoint' => $relatedEndpoint,
            'related_finding' => $relatedFinding,
            'related_scan_run' => $relatedScanRun,
            'related_release_gate' => $relatedReleaseGate,
        ];
    }

    private function endpointEvidenceSeverity(Endpoint $endpoint): string
    {
        return match ($endpoint->risk_level) {
            Endpoint::RISK_CRITICAL => self::SEVERITY_CRITICAL,
            Endpoint::RISK_HIGH => self::SEVERITY_HIGH,
            default => self::SEVERITY_HIGH,
        };
    }

    private function hasNoAuthComparisonEvidence(Endpoint $endpoint): bool
    {
        return $endpoint->scanResults()
            ->whereNotNull('broken_auth_summary_json')
            ->exists();
    }

    private function hasRetestEvidence(Finding $finding): bool
    {
        $fixedAt = $finding->resolved_at ?: $finding->lifecycle_changed_at ?: $finding->updated_at;

        return $finding->evidence->contains(function (FindingEvidence $evidence) use ($fixedAt): bool {
            $metadata = is_array($evidence->metadata_json) ? $evidence->metadata_json : [];
            $source = strtolower((string) $evidence->source_label);
            $content = strtolower((string) $evidence->content);

            if ($evidence->type === FindingEvidence::TYPE_RETEST) {
                return true;
            }

            if (($metadata['purpose'] ?? null) === 'retest' || ($metadata['retest'] ?? false) === true) {
                return true;
            }

            if (str_contains($source, 'retest') || str_contains($content, 'retest')) {
                return true;
            }

            return $fixedAt instanceof CarbonInterface
                && $evidence->captured_at instanceof CarbonInterface
                && $evidence->captured_at->gte($fixedAt)
                && in_array($evidence->type, [
                    FindingEvidence::TYPE_REQUEST_RESPONSE,
                    FindingEvidence::TYPE_JSON_RESPONSE,
                    FindingEvidence::TYPE_CURL_COMMAND,
                    FindingEvidence::TYPE_FILE,
                    FindingEvidence::TYPE_LINK,
                ], true);
        });
    }

    private function severityRank(string $severity): int
    {
        return match ($severity) {
            self::SEVERITY_CRITICAL => 0,
            self::SEVERITY_HIGH => 1,
            self::SEVERITY_MEDIUM => 2,
            default => 3,
        };
    }

    private function severityCss(string $severity): string
    {
        return match ($severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_HIGH => 'warning',
            self::SEVERITY_MEDIUM => 'info',
            default => 'default',
        };
    }
}
