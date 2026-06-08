<?php

namespace App\Services;

use App\Models\ApiMonitor;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\TestCase;
use App\Services\Risk\RiskAnalyzer;
use App\Services\Settings\SettingService;
use Illuminate\Support\Collection;

class ReleaseReadinessService
{
    public const STATUS_PASS = 'pass';
    public const STATUS_WARNING = 'warning';
    public const STATUS_FAIL = 'fail';
    public const STATUS_IDLE = 'idle';

    public function __construct(
        private readonly RiskAnalyzer $riskAnalyzer,
        private readonly AssertionEvaluationService $assertions,
        private readonly RegressionEvaluationService $regressions,
        private readonly QaCoverageMatrixService $coverageMatrix,
        private readonly SettingService $settings,
    ) {
    }

    /** @return array<string, mixed> */
    public function summarize(Project $project): array
    {
        $project->loadMissing([
            'scanRuns.environment',
            'snapshots.environment',
            'compareRuns.items',
            'apiMonitors.environment',
            'contractValidationRuns',
            'findings',
            'testCases',
        ]);
        $project->loadCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'apiMonitors', 'testCases']);

        $endpoints = $project->endpoints()
            ->with(['project', 'environment', 'authProfile', 'latestScanResult'])
            ->get();
        $latestScan = $project->scanRuns()->with(['environment', 'results.endpoint'])->latest()->first();
        $latestCompare = $project->compareRuns()->with(['snapshotA', 'snapshotB', 'items'])->latest()->first();
        $latestSnapshot = $project->snapshots()->with('environment')->latest()->first();
        $latestContractValidation = $project->contractValidationRuns()->latest()->first();
        $openFindings = $project->findings()
            ->whereIn('status', Finding::OPEN_STATUSES)
            ->with(['endpoint', 'testCase', 'evidence'])
            ->latest('detected_at')
            ->get();
        $findingCounts = [
            'open' => $openFindings->count(),
            'critical_open' => $openFindings->where('severity', Finding::SEVERITY_CRITICAL)->count(),
            'high_open' => $openFindings->where('severity', Finding::SEVERITY_HIGH)->count(),
            'medium_open' => $openFindings->where('severity', Finding::SEVERITY_MEDIUM)->count(),
            'low_open' => $openFindings->where('severity', Finding::SEVERITY_LOW)->count(),
        ];
        $testRunCounts = collect(TestCase::RUN_STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => 0])
            ->all();
        foreach ($project->testCases as $testCase) {
            $status = $testCase->last_run_status ?: TestCase::RUN_NOT_RUN;
            $testRunCounts[$status] = ($testRunCounts[$status] ?? 0) + 1;
        }
        $testExecutedCount = $testRunCounts[TestCase::RUN_PASS]
            + $testRunCounts[TestCase::RUN_FAIL]
            + $testRunCounts[TestCase::RUN_BLOCKED]
            + $testRunCounts[TestCase::RUN_SKIPPED];
        $testExecution = [
            'total' => (int) $project->test_cases_count,
            'executed' => $testExecutedCount,
            'execution_percent' => $project->test_cases_count > 0 ? (int) round(($testExecutedCount / $project->test_cases_count) * 100) : 0,
            'pass_rate' => $testExecutedCount > 0 ? (int) round(($testRunCounts[TestCase::RUN_PASS] / $testExecutedCount) * 100) : 0,
            'run_counts' => $testRunCounts,
        ];
        $qaCoverage = $this->coverageMatrix->summarize($project)['summary'];

        $regression = $latestCompare
            ? $this->regressions->evaluateCompare($latestCompare)
            : $this->emptyRegression();

        $assertionCounts = [
            AssertionEvaluationService::STATUS_PASS => 0,
            AssertionEvaluationService::STATUS_WARNING => 0,
            AssertionEvaluationService::STATUS_FAIL => 0,
            AssertionEvaluationService::STATUS_NOT_CONFIGURED => 0,
        ];

        $riskCounts = collect(Endpoint::RISKS)->mapWithKeys(fn (string $risk): array => [$risk => 0])->all();
        $failedEndpoints = collect();
        $warningEndpoints = collect();
        $slowEndpoints = collect();
        $securityHeaderIssues = collect();
        $topRiskEndpoints = collect();
        $riskTrend = $this->riskTrend($project);
        $regressionTrend = $this->regressionTrend($project);

        foreach ($endpoints as $endpoint) {
            $latest = $endpoint->latestScanResult;
            $assertion = $this->assertions->evaluate($endpoint, $latest);
            $analysis = $this->riskAnalyzer->analyze($endpoint, $latest);

            $assertionCounts[$assertion['status']] = ($assertionCounts[$assertion['status']] ?? 0) + 1;
            $riskCounts[$analysis['final_level']] = ($riskCounts[$analysis['final_level']] ?? 0) + 1;

            $row = [
                'endpoint' => $endpoint,
                'latest' => $latest,
                'assertion' => $assertion,
                'analysis' => $analysis,
            ];

            if ($assertion['status'] === AssertionEvaluationService::STATUS_FAIL) {
                $failedEndpoints->push($row);
            } elseif ($assertion['status'] === AssertionEvaluationService::STATUS_WARNING) {
                $warningEndpoints->push($row);
            }

            if ($latest?->response_time_ms !== null) {
                $slowEndpoints->push($row);
            }

            if (collect($analysis['signals'])->contains(fn (array $signal): bool => ($signal['key'] ?? '') === 'security_headers_missing')) {
                $securityHeaderIssues->push($row);
            }

            $topRiskEndpoints->push($row);
        }

        $coverageCount = $latestScan
            ? $latestScan->results->whereNotNull('endpoint_id')->unique('endpoint_id')->count()
            : $endpoints->filter(fn (Endpoint $endpoint): bool => $endpoint->latestScanResult !== null)->count();
        $endpointCount = max(0, (int) $project->endpoints_count);
        $coveragePercent = $endpointCount > 0 ? (int) round(($coverageCount / $endpointCount) * 100) : 0;

        $blockingIssues = $this->blockingIssues($project, $latestScan, $assertionCounts, $regression, $riskCounts, $coveragePercent, $latestContractValidation, $findingCounts, $testExecution);
        $warnings = $this->warnings($latestScan, $assertionCounts, $regression, $riskCounts, $coveragePercent, $project, $latestContractValidation, $findingCounts, $testExecution);

        $score = $this->score($endpointCount, $coveragePercent, $assertionCounts, $regression, $riskCounts, $latestScan, $project, $latestContractValidation, $findingCounts, $testExecution);
        $status = $this->status($project, $latestScan, $blockingIssues, $warnings, $score);

        return [
            'status' => $status,
            'label' => __('messages.release_readiness.statuses.'.$status),
            'css' => $this->statusCss($status),
            'score' => $score,
            'grade' => $this->grade($score),
            'endpoint_count' => $endpointCount,
            'coverage_count' => $coverageCount,
            'coverage_percent' => $coveragePercent,
            'latest_scan' => $latestScan,
            'latest_snapshot' => $latestSnapshot,
            'latest_compare' => $latestCompare,
            'latest_contract_validation' => $latestContractValidation,
            'finding_counts' => $findingCounts,
            'test_execution' => $testExecution,
            'qa_coverage' => $qaCoverage,
            'open_findings' => $openFindings->take(10)->values(),
            'assertion_counts' => $assertionCounts,
            'risk_counts' => $riskCounts,
            'regression' => $regression,
            'risk_trend' => $riskTrend,
            'regression_trend' => $regressionTrend,
            'blocking_issues' => $blockingIssues,
            'warnings' => $warnings,
            'failed_endpoints' => $failedEndpoints->take(10)->values(),
            'warning_endpoints' => $warningEndpoints->take(10)->values(),
            'top_slow_endpoints' => $slowEndpoints->sortByDesc(fn (array $row): int => (int) ($row['latest']?->response_time_ms ?? 0))->take(10)->values(),
            'top_risk_endpoints' => $topRiskEndpoints->sortByDesc(fn (array $row): int => (int) ($row['analysis']['score'] ?? 0))->take(10)->values(),
            'security_header_issues' => $securityHeaderIssues->take(10)->values(),
            'recommended_actions' => $this->recommendedActions($blockingIssues, $warnings, $regression, $assertionCounts, $coveragePercent, $findingCounts),
            'policy' => $this->releasePolicy(),
        ];
    }

    public function markdown(Project $project): string
    {
        $summary = $this->summarize($project);
        $lines = [];
        $lines[] = '# Aptoria Release Readiness Report';
        $lines[] = '';
        $lines[] = '**Project:** '.$project->name;
        $lines[] = '**Base URL:** '.$project->display_base_url;
        $lines[] = '**Generated:** '.now()->format('Y-m-d H:i:s');
        $lines[] = '**Aptoria version:** '.config('aptoria.version');
        $lines[] = '';
        $lines[] = '## Overall Status';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Status | '.$this->md($summary['label']).' |';
        $lines[] = '| Release readiness score | '.$summary['score'].' / 100 |';
        $lines[] = '| Grade | '.$summary['grade'].' |';
        $lines[] = '| Endpoint coverage | '.$summary['coverage_percent'].'% |';
        $lines[] = '| Test execution coverage | '.$summary['test_execution']['execution_percent'].'% |';
        $lines[] = '| Test pass rate | '.$summary['test_execution']['pass_rate'].'% |';
        $lines[] = '| QA coverage | '.$summary['qa_coverage']['coverage_percent'].'% |';
        $lines[] = '| QA coverage blocked endpoints | '.$summary['qa_coverage']['blocked'].' |';
        $lines[] = '| Blocking issues | '.count($summary['blocking_issues']).' |';
        $lines[] = '| Warnings | '.count($summary['warnings']).' |';
        $lines[] = '';
        $lines[] = '## Evidence Summary';
        $lines[] = '';
        $lines[] = '| Evidence | Value |';
        $lines[] = '|---|---|';
        $lines[] = '| Latest scan | '.$this->md($summary['latest_scan'] ? '#'.$summary['latest_scan']->id.' '.$summary['latest_scan']->status_label : 'n/a').' |';
        $lines[] = '| Latest snapshot | '.$this->md($summary['latest_snapshot']?->name ?: 'n/a').' |';
        $lines[] = '| Latest compare | '.$this->md($summary['latest_compare'] ? '#'.$summary['latest_compare']->id : 'n/a').' |';
        $lines[] = '| Latest contract validation | '.$this->md($summary['latest_contract_validation'] ? '#'.$summary['latest_contract_validation']->id.' '.$summary['latest_contract_validation']->health_label : 'n/a').' |';
        $lines[] = '| Open findings | '.$summary['finding_counts']['open'].' |';
        $lines[] = '| Critical open findings | '.$summary['finding_counts']['critical_open'].' |';
        $lines[] = '| High open findings | '.$summary['finding_counts']['high_open'].' |';
        $lines[] = '| Regression status | '.$this->md($summary['regression']['label']).' |';
        $lines[] = '';
        $lines[] = '## Test Execution Summary';
        $lines[] = '';
        $lines[] = '| Status | Count |';
        $lines[] = '|---|---:|';
        foreach ($summary['test_execution']['run_counts'] as $status => $count) {
            $lines[] = '| '.$this->md(__('messages.test_cases.run_statuses.'.$status)).' | '.$count.' |';
        }
        $lines[] = '| Executed | '.$summary['test_execution']['executed'].' |';
        $lines[] = '| Execution coverage | '.$summary['test_execution']['execution_percent'].'% |';
        $lines[] = '| Pass rate | '.$summary['test_execution']['pass_rate'].'% |';
        $lines[] = '';
        $lines[] = '## QA Coverage Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Full coverage | '.$summary['qa_coverage']['coverage_percent'].'% |';
        $lines[] = '| Fully covered endpoints | '.$summary['qa_coverage']['fully_covered'].' |';
        $lines[] = '| Needs attention | '.$summary['qa_coverage']['warning'].' |';
        $lines[] = '| Blocked endpoints | '.$summary['qa_coverage']['blocked'].' |';
        $lines[] = '| Missing test cases | '.$summary['qa_coverage']['missing_tests'].' |';
        $lines[] = '| Missing assertions | '.$summary['qa_coverage']['missing_assertions'].' |';
        $lines[] = '| Not scanned | '.$summary['qa_coverage']['not_scanned'].' |';
        $lines[] = '| Missing contract result | '.$summary['qa_coverage']['missing_contract'].' |';
        $lines[] = '';

        $lines[] = '## Assertion Summary';
        $lines[] = '';
        $lines[] = '| Status | Count |';
        $lines[] = '|---|---:|';
        foreach ($summary['assertion_counts'] as $status => $count) {
            $lines[] = '| '.$this->md(__('messages.assertions.statuses.'.$status)).' | '.$count.' |';
        }
        $lines[] = '';
        $lines[] = '## Contract Validation Summary';
        $lines[] = '';
        if ($summary['latest_contract_validation']) {
            $contract = $summary['latest_contract_validation'];
            $lines[] = '| Metric | Count |';
            $lines[] = '|---|---:|';
            $lines[] = '| Total checks | '.$contract->total_checks.' |';
            $lines[] = '| Breaking issues | '.$contract->breaking_count.' |';
            $lines[] = '| Failed checks | '.$contract->failed_count.' |';
            $lines[] = '| Warnings | '.$contract->warning_count.' |';
            $lines[] = '| Missing endpoints | '.$contract->missing_endpoint_count.' |';
            $lines[] = '| Undocumented endpoints | '.$contract->undocumented_endpoint_count.' |';
        } else {
            $lines[] = 'No contract validation has been recorded yet.';
        }
        $lines[] = '';

        $lines[] = '## Risk Summary';
        $lines[] = '';
        $lines[] = '| Risk | Count |';
        $lines[] = '|---|---:|';
        foreach ($summary['risk_counts'] as $risk => $count) {
            $lines[] = '| '.$this->md(__('messages.endpoints.risks.'.$risk)).' | '.$count.' |';
        }
        $lines[] = '';
        $lines[] = '## Open Findings';
        $lines[] = '';
        if ($summary['open_findings']->isEmpty()) {
            $lines[] = 'No open findings.';
        } else {
            $lines[] = '| Severity | Status | Finding | Endpoint | Evidence |';
            $lines[] = '|---|---|---|---|---:|';
            foreach ($summary['open_findings'] as $finding) {
                $endpoint = $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : 'n/a';
                $lines[] = '| '.$this->md($finding->severity_label).' | '.$this->md($finding->status_label).' | '.$this->md($finding->title).' | '.$this->md($endpoint).' | '.$finding->evidence->count().' |';
            }
        }
        $lines[] = '';
        $lines[] = '## Blocking Issues';
        $lines[] = '';
        $lines = array_merge($lines, $this->bulletList($summary['blocking_issues'], 'No blocking issues detected.'));
        $lines[] = '';
        $lines[] = '## Warnings';
        $lines[] = '';
        $lines = array_merge($lines, $this->bulletList($summary['warnings'], 'No warnings detected.'));
        $lines[] = '';
        $lines[] = '## Top Failing Endpoints';
        $lines[] = '';
        $lines = array_merge($lines, $this->endpointTable($summary['failed_endpoints']));
        $lines[] = '';
        $lines[] = '## Top Slow Endpoints';
        $lines[] = '';
        $lines = array_merge($lines, $this->endpointTable($summary['top_slow_endpoints']));
        $lines[] = '';
        $lines[] = '## Security Header Issues';
        $lines[] = '';
        $lines = array_merge($lines, $this->endpointTable($summary['security_header_issues']));
        $lines[] = '';
        $lines[] = '## Recommended Actions';
        $lines[] = '';
        $lines = array_merge($lines, $this->bulletList($summary['recommended_actions'], 'No immediate action required.'));
        $lines[] = '';
        $lines[] = '_This report uses stored safe GET/HEAD probe, snapshot and compare evidence. It does not execute new HTTP requests._';

        return implode("\n", $lines)."\n";
    }

    /** @return array<int, string> */
    private function blockingIssues(Project $project, $latestScan, array $assertionCounts, array $regression, array $riskCounts, int $coveragePercent, $latestContractValidation, array $findingCounts, array $testExecution): array
    {
        $issues = [];

        if (! $project->is_active) {
            $issues[] = __('messages.release_readiness.issues.project_inactive');
        }
        if ($this->settings->boolean('release.minimum_successful_scan_required', true)) {
            if (! $latestScan) {
                $issues[] = __('messages.release_readiness.issues.no_scan');
            } elseif ($latestScan->status !== \App\Models\ScanRun::STATUS_COMPLETED) {
                $issues[] = __('messages.release_readiness.issues.latest_scan_failed');
            }
        }
        if (($latestScan?->error_count ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.scan_errors', ['count' => $latestScan->error_count]);
        }
        if ($this->settings->boolean('release.failed_assertions_block_release', true) && ($assertionCounts[AssertionEvaluationService::STATUS_FAIL] ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.assertion_failures', ['count' => $assertionCounts[AssertionEvaluationService::STATUS_FAIL]]);
        }
        if ($this->settings->boolean('release.regressions_block_release', true) && ($regression['detected_count'] ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.regression_detected', ['count' => $regression['detected_count']]);
        }
        if ($this->settings->boolean('assertions.treat_regression_as_failure', true) && ($regression['warning_count'] ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.warnings.regression_warnings', ['count' => $regression['warning_count']]);
        }
        if ($this->settings->boolean('release.critical_findings_block_release', true) && ($riskCounts[Endpoint::RISK_CRITICAL] ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.critical_risk', ['count' => $riskCounts[Endpoint::RISK_CRITICAL]]);
        }
        if ($project->endpoints_count > 0 && $coveragePercent < max(0, min(100, $this->settings->integer('release.minimum_coverage_percent', 80)))) {
            $issues[] = __('messages.release_readiness.issues.low_coverage', ['percent' => $coveragePercent]);
        }
        if (($latestContractValidation?->breaking_count ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.contract_breaking', ['count' => $latestContractValidation->breaking_count]);
        }
        if (($latestContractValidation?->failed_count ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.contract_failed', ['count' => $latestContractValidation->failed_count]);
        }
        if ($this->settings->boolean('release.critical_findings_block_release', true) && ($findingCounts['critical_open'] ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.critical_findings', ['count' => $findingCounts['critical_open']]);
        }
        if (($testExecution['run_counts'][TestCase::RUN_FAIL] ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.failed_tests', ['count' => $testExecution['run_counts'][TestCase::RUN_FAIL]]);
        }
        if ($this->settings->boolean('release.required_evidence_before_release', true) && $project->findingEvidence()->count() === 0) {
            $issues[] = 'No QA evidence has been recorded for this project.';
        }
        if ($this->settings->boolean('release.required_snapshot_before_release', false) && $project->snapshots()->count() === 0) {
            $issues[] = 'No baseline snapshot has been saved for this project.';
        }
        if ($this->settings->boolean('release.required_report_before_release', true) && $project->qaReleaseGates()->count() === 0) {
            $issues[] = 'No release gate/report snapshot has been generated for this project.';
        }

        return array_values(array_unique($issues));
    }

    /** @return array<int, string> */
    private function warnings($latestScan, array $assertionCounts, array $regression, array $riskCounts, int $coveragePercent, Project $project, $latestContractValidation, array $findingCounts, array $testExecution): array
    {
        $warnings = [];

        if (($assertionCounts[AssertionEvaluationService::STATUS_WARNING] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.assertion_warnings', ['count' => $assertionCounts[AssertionEvaluationService::STATUS_WARNING]]);
        }
        if (($assertionCounts[AssertionEvaluationService::STATUS_NOT_CONFIGURED] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.assertions_not_configured', ['count' => $assertionCounts[AssertionEvaluationService::STATUS_NOT_CONFIGURED]]);
        }
        if (($latestScan?->warning_count ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.scan_warnings', ['count' => $latestScan->warning_count]);
        }
        if (! $this->settings->boolean('assertions.treat_regression_as_failure', true) && ($regression['warning_count'] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.regression_warnings', ['count' => $regression['warning_count']]);
        }
        if ($this->settings->boolean('release.high_findings_require_review', true) && ($riskCounts[Endpoint::RISK_HIGH] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.high_risk', ['count' => $riskCounts[Endpoint::RISK_HIGH]]);
        }
        if ($project->endpoints_count > 0 && $coveragePercent < max(0, min(100, $this->settings->integer('release.minimum_coverage_percent', 80)))) {
            $warnings[] = __('messages.release_readiness.warnings.coverage_warning', ['percent' => $coveragePercent]);
        }
        if ($project->apiMonitors()->where('is_enabled', true)->count() === 0) {
            $warnings[] = __('messages.release_readiness.warnings.no_monitor');
        }
        if (! $latestContractValidation) {
            $warnings[] = __('messages.release_readiness.warnings.no_contract_validation');
        } elseif (($latestContractValidation->warning_count ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.contract_warnings', ['count' => $latestContractValidation->warning_count]);
        }
        if (($findingCounts['high_open'] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.high_findings', ['count' => $findingCounts['high_open']]);
        }
        if (($findingCounts['medium_open'] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.medium_findings', ['count' => $findingCounts['medium_open']]);
        }
        if (($testExecution['run_counts'][TestCase::RUN_BLOCKED] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.blocked_tests', ['count' => $testExecution['run_counts'][TestCase::RUN_BLOCKED]]);
        }
        if (($testExecution['total'] ?? 0) > 0 && ($testExecution['execution_percent'] ?? 0) < 80) {
            $warnings[] = __('messages.release_readiness.warnings.low_test_execution', ['percent' => $testExecution['execution_percent']]);
        }

        return array_values(array_unique($warnings));
    }

    private function score(int $endpointCount, int $coveragePercent, array $assertionCounts, array $regression, array $riskCounts, $latestScan, Project $project, $latestContractValidation, array $findingCounts, array $testExecution): int
    {
        if ($endpointCount === 0 || ! $latestScan) {
            return 0;
        }

        $score = 100;
        $score -= max(0, $this->settings->integer('release.minimum_coverage_percent', 80) - $coveragePercent) * 0.40;
        $score -= ($assertionCounts[AssertionEvaluationService::STATUS_FAIL] ?? 0) * 12;
        $score -= ($assertionCounts[AssertionEvaluationService::STATUS_WARNING] ?? 0) * 5;
        $score -= ($assertionCounts[AssertionEvaluationService::STATUS_NOT_CONFIGURED] ?? 0) * 2;
        $score -= ($latestScan->error_count ?? 0) * 10;
        $score -= ($latestScan->warning_count ?? 0) * 4;
        $score -= ($regression['detected_count'] ?? 0) * 15;
        $score -= ($regression['warning_count'] ?? 0) * 7;
        $score -= ($riskCounts[Endpoint::RISK_CRITICAL] ?? 0) * 10;
        $score -= ($riskCounts[Endpoint::RISK_HIGH] ?? 0) * 5;
        $score -= ($latestContractValidation?->breaking_count ?? 0) * 15;
        $score -= ($latestContractValidation?->failed_count ?? 0) * 8;
        $score -= ($latestContractValidation?->warning_count ?? 0) * 3;
        $score -= ($findingCounts['critical_open'] ?? 0) * 18;
        $score -= ($findingCounts['high_open'] ?? 0) * 10;
        $score -= ($findingCounts['medium_open'] ?? 0) * 4;
        $score -= ($testExecution['run_counts'][TestCase::RUN_FAIL] ?? 0) * 12;
        $score -= ($testExecution['run_counts'][TestCase::RUN_BLOCKED] ?? 0) * 6;
        if (($testExecution['total'] ?? 0) > 0) {
            $score -= max(0, 100 - ($testExecution['execution_percent'] ?? 0)) * 0.15;
        }
        $score += min(5, ($regression['recovered_count'] ?? 0));
        $score += min(5, ($regression['improved_count'] ?? 0));

        if (! $project->is_active) {
            $score -= 30;
        }

        return (int) max(0, min(100, round($score)));
    }

    /** @param array<int, string> $blockingIssues @param array<int, string> $warnings */
    private function status(Project $project, $latestScan, array $blockingIssues, array $warnings, int $score): string
    {
        if (! $latestScan || $project->endpoints_count === 0) {
            return self::STATUS_IDLE;
        }
        $failScore = $this->settings->boolean('release.security_audit_must_pass', true) ? 70 : 60;
        if ($blockingIssues !== [] || $score < $failScore) {
            return self::STATUS_FAIL;
        }
        if ($warnings !== [] || $score < 90) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_PASS;
    }

    /** @return array<int, array<string, mixed>> */
    private function riskTrend(Project $project): array
    {
        return $project->scanRuns()
            ->with('results.endpoint')
            ->latest()
            ->limit(7)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($scanRun): array {
                $scores = $scanRun->results
                    ->filter(fn (ScanResult $result): bool => $result->endpoint !== null)
                    ->map(fn (ScanResult $result): int => (int) $this->riskAnalyzer->analyze($result->endpoint, $result)['score']);

                return [
                    'label' => $scanRun->started_at?->format('m-d H:i') ?: $scanRun->created_at->format('m-d H:i'),
                    'value' => $scores->count() > 0 ? (int) round($scores->avg()) : 0,
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function regressionTrend(Project $project): array
    {
        return $project->compareRuns()
            ->with('items')
            ->latest()
            ->limit(7)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($compareRun): array {
                $regression = $this->regressions->evaluateCompare($compareRun);

                return [
                    'label' => $compareRun->created_at->format('m-d H:i'),
                    'value' => (int) (($regression['detected_count'] ?? 0) + ($regression['warning_count'] ?? 0)),
                ];
            })
            ->all();
    }

    /** @return array<int, string> */
    private function recommendedActions(array $blockingIssues, array $warnings, array $regression, array $assertionCounts, int $coveragePercent, array $findingCounts): array
    {
        $actions = [];
        if ($blockingIssues !== []) {
            $actions[] = __('messages.release_readiness.actions.fix_blockers');
        }
        if (($assertionCounts[AssertionEvaluationService::STATUS_FAIL] ?? 0) > 0 || ($assertionCounts[AssertionEvaluationService::STATUS_WARNING] ?? 0) > 0) {
            $actions[] = __('messages.release_readiness.actions.review_assertions');
        }
        if (($regression['detected_count'] ?? 0) > 0 || ($regression['warning_count'] ?? 0) > 0) {
            $actions[] = __('messages.release_readiness.actions.review_regressions');
        }
        if ($coveragePercent < 80) {
            $actions[] = __('messages.release_readiness.actions.increase_coverage');
        }
        if (($findingCounts['open'] ?? 0) > 0) {
            $actions[] = __('messages.release_readiness.actions.review_findings');
        }
        if ($warnings !== []) {
            $actions[] = __('messages.release_readiness.actions.review_warnings');
        }
        $actions[] = __('messages.release_readiness.actions.save_clean_baseline');

        return array_values(array_unique($actions));
    }


    /** @return array<string, mixed> */
    private function releasePolicy(): array
    {
        return [
            'minimum_successful_scan_required' => $this->settings->boolean('release.minimum_successful_scan_required', true),
            'failed_assertions_block_release' => $this->settings->boolean('release.failed_assertions_block_release', true),
            'critical_findings_block_release' => $this->settings->boolean('release.critical_findings_block_release', true),
            'high_findings_require_review' => $this->settings->boolean('release.high_findings_require_review', true),
            'regressions_block_release' => $this->settings->boolean('release.regressions_block_release', true),
            'treat_regression_as_failure' => $this->settings->boolean('assertions.treat_regression_as_failure', true),
            'security_audit_must_pass' => $this->settings->boolean('release.security_audit_must_pass', true),
            'required_evidence_before_release' => $this->settings->boolean('release.required_evidence_before_release', true),
            'required_snapshot_before_release' => $this->settings->boolean('release.required_snapshot_before_release', false),
            'required_report_before_release' => $this->settings->boolean('release.required_report_before_release', true),
            'minimum_coverage_percent' => $this->settings->integer('release.minimum_coverage_percent', 80),
        ];
    }

    private function statusCss(string $status): string
    {
        return match ($status) {
            self::STATUS_PASS => 'success',
            self::STATUS_WARNING => 'warning',
            self::STATUS_FAIL => 'danger',
            default => 'default',
        };
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    /** @return array<string, mixed> */
    private function emptyRegression(): array
    {
        return [
            'status' => RegressionEvaluationService::STATUS_NONE,
            'label' => __('messages.regressions.statuses.none'),
            'css' => 'success',
            'detected_count' => 0,
            'warning_count' => 0,
            'recovered_count' => 0,
            'improved_count' => 0,
        ];
    }

    /** @param array<int, string> $items @return array<int, string> */
    private function bulletList(array $items, string $empty): array
    {
        if ($items === []) {
            return [$empty];
        }

        return array_map(fn (string $item): string => '- '.$item, $items);
    }

    /** @param Collection<int, array<string, mixed>> $rows @return array<int, string> */
    private function endpointTable(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return ['No endpoints in this section.'];
        }

        $lines = [];
        $lines[] = '| Method | Endpoint | HTTP | Time | Risk | Assertion |';
        $lines[] = '|---|---|---:|---:|---|---|';
        foreach ($rows as $row) {
            $endpoint = $row['endpoint'];
            $latest = $row['latest'];
            $lines[] = '| '.$this->md($endpoint->method).' | '.$this->md($endpoint->path).' | '.$this->md($latest?->status_code ?: 'n/a').' | '.$this->md($latest?->response_time_ms !== null ? $latest->response_time_ms.' ms' : 'n/a').' | '.$this->md($row['analysis']['final_label'] ?? 'n/a').' | '.$this->md($row['assertion']['label'] ?? 'n/a').' |';
        }

        return $lines;
    }

    private function md(?string $value): string
    {
        return str_replace('|', '\\|', (string) ($value ?? ''));
    }
}
