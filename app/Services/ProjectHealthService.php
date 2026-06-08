<?php

namespace App\Services;

use App\Models\ContractValidationRun;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\TestCase;

class ProjectHealthService
{
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_WARNINGS = 'warnings';
    public const STATUS_FAILING = 'failing';
    public const STATUS_IDLE = 'idle';

    public function __construct(
        private readonly AssertionEvaluationService $assertions,
        private readonly RegressionEvaluationService $regressions,
        private readonly QaCoverageMatrixService $coverageMatrix
    ) {
    }

    /** @return array<string, mixed> */
    public function summarize(Project $project): array
    {
        $project->loadCount('endpoints');

        $latestScan = $project->scanRuns()
            ->with(['environment', 'results.endpoint'])
            ->latest()
            ->first();

        $latestCompare = $project->compareRuns()
            ->latest()
            ->first();

        $assertionCounts = [
            AssertionEvaluationService::STATUS_PASS => 0,
            AssertionEvaluationService::STATUS_WARNING => 0,
            AssertionEvaluationService::STATUS_FAIL => 0,
            AssertionEvaluationService::STATUS_NOT_CONFIGURED => 0,
        ];

        if ($latestScan) {
            $latestScan->results
                ->filter(fn (ScanResult $result): bool => $result->endpoint !== null)
                ->unique('endpoint_id')
                ->each(function (ScanResult $result) use (&$assertionCounts): void {
                    $status = $this->assertions->evaluate($result->endpoint, $result)['status'];
                    $assertionCounts[$status] = ($assertionCounts[$status] ?? 0) + 1;
                });
        }

        $testRunCounts = $project->testCases()
            ->selectRaw('last_run_status, COUNT(*) as total')
            ->groupBy('last_run_status')
            ->pluck('total', 'last_run_status');

        $testCounts = [
            'total' => $project->testCases()->count(),
            TestCase::RUN_PASS => (int) ($testRunCounts[TestCase::RUN_PASS] ?? 0),
            TestCase::RUN_FAIL => (int) ($testRunCounts[TestCase::RUN_FAIL] ?? 0),
            TestCase::RUN_BLOCKED => (int) ($testRunCounts[TestCase::RUN_BLOCKED] ?? 0),
            TestCase::RUN_SKIPPED => (int) ($testRunCounts[TestCase::RUN_SKIPPED] ?? 0),
            TestCase::RUN_NOT_RUN => (int) (($testRunCounts[TestCase::RUN_NOT_RUN] ?? 0) + ($testRunCounts[''] ?? 0)),
        ];
        $testCounts['executed'] = $testCounts[TestCase::RUN_PASS]
            + $testCounts[TestCase::RUN_FAIL]
            + $testCounts[TestCase::RUN_BLOCKED]
            + $testCounts[TestCase::RUN_SKIPPED];
        $testCounts['execution_percent'] = $testCounts['total'] > 0
            ? (int) round(($testCounts['executed'] / $testCounts['total']) * 100)
            : 0;
        $testCounts['pass_rate'] = $testCounts['executed'] > 0
            ? (int) round(($testCounts[TestCase::RUN_PASS] / $testCounts['executed']) * 100)
            : 0;

        $regression = $latestCompare
            ? $this->regressions->evaluateCompare($latestCompare)
            : [
                'status' => RegressionEvaluationService::STATUS_NONE,
                'label' => __('messages.regressions.statuses.none'),
                'css' => 'success',
                'detected_count' => 0,
                'warning_count' => 0,
                'recovered_count' => 0,
                'improved_count' => 0,
            ];

        $latestContractValidation = $project->contractValidationRuns()->latest()->first();
        $contractCounts = [
            'total_runs' => $project->contractValidationRuns()->count(),
            'latest_run' => $latestContractValidation,
            'breaking_count' => $latestContractValidation?->breaking_count ?? 0,
            'failed_count' => $latestContractValidation?->failed_count ?? 0,
            'warning_count' => $latestContractValidation?->warning_count ?? 0,
            'missing_endpoint_count' => $latestContractValidation?->missing_endpoint_count ?? 0,
            'undocumented_endpoint_count' => $latestContractValidation?->undocumented_endpoint_count ?? 0,
        ];

        $findingStatusCounts = $project->findings()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $openFindingQuery = $project->findings()->whereIn('status', Finding::OPEN_STATUSES);
        $findingCounts = [
            'total' => $project->findings()->count(),
            'open' => (int) $openFindingQuery->count(),
            'critical_open' => (int) $project->findings()->whereIn('status', Finding::OPEN_STATUSES)->where('severity', Finding::SEVERITY_CRITICAL)->count(),
            'high_open' => (int) $project->findings()->whereIn('status', Finding::OPEN_STATUSES)->where('severity', Finding::SEVERITY_HIGH)->count(),
            'fixed' => (int) ($findingStatusCounts[Finding::STATUS_FIXED] ?? 0),
            'closed' => (int) ($findingStatusCounts[Finding::STATUS_CLOSED] ?? 0),
            'accepted_risk' => (int) ($findingStatusCounts[Finding::STATUS_ACCEPTED_RISK] ?? 0),
        ];

        $qaCoverage = $this->coverageMatrix->summarize($project)['summary'];

        $hasHealthEvidence = $latestScan
            || $testCounts['total'] > 0
            || $latestCompare
            || $latestContractValidation
            || $findingCounts['total'] > 0;

        $status = self::STATUS_IDLE;
        if ($hasHealthEvidence) {
            if (($latestScan?->error_count ?? 0) > 0
                || $assertionCounts[AssertionEvaluationService::STATUS_FAIL] > 0
                || $testCounts[TestCase::RUN_FAIL] > 0
                || $findingCounts['critical_open'] > 0
                || ($contractCounts['breaking_count'] ?? 0) > 0
                || ($contractCounts['failed_count'] ?? 0) > 0
                || ($qaCoverage['blocked'] ?? 0) > 0
                || $regression['status'] === RegressionEvaluationService::STATUS_DETECTED) {
                $status = self::STATUS_FAILING;
            } elseif (($latestScan?->warning_count ?? 0) > 0
                || $assertionCounts[AssertionEvaluationService::STATUS_WARNING] > 0
                || $testCounts[TestCase::RUN_BLOCKED] > 0
                || $findingCounts['high_open'] > 0
                || $findingCounts['open'] > 0
                || ($contractCounts['warning_count'] ?? 0) > 0
                || ($qaCoverage['warning'] ?? 0) > 0
                || $regression['status'] === RegressionEvaluationService::STATUS_WARNING) {
                $status = self::STATUS_WARNINGS;
            } else {
                $status = self::STATUS_HEALTHY;
            }
        }

        return [
            'status' => $status,
            'label' => __('messages.project_health.statuses.'.$status),
            'css' => $this->statusCss($status),
            'endpoint_count' => $project->endpoints_count,
            'latest_scan' => $latestScan,
            'scanned_count' => $latestScan?->scanned_count ?? 0,
            'success_count' => $latestScan?->success_count ?? 0,
            'warning_count' => $latestScan?->warning_count ?? 0,
            'error_count' => $latestScan?->error_count ?? 0,
            'assertion_pass_count' => $assertionCounts[AssertionEvaluationService::STATUS_PASS],
            'assertion_warning_count' => $assertionCounts[AssertionEvaluationService::STATUS_WARNING],
            'assertion_fail_count' => $assertionCounts[AssertionEvaluationService::STATUS_FAIL],
            'assertion_not_configured_count' => $assertionCounts[AssertionEvaluationService::STATUS_NOT_CONFIGURED],
            'regression' => $regression,
            'test_cases' => $testCounts,
            'contract' => $contractCounts,
            'findings' => $findingCounts,
            'qa_coverage' => $qaCoverage,
        ];
    }

    private function statusCss(string $status): string
    {
        return match ($status) {
            self::STATUS_HEALTHY => 'success',
            self::STATUS_WARNINGS => 'warning',
            self::STATUS_FAILING => 'danger',
            default => 'default',
        };
    }
}
