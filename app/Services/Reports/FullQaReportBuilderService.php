<?php

namespace App\Services\Reports;

use App\Models\ContractValidationResult;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\TestCase;
use App\Services\AssertionEvaluationService;
use App\Services\Exports\ExportCreditService;
use App\Services\Auth\AuthProfileRuntimeService;
use App\Services\QaCoverageMatrixService;
use App\Services\ReleaseReadinessService;
use App\Services\Risk\RiskAnalyzer;
use Illuminate\Support\Collection;

class FullQaReportBuilderService
{
    public const SECTION_EXECUTIVE_SUMMARY = 'executive_summary';
    public const SECTION_RELEASE_READINESS = 'release_readiness';
    public const SECTION_RELEASE_GATE = 'release_gate';
    public const SECTION_QA_COVERAGE = 'qa_coverage';
    public const SECTION_TEST_EXECUTION = 'test_execution';
    public const SECTION_TEST_SUITES_CASES = 'test_suites_cases';
    public const SECTION_FINDINGS_EVIDENCE = 'findings_evidence';
    public const SECTION_CONTRACT_VALIDATION = 'contract_validation';
    public const SECTION_SCANS_SNAPSHOTS = 'scans_snapshots';
    public const SECTION_ENDPOINT_INVENTORY = 'endpoint_inventory';
    public const SECTION_RECOMMENDATIONS = 'recommendations';
    public const SECTION_APPENDIX = 'appendix';

    public const SECTIONS = [
        self::SECTION_EXECUTIVE_SUMMARY,
        self::SECTION_RELEASE_READINESS,
        self::SECTION_RELEASE_GATE,
        self::SECTION_QA_COVERAGE,
        self::SECTION_TEST_EXECUTION,
        self::SECTION_TEST_SUITES_CASES,
        self::SECTION_FINDINGS_EVIDENCE,
        self::SECTION_CONTRACT_VALIDATION,
        self::SECTION_SCANS_SNAPSHOTS,
        self::SECTION_ENDPOINT_INVENTORY,
        self::SECTION_RECOMMENDATIONS,
        self::SECTION_APPENDIX,
    ];

    public function __construct(
        private readonly ReleaseReadinessService $releaseReadiness,
        private readonly QaCoverageMatrixService $coverageMatrix,
        private readonly RiskAnalyzer $riskAnalyzer,
        private readonly AssertionEvaluationService $assertions,
        private readonly AuthProfileRuntimeService $authRuntime,
        private readonly ExportCreditService $credits,
    ) {
    }

    /** @return array<int, string> */
    public static function defaultSections(): array
    {
        return self::SECTIONS;
    }

    /** @return array<string, string> */
    public static function audienceOptions(): array
    {
        return [
            'internal' => __('messages.report_builder.audiences.internal'),
            'management' => __('messages.report_builder.audiences.management'),
            'client' => __('messages.report_builder.audiences.client'),
            'release' => __('messages.report_builder.audiences.release'),
        ];
    }

    /** @return array<string, string> */
    public static function decisionOptions(): array
    {
        return [
            'draft' => __('messages.report_builder.decisions.draft'),
            'ready' => __('messages.report_builder.decisions.ready'),
            'conditional' => __('messages.report_builder.decisions.conditional'),
            'blocked' => __('messages.report_builder.decisions.blocked'),
        ];
    }

    /** @param array<string, mixed> $options */
    public function markdown(Project $project, array $options = []): string
    {
        $project->loadMissing([
            'environments',
            'authProfiles',
            'endpoints.environment.authProfile',
            'endpoints.authProfile',
            'endpoints.latestScanResult',
            'endpoints.assertionRules',
            'endpoints.testCases.latestResult',
            'endpoints.findings.evidence',
            'testSuites.testCases.endpoint',
            'testCases.testSuite',
            'testCases.endpoint',
            'testCases.latestResult.scanResult.scanRun',
            'scanRuns.environment',
            'snapshots.environment',
            'compareRuns.snapshotA',
            'compareRuns.snapshotB',
            'contractValidationRuns.results.endpoint',
            'contractValidationRuns.scanRun.environment',
            'findings.endpoint',
            'findings.testCase',
            'findings.evidence',
            'qaReleaseGates',
        ]);
        $project->loadCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'testSuites', 'testCases', 'contractValidationRuns', 'findings', 'qaReleaseGates']);

        $sections = $this->selectedSections($options['sections'] ?? self::defaultSections());
        $summary = $this->releaseReadiness->summarize($project);
        $coverage = $this->coverageMatrix->summarize($project);
        $latestScan = $project->scanRuns()->with('environment')->latest()->first();
        $latestContract = $project->contractValidationRuns()->with(['scanRun.environment', 'results.endpoint'])->latest()->first();
        $latestSnapshot = $project->snapshots()->with('environment')->latest()->first();
        $latestCompare = $project->compareRuns()->with(['snapshotA', 'snapshotB'])->latest()->first();
        $latestReleaseGate = $project->qaReleaseGates()->latest()->first();

        $lines = [];
        $lines[] = '# '.$this->md((string) ($options['title'] ?? __('messages.report_builder.default_title')));
        $lines[] = '';
        $lines[] = '**Project:** '.$this->md($project->name);
        $lines[] = '**Base URL:** '.$this->md($project->display_base_url);
        $lines[] = '**Audience:** '.$this->md($this->audienceLabel((string) ($options['audience'] ?? 'internal')));
        $lines[] = '**Release decision:** '.$this->md($this->decisionLabel((string) ($options['decision'] ?? 'draft')));
        $lines[] = '**Generated:** '.now()->format('Y-m-d H:i:s');
        $lines[] = '**Aptoria version:** '.config('aptoria.version');
        $lines[] = '';

        $scopeNotes = trim((string) ($options['scope_notes'] ?? ''));
        if ($scopeNotes !== '') {
            $lines[] = '## Scope Notes';
            $lines[] = '';
            $lines[] = $this->paragraph($scopeNotes);
            $lines[] = '';
        }

        if (in_array(self::SECTION_EXECUTIVE_SUMMARY, $sections, true)) {
            $this->appendExecutiveSummary($lines, $project, $summary, $coverage['summary']);
        }

        if (in_array(self::SECTION_RELEASE_READINESS, $sections, true)) {
            $this->appendReleaseReadiness($lines, $summary);
        }

        if (in_array(self::SECTION_RELEASE_GATE, $sections, true)) {
            $this->appendReleaseGate($lines, $latestReleaseGate);
        }

        if (in_array(self::SECTION_QA_COVERAGE, $sections, true)) {
            $this->appendQaCoverage($lines, $coverage, (int) ($options['endpoint_limit'] ?? 50));
        }

        if (in_array(self::SECTION_TEST_EXECUTION, $sections, true)) {
            $this->appendTestExecution($lines, $project, $summary, (int) ($options['test_case_limit'] ?? 50));
        }

        if (in_array(self::SECTION_TEST_SUITES_CASES, $sections, true)) {
            $this->appendTestSuitesAndCases($lines, $project, (int) ($options['test_case_limit'] ?? 50));
        }

        if (in_array(self::SECTION_FINDINGS_EVIDENCE, $sections, true)) {
            $this->appendFindingsAndEvidence(
                $lines,
                $project,
                (int) ($options['finding_limit'] ?? 50),
                (bool) ($options['include_evidence_details'] ?? false)
            );
        }

        if (in_array(self::SECTION_CONTRACT_VALIDATION, $sections, true)) {
            $this->appendContractValidation($lines, $latestContract, (int) ($options['contract_result_limit'] ?? 50));
        }

        if (in_array(self::SECTION_SCANS_SNAPSHOTS, $sections, true)) {
            $this->appendScansSnapshots($lines, $project, $latestScan, $latestSnapshot, $latestCompare);
        }

        if (in_array(self::SECTION_ENDPOINT_INVENTORY, $sections, true)) {
            $this->appendEndpointInventory(
                $lines,
                $project,
                $coverage['all_rows'],
                (bool) ($options['problem_endpoints_only'] ?? false),
                (int) ($options['endpoint_limit'] ?? 50)
            );
        }

        if (in_array(self::SECTION_RECOMMENDATIONS, $sections, true)) {
            $this->appendRecommendations($lines, $summary, $coverage['summary']);
        }

        if (in_array(self::SECTION_APPENDIX, $sections, true)) {
            $this->appendAppendix($lines);
        }

        $lines[] = '';
        $this->credits->appendMarkdownFooter($lines, 'custom_qa_report', $project);

        return implode("\n", $lines)."\n";
    }

    /** @param array<int, string>|mixed $sections @return array<int, string> */
    private function selectedSections(mixed $sections): array
    {
        if (! is_array($sections) || $sections === []) {
            return self::defaultSections();
        }

        return array_values(array_intersect(self::SECTIONS, $sections));
    }

    /** @param array<int, string> $lines @param array<string, mixed> $summary @param array<string, mixed> $coverageSummary */
    private function appendExecutiveSummary(array &$lines, Project $project, array $summary, array $coverageSummary): void
    {
        $lines[] = '## Executive Summary';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Endpoints | '.$project->endpoints_count.' |';
        $lines[] = '| Test suites | '.$project->test_suites_count.' |';
        $lines[] = '| Test cases | '.$project->test_cases_count.' |';
        $lines[] = '| Scan runs | '.$project->scan_runs_count.' |';
        $lines[] = '| Contract validation runs | '.$project->contract_validation_runs_count.' |';
        $lines[] = '| Findings | '.$project->findings_count.' |';
        $lines[] = '| Open findings | '.$summary['finding_counts']['open'].' |';
        $lines[] = '| Release readiness | '.$this->md($summary['label']).' |';
        $lines[] = '| Release score | '.$summary['score'].' / 100 |';
        $lines[] = '| QA coverage | '.$coverageSummary['coverage_percent'].'% |';
        $lines[] = '| Blocked endpoints | '.$coverageSummary['blocked'].' |';
        $lines[] = '| Test execution coverage | '.$summary['test_execution']['execution_percent'].'% |';
        $lines[] = '| Test pass rate | '.$summary['test_execution']['pass_rate'].'% |';
        $lines[] = '';
    }

    /** @param array<int, string> $lines @param array<string, mixed> $summary */
    private function appendReleaseReadiness(array &$lines, array $summary): void
    {
        $lines[] = '## Release Readiness';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Status | '.$this->md($summary['label']).' |';
        $lines[] = '| Score | '.$summary['score'].' / 100 |';
        $lines[] = '| Grade | '.$this->md($summary['grade']).' |';
        $lines[] = '| Blocking issues | '.count($summary['blocking_issues']).' |';
        $lines[] = '| Warnings | '.count($summary['warnings']).' |';
        $lines[] = '';

        $this->appendIssueList($lines, 'Blocking Issues', $summary['blocking_issues']);
        $this->appendIssueList($lines, 'Warnings', $summary['warnings']);
    }


    /** @param array<int, string> $lines */
    private function appendReleaseGate(array &$lines, $latestReleaseGate): void
    {
        $lines[] = '## QA Release Gate';
        $lines[] = '';
        if (! $latestReleaseGate) {
            $lines[] = 'No saved QA Release Gate snapshot yet.';
            $lines[] = '';

            return;
        }

        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Release | '.$this->md($latestReleaseGate->release_name).' |';
        $lines[] = '| Automated status | '.$this->md($latestReleaseGate->automated_status_label).' |';
        $lines[] = '| Final decision | '.$this->md($latestReleaseGate->final_decision_label).' |';
        $lines[] = '| Score | '.$latestReleaseGate->score.' / 100 |';
        $lines[] = '| Blockers | '.$latestReleaseGate->blocker_count.' |';
        $lines[] = '| Warnings | '.$latestReleaseGate->warning_count.' |';
        $lines[] = '| QA coverage | '.$latestReleaseGate->qa_coverage_percent.'% |';
        $lines[] = '| Test execution | '.$latestReleaseGate->test_execution_percent.'% |';
        $lines[] = '| Created | '.$this->dateValue($latestReleaseGate->created_at).' |';
        $lines[] = '';
    }

    /** @param array<int, string> $lines @param array<string, mixed> $coverage */
    private function appendQaCoverage(array &$lines, array $coverage, int $limit): void
    {
        $summary = $coverage['summary'];
        $lines[] = '## QA Coverage Matrix Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Endpoint count | '.$summary['endpoint_count'].' |';
        $lines[] = '| Full coverage | '.$summary['coverage_percent'].'% |';
        $lines[] = '| Fully covered | '.$summary['fully_covered'].' |';
        $lines[] = '| Needs attention | '.$summary['warning'].' |';
        $lines[] = '| Blocked endpoints | '.$summary['blocked'].' |';
        $lines[] = '| Missing test cases | '.$summary['missing_tests'].' |';
        $lines[] = '| Missing assertions | '.$summary['missing_assertions'].' |';
        $lines[] = '| Not scanned | '.$summary['not_scanned'].' |';
        $lines[] = '| Missing contract result | '.$summary['missing_contract'].' |';
        $lines[] = '| Open findings | '.$summary['open_findings'].' |';
        $lines[] = '';

        $problemRows = $coverage['all_rows']
            ->filter(fn (array $row): bool => $row['status'] !== QaCoverageMatrixService::STATUS_COVERED)
            ->sortBy([['status', 'asc'], ['score', 'asc']])
            ->take($this->limit($limit));

        if ($problemRows->isEmpty()) {
            $lines[] = 'No QA coverage gaps were detected.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Status | Score | Endpoint | Tests | Assertions | Scan | Contract | Findings | Gaps |';
        $lines[] = '|---|---:|---|---:|---:|---|---|---:|---|';
        foreach ($problemRows as $row) {
            $endpoint = $row['endpoint']->method.' '.$row['endpoint']->path;
            $scan = $row['has_scan'] ? ($row['latest_scan']?->status_label ?: 'scan evidence') : 'n/a';
            $contract = $row['has_contract'] ? 'pass '.$row['contract_counts']['pass'].' / warn '.$row['contract_counts']['warning'].' / fail '.$row['contract_counts']['fail'] : 'n/a';
            $gaps = implode('; ', array_merge($row['blockers'], $row['warnings']));
            $lines[] = '| '.$this->md($row['status_label']).' | '.$row['score'].' | '.$this->md($endpoint).' | '.$row['test_cases_count'].' | '.$row['assertion_rules_count'].' | '.$this->md($scan).' | '.$this->md($contract).' | '.$row['open_findings_count'].' | '.$this->md($gaps ?: 'n/a').' |';
        }
        $lines[] = '';
    }

    /** @param array<int, string> $lines @param array<string, mixed> $summary */
    private function appendTestExecution(array &$lines, Project $project, array $summary, int $limit): void
    {
        $testExecution = $summary['test_execution'];
        $lines[] = '## Test Execution';
        $lines[] = '';
        $lines[] = '| Status | Count |';
        $lines[] = '|---|---:|';
        foreach ($testExecution['run_counts'] as $status => $count) {
            $lines[] = '| '.$this->md(__('messages.test_cases.run_statuses.'.$status)).' | '.$count.' |';
        }
        $lines[] = '| Executed | '.$testExecution['executed'].' |';
        $lines[] = '| Execution coverage | '.$testExecution['execution_percent'].'% |';
        $lines[] = '| Pass rate | '.$testExecution['pass_rate'].'% |';
        $lines[] = '';

        $problemCases = $project->testCases
            ->filter(fn (TestCase $testCase): bool => in_array($testCase->last_run_status, [TestCase::RUN_FAIL, TestCase::RUN_BLOCKED, TestCase::RUN_NOT_RUN], true))
            ->sortBy([['last_run_status', 'asc'], ['priority', 'asc'], ['title', 'asc']])
            ->take($this->limit($limit));

        if ($problemCases->isEmpty()) {
            $lines[] = 'No failed, blocked or not-run test cases were found.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Last Result | Priority | Test Case | Suite | Endpoint | Actual Result |';
        $lines[] = '|---|---|---|---|---|---|';
        foreach ($problemCases as $testCase) {
            $endpoint = $testCase->endpoint ? $testCase->endpoint->method.' '.$testCase->endpoint->path : 'n/a';
            $lines[] = '| '.$this->md($testCase->last_run_status_label).' | '.$this->md($testCase->priority_label).' | '.$this->md($testCase->title).' | '.$this->md($testCase->testSuite?->name ?: 'n/a').' | '.$this->md($endpoint).' | '.$this->md($testCase->actual_result ?: 'n/a').' |';
        }
        $lines[] = '';
    }

    /** @param array<int, string> $lines */
    private function appendTestSuitesAndCases(array &$lines, Project $project, int $limit): void
    {
        $lines[] = '## Test Suites & Test Cases';
        $lines[] = '';
        if ($project->testSuites->isEmpty()) {
            $lines[] = 'No test suites have been created yet.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Suite | Status | Test cases | Description |';
        $lines[] = '|---|---|---:|---|';
        foreach ($project->testSuites->sortBy('name') as $suite) {
            $lines[] = '| '.$this->md($suite->name).' | '.$this->md($suite->status_label).' | '.$suite->testCases->count().' | '.$this->md($suite->description ?: 'n/a').' |';
        }
        $lines[] = '';

        $cases = $project->testCases
            ->sortBy([['test_suite_id', 'asc'], ['priority', 'asc'], ['title', 'asc']])
            ->take($this->limit($limit));

        if ($cases->isEmpty()) {
            $lines[] = 'No test cases have been created yet.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Suite | Test Case | Endpoint | Type | Priority | Status | Last Result |';
        $lines[] = '|---|---|---|---|---|---|---|';
        foreach ($cases as $testCase) {
            $endpoint = $testCase->endpoint ? $testCase->endpoint->method.' '.$testCase->endpoint->path : 'n/a';
            $lines[] = '| '.$this->md($testCase->testSuite?->name ?: 'n/a').' | '.$this->md($testCase->title).' | '.$this->md($endpoint).' | '.$this->md($testCase->type_label).' | '.$this->md($testCase->priority_label).' | '.$this->md($testCase->status_label).' | '.$this->md($testCase->last_run_status_label).' |';
        }
        $lines[] = '';
    }

    /** @param array<int, string> $lines */
    private function appendFindingsAndEvidence(array &$lines, Project $project, int $limit, bool $includeEvidenceDetails): void
    {
        $openFindings = $project->findings
            ->filter(fn (Finding $finding): bool => in_array($finding->status, Finding::OPEN_STATUSES, true))
            ->sortBy([['severity', 'asc'], ['detected_at', 'desc']])
            ->take($this->limit($limit));

        $lines[] = '## Findings & Evidence';
        $lines[] = '';
        if ($openFindings->isEmpty()) {
            $lines[] = 'No open findings were recorded.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Severity | Status | Source | Finding | Endpoint | Test Case | Evidence |';
        $lines[] = '|---|---|---|---|---|---|---:|';
        foreach ($openFindings as $finding) {
            $endpoint = $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : 'n/a';
            $testCase = $finding->testCase?->title ?: 'n/a';
            $lines[] = '| '.$this->md($finding->severity_label).' | '.$this->md($finding->status_label).' | '.$this->md($finding->source_label).' | '.$this->md($finding->title).' | '.$this->md($endpoint).' | '.$this->md($testCase).' | '.$finding->evidence->count().' |';
        }
        $lines[] = '';

        if (! $includeEvidenceDetails) {
            return;
        }

        $lines[] = '### Evidence Details';
        $lines[] = '';
        foreach ($openFindings as $finding) {
            $lines[] = '#### '.$this->md($finding->title);
            $lines[] = '';
            $lines[] = '- **Expected:** '.$this->md($finding->expected_result ?: 'n/a');
            $lines[] = '- **Actual:** '.$this->md($finding->actual_result ?: 'n/a');
            $lines[] = '- **Recommendation:** '.$this->md($finding->recommendation ?: 'n/a');
            foreach ($finding->evidence as $evidence) {
                $lines[] = '- **'.$this->md($evidence->type_label).':** '.$this->md($evidence->source_label ?: 'Evidence').' — '.$this->md($evidence->content ?: $evidence->url ?: 'n/a');
            }
            $lines[] = '';
        }
    }

    /** @param array<int, string> $lines */
    private function appendContractValidation(array &$lines, mixed $latestContract, int $limit): void
    {
        $lines[] = '## OpenAPI Contract Validation';
        $lines[] = '';
        if (! $latestContract) {
            $lines[] = 'No OpenAPI contract validation has been recorded yet.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total checks | '.$latestContract->total_checks.' |';
        $lines[] = '| Passed | '.$latestContract->passed_count.' |';
        $lines[] = '| Warnings | '.$latestContract->warning_count.' |';
        $lines[] = '| Failed | '.$latestContract->failed_count.' |';
        $lines[] = '| Breaking | '.$latestContract->breaking_count.' |';
        $lines[] = '| Missing endpoints | '.$latestContract->missing_endpoint_count.' |';
        $lines[] = '| Undocumented endpoints | '.$latestContract->undocumented_endpoint_count.' |';
        $lines[] = '';

        $problems = $latestContract->results
            ->filter(fn (ContractValidationResult $result): bool => in_array($result->status, [ContractValidationResult::STATUS_FAIL, ContractValidationResult::STATUS_WARNING], true))
            ->take($this->limit($limit));

        if ($problems->isEmpty()) {
            $lines[] = 'No failed or warning contract validation checks were found.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Status | Severity | Check | Endpoint | Message | Expected | Actual |';
        $lines[] = '|---|---|---|---|---|---|---|';
        foreach ($problems as $result) {
            $endpoint = trim(($result->method ?: '').' '.($result->path ?: ''));
            $lines[] = '| '.$this->md($result->status_label).' | '.$this->md($result->severity_label).' | '.$this->md($result->check_type_label).' | '.$this->md($endpoint ?: 'n/a').' | '.$this->md($result->message).' | '.$this->md($result->expected ?: 'n/a').' | '.$this->md($result->actual ?: 'n/a').' |';
        }
        $lines[] = '';
    }

    /** @param array<int, string> $lines */
    private function appendScansSnapshots(array &$lines, Project $project, mixed $latestScan, mixed $latestSnapshot, mixed $latestCompare): void
    {
        $lines[] = '## Scan, Snapshot & Regression Evidence';
        $lines[] = '';
        $lines[] = '| Evidence | Value |';
        $lines[] = '|---|---|';
        $lines[] = '| Latest scan | '.$this->md($latestScan ? '#'.$latestScan->id.' '.$latestScan->status_label.' — '.($latestScan->environment?->name ?: __('messages.endpoints.project_default')) : 'n/a').' |';
        $lines[] = '| Latest snapshot | '.$this->md($latestSnapshot ? '#'.$latestSnapshot->id.' '.$latestSnapshot->name.' — '.$latestSnapshot->endpoint_count.' endpoints' : 'n/a').' |';
        $lines[] = '| Latest compare | '.$this->md($latestCompare ? '#'.$latestCompare->id.' — '.(($latestCompare->summary_json['total_changes'] ?? 0).' changes') : 'n/a').' |';
        $lines[] = '| Total scan runs | '.$project->scan_runs_count.' |';
        $lines[] = '| Total snapshots | '.$project->snapshots_count.' |';
        $lines[] = '| Total compare runs | '.$project->compare_runs_count.' |';
        $lines[] = '';
    }

    /** @param array<int, string> $lines @param Collection<int, array<string, mixed>> $coverageRows */
    private function appendEndpointInventory(array &$lines, Project $project, Collection $coverageRows, bool $problemOnly, int $limit): void
    {
        $rows = $coverageRows;
        if ($problemOnly) {
            $rows = $rows->filter(fn (array $row): bool => $row['status'] !== QaCoverageMatrixService::STATUS_COVERED);
        }

        $rows = $rows->sortBy([['status', 'asc'], ['score', 'asc']])->take($this->limit($limit));
        $lines[] = '## Endpoint Inventory';
        $lines[] = '';
        if ($rows->isEmpty()) {
            $lines[] = 'No endpoint rows match the selected report options.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Method | Endpoint | Auth | Risk | HTTP | Assertion | Coverage | Score | Findings |';
        $lines[] = '|---|---|---|---|---:|---|---|---:|---:|';
        foreach ($rows as $row) {
            $endpoint = $row['endpoint'];
            $latest = $endpoint->latestScanResult;
            $analysis = $this->riskAnalyzer->analyze($endpoint, $latest);
            $assertion = $this->assertions->evaluate($endpoint, $latest);
            $auth = $this->authRuntime->maskedSummary($this->authRuntime->resolveForEndpoint($endpoint));
            $lines[] = '| '.$this->md($endpoint->method).' | '.$this->md($endpoint->path).' | '.$this->md($auth).' | '.$this->md($analysis['final_label']).' | '.$this->md($latest?->status_code ?: 'n/a').' | '.$this->md($assertion['label']).' | '.$this->md($row['status_label']).' | '.$row['score'].' | '.$row['open_findings_count'].' |';
        }
        $lines[] = '';
    }

    /** @param array<int, string> $lines @param array<string, mixed> $summary @param array<string, mixed> $coverageSummary */
    private function appendRecommendations(array &$lines, array $summary, array $coverageSummary): void
    {
        $lines[] = '## Recommendations';
        $lines[] = '';
        if (count($summary['blocking_issues']) > 0) {
            $lines[] = '- Resolve all blocking issues before release approval.';
        }
        if (($summary['finding_counts']['critical_open'] ?? 0) > 0 || ($summary['finding_counts']['high_open'] ?? 0) > 0) {
            $lines[] = '- Close or explicitly accept critical/high findings with documented evidence.';
        }
        if (($summary['test_execution']['run_counts'][TestCase::RUN_FAIL] ?? 0) > 0 || ($summary['test_execution']['run_counts'][TestCase::RUN_BLOCKED] ?? 0) > 0) {
            $lines[] = '- Re-run failed or blocked test cases after fixes and attach fresh evidence.';
        }
        if (($coverageSummary['missing_tests'] ?? 0) > 0 || ($coverageSummary['missing_assertions'] ?? 0) > 0) {
            $lines[] = '- Close endpoint coverage gaps by adding linked test cases and enabled assertions.';
        }
        if (($coverageSummary['missing_contract'] ?? 0) > 0) {
            $lines[] = '- Run OpenAPI contract validation against the latest API documentation.';
        }
        if (count($summary['warnings']) > 0) {
            $lines[] = '- Review warning items and document accepted-risk decisions where needed.';
        }
        if (count($summary['recommended_actions']) > 0) {
            foreach ($summary['recommended_actions'] as $action) {
                $lines[] = '- '.$this->md($action);
            }
        }
        $lines[] = '- Keep one clean baseline snapshot before releases and compare it after deployment.';
        $lines[] = '';
    }

    /** @param array<int, string> $lines */
    private function appendAppendix(array &$lines): void
    {
        $lines[] = '## Appendix';
        $lines[] = '';
        $lines[] = '- This report is generated from stored Aptoria evidence and does not execute new HTTP requests.';
        $lines[] = '- Safe scan evidence is based on non-destructive GET/HEAD probing.';
        $lines[] = '- Secrets and authentication material are masked in UI and exports by design.';
        $lines[] = '- Risk levels are QA review signals, not exploit confirmations.';
        $lines[] = '';
    }

    /** @param array<int, string> $lines @param array<int, string> $items */
    private function appendIssueList(array &$lines, string $title, array $items): void
    {
        $lines[] = '### '.$title;
        $lines[] = '';
        if ($items === []) {
            $lines[] = '- None.';
        } else {
            foreach ($items as $item) {
                $lines[] = '- '.$this->md($item);
            }
        }
        $lines[] = '';
    }

    private function audienceLabel(string $audience): string
    {
        return self::audienceOptions()[$audience] ?? self::audienceOptions()['internal'];
    }

    private function decisionLabel(string $decision): string
    {
        return self::decisionOptions()[$decision] ?? self::decisionOptions()['draft'];
    }

    private function limit(int $value): int
    {
        return min(500, max(5, $value));
    }

    private function paragraph(string $value): string
    {
        return str_replace(["\r\n", "\r"], "\n", trim($value));
    }

    private function dateValue(mixed $date): string
    {
        if ($date instanceof \Carbon\CarbonInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        return $date ? (string) $date : 'n/a';
    }

    private function md(mixed $value): string
    {
        $text = trim($this->authRuntime->maskForExport((string) $value));
        $text = str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], $text);

        return $text === '' ? 'n/a' : $text;
    }
}
