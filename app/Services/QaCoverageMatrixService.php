<?php

namespace App\Services;

use App\Models\ContractValidationResult;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Support\Collection;

class QaCoverageMatrixService
{
    public const STATUS_COVERED = 'covered';
    public const STATUS_WARNING = 'warning';
    public const STATUS_BLOCKED = 'blocked';

    public function __construct(private readonly AssertionEvaluationService $assertions)
    {
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function summarize(Project $project, array $filters = []): array
    {
        $endpoints = $project->endpoints()
            ->with([
                'project',
                'environment',
                'authProfile',
                'latestScanResult',
                'assertionRules',
                'testCases.latestResult',
                'findings',
            ])
            ->orderBy('method')
            ->orderBy('path')
            ->get();

        $latestContractRun = $project->contractValidationRuns()
            ->with('results')
            ->latest()
            ->first();
        $contractByEndpoint = $latestContractRun
            ? $latestContractRun->results->whereNotNull('endpoint_id')->groupBy('endpoint_id')
            : collect();

        $rows = $endpoints->map(function (Endpoint $endpoint) use ($contractByEndpoint): array {
            return $this->row($endpoint, $contractByEndpoint->get($endpoint->id, collect()));
        });

        $summary = $this->summary($rows, $endpoints->count(), (bool) $latestContractRun);
        $filteredRows = $this->applyFilters($rows, $filters)->values();

        return [
            'summary' => $summary,
            'rows' => $filteredRows,
            'all_rows' => $rows->values(),
            'latest_contract_run' => $latestContractRun,
            'filters' => [
                'status' => (string) ($filters['status'] ?? ''),
                'gap' => (string) ($filters['gap'] ?? ''),
                'risk' => (string) ($filters['risk'] ?? ''),
            ],
        ];
    }

    /** @param Collection<int, ContractValidationResult> $contractResults @return array<string, mixed> */
    private function row(Endpoint $endpoint, Collection $contractResults): array
    {
        $latestScan = $endpoint->latestScanResult;
        $assertion = $this->assertions->evaluate($endpoint, $latestScan);
        $testCases = $endpoint->testCases;
        $openFindings = $endpoint->findings->filter(fn (Finding $finding): bool => in_array($finding->status, Finding::OPEN_STATUSES, true));
        $criticalOpenFindings = $openFindings->where('severity', Finding::SEVERITY_CRITICAL)->count();
        $highOpenFindings = $openFindings->where('severity', Finding::SEVERITY_HIGH)->count();

        $testRunCounts = collect(TestCase::RUN_STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => 0])
            ->all();
        foreach ($testCases as $testCase) {
            $status = $testCase->last_run_status ?: TestCase::RUN_NOT_RUN;
            $testRunCounts[$status] = ($testRunCounts[$status] ?? 0) + 1;
        }

        $contractCounts = [
            ContractValidationResult::STATUS_PASS => 0,
            ContractValidationResult::STATUS_FAIL => 0,
            ContractValidationResult::STATUS_WARNING => 0,
            ContractValidationResult::STATUS_SKIPPED => 0,
            'breaking' => 0,
        ];
        foreach ($contractResults as $result) {
            $contractCounts[$result->status] = ($contractCounts[$result->status] ?? 0) + 1;
            if (in_array($result->severity, [ContractValidationResult::SEVERITY_HIGH, ContractValidationResult::SEVERITY_CRITICAL], true)
                && $result->status === ContractValidationResult::STATUS_FAIL) {
                $contractCounts['breaking']++;
            }
        }

        $gaps = [];
        $warnings = [];
        $blockers = [];

        if ($testCases->isEmpty()) {
            $gaps[] = 'missing_test_cases';
            $warnings[] = __('messages.qa_coverage.gaps.missing_test_cases');
        }
        if ($endpoint->assertionRules->where('enabled', true)->isEmpty()) {
            $gaps[] = 'missing_assertions';
            $warnings[] = __('messages.qa_coverage.gaps.missing_assertions');
        }
        if (! $latestScan) {
            $gaps[] = 'not_scanned';
            $warnings[] = __('messages.qa_coverage.gaps.not_scanned');
        }
        if ($contractResults->isEmpty()) {
            $gaps[] = 'missing_contract';
            $warnings[] = __('messages.qa_coverage.gaps.missing_contract');
        }
        if ($openFindings->isNotEmpty()) {
            $gaps[] = 'open_findings';
            $warnings[] = __('messages.qa_coverage.gaps.open_findings');
        }

        if ($testRunCounts[TestCase::RUN_FAIL] > 0) {
            $blockers[] = __('messages.qa_coverage.blockers.failed_tests');
        }
        if ($assertion['status'] === AssertionEvaluationService::STATUS_FAIL) {
            $blockers[] = __('messages.qa_coverage.blockers.failed_assertions');
        } elseif ($assertion['status'] === AssertionEvaluationService::STATUS_WARNING) {
            $warnings[] = __('messages.qa_coverage.gaps.assertion_warnings');
        }
        if ($contractCounts[ContractValidationResult::STATUS_FAIL] > 0) {
            $blockers[] = __('messages.qa_coverage.blockers.contract_failures');
        } elseif ($contractCounts[ContractValidationResult::STATUS_WARNING] > 0) {
            $warnings[] = __('messages.qa_coverage.gaps.contract_warnings');
        }
        if ($criticalOpenFindings > 0) {
            $blockers[] = __('messages.qa_coverage.blockers.critical_findings');
        } elseif ($highOpenFindings > 0) {
            $warnings[] = __('messages.qa_coverage.gaps.high_findings');
        }
        if ($testRunCounts[TestCase::RUN_BLOCKED] > 0) {
            $warnings[] = __('messages.qa_coverage.gaps.blocked_tests');
        }

        $score = $this->score($testCases->count(), $endpoint->assertionRules->where('enabled', true)->count(), $latestScan !== null, $contractResults->isNotEmpty(), $openFindings->count(), $assertion['status'], $testRunCounts, $contractCounts, $criticalOpenFindings, $highOpenFindings);
        $status = $blockers !== [] ? self::STATUS_BLOCKED : ($warnings !== [] ? self::STATUS_WARNING : self::STATUS_COVERED);

        return [
            'endpoint' => $endpoint,
            'score' => $score,
            'status' => $status,
            'status_label' => __('messages.qa_coverage.statuses.'.$status),
            'status_css' => $this->statusCss($status),
            'gaps' => array_values(array_unique($gaps)),
            'warnings' => array_values(array_unique($warnings)),
            'blockers' => array_values(array_unique($blockers)),
            'test_cases_count' => $testCases->count(),
            'test_run_counts' => $testRunCounts,
            'assertion_rules_count' => $endpoint->assertionRules->where('enabled', true)->count(),
            'assertion_status' => $assertion['status'],
            'assertion_label' => $assertion['label'],
            'assertion_css' => $assertion['css'],
            'has_scan' => $latestScan !== null,
            'latest_scan' => $latestScan,
            'contract_counts' => $contractCounts,
            'has_contract' => $contractResults->isNotEmpty(),
            'open_findings_count' => $openFindings->count(),
            'critical_open_findings' => $criticalOpenFindings,
            'high_open_findings' => $highOpenFindings,
        ];
    }

    /** @param Collection<int, array<string, mixed>> $rows @return array<string, mixed> */
    private function summary(Collection $rows, int $endpointCount, bool $hasContractRun): array
    {
        $fullyCovered = $rows->where('status', self::STATUS_COVERED)->count();
        $blocked = $rows->where('status', self::STATUS_BLOCKED)->count();
        $warning = $rows->where('status', self::STATUS_WARNING)->count();
        $missingTests = $rows->filter(fn (array $row): bool => in_array('missing_test_cases', $row['gaps'], true))->count();
        $missingAssertions = $rows->filter(fn (array $row): bool => in_array('missing_assertions', $row['gaps'], true))->count();
        $notScanned = $rows->filter(fn (array $row): bool => in_array('not_scanned', $row['gaps'], true))->count();
        $missingContract = $rows->filter(fn (array $row): bool => in_array('missing_contract', $row['gaps'], true))->count();
        $openFindings = (int) $rows->sum('open_findings_count');
        $avgScore = $rows->count() > 0 ? (int) round($rows->avg('score')) : 0;

        return [
            'endpoint_count' => $endpointCount,
            'fully_covered' => $fullyCovered,
            'warning' => $warning,
            'blocked' => $blocked,
            'coverage_percent' => $endpointCount > 0 ? (int) round(($fullyCovered / $endpointCount) * 100) : 0,
            'average_score' => $avgScore,
            'missing_tests' => $missingTests,
            'missing_assertions' => $missingAssertions,
            'not_scanned' => $notScanned,
            'missing_contract' => $missingContract,
            'open_findings' => $openFindings,
            'has_contract_run' => $hasContractRun,
        ];
    }

    /** @param Collection<int, array<string, mixed>> $rows @param array<string, mixed> $filters @return Collection<int, array<string, mixed>> */
    private function applyFilters(Collection $rows, array $filters): Collection
    {
        $status = (string) ($filters['status'] ?? '');
        $gap = (string) ($filters['gap'] ?? '');
        $risk = (string) ($filters['risk'] ?? '');

        return $rows
            ->when($status !== '', fn (Collection $items): Collection => $items->where('status', $status))
            ->when($gap !== '', fn (Collection $items): Collection => $items->filter(fn (array $row): bool => in_array($gap, $row['gaps'], true)))
            ->when($risk !== '', fn (Collection $items): Collection => $items->filter(fn (array $row): bool => $row['endpoint']->risk_level === $risk));
    }

    /** @param array<string, int> $testRunCounts @param array<string, int> $contractCounts */
    private function score(int $testCount, int $assertionCount, bool $hasScan, bool $hasContract, int $openFindings, string $assertionStatus, array $testRunCounts, array $contractCounts, int $criticalFindings, int $highFindings): int
    {
        $score = 100;
        if ($testCount === 0) {
            $score -= 20;
        }
        if ($assertionCount === 0) {
            $score -= 20;
        }
        if (! $hasScan) {
            $score -= 20;
        }
        if (! $hasContract) {
            $score -= 15;
        }
        if ($openFindings > 0) {
            $score -= min(20, $openFindings * 5);
        }
        if ($assertionStatus === AssertionEvaluationService::STATUS_FAIL) {
            $score -= 20;
        } elseif ($assertionStatus === AssertionEvaluationService::STATUS_WARNING) {
            $score -= 8;
        }
        $score -= ($testRunCounts[TestCase::RUN_FAIL] ?? 0) * 20;
        $score -= ($testRunCounts[TestCase::RUN_BLOCKED] ?? 0) * 10;
        $score -= ($contractCounts[ContractValidationResult::STATUS_FAIL] ?? 0) * 12;
        $score -= ($contractCounts[ContractValidationResult::STATUS_WARNING] ?? 0) * 5;
        $score -= $criticalFindings * 20;
        $score -= $highFindings * 10;

        return (int) max(0, min(100, $score));
    }

    private function statusCss(string $status): string
    {
        return match ($status) {
            self::STATUS_COVERED => 'success',
            self::STATUS_BLOCKED => 'danger',
            default => 'warning',
        };
    }
}
