<?php

namespace App\Services\Reports;

use App\Models\CompareItem;
use App\Models\CompareRun;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Models\SnapshotItem;
use App\Models\TestCase;
use App\Services\AssertionEvaluationService;
use App\Services\Exports\ExportCreditService;
use App\Services\Auth\AuthProfileRuntimeService;
use App\Services\QaCoverageMatrixService;
use App\Services\RegressionEvaluationService;
use App\Services\Risk\RiskAnalyzer;
use App\Services\Settings\SettingService;
use App\Services\Settings\SettingsRuntimeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportExportService
{
    public function __construct(
        private readonly RiskAnalyzer $riskAnalyzer,
        private readonly AssertionEvaluationService $assertions,
        private readonly RegressionEvaluationService $regressions,
        private readonly AuthProfileRuntimeService $authRuntime,
        private readonly QaCoverageMatrixService $coverageMatrix,
        private readonly SettingService $settings,
        private readonly SettingsRuntimeService $runtime,
        private readonly ExportCreditService $credits
    ) {
    }

    public function fullProjectMarkdown(Project $project): string
    {
        $project->load([
            'environments.authProfile',
            'authProfiles',
            'endpoints.environment.authProfile',
            'endpoints.authProfile',
            'endpoints.latestScanResult',
            'testSuites.testCases.endpoint',
            'testCases.testSuite',
            'testCases.endpoint',
            'testCases.latestResult.scanResult.scanRun',
            'contractValidationRuns.results.endpoint',
            'contractValidationRuns.scanRun',
            'findings.endpoint',
            'findings.testCase',
            'findings.evidence.capturedBy',
            'qaReleaseGates',
        ]);
        $project->loadCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'testSuites', 'testCases', 'contractValidationRuns', 'findings', 'qaReleaseGates']);

        $latestScan = $project->scanRuns()
            ->with(['environment', 'results.endpoint'])
            ->latest()
            ->first();

        $latestSnapshot = $project->snapshots()
            ->with('environment')
            ->latest()
            ->first();

        $latestCompare = $project->compareRuns()
            ->with(['snapshotA', 'snapshotB', 'items'])
            ->latest()
            ->first();

        $latestContractValidation = $project->contractValidationRuns()
            ->with(['scanRun', 'results.endpoint'])
            ->latest()
            ->first();

        $latestReleaseGate = $project->qaReleaseGates()
            ->latest()
            ->first();

        $latestSensitiveResults = $latestScan
            ? $latestScan->results->where('sensitive_data_detected', true)->count()
            : 0;
        $latestBrokenAuthResults = $latestScan
            ? $latestScan->results->where('broken_auth_detected', true)->count()
            : 0;
        $latestSchemaDriftResults = $latestScan
            ? $latestScan->results->where('schema_drift_detected', true)->count()
            : 0;

        $coverageMatrix = $this->coverageMatrix->summarize($project);
        $coverageSummary = $coverageMatrix['summary'];

        $riskSummary = collect(Endpoint::RISKS)->mapWithKeys(fn (string $risk): array => [$risk => 0]);
        $assertionSummary = [
            AssertionEvaluationService::STATUS_PASS => 0,
            AssertionEvaluationService::STATUS_WARNING => 0,
            AssertionEvaluationService::STATUS_FAIL => 0,
            AssertionEvaluationService::STATUS_NOT_CONFIGURED => 0,
        ];
        $failedEndpoints = [];
        $warningEndpoints = [];

        foreach ($project->endpoints->sortBy([['method', 'asc'], ['path', 'asc']]) as $endpoint) {
            $latest = $endpoint->latestScanResult;
            $analysis = $this->riskAnalyzer->analyze($endpoint, $latest);
            $assertion = $this->assertions->evaluate($endpoint, $latest);
            $riskSummary[$analysis['final_level']] = ((int) ($riskSummary[$analysis['final_level']] ?? 0)) + 1;
            $assertionSummary[$assertion['status']] = ((int) ($assertionSummary[$assertion['status']] ?? 0)) + 1;

            if ($assertion['status'] === AssertionEvaluationService::STATUS_FAIL) {
                $failedEndpoints[] = [$endpoint, $latest, $assertion, $analysis];
            } elseif ($assertion['status'] === AssertionEvaluationService::STATUS_WARNING) {
                $warningEndpoints[] = [$endpoint, $latest, $assertion, $analysis];
            }
        }

        $regression = $latestCompare ? $this->regressions->evaluateCompare($latestCompare) : null;

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
        $testExecutionCoverage = $project->test_cases_count > 0
            ? (int) round(($testExecutedCount / $project->test_cases_count) * 100)
            : 0;
        $testPassRate = $testExecutedCount > 0
            ? (int) round(($testRunCounts[TestCase::RUN_PASS] / $testExecutedCount) * 100)
            : 0;

        $latestTestResults = $project->testCases()
            ->with(['latestResult.scanResult.scanRun'])
            ->whereHas('latestResult')
            ->get()
            ->sortByDesc(fn (TestCase $testCase): string => (string) $testCase->latestResult?->executed_at)
            ->take(20);

        $failedOrBlockedTestCases = $project->testCases()
            ->with(['testSuite', 'endpoint'])
            ->whereIn('last_run_status', [TestCase::RUN_FAIL, TestCase::RUN_BLOCKED])
            ->orderBy('last_run_status')
            ->orderBy('priority')
            ->orderBy('title')
            ->get();

        $openFindings = $project->findings
            ->filter(fn (Finding $finding): bool => in_array($finding->status, Finding::OPEN_STATUSES, true))
            ->sortBy([['severity', 'asc'], ['detected_at', 'desc']]);
        $criticalFindings = $openFindings->where('severity', Finding::SEVERITY_CRITICAL)->count();
        $highFindings = $openFindings->where('severity', Finding::SEVERITY_HIGH)->count();

        $lines = [];
        $lines[] = '# Aptoria Full Project QA Report';
        $lines[] = '';
        $lines[] = '**Project:** '.$project->name;
        $lines[] = '**Base URL:** '.$this->md($project->display_base_url);
        foreach ($this->credits->projectBrandingMarkdownLines($project) as $brandingLine) {
            $lines[] = $this->mdBrandingLine($brandingLine);
        }
        if (($disclaimer = $this->credits->projectDisclaimerMarkdown($project)) !== '') {
            $lines[] = '**Disclaimer:** '.$this->md($disclaimer);
        }
        if ($this->settings->boolean('exports.include_timestamps', true)) {
            $lines[] = '**Generated:** '.$this->runtime->formatDate(now());
        }
        $lines[] = '**Report type:** '.$this->settings->string('report.default_type', 'technical');
        $lines[] = '**Aptoria version:** '.config('aptoria.version');
        $lines[] = '';
        $lines[] = '## Executive Summary';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Endpoints | '.$project->endpoints_count.' |';
        $lines[] = '| Environments | '.$project->environments->count().' |';
        $lines[] = '| Default environment | '.$this->md($project->defaultEnvironment()?->name ?: 'n/a').' |';
        $lines[] = '| Auth profiles | '.$project->authProfiles->count().' |';
        $lines[] = '| Scan runs | '.$project->scan_runs_count.' |';
        $lines[] = '| Snapshots | '.$project->snapshots_count.' |';
        $lines[] = '| Compare runs | '.$project->compare_runs_count.' |';
        $lines[] = '| Test suites | '.$project->test_suites_count.' |';
        $lines[] = '| Test cases | '.$project->test_cases_count.' |';
        $lines[] = '| Contract validations | '.$project->contract_validation_runs_count.' |';
        $lines[] = '| Release gates | '.$project->qa_release_gates_count.' |';
        $lines[] = '| Latest release gate | '.$this->md($latestReleaseGate ? '#'.$latestReleaseGate->id.' '.$latestReleaseGate->final_decision_label : 'n/a').' |';
        $lines[] = '| Latest scan sensitive data results | '.$latestSensitiveResults.' |';
        $lines[] = '| Latest scan broken auth results | '.$latestBrokenAuthResults.' |';
        $lines[] = '| Latest scan schema drift results | '.$latestSchemaDriftResults.' |';
        $lines[] = '| Findings | '.$project->findings_count.' |';
        $lines[] = '| Open Findings | '.$openFindings->count().' |';
        $lines[] = '| Critical Open Findings | '.$criticalFindings.' |';
        $lines[] = '| High Open Findings | '.$highFindings.' |';
        $lines[] = '| Test execution coverage | '.$testExecutionCoverage.'% |';
        $lines[] = '| Test pass rate | '.$testPassRate.'% |';
        $lines[] = '| QA coverage | '.$coverageSummary['coverage_percent'].'% |';
        $lines[] = '| QA coverage blocked endpoints | '.$coverageSummary['blocked'].' |';
        $lines[] = '| Test Passed | '.$testRunCounts[TestCase::RUN_PASS].' |';
        $lines[] = '| Test Failed | '.$testRunCounts[TestCase::RUN_FAIL].' |';
        $lines[] = '| Test Blocked | '.$testRunCounts[TestCase::RUN_BLOCKED].' |';
        $lines[] = '| Test Not run | '.$testRunCounts[TestCase::RUN_NOT_RUN].' |';
        $lines[] = '| Assertion PASS | '.$assertionSummary[AssertionEvaluationService::STATUS_PASS].' |';
        $lines[] = '| Assertion WARNING | '.$assertionSummary[AssertionEvaluationService::STATUS_WARNING].' |';
        $lines[] = '| Assertion FAIL | '.$assertionSummary[AssertionEvaluationService::STATUS_FAIL].' |';
        $lines[] = '| Regression status | '.$this->md($regression['label'] ?? __('messages.regressions.statuses.none')).' |';
        if ($latestCompare) {
            $latestCompareSummary = $latestCompare->summary_json ?: [];
            $lines[] = '| Latest compare breaking changes | '.(int) ($latestCompareSummary['breaking_count'] ?? 0).' |';
            $lines[] = '| Latest compare schema changes | '.(int) ($latestCompareSummary['schema_count'] ?? 0).' |';
            $lines[] = '| Latest compare header changes | '.(int) ($latestCompareSummary['header_count'] ?? 0).' |';
            $lines[] = '| Latest compare body changes | '.(int) ($latestCompareSummary['body_count'] ?? 0).' |';
        }
        $lines[] = '| Latest contract validation | '.$this->md($latestContractValidation ? '#'.$latestContractValidation->id.' '.$latestContractValidation->health_label : 'n/a').' |';
        $lines[] = '';

        if ($project->environments->isNotEmpty()) {
            $lines[] = '## Environment Matrix';
            $lines[] = '';
            $lines[] = '| Environment | Type | Base URL | Auth profile | Production |';
            $lines[] = '|---|---|---|---|---|';
            foreach ($project->environments as $environment) {
                $lines[] = '| '.$this->md($environment->name).' | '.$this->md($environment->environment_type_label).' | '.$this->md($environment->display_base_url).' | '.$this->md($environment->authProfile?->name ?: __('messages.environments.use_project_default_auth')).' | '.($environment->is_production ? __('messages.common.yes') : __('messages.common.no')).' |';
            }
            $lines[] = '';
        }

        if ($latestScan) {
            $lines[] = '## Latest Scan';
            $lines[] = '';
            $lines[] = '| Field | Value |';
            $lines[] = '|---|---|';
            $lines[] = '| Environment | '.$this->md($latestScan->environment?->name ?: __('messages.endpoints.project_default')).' |';
            $lines[] = '| Status | '.$this->md($latestScan->status_label).' |';
            $lines[] = '| Started | '.$this->md($this->dateValue($latestScan->started_at)).' |';
            $lines[] = '| Finished | '.$this->md($this->dateValue($latestScan->finished_at)).' |';
            $lines[] = '| Duration | '.$this->md($latestScan->duration_label).' |';
            $lines[] = '| Scanned | '.$latestScan->scanned_count.' |';
            $lines[] = '| Success | '.$latestScan->success_count.' |';
            $lines[] = '| Warnings | '.$latestScan->warning_count.' |';
            $lines[] = '| Errors | '.$latestScan->error_count.' |';
            $lines[] = '';
        }

        $lines[] = '## QA Release Gate';
        $lines[] = '';
        if ($latestReleaseGate) {
            $lines[] = '| Metric | Value |';
            $lines[] = '|---|---:|';
            $lines[] = '| Release | '.$this->md($latestReleaseGate->release_name).' |';
            $lines[] = '| Automated status | '.$this->md($latestReleaseGate->automated_status_label).' |';
            $lines[] = '| Final decision | '.$this->md($latestReleaseGate->final_decision_label).' |';
            $lines[] = '| Score | '.$latestReleaseGate->score.' / 100 |';
            $lines[] = '| Blockers | '.$latestReleaseGate->blocker_count.' |';
            $lines[] = '| Warnings | '.$latestReleaseGate->warning_count.' |';
            $lines[] = '| Created | '.$this->dateValue($latestReleaseGate->created_at).' |';
        } else {
            $lines[] = 'No saved QA Release Gate snapshot yet.';
        }
        $lines[] = '';

        $lines[] = '## Risk Summary';
        $lines[] = '';
        $lines[] = '| Risk | Count |';
        $lines[] = '|---|---:|';
        foreach (Endpoint::RISKS as $risk) {
            $lines[] = '| '.$this->riskLabel($risk).' | '.($riskSummary[$risk] ?? 0).' |';
        }
        $lines[] = '';

        $lines[] = '## Assertion Summary';
        $lines[] = '';
        $lines[] = '| Status | Count |';
        $lines[] = '|---|---:|';
        foreach ([AssertionEvaluationService::STATUS_PASS, AssertionEvaluationService::STATUS_WARNING, AssertionEvaluationService::STATUS_FAIL, AssertionEvaluationService::STATUS_NOT_CONFIGURED] as $status) {
            $lines[] = '| '.__('messages.assertions.statuses.'.$status).' | '.($assertionSummary[$status] ?? 0).' |';
        }
        $lines[] = '';

        if ($latestSnapshot) {
            $lines[] = '## Latest Snapshot';
            $lines[] = '';
            $lines[] = '- **Name:** '.$latestSnapshot->name;
            $lines[] = '- **Environment:** '.($latestSnapshot->environment?->name ?: __('messages.endpoints.project_default'));
            $lines[] = '- **Endpoints:** '.$latestSnapshot->endpoint_count;
            $lines[] = '- **Created:** '.$this->dateValue($latestSnapshot->created_at);
            $lines[] = '';
        }

        if ($latestCompare) {
            $summary = $latestCompare->summary_json ?: [];
            $lines[] = '## Latest Snapshot Compare';
            $lines[] = '';
            $lines[] = '- **Baseline:** '.($latestCompare->snapshotA?->name ?: 'n/a');
            $lines[] = '- **Target:** '.($latestCompare->snapshotB?->name ?: 'n/a');
            $lines[] = '- **Total changes:** '.($summary['total_changes'] ?? 0);
            $lines[] = '- **Regression status:** '.($regression['label'] ?? __('messages.regressions.statuses.none'));
            $lines[] = '- **Detected regressions:** '.($regression['detected_count'] ?? 0);
            $lines[] = '- **Warnings:** '.($regression['warning_count'] ?? 0);
            $lines[] = '- **Recovered:** '.($regression['recovered_count'] ?? 0);
            $lines[] = '- **Improved:** '.($regression['improved_count'] ?? 0);
            $lines[] = '';
        }

        $lines[] = '## OpenAPI Contract Validation';
        $lines[] = '';
        if ($latestContractValidation) {
            $lines[] = '| Metric | Count |';
            $lines[] = '|---|---:|';
            $lines[] = '| Total checks | '.$latestContractValidation->total_checks.' |';
            $lines[] = '| Passed | '.$latestContractValidation->passed_count.' |';
            $lines[] = '| Warnings | '.$latestContractValidation->warning_count.' |';
            $lines[] = '| Failed | '.$latestContractValidation->failed_count.' |';
            $lines[] = '| Breaking | '.$latestContractValidation->breaking_count.' |';
            $lines[] = '| Missing endpoints | '.$latestContractValidation->missing_endpoint_count.' |';
            $lines[] = '| Undocumented endpoints | '.$latestContractValidation->undocumented_endpoint_count.' |';
            $lines[] = '';
            $problemResults = $latestContractValidation->results->filter(fn ($result): bool => in_array($result->status, ['fail', 'warning'], true))->take(20);
            if ($problemResults->isEmpty()) {
                $lines[] = 'No failed or warning contract validation checks were found.';
            } else {
                $lines[] = '| Status | Severity | Check | Endpoint | Message | Expected | Actual |';
                $lines[] = '|---|---|---|---|---|---|---|';
                foreach ($problemResults as $result) {
                    $endpointLabel = trim(($result->method ?: '').' '.($result->path ?: ''));
                    $lines[] = '| '.$this->md($result->status_label).' | '.$this->md($result->severity_label).' | '.$this->md($result->check_type_label).' | '.$this->md($endpointLabel ?: 'n/a').' | '.$this->md($result->message).' | '.$this->md($result->expected ?: 'n/a').' | '.$this->md($result->actual ?: 'n/a').' |';
                }
            }
        } else {
            $lines[] = 'No OpenAPI contract validation has been recorded yet.';
        }
        $lines[] = '';

        $lines[] = '## Findings & Evidence';
        $lines[] = '';
        if ($openFindings->isEmpty()) {
            $lines[] = 'No open findings were recorded.';
        } else {
            $lines[] = '| Severity | Status | Source | Finding | Endpoint | Test Case | Evidence |';
            $lines[] = '|---|---|---|---|---|---|---:|';
            foreach ($openFindings->take(50) as $finding) {
                $endpoint = $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : 'n/a';
                $testCase = $finding->testCase?->title ?: 'n/a';
                $lines[] = '| '.$this->md($finding->severity_label).' | '.$this->md($finding->status_label).' | '.$this->md($finding->source_label).' | '.$this->md($finding->title).' | '.$this->md($endpoint).' | '.$this->md($testCase).' | '.$finding->evidence->count().' |';
            }

            $evidenceRows = $openFindings
                ->flatMap(fn (Finding $finding) => $finding->evidence->map(fn ($evidence) => [$finding, $evidence]))
                ->take(25);
            if ($evidenceRows->isNotEmpty()) {
                $lines[] = '';
                $lines[] = '### Finding Evidence Index';
                $lines[] = '';
                $lines[] = '| Finding | Type | Captured | Attachment | Summary |';
                $lines[] = '|---|---|---|---|---|';
                foreach ($evidenceRows as [$finding, $evidence]) {
                    $captured = $evidence->captured_at ? $evidence->captured_at->format('Y-m-d H:i') : 'n/a';
                    $attachment = $evidence->has_attachment ? $evidence->attachment_original_name.' ('.$evidence->attachment_size_label.')' : 'n/a';
                    $lines[] = '| '.$this->md($finding->title).' | '.$this->md($evidence->type_label).' | '.$this->md($captured).' | '.$this->md($attachment).' | '.$this->md($evidence->summary).' |';
                }
            }
        }
        $lines[] = '';

        $lines[] = '## Test Execution Summary';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total test cases | '.$project->test_cases_count.' |';
        $lines[] = '| Executed test cases | '.$testExecutedCount.' |';
        $lines[] = '| Execution coverage | '.$testExecutionCoverage.'% |';
        $lines[] = '| Pass rate | '.$testPassRate.'% |';
        $lines[] = '| Passed | '.$testRunCounts[TestCase::RUN_PASS].' |';
        $lines[] = '| Failed | '.$testRunCounts[TestCase::RUN_FAIL].' |';
        $lines[] = '| Blocked | '.$testRunCounts[TestCase::RUN_BLOCKED].' |';
        $lines[] = '| Skipped | '.$testRunCounts[TestCase::RUN_SKIPPED].' |';
        $lines[] = '| Not run | '.$testRunCounts[TestCase::RUN_NOT_RUN].' |';
        $lines[] = '';

        $lines[] = '## Test Suites';
        $lines[] = '';
        if ($project->testSuites->isEmpty()) {
            $lines[] = 'No test suites have been created yet.';
        } else {
            $lines[] = '| Suite | Status | Test cases | Description |';
            $lines[] = '|---|---|---:|---|';
            foreach ($project->testSuites->sortBy('name') as $suite) {
                $lines[] = '| '.$this->md($suite->name).' | '.$this->md($suite->status_label).' | '.$suite->testCases->count().' | '.$this->md($suite->description ?: 'n/a').' |';
            }
        }
        $lines[] = '';

        $lines[] = '## Test Cases';
        $lines[] = '';
        if ($project->testCases->isEmpty()) {
            $lines[] = 'No test cases have been created yet.';
        } else {
            $lines[] = '| Suite | Test Case | Endpoint | Type | Priority | Status | Last Result | Last Run |';
            $lines[] = '|---|---|---|---|---|---|---|---|';
            foreach ($project->testCases->sortBy([['test_suite_id', 'asc'], ['priority', 'asc'], ['title', 'asc']]) as $testCase) {
                $endpointLabel = $testCase->endpoint ? $testCase->endpoint->method.' '.$testCase->endpoint->path : __('messages.common.none');
                $lines[] = '| '.$this->md($testCase->testSuite?->name ?: 'n/a').' | '.$this->md($testCase->title).' | '.$this->md($endpointLabel).' | '.$this->md($testCase->type_label).' | '.$this->md($testCase->priority_label).' | '.$this->md($testCase->status_label).' | '.$this->md($testCase->last_run_status_label).' | '.$this->md($this->dateValue($testCase->last_run_at)).' |';
            }
        }
        $lines[] = '';

        $lines[] = '## Latest Test Results';
        $lines[] = '';
        if ($latestTestResults->isEmpty()) {
            $lines[] = 'No test case results have been recorded yet.';
        } else {
            $lines[] = '| Executed | Test Case | Result | Actual Result | Notes | Evidence |';
            $lines[] = '|---|---|---|---|---|---|';
            foreach ($latestTestResults as $testCase) {
                $result = $testCase->latestResult;
                $evidence = $result?->scanResult ? 'Scan result #'.$result->scanResult->id : 'n/a';
                $lines[] = '| '.$this->md($this->dateValue($result?->executed_at)).' | '.$this->md($testCase->title).' | '.$this->md($result?->status_label ?: 'n/a').' | '.$this->md($result?->actual_result ?: 'n/a').' | '.$this->md($result?->notes ?: 'n/a').' | '.$this->md($evidence).' |';
            }
        }
        $lines[] = '';

        $lines[] = '## Failed / Blocked Test Cases';
        $lines[] = '';
        if ($failedOrBlockedTestCases->isEmpty()) {
            $lines[] = 'No failed or blocked test cases were found.';
        } else {
            foreach ($failedOrBlockedTestCases as $testCase) {
                $lines[] = '- **'.$this->md($testCase->title).'** — '.$this->md($testCase->last_run_status_label).' — '.$this->md($testCase->actual_result ?: 'No actual result recorded.');
            }
        }
        $lines[] = '';


        $lines[] = '## QA Coverage Matrix';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Endpoint count | '.$coverageSummary['endpoint_count'].' |';
        $lines[] = '| Full coverage | '.$coverageSummary['coverage_percent'].'% |';
        $lines[] = '| Fully covered endpoints | '.$coverageSummary['fully_covered'].' |';
        $lines[] = '| Needs attention | '.$coverageSummary['warning'].' |';
        $lines[] = '| Blocked endpoints | '.$coverageSummary['blocked'].' |';
        $lines[] = '| Missing test cases | '.$coverageSummary['missing_tests'].' |';
        $lines[] = '| Missing assertions | '.$coverageSummary['missing_assertions'].' |';
        $lines[] = '| Not scanned | '.$coverageSummary['not_scanned'].' |';
        $lines[] = '| Missing contract result | '.$coverageSummary['missing_contract'].' |';
        $lines[] = '| Open findings | '.$coverageSummary['open_findings'].' |';
        $lines[] = '';
        $coverageProblemRows = $coverageMatrix['all_rows']
            ->filter(fn (array $row): bool => $row['status'] !== QaCoverageMatrixService::STATUS_COVERED)
            ->sortBy([['status', 'asc'], ['score', 'asc']])
            ->take(25);
        if ($coverageProblemRows->isEmpty()) {
            $lines[] = 'No QA coverage gaps were detected.';
        } else {
            $lines[] = '| Status | Score | Endpoint | Tests | Assertions | Scan | Contract | Findings | Gaps |';
            $lines[] = '|---|---:|---|---:|---:|---|---|---:|---|';
            foreach ($coverageProblemRows as $row) {
                $endpoint = $row['endpoint']->method.' '.$row['endpoint']->path;
                $scan = $row['has_scan'] ? ($row['latest_scan']?->status_label ?: 'scan evidence') : 'n/a';
                $contract = $row['has_contract'] ? 'pass '.$row['contract_counts']['pass'].' / warn '.$row['contract_counts']['warning'].' / fail '.$row['contract_counts']['fail'] : 'n/a';
                $gaps = implode('; ', array_merge($row['blockers'], $row['warnings']));
                $lines[] = '| '.$this->md($row['status_label']).' | '.$row['score'].' | '.$this->md($endpoint).' | '.$row['test_cases_count'].' | '.$row['assertion_rules_count'].' | '.$this->md($scan).' | '.$this->md($contract).' | '.$row['open_findings_count'].' | '.$this->md($gaps ?: 'n/a').' |';
            }
        }
        $lines[] = '';

        if ($this->settings->boolean('exports.include_endpoint_details', true)) {
            $lines[] = '## Endpoint Inventory';
            $lines[] = '';
            $lines[] = '| Method | Endpoint | Auth | HTTP | Time | Risk | Assertion | Failed rules | Warning rules |';
            $lines[] = '|---|---|---|---:|---:|---|---|---|---|';
            foreach ($project->endpoints->sortBy([['method', 'asc'], ['path', 'asc']]) as $endpoint) {
                $latest = $endpoint->latestScanResult;
                $analysis = $this->riskAnalyzer->analyze($endpoint, $latest);
                $assertion = $this->assertions->evaluate($endpoint, $latest);
                $effectiveAuthProfile = $this->authRuntime->resolveForEndpoint($endpoint);
                $lines[] = '| '.$this->md($endpoint->method).' | '.$this->md($endpoint->path).' | '.$this->md($this->authRuntime->maskedSummary($effectiveAuthProfile)).' | '.$this->md($latest?->status_code ?: 'n/a').' | '.$this->md($latest?->response_time_ms !== null ? $latest->response_time_ms.' ms' : 'n/a').' | '.$this->md($analysis['final_label']).' | '.$this->md($assertion['label']).' | '.$this->md(implode('; ', array_column($assertion['failed_rules'], 'rule_label'))).' | '.$this->md(implode('; ', array_column($assertion['warning_rules'], 'rule_label'))).' |';
            }
            $lines[] = '';
        }

        $lines[] = '## Failed Endpoints';
        $lines[] = '';
        if ($failedEndpoints === []) {
            $lines[] = 'No failed assertion endpoints were found in the latest evidence.';
        } else {
            foreach ($failedEndpoints as [$endpoint, $latest, $assertion, $analysis]) {
                $lines[] = '- **'.$endpoint->method.' '.$endpoint->path.'** — '.$this->md(implode('; ', array_column($assertion['failed_rules'], 'message')));
            }
        }
        $lines[] = '';

        $lines[] = '## Warning Endpoints';
        $lines[] = '';
        if ($warningEndpoints === []) {
            $lines[] = 'No assertion warning endpoints were found in the latest evidence.';
        } else {
            foreach ($warningEndpoints as [$endpoint, $latest, $assertion, $analysis]) {
                $lines[] = '- **'.$endpoint->method.' '.$endpoint->path.'** — '.$this->md(implode('; ', array_column($assertion['warning_rules'], 'message')));
            }
        }
        $lines[] = '';

        $lines[] = '## Recommendations';
        $lines[] = '';
        if ($assertionSummary[AssertionEvaluationService::STATUS_FAIL] > 0) {
            $lines[] = '- Fix failed assertion rules before accepting the API state as release-ready.';
        }
        if (($regression['detected_count'] ?? 0) > 0) {
            $lines[] = '- Investigate detected regressions against the expected API contract.';
        }
        if (($regression['recovered_count'] ?? 0) > 0) {
            $lines[] = '- Review recovered endpoints and consider saving a fresh baseline snapshot.';
        }
        if ($testRunCounts[TestCase::RUN_FAIL] > 0 || $testRunCounts[TestCase::RUN_BLOCKED] > 0) {
            $lines[] = '- Resolve failed or blocked test cases before accepting release readiness.';
        }
        if (($latestContractValidation?->breaking_count ?? 0) > 0 || ($latestContractValidation?->failed_count ?? 0) > 0) {
            $lines[] = '- Fix OpenAPI contract validation failures before accepting release readiness.';
        }
        if ($criticalFindings > 0 || $highFindings > 0) {
            $lines[] = '- Resolve critical/high open findings or document an accepted-risk decision before release.';
        }
        $lines[] = '- Keep one clean baseline snapshot before releases and compare it with post-release scans.';
        $lines[] = '- Secrets and authentication material are masked in UI and exports by design.';
        $lines[] = '- This report is generated from stored GET/HEAD safe probe evidence and does not execute new requests.';

        $lines[] = '';
        $this->credits->appendMarkdownFooter($lines, 'full_project_qa_report', $project);

        return implode("\n", $lines)."\n";
    }

    public function endpointInventoryCsv(Project $project): string
    {
        $project->load(['endpoints.environment.authProfile', 'endpoints.authProfile', 'endpoints.latestScanResult']);

        $rows = [[
            'method',
            'path',
            'full_url',
            'environment',
            'auth_profile',
            'auth_required',
            'effective_auth_profile',
            'effective_auth_summary',
            'risk_level',
            'expected_status',
            'expected_content_type',
            'last_status',
            'last_response_time_ms',
            'last_content_type',
            'assertion_status',
            'regression_status',
            'failed_rules',
            'warning_rules',
            'active',
            'excluded_from_scan',
            'tags',
            'qa_notes',
            'generated_by',
            'aptoria_version',
            'repository',
            'author',
        ]];

        foreach ($project->endpoints->sortBy([['method', 'asc'], ['path', 'asc']]) as $endpoint) {
            $latest = $endpoint->latestScanResult;
            $assertion = $this->assertions->evaluate($endpoint, $latest);
            $regression = $this->regressions->latestForEndpoint($endpoint);

            if ($this->settings->boolean('report.include_failed_endpoints_only', false) && ! $this->isProblemEndpoint($latest, $assertion, $regression)) {
                continue;
            }

            $effectiveAuthProfile = $this->authRuntime->resolveForEndpoint($endpoint);
            $rows[] = [
                $endpoint->method,
                $endpoint->path,
                $this->authRuntime->maskValue($endpoint->full_url),
                $endpoint->environment?->name ?: __('messages.endpoints.project_default'),
                $endpoint->authProfile?->name ?: __('messages.common.none'),
                $this->yesNo($endpoint->auth_required),
                $effectiveAuthProfile?->name ?: __('messages.common.none'),
                $this->authRuntime->maskedSummary($effectiveAuthProfile),
                $endpoint->risk_level,
                $endpoint->expected_status,
                $endpoint->expected_content_type,
                $latest?->status_code,
                $latest?->response_time_ms,
                $latest?->content_type,
                $assertion['status'],
                $regression['status'],
                implode('; ', array_column($assertion['failed_rules'], 'rule_label')),
                implode('; ', array_column($assertion['warning_rules'], 'rule_label')),
                $this->yesNo($endpoint->is_active),
                $this->yesNo($endpoint->excluded_from_scan),
                implode(', ', $endpoint->tag_list),
                $endpoint->qa_notes,
                config('aptoria.product_name', 'Aptoria'),
                config('aptoria.version'),
                config('aptoria.repository_url'),
                config('aptoria.author'),
            ];
        }

        return $this->csv($rows);
    }

    public function scanMarkdown(ScanRun $scanRun): string
    {
        $scanRun->loadMissing(['project', 'environment', 'creator', 'results.endpoint', 'results.authProfile']);

        $analyses = $scanRun->results->mapWithKeys(fn (ScanResult $result): array => [
            $result->id => $this->riskAnalyzer->analyze($result->endpoint, $result),
        ]);
        $assertionEvaluations = $scanRun->results
            ->filter(fn (ScanResult $result): bool => $result->endpoint !== null)
            ->mapWithKeys(fn (ScanResult $result): array => [$result->id => $this->assertions->evaluate($result->endpoint, $result)]);
        $regressionEvaluations = $scanRun->results
            ->filter(fn (ScanResult $result): bool => $result->endpoint !== null)
            ->mapWithKeys(fn (ScanResult $result): array => [$result->id => $this->regressions->latestForEndpoint($result->endpoint)]);

        $riskSummary = collect(Endpoint::RISKS)->mapWithKeys(fn (string $risk): array => [$risk => 0]);
        foreach ($analyses as $analysis) {
            $riskSummary[$analysis['final_level']] = ((int) $riskSummary[$analysis['final_level']]) + 1;
        }

        $lines = [];
        $lines[] = '# Aptoria Scan Report';
        $lines[] = '';
        $lines[] = '**Project:** '.$scanRun->project->name;
        foreach ($this->credits->projectBrandingMarkdownLines($scanRun->project) as $brandingLine) {
            $lines[] = $this->mdBrandingLine($brandingLine);
        }
        if (($disclaimer = $this->credits->projectDisclaimerMarkdown($scanRun->project)) !== '') {
            $lines[] = '**Disclaimer:** '.$this->md($disclaimer);
        }
        $lines[] = '**Environment:** '.($scanRun->environment?->name ?: __('messages.endpoints.project_default'));
        $lines[] = '**Status:** '.$scanRun->status;
        $lines[] = '**Started:** '.$this->dateValue($scanRun->started_at);
        $lines[] = '**Finished:** '.$this->dateValue($scanRun->finished_at);
        $lines[] = '**Duration:** '.$scanRun->duration_label;
        if ($this->settings->boolean('exports.include_timestamps', true)) {
            $lines[] = '**Generated:** '.$this->runtime->formatDate(now());
        }
        $lines[] = '**Report type:** '.$this->settings->string('report.default_type', 'technical');
        $lines[] = '';
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total endpoints | '.$scanRun->total_endpoints.' |';
        $lines[] = '| Scanned | '.$scanRun->scanned_count.' |';
        $lines[] = '| Skipped | '.$scanRun->skipped_count.' |';
        $lines[] = '| Success | '.$scanRun->success_count.' |';
        $lines[] = '| Warnings | '.$scanRun->warning_count.' |';
        $lines[] = '| Errors | '.$scanRun->error_count.' |';
        $lines[] = '';
        $lines[] = '## Risk Summary';
        $lines[] = '';
        $lines[] = '| Risk | Count |';
        $lines[] = '|---|---:|';
        foreach (Endpoint::RISKS as $risk) {
            $lines[] = '| '.$this->riskLabel($risk).' | '.($riskSummary[$risk] ?? 0).' |';
        }
        $lines[] = '';
        $lines[] = '## Results';
        $lines[] = '';
        $lines[] = '| Method | Endpoint | Auth | Result | HTTP | Time | Content-Type | Risk | Signals | Assertion | Regression | Failed rules | Warning rules |';
        $lines[] = '|---|---|---|---|---:|---:|---|---|---:|---|---|---|---|';

        foreach ($scanRun->results->sortBy([['status', 'asc'], ['method', 'asc'], ['url', 'asc']]) as $result) {
            $analysis = $analyses[$result->id];
            $assertion = $assertionEvaluations[$result->id] ?? null;
            $regression = $regressionEvaluations[$result->id] ?? null;

            if ($this->settings->boolean('report.include_failed_endpoints_only', false) && ! $this->isProblemEndpoint($result, $assertion, $regression)) {
                continue;
            }

            $lines[] = '| '.$this->md($result->method).' | '.$this->md($result->endpoint?->path ?: $result->url).' | '.$this->md($result->auth_summary ?: __('messages.common.none')).' | '.$this->md($result->status).' | '.$this->md($result->status_code ?: 'n/a').' | '.$this->md($result->response_time_ms !== null ? $result->response_time_ms.' ms' : 'n/a').' | '.$this->md($result->content_type ?: 'n/a').' | '.$this->md($analysis['final_label']).' | '.count($analysis['signals']).' | '.$this->md($assertion['label'] ?? __('messages.assertions.statuses.not_configured')).' | '.$this->md($regression['label'] ?? __('messages.regressions.statuses.none')).' | '.$this->md($assertion ? implode('; ', array_column($assertion['failed_rules'], 'rule_label')) : '').' | '.$this->md($assertion ? implode('; ', array_column($assertion['warning_rules'], 'rule_label')) : '').' |';
        }

        $lines[] = '';
        $lines[] = '## QA Notes';
        $lines[] = '';
        $lines[] = '- This report is generated from non-destructive GET/HEAD safe probes.';
        $lines[] = '- POST, PUT, PATCH and DELETE endpoints are not executed automatically.';
        $lines[] = '- Risk levels are QA review signals, not exploit confirmations.';
        $lines[] = '';
        $this->credits->appendMarkdownFooter($lines, 'scan_report', $scanRun->project);

        return implode("\n", $lines)."\n";
    }

    public function snapshotJson(Snapshot $snapshot): string
    {
        $snapshot->loadMissing(['project', 'environment', 'scanRun', 'items.endpoint']);

        $payload = [
            'exported_by' => 'Aptoria',
            'exported_at' => now()->toIso8601String(),
            'version' => config('aptoria.version'),
            'generated_by' => $this->credits->metadata('snapshot_json', $snapshot->project),
            'snapshot' => [
                'id' => $snapshot->id,
                'name' => $snapshot->name,
                'description' => $snapshot->description,
                'hash' => $snapshot->snapshot_hash,
                'short_hash' => $snapshot->short_hash,
                'created_at' => $snapshot->created_at?->toIso8601String(),
                'endpoint_count' => $snapshot->endpoint_count,
                'summary' => $snapshot->summary_json ?: [],
            ],
            'project' => [
                'id' => $snapshot->project?->id,
                'name' => $snapshot->project?->name,
                'base_url' => $this->authRuntime->maskForExport($snapshot->project?->base_url),
            ],
            'environment' => [
                'id' => $snapshot->environment?->id,
                'name' => $snapshot->environment?->name,
                'base_url' => $this->authRuntime->maskForExport($snapshot->environment?->base_url),
                'is_production' => (bool) ($snapshot->environment?->is_production ?? false),
            ],
            'items' => $snapshot->items->sortBy([['method', 'asc'], ['path', 'asc']])->map(function (SnapshotItem $item): array {
                $assertion = $item->endpoint
                    ? $this->assertions->evaluate($item->endpoint, null, $item)
                    : null;

                return [
                    'method' => $item->method,
                    'path' => $item->path,
                    'auth_required' => (bool) $item->auth_required,
                    'risk_level' => $item->risk_level,
                    'status_code' => $item->status_code,
                    'content_type' => $item->content_type,
                    'response_time_ms' => $item->response_time_ms,
                    'expected_status' => $item->expected_status,
                    'expected_content_type' => $item->expected_content_type,
                    'assertion_status' => $assertion['status'] ?? AssertionEvaluationService::STATUS_NOT_CONFIGURED,
                    'failed_rules' => $assertion ? array_column($assertion['failed_rules'], 'rule_label') : [],
                    'warning_rules' => $assertion ? array_column($assertion['warning_rules'], 'rule_label') : [],
                    'source_hash' => $item->source_hash,
                    'metadata' => $item->metadata_json ?: [],
                ];
            })->values()->all(),
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    }

    public function compareMarkdown(CompareRun $compareRun): string
    {
        $compareRun->loadMissing(['project', 'snapshotA', 'snapshotB.items.endpoint', 'items']);
        $summary = $compareRun->summary_json ?: [];
        $regression = $this->regressions->evaluateCompare($compareRun);
        $targetItems = $compareRun->snapshotB?->items
            ->keyBy(fn (SnapshotItem $item): string => strtoupper($item->method).' '.strtolower($item->path))
            ?? collect();

        $lines = [];
        $lines[] = '# Aptoria Snapshot Compare Report';
        $lines[] = '';
        $lines[] = '**Project:** '.$compareRun->project->name;
        foreach ($this->credits->projectBrandingMarkdownLines($compareRun->project) as $brandingLine) {
            $lines[] = $this->mdBrandingLine($brandingLine);
        }
        if (($disclaimer = $this->credits->projectDisclaimerMarkdown($compareRun->project)) !== '') {
            $lines[] = '**Disclaimer:** '.$this->md($disclaimer);
        }
        $lines[] = '**Baseline snapshot:** '.($compareRun->snapshotA?->name ?: 'n/a');
        $lines[] = '**Target snapshot:** '.($compareRun->snapshotB?->name ?: 'n/a');
        $lines[] = '**Created:** '.$this->dateValue($compareRun->created_at);
        if ($this->settings->boolean('exports.include_timestamps', true)) {
            $lines[] = '**Generated:** '.$this->runtime->formatDate(now());
        }
        $lines[] = '**Report type:** '.$this->settings->string('report.default_type', 'technical');
        $lines[] = '';
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total changes | '.($summary['total_changes'] ?? 0).' |';
        $lines[] = '| New endpoints | '.($summary['new_count'] ?? 0).' |';
        $lines[] = '| Removed endpoints | '.($summary['removed_count'] ?? 0).' |';
        $lines[] = '| Changed endpoints/fields | '.($summary['changed_count'] ?? 0).' |';
        $lines[] = '| Critical changes | '.($summary['critical_count'] ?? 0).' |';
        $lines[] = '| High changes | '.($summary['high_count'] ?? 0).' |';
        $lines[] = '| Breaking changes | '.($summary['breaking_count'] ?? 0).' |';
        $lines[] = '| Status code changes | '.($summary['status_code_count'] ?? 0).' |';
        $lines[] = '| Header changes | '.($summary['header_count'] ?? 0).' |';
        $lines[] = '| Body changes | '.($summary['body_count'] ?? 0).' |';
        $lines[] = '| Schema changes | '.($summary['schema_count'] ?? 0).' |';
        $lines[] = '| Sensitive data changes | '.($summary['sensitive_data_count'] ?? 0).' |';
        $lines[] = '| Broken auth changes | '.($summary['broken_auth_count'] ?? 0).' |';
        $lines[] = '| Schema drift changes | '.($summary['schema_drift_count'] ?? 0).' |';
        $lines[] = '| Regression status | '.$regression['label'].' |';
        $lines[] = '| Regression detected endpoints | '.$regression['detected_count'].' |';
        $lines[] = '| Regression warning endpoints | '.$regression['warning_count'].' |';
        $lines[] = '| Recovered endpoints | '.$regression['recovered_count'].' |';
        $lines[] = '| Improved endpoints | '.$regression['improved_count'].' |';
        $lines[] = '';
        $lines[] = '## Detected Changes';
        $lines[] = '';

        if ($compareRun->items->isEmpty()) {
            $lines[] = 'No changes detected.';
            $lines[] = '';
            $this->credits->appendMarkdownFooter($lines, 'snapshot_compare_report', $compareRun->project);
            return implode("\n", $lines)."\n";
        }

        $lines[] = '| Type | Severity | Group | Breaking | Method | Endpoint | Field | Old | New | Assertion | Regression | Failed rules | Warning rules |';
        $lines[] = '|---|---|---|---|---|---|---|---|---|---|---|---|---|';
        foreach ($compareRun->items->sortBy([['severity', 'asc'], ['change_type', 'asc'], ['method', 'asc'], ['path', 'asc']]) as $item) {
            $key = strtoupper($item->method).' '.strtolower($item->path);
            $targetItem = $targetItems->get($key);
            $assertion = $targetItem?->endpoint ? $this->assertions->evaluate($targetItem->endpoint, null, $targetItem) : null;
            $endpointRegression = $regression['endpoints'][$key] ?? null;
            $lines[] = '| '.$this->md($item->change_label).' | '.$this->md($item->severity_label).' | '.$this->md($item->diff_group_label).' | '.$this->md($item->breaking_change ? __('messages.common.yes') : __('messages.common.no')).' | '.$this->md($item->method).' | '.$this->md($item->path).' | '.$this->md($item->field_changed ?: 'n/a').' | '.$this->md($item->old_value ?: 'n/a').' | '.$this->md($item->new_value ?: 'n/a').' | '.$this->md($assertion['label'] ?? __('messages.assertions.statuses.not_configured')).' | '.$this->md($endpointRegression['label'] ?? __('messages.regressions.statuses.none')).' | '.$this->md($assertion ? implode('; ', array_column($assertion['failed_rules'], 'rule_label')) : '').' | '.$this->md($assertion ? implode('; ', array_column($assertion['warning_rules'], 'rule_label')) : '').' |';
        }

        $lines[] = '';
        $lines[] = '## QA Notes';
        $lines[] = '';
        $lines[] = '- New and removed endpoints should be reviewed against the expected API contract.';
        $lines[] = '- Risk/status/content-type changes should be reviewed before release acceptance.';
        $lines[] = '- Schema, header, body and security diff groups help separate breaking changes from informational drift.';
        $lines[] = '- This compare report is based on saved snapshots and does not execute requests.';
        $lines[] = '';
        $this->credits->appendMarkdownFooter($lines, 'snapshot_compare_report', $compareRun->project);

        return implode("\n", $lines)."\n";
    }

    public function filename(Project $project, string $suffix, string $extension): string
    {
        return Str::slug($project->name ?: 'aptoria').'-'.$suffix.'-'.now()->format('Ymd-His').'.'.$extension;
    }

    private function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'w+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }


    /** @param array<string, mixed>|null $assertion @param array<string, mixed>|null $regression */
    private function isProblemEndpoint(?ScanResult $result, ?array $assertion, ?array $regression): bool
    {
        if (($assertion['status'] ?? null) === AssertionEvaluationService::STATUS_FAIL || ($assertion['status'] ?? null) === AssertionEvaluationService::STATUS_WARNING) {
            return true;
        }

        if (($regression['status'] ?? null) !== null && ($regression['status'] ?? null) !== RegressionEvaluationService::STATUS_NONE) {
            return true;
        }

        if ($result?->status === ScanResult::STATUS_FAILED) {
            return true;
        }

        return ($result?->status_code ?? 0) >= 400;
    }

    private function yesNo(bool $value): string
    {
        return $value ? __('messages.common.yes') : __('messages.common.no');
    }

    private function dateValue(mixed $date): string
    {
        return $date ? $date->format('Y-m-d H:i:s') : 'n/a';
    }

    private function riskLabel(string $risk): string
    {
        return __('messages.endpoints.risks.'.$risk);
    }

    private function mdBrandingLine(string $line): string
    {
        if (! str_contains($line, ':** ')) {
            return $this->md($line);
        }

        [$label, $value] = explode(':** ', $line, 2);

        return $label.':** '.$this->md($value);
    }

    private function md(mixed $value): string
    {
        $text = trim($this->authRuntime->maskForExport((string) $value));
        $text = str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], $text);

        return $text === '' ? 'n/a' : $text;
    }
}
