<?php

namespace App\Services;

use App\Models\ApiMonitor;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Models\ScanResult;
use App\Models\TestCase;
use App\Services\Risk\RiskAnalyzer;
use App\Services\Risk\RiskAcceptanceLedgerService;
use App\Services\BlindSpots\QaBlindSpotDetectorService;
use App\Services\Contracts\ContractRealityService;
use App\Services\Settings\SettingService;
use App\Services\Exports\ExportCreditService;
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
        private readonly ExportCreditService $credits,
        private readonly QaBlindSpotDetectorService $blindSpots,
        private readonly RiskAcceptanceLedgerService $riskLedger,
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
            'findings.owner',
            'findings.verifiedBy',
            'findings.evidence',
            'riskAcceptances.finding.endpoint',
            'riskAcceptances.acceptedBy',
            'latestReleaseDecision.owner',
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
            ->with(['endpoint', 'testCase', 'evidence.capturedBy', 'lifecycleChangedBy'])
            ->latest('detected_at')
            ->get();
        $findingStatusCounts = $project->findings()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $findingLifecycleCounts = collect(Finding::LIFECYCLE_STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => (int) ($findingStatusCounts[$status] ?? 0)])
            ->all();
        $fixedUnverifiedCount = $project->findings
            ->filter(fn (Finding $finding): bool => $finding->status === Finding::STATUS_FIXED && $finding->verification_status !== Finding::VERIFICATION_VERIFIED)
            ->count();
        $overdueFindingsCount = $project->findings
            ->filter(fn (Finding $finding): bool => $finding->is_overdue)
            ->count();
        $fixEvidenceMissingCount = $project->findings
            ->filter(fn (Finding $finding): bool => (bool) $finding->fix_evidence_required && $finding->evidence->isEmpty())
            ->count();

        $findingCounts = [
            'total' => (int) $project->findings->count(),
            'open' => $openFindings->count(),
            'critical_open' => $openFindings->where('severity', Finding::SEVERITY_CRITICAL)->count(),
            'high_open' => $openFindings->where('severity', Finding::SEVERITY_HIGH)->count(),
            'medium_open' => $openFindings->where('severity', Finding::SEVERITY_MEDIUM)->count(),
            'low_open' => $openFindings->where('severity', Finding::SEVERITY_LOW)->count(),
            'fixed' => $findingLifecycleCounts[Finding::STATUS_FIXED] ?? 0,
            'ready_for_retest' => $findingLifecycleCounts[Finding::STATUS_READY_FOR_RETEST] ?? 0,
            'retest_failed' => $findingLifecycleCounts[Finding::STATUS_RETEST_FAILED] ?? 0,
            'verified' => $findingLifecycleCounts[Finding::STATUS_VERIFIED] ?? 0,
            'fixed_unverified' => $fixedUnverifiedCount,
            'overdue' => $overdueFindingsCount,
            'fix_evidence_missing' => $fixEvidenceMissingCount,
            'false_positive' => $findingLifecycleCounts[Finding::STATUS_FALSE_POSITIVE] ?? 0,
            'accepted_risk' => $findingLifecycleCounts[Finding::STATUS_ACCEPTED_RISK] ?? 0,
            'reopened' => $findingLifecycleCounts[Finding::STATUS_REOPENED] ?? 0,
            'resolved' => (int) (($findingLifecycleCounts[Finding::STATUS_FIXED] ?? 0) + ($findingLifecycleCounts[Finding::STATUS_VERIFIED] ?? 0) + ($findingLifecycleCounts[Finding::STATUS_FALSE_POSITIVE] ?? 0) + ($findingLifecycleCounts[Finding::STATUS_ACCEPTED_RISK] ?? 0)),
            'status_counts' => $findingLifecycleCounts,
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
        $blindSpotSummary = $this->blindSpots->summarize($project);
        $riskAcceptanceSummary = $this->riskLedger->summarize($project);
        $contractRealitySummary = app(ContractRealityService::class)->summarize($project, $latestContractValidation);

        $blockingIssues = $this->blockingIssues($project, $latestScan, $assertionCounts, $regression, $riskCounts, $coveragePercent, $latestContractValidation, $findingCounts, $testExecution, $blindSpotSummary, $riskAcceptanceSummary);
        if (($contractRealitySummary['summary']['breaking_contract_mismatch'] ?? 0) > 0) {
            $blockingIssues[] = __('messages.release_readiness.issues.contract_reality_breaking', ['count' => $contractRealitySummary['summary']['breaking_contract_mismatch']]);
        }

        $warnings = $this->warnings($latestScan, $assertionCounts, $regression, $riskCounts, $coveragePercent, $project, $latestContractValidation, $findingCounts, $testExecution, $blindSpotSummary, $riskAcceptanceSummary);
        $contractRealityWarnings = (int) (($contractRealitySummary['summary']['auth_contract_mismatch'] ?? 0) + ($contractRealitySummary['summary']['undocumented_response'] ?? 0) + ($contractRealitySummary['summary']['undocumented_endpoint'] ?? 0));
        if ($contractRealityWarnings > 0) {
            $warnings[] = __('messages.release_readiness.warnings.contract_reality_warnings', ['count' => $contractRealityWarnings]);
        }
        $blockingIssues = array_values(array_unique($blockingIssues));
        $warnings = array_values(array_unique($warnings));

        $scoreComponents = $this->scoreComponents(
            $project,
            $endpoints,
            $latestScan,
            $latestSnapshot,
            $latestCompare,
            $latestContractValidation,
            $findingCounts,
            $testExecution,
            $qaCoverage,
            $assertionCounts,
            $regression,
            $riskCounts,
            $coveragePercent,
            $blindSpotSummary,
            $riskAcceptanceSummary
        );
        $score = $this->componentScore($scoreComponents);
        $status = $this->status($project, $latestScan, $blockingIssues, $warnings, $score);

        return [
            'status' => $status,
            'label' => __('messages.release_readiness.statuses.'.$status),
            'css' => $this->statusCss($status),
            'score' => $score,
            'grade' => $this->grade($score),
            'score_components' => $scoreComponents,
            'score_component_total' => array_sum(array_column($scoreComponents, 'max_points')),
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
            'blind_spots' => $blindSpotSummary,
            'risk_acceptances' => $riskAcceptanceSummary,
            'contract_reality' => $contractRealitySummary,
            'latest_release_decision' => $project->latestReleaseDecision,
            'risk_trend' => $riskTrend,
            'regression_trend' => $regressionTrend,
            'blocking_issues' => $blockingIssues,
            'warnings' => $warnings,
            'failed_endpoints' => $failedEndpoints->take(10)->values(),
            'warning_endpoints' => $warningEndpoints->take(10)->values(),
            'top_slow_endpoints' => $slowEndpoints->sortByDesc(fn (array $row): int => (int) ($row['latest']?->response_time_ms ?? 0))->take(10)->values(),
            'top_risk_endpoints' => $topRiskEndpoints->sortByDesc(fn (array $row): int => (int) ($row['analysis']['score'] ?? 0))->take(10)->values(),
            'security_header_issues' => $securityHeaderIssues->take(10)->values(),
            'recommended_actions' => $this->recommendedActions($blockingIssues, $warnings, $regression, $assertionCounts, $coveragePercent, $findingCounts, $blindSpotSummary, $riskAcceptanceSummary),
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
        foreach ($this->credits->projectBrandingMarkdownLines($project) as $brandingLine) {
            $lines[] = $this->mdBrandingLine($brandingLine);
        }
        if (($disclaimer = $this->credits->projectDisclaimerMarkdown($project)) !== '') {
            $lines[] = '**Disclaimer:** '.$this->md($disclaimer);
        }
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
        $lines[] = '| Blind spots | '.($summary['blind_spots']['summary']['total'] ?? 0).' |';
        $lines[] = '| Release-blocking blind spots | '.($summary['blind_spots']['summary']['release_blockers'] ?? 0).' |';
        $lines[] = '| Contract reality breaking mismatches | '.($summary['contract_reality']['summary']['breaking_contract_mismatch'] ?? 0).' |';
        $lines[] = '| Contract reality auth mismatches | '.($summary['contract_reality']['summary']['auth_contract_mismatch'] ?? 0).' |';
        $lines[] = '| Undocumented response fields | '.($summary['contract_reality']['summary']['undocumented_response'] ?? 0).' |';
        if ($summary['latest_release_decision'] instanceof ReleaseDecision) {
            $lines[] = '| Latest release decision | '.$this->md($summary['latest_release_decision']->status_label).' |';
            $lines[] = '| Release decision owner | '.$this->md($summary['latest_release_decision']->owner?->name ?: 'n/a').' |';
            $lines[] = '| Release decision checksum | '.$this->md($summary['latest_release_decision']->package_checksum ?: 'n/a').' |';
        } else {
            $lines[] = '| Latest release decision | n/a |';
        }
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
        $lines[] = '| Reopened findings | '.($summary['finding_counts']['reopened'] ?? 0).' |';
        $lines[] = '| Fixed findings | '.($summary['finding_counts']['fixed'] ?? 0).' |';
        $lines[] = '| Fixed but unverified findings | '.($summary['finding_counts']['fixed_unverified'] ?? 0).' |';
        $lines[] = '| Ready for retest findings | '.($summary['finding_counts']['ready_for_retest'] ?? 0).' |';
        $lines[] = '| Retest failed findings | '.($summary['finding_counts']['retest_failed'] ?? 0).' |';
        $lines[] = '| Verified findings | '.($summary['finding_counts']['verified'] ?? 0).' |';
        $lines[] = '| Overdue findings | '.($summary['finding_counts']['overdue'] ?? 0).' |';
        $lines[] = '| False positives | '.($summary['finding_counts']['false_positive'] ?? 0).' |';
        $lines[] = '| Accepted risks | '.($summary['finding_counts']['accepted_risk'] ?? 0).' |';
        $lines[] = '| Active risk acceptances | '.($summary['risk_acceptances']['summary']['active'] ?? 0).' |';
        $lines[] = '| Expired risk acceptances | '.($summary['risk_acceptances']['summary']['expired'] ?? 0).' |';
        $lines[] = '| Accepted risks without expiry | '.($summary['risk_acceptances']['summary']['without_expiry'] ?? 0).' |';
        $lines[] = '| Regression status | '.$this->md($summary['regression']['label']).' |';
        $lines[] = '';

        $this->appendBlindSpotMarkdownSummary($lines, $summary['blind_spots']);

        $lines[] = '## Contract Reality Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Matches contract | '.($summary['contract_reality']['summary']['matches_contract'] ?? 0).' |';
        $lines[] = '| Contract drift | '.($summary['contract_reality']['summary']['contract_drift'] ?? 0).' |';
        $lines[] = '| Auth mismatches | '.($summary['contract_reality']['summary']['auth_contract_mismatch'] ?? 0).' |';
        $lines[] = '| Undocumented response | '.($summary['contract_reality']['summary']['undocumented_response'] ?? 0).' |';
        $lines[] = '| Breaking mismatches | '.($summary['contract_reality']['summary']['breaking_contract_mismatch'] ?? 0).' |';
        $lines[] = '';

        $lines[] = '## Finding Lifecycle Status Summary';
        $lines[] = '';
        $lines[] = '| Status | Count | Release impact |';
        $lines[] = '|---|---:|---|';
        foreach (Finding::LIFECYCLE_STATUSES as $status) {
            $impact = in_array($status, Finding::OPEN_STATUSES, true)
                ? 'Counts as open release risk'
                : 'Does not count as open release risk';
            $lines[] = '| '.$this->md(__('messages.findings.statuses.'.$status)).' | '.($summary['finding_counts']['status_counts'][$status] ?? 0).' | '.$this->md($impact).' |';
        }
        $lines[] = '';
        $lines[] = '## Finding Verification Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Ready for retest | '.($summary['finding_counts']['ready_for_retest'] ?? 0).' |';
        $lines[] = '| Retest failed | '.($summary['finding_counts']['retest_failed'] ?? 0).' |';
        $lines[] = '| Fixed but not verified | '.($summary['finding_counts']['fixed_unverified'] ?? 0).' |';
        $lines[] = '| Verified | '.($summary['finding_counts']['verified'] ?? 0).' |';
        $lines[] = '| Overdue | '.($summary['finding_counts']['overdue'] ?? 0).' |';
        $lines[] = '';

        $this->appendRiskAcceptanceMarkdownSummary($lines, $summary['risk_acceptances']);

        $lines[] = '## Release Decision';
        $lines[] = '';
        if ($summary['latest_release_decision'] instanceof ReleaseDecision) {
            $decision = $summary['latest_release_decision'];
            $lines[] = '| Metric | Value |';
            $lines[] = '|---|---|';
            $lines[] = '| Decision | '.$this->md($decision->status_label).' |';
            $lines[] = '| Release | '.$this->md($decision->release_name ?: 'n/a').' |';
            $lines[] = '| Owner | '.$this->md($decision->owner?->name ?: 'n/a').' |';
            $lines[] = '| Timestamp | '.$this->md($decision->decided_at?->format('Y-m-d H:i:s') ?: 'pending').' |';
            $lines[] = '| Package checksum | '.$this->md($decision->package_checksum ?: 'n/a').' |';
        } else {
            $lines[] = 'No release decision package has been finalized yet.';
        }
        $lines[] = '';

        $lines[] = '## Readiness Score Breakdown';
        $lines[] = '';
        $lines[] = '| Component | Points | Percent | Status |';
        $lines[] = '|---|---:|---:|---|';
        foreach ($summary['score_components'] as $component) {
            $lines[] = '| '.$this->md((string) $component['label']).' | '.$component['earned_points'].' / '.$component['max_points'].' | '.$component['percent'].'% | '.$this->md((string) $component['status_label']).' |';
        }
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
            $lines[] = '| Severity | Status | Finding | Endpoint | Evidence | Attachments |';
            $lines[] = '|---|---|---|---|---:|---:|';
            foreach ($summary['open_findings'] as $finding) {
                $endpoint = $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : 'n/a';
                $attachmentCount = $finding->evidence->filter(fn ($evidence): bool => $evidence->has_attachment)->count();
                $lines[] = '| '.$this->md($finding->severity_label).' | '.$this->md($finding->status_label).' | '.$this->md($finding->title).' | '.$this->md($endpoint).' | '.$finding->evidence->count().' | '.$attachmentCount.' |';
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
        $lines[] = '';
        $this->credits->appendMarkdownFooter($lines, 'release_readiness_report', $project);

        return implode("\n", $lines)."\n";
    }


    /**
     * @param Collection<int, Endpoint> $endpoints
     * @return array<int, array<string, mixed>>
     */
    private function scoreComponents(Project $project, Collection $endpoints, $latestScan, $latestSnapshot, $latestCompare, $latestContractValidation, array $findingCounts, array $testExecution, array $qaCoverage, array $assertionCounts, array $regression, array $riskCounts, int $coveragePercent, array $blindSpotSummary, array $riskAcceptanceSummary): array
    {
        $latestScanResults = $latestScan?->results ?? collect();
        $evidenceCount = $project->findingEvidence()->count();
        $releaseGateCount = $project->qaReleaseGates()->count();
        $authRequired = $endpoints->filter(fn (Endpoint $endpoint): bool => (bool) $endpoint->auth_required);
        $authConfigured = $authRequired->filter(fn (Endpoint $endpoint): bool => $endpoint->auth_profile_id !== null);
        $securityEvents = (int) $latestScanResults->filter(fn (ScanResult $result): bool => (bool) $result->sensitive_data_detected || (bool) $result->broken_auth_detected || (bool) $result->schema_drift_detected)->count();

        $components = [];
        $components[] = $this->scoreComponent('evidence', 10, min(10, ($latestScan && $latestScan->status === \App\Models\ScanRun::STATUS_COMPLETED ? 4 : 0) + ($latestSnapshot ? 2 : 0) + ($latestCompare ? 2 : 0) + min(2, $evidenceCount > 0 ? 2 : 0)), [
            __('messages.release_readiness.score_checks.latest_scan').': '.($latestScan ? $latestScan->status_label : __('messages.common.not_available')),
            __('messages.release_readiness.score_checks.snapshot').': '.($latestSnapshot ? $latestSnapshot->name : __('messages.common.not_available')),
            __('messages.release_readiness.score_checks.evidence_count').': '.$evidenceCount,
        ]);
        $components[] = $this->scoreComponent('endpoint_coverage', 15, (int) round(15 * ($coveragePercent / 100)), [
            __('messages.release_readiness.covered_endpoints').': '.$coveragePercent.'%',
        ]);
        $components[] = $this->scoreComponent('qa_coverage', 10, (int) round(10 * (($qaCoverage['coverage_percent'] ?? 0) / 100)), [
            __('messages.qa_coverage.coverage_percent').': '.($qaCoverage['coverage_percent'] ?? 0).'%',
            __('messages.qa_coverage.gap_filters.not_scanned').': '.($qaCoverage['not_scanned'] ?? 0),
        ]);
        $endpointCount = max(1, (int) $project->endpoints_count);
        $assertionQuality = (($assertionCounts[AssertionEvaluationService::STATUS_PASS] ?? 0) + (($assertionCounts[AssertionEvaluationService::STATUS_WARNING] ?? 0) * 0.5)) / $endpointCount;
        $components[] = $this->scoreComponent('assertions', 10, (int) round(10 * max(0, min(1, $assertionQuality))), [
            __('messages.assertions.statuses.pass').': '.($assertionCounts[AssertionEvaluationService::STATUS_PASS] ?? 0),
            __('messages.assertions.statuses.fail').': '.($assertionCounts[AssertionEvaluationService::STATUS_FAIL] ?? 0),
            __('messages.assertions.statuses.not_configured').': '.($assertionCounts[AssertionEvaluationService::STATUS_NOT_CONFIGURED] ?? 0),
        ]);
        $testScore = ($testExecution['total'] ?? 0) > 0
            ? ((($testExecution['execution_percent'] ?? 0) * 0.45) + (($testExecution['pass_rate'] ?? 0) * 0.55)) / 100
            : 0;
        $components[] = $this->scoreComponent('test_execution', 10, (int) round(10 * max(0, min(1, $testScore))), [
            __('messages.test_execution.execution_coverage').': '.($testExecution['execution_percent'] ?? 0).'%',
            __('messages.test_execution.pass_rate').': '.($testExecution['pass_rate'] ?? 0).'%',
        ]);
        $regressionPenalty = min(10, (($regression['detected_count'] ?? 0) * 5) + (($regression['warning_count'] ?? 0) * 2));
        $components[] = $this->scoreComponent('regression', 10, max(0, 10 - $regressionPenalty), [
            __('messages.regressions.regression_status').': '.$regression['label'],
            __('messages.release_readiness.score_checks.regressions').': '.($regression['detected_count'] ?? 0),
        ]);
        $securityPenalty = min(15, ($securityEvents * 5) + (($riskCounts[Endpoint::RISK_CRITICAL] ?? 0) * 5) + (($riskCounts[Endpoint::RISK_HIGH] ?? 0) * 2) + max(0, $authRequired->count() - $authConfigured->count()) * 3);
        $components[] = $this->scoreComponent('security', 15, max(0, 15 - $securityPenalty), [
            __('messages.release_readiness.score_checks.security_events').': '.$securityEvents,
            __('messages.release_readiness.score_checks.auth_ready').': '.$authConfigured->count().' / '.$authRequired->count(),
        ]);
        $riskAcceptanceCounts = $riskAcceptanceSummary['summary'] ?? [];
        $findingPenalty = min(10, (($findingCounts['critical_open'] ?? 0) * 5) + (($findingCounts['high_open'] ?? 0) * 3) + (($findingCounts['medium_open'] ?? 0) * 1) + (($findingCounts['retest_failed'] ?? 0) * 4) + (($findingCounts['fixed_unverified'] ?? 0) * 2) + (($findingCounts['overdue'] ?? 0) * 2) + (($riskAcceptanceCounts['expired'] ?? 0) * 4) + (($riskAcceptanceCounts['without_expiry'] ?? 0) * 1));
        $components[] = $this->scoreComponent('findings', 10, max(0, 10 - $findingPenalty), [
            __('messages.findings.open_findings').': '.($findingCounts['open'] ?? 0),
            __('messages.findings.severities.critical').': '.($findingCounts['critical_open'] ?? 0),
            __('messages.findings.severities.high').': '.($findingCounts['high_open'] ?? 0),
            __('messages.findings.statuses.reopened').': '.($findingCounts['reopened'] ?? 0),
            __('messages.findings.statuses.accepted_risk').': '.($findingCounts['accepted_risk'] ?? 0),
            __('messages.findings.statuses.false_positive').': '.($findingCounts['false_positive'] ?? 0),
            __('messages.findings.statuses.fixed').': '.($findingCounts['fixed'] ?? 0),
            __('messages.release_readiness.score_checks.retest_failed_findings').': '.($findingCounts['retest_failed'] ?? 0),
            __('messages.release_readiness.score_checks.fixed_unverified_findings').': '.($findingCounts['fixed_unverified'] ?? 0),
            __('messages.release_readiness.score_checks.overdue_findings').': '.($findingCounts['overdue'] ?? 0),
            __('messages.risk_acceptances.expired').': '.($riskAcceptanceCounts['expired'] ?? 0),
            __('messages.risk_acceptances.without_expiry').': '.($riskAcceptanceCounts['without_expiry'] ?? 0),
        ]);
        if ($latestContractValidation) {
            $contractPenalty = min(5, (($latestContractValidation->breaking_count ?? 0) * 3) + (($latestContractValidation->failed_count ?? 0) * 2) + (($latestContractValidation->warning_count ?? 0) * 1));
            $contractEarned = max(0, 5 - $contractPenalty);
        } else {
            $contractEarned = 1;
        }
        $components[] = $this->scoreComponent('contract', 5, $contractEarned, [
            __('messages.contract_validations.short_title').': '.($latestContractValidation ? $latestContractValidation->health_label : __('messages.common.not_available')),
        ]);
        $blindSpotCounts = $blindSpotSummary['summary'] ?? [];
        $blindSpotPenalty = min(5, (($blindSpotCounts['release_blockers'] ?? 0) * 2) + (($blindSpotCounts['high'] ?? 0) * 1) + (($blindSpotCounts['medium'] ?? 0) > 0 ? 1 : 0));
        $components[] = $this->scoreComponent('report_signoff', 5, max(0, min(5, ($releaseGateCount > 0 ? 3 : 0) + ($project->apiMonitors()->where('is_enabled', true)->exists() ? 1 : 0) + ($project->is_active ? 1 : 0)) - $blindSpotPenalty), [
            __('messages.release_readiness.score_checks.release_gates').': '.$releaseGateCount,
            __('messages.release_readiness.score_checks.enabled_monitors').': '.$project->apiMonitors()->where('is_enabled', true)->count(),
            __('messages.release_readiness.score_checks.blind_spots').': '.($blindSpotCounts['total'] ?? 0),
            __('messages.risk_acceptances.active').': '.($riskAcceptanceCounts['active'] ?? 0),
        ]);

        return $components;
    }

    /** @return array<string, mixed> */
    private function scoreComponent(string $key, int $maxPoints, int $earnedPoints, array $checks = []): array
    {
        $earnedPoints = max(0, min($maxPoints, $earnedPoints));
        $percent = $maxPoints > 0 ? (int) round(($earnedPoints / $maxPoints) * 100) : 0;
        $status = match (true) {
            $percent >= 85 => 'strong',
            $percent >= 55 => 'review',
            default => 'weak',
        };

        return [
            'key' => $key,
            'label' => __('messages.release_readiness.score_components.'.$key),
            'earned_points' => $earnedPoints,
            'max_points' => $maxPoints,
            'percent' => $percent,
            'status' => $status,
            'status_label' => __('messages.release_readiness.score_statuses.'.$status),
            'css' => match ($status) {
                'strong' => 'success',
                'review' => 'warning',
                default => 'danger',
            },
            'checks' => array_values(array_filter($checks)),
        ];
    }

    /** @param array<int, array<string, mixed>> $components */
    private function componentScore(array $components): int
    {
        $max = array_sum(array_map(fn (array $component): int => (int) $component['max_points'], $components));
        $earned = array_sum(array_map(fn (array $component): int => (int) $component['earned_points'], $components));

        return $max > 0 ? (int) round(($earned / $max) * 100) : 0;
    }

    /** @return array<int, string> */
    private function blockingIssues(Project $project, $latestScan, array $assertionCounts, array $regression, array $riskCounts, int $coveragePercent, $latestContractValidation, array $findingCounts, array $testExecution, array $blindSpotSummary, array $riskAcceptanceSummary): array
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
        if (($findingCounts['retest_failed'] ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.retest_failed_findings', ['count' => $findingCounts['retest_failed']]);
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
        if (($blindSpotSummary['summary']['release_blockers'] ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.release_blocking_blind_spots', ['count' => $blindSpotSummary['summary']['release_blockers']]);
        }
        if (($riskAcceptanceSummary['summary']['expired'] ?? 0) > 0) {
            $issues[] = __('messages.release_readiness.issues.expired_risk_acceptances', ['count' => $riskAcceptanceSummary['summary']['expired']]);
        }

        return array_values(array_unique($issues));
    }

    /** @return array<int, string> */
    private function warnings($latestScan, array $assertionCounts, array $regression, array $riskCounts, int $coveragePercent, Project $project, $latestContractValidation, array $findingCounts, array $testExecution, array $blindSpotSummary, array $riskAcceptanceSummary): array
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
        if (($findingCounts['fixed_unverified'] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.fixed_unverified_findings', ['count' => $findingCounts['fixed_unverified']]);
        }
        if (($findingCounts['ready_for_retest'] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.ready_for_retest_findings', ['count' => $findingCounts['ready_for_retest']]);
        }
        if (($findingCounts['overdue'] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.overdue_findings', ['count' => $findingCounts['overdue']]);
        }
        if (($testExecution['total'] ?? 0) > 0 && ($testExecution['execution_percent'] ?? 0) < 80) {
            $warnings[] = __('messages.release_readiness.warnings.low_test_execution', ['percent' => $testExecution['execution_percent']]);
        }
        if (($riskAcceptanceSummary['summary']['without_expiry'] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.risk_acceptance_without_expiry', ['count' => $riskAcceptanceSummary['summary']['without_expiry']]);
        }
        if (($riskAcceptanceSummary['summary']['expiring_soon'] ?? 0) > 0) {
            $warnings[] = __('messages.release_readiness.warnings.risk_acceptance_expiring_soon', ['count' => $riskAcceptanceSummary['summary']['expiring_soon']]);
        }
        $nonBlockingBlindSpots = max(0, (int) ($blindSpotSummary['summary']['total'] ?? 0) - (int) ($blindSpotSummary['summary']['release_blockers'] ?? 0));
        if ($nonBlockingBlindSpots > 0) {
            $warnings[] = __('messages.release_readiness.warnings.blind_spots', ['count' => $nonBlockingBlindSpots]);
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
    private function recommendedActions(array $blockingIssues, array $warnings, array $regression, array $assertionCounts, int $coveragePercent, array $findingCounts, array $blindSpotSummary, array $riskAcceptanceSummary): array
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
        if (($findingCounts['fixed_unverified'] ?? 0) > 0 || ($findingCounts['ready_for_retest'] ?? 0) > 0 || ($findingCounts['retest_failed'] ?? 0) > 0) {
            $actions[] = __('messages.release_readiness.actions.verify_findings');
        }
        if (($blindSpotSummary['summary']['total'] ?? 0) > 0) {
            $actions[] = __('messages.release_readiness.actions.close_blind_spots');
        }
        if (($riskAcceptanceSummary['summary']['expired'] ?? 0) > 0 || ($riskAcceptanceSummary['summary']['without_expiry'] ?? 0) > 0 || ($riskAcceptanceSummary['summary']['expiring_soon'] ?? 0) > 0) {
            $actions[] = __('messages.release_readiness.actions.review_risk_acceptances');
        }
        if ($warnings !== []) {
            $actions[] = __('messages.release_readiness.actions.review_warnings');
        }
        $actions[] = __('messages.release_readiness.actions.save_clean_baseline');

        return array_values(array_unique($actions));
    }



    /** @param array<int, string> $lines @param array<string, mixed> $blindSpots */
    private function appendBlindSpotMarkdownSummary(array &$lines, array $blindSpots): void
    {
        $counts = $blindSpots['summary'] ?? [];
        $lines[] = '## '.$this->md(__('messages.blind_spots.report_summary_title'));
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total blind spots | '.($counts['total'] ?? 0).' |';
        $lines[] = '| Critical blind spots | '.($counts['critical'] ?? 0).' |';
        $lines[] = '| High blind spots | '.($counts['high'] ?? 0).' |';
        $lines[] = '| Stale evidence | '.($counts['stale_evidence'] ?? 0).' |';
        $lines[] = '| Untested endpoints | '.($counts['untested_endpoints'] ?? 0).' |';
        $lines[] = '| Missing assertions | '.($counts['missing_assertions'] ?? 0).' |';
        $lines[] = '| Missing auth comparisons | '.($counts['missing_auth_comparisons'] ?? 0).' |';
        $lines[] = '| Unverified fixes | '.($counts['unverified_fixes'] ?? 0).' |';
        $lines[] = '| Risk expiry issues | '.(($counts['risk_without_expiry'] ?? 0) + ($counts['expired_accepted_risks'] ?? 0)).' |';
        $lines[] = '| Missing recent reports | '.($counts['missing_recent_reports'] ?? 0).' |';
        $lines[] = '| Release blockers | '.($counts['release_blockers'] ?? 0).' |';
        $lines[] = '';

        $items = $blindSpots['top_items'] ?? $blindSpots['items'] ?? collect();
        if (is_array($items)) {
            $items = collect($items);
        }

        if ($items instanceof Collection && $items->isNotEmpty()) {
            $lines[] = '| Severity | Module | Type | Affected item | Release blocker | Suggested action |';
            $lines[] = '|---|---|---|---|---|---|';
            foreach ($items->take(10) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $lines[] = '| '
                    .$this->md((string) ($item['severity_label'] ?? $item['severity'] ?? 'n/a')).' | '
                    .$this->md((string) ($item['module_label'] ?? $item['module'] ?? 'n/a')).' | '
                    .$this->md((string) ($item['type_label'] ?? $item['category_label'] ?? $item['category'] ?? 'n/a')).' | '
                    .$this->md((string) ($item['related_label'] ?? 'n/a')).' | '
                    .(($item['release_blocker'] ?? false) ? 'Yes' : 'No').' | '
                    .$this->md((string) ($item['suggested_action'] ?? 'n/a')).' |';
            }
            $lines[] = '';
        }
    }



    /** @param array<int, string> $lines @param array<string, mixed> $riskAcceptances */
    private function appendRiskAcceptanceMarkdownSummary(array &$lines, array $riskAcceptances): void
    {
        $counts = $riskAcceptances['summary'] ?? [];
        $lines[] = '## Risk Acceptance Ledger Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total accepted risks | '.($counts['total'] ?? 0).' |';
        $lines[] = '| Active accepted risks | '.($counts['active'] ?? 0).' |';
        $lines[] = '| High or critical active accepted risks | '.($counts['active_high_or_critical'] ?? 0).' |';
        $lines[] = '| Without expiry | '.($counts['without_expiry'] ?? 0).' |';
        $lines[] = '| Expiring soon | '.($counts['expiring_soon'] ?? 0).' |';
        $lines[] = '| Expired | '.($counts['expired'] ?? 0).' |';
        $lines[] = '';

        $items = $riskAcceptances['active_items'] ?? collect();
        if ($items instanceof Collection && $items->isNotEmpty()) {
            $lines[] = '| Finding | Severity | Accepted by | Accepted until | Scope | Expiry action | Reason |';
            $lines[] = '|---|---|---|---|---|---|---|';
            foreach ($items->take(10) as $acceptance) {
                $lines[] = '| '.$this->md((string) $acceptance->finding?->title).' | '.$this->md((string) $acceptance->finding?->severity_label).' | '.$this->md((string) ($acceptance->acceptedBy?->name ?: 'n/a')).' | '.$this->md((string) ($acceptance->accepted_until?->format('Y-m-d') ?: 'n/a')).' | '.$this->md((string) ($acceptance->release_scope ?: 'n/a')).' | '.$this->md((string) $acceptance->expiry_action_label).' | '.$this->md((string) $acceptance->reason).' |';
            }
            $lines[] = '';
        }
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

    private function mdBrandingLine(string $line): string
    {
        if (! str_contains($line, ':** ')) {
            return $this->md($line);
        }

        [$label, $value] = explode(':** ', $line, 2);

        return $label.':** '.$this->md($value);
    }

    private function md(?string $value): string
    {
        return str_replace('|', '\\|', (string) ($value ?? ''));
    }
}
