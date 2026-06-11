<?php

namespace App\Services\Reports;

use App\Models\ContractValidationResult;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Models\ScanResult;
use App\Models\TestCase;
use App\Services\AssertionEvaluationService;
use App\Services\Behavior\ApiBehaviorMapService;
use App\Services\Contracts\ContractRealityService;
use App\Services\Evidence\EvidenceGraphService;
use App\Services\Exports\ExportCreditService;
use App\Services\Auth\AuthProfileRuntimeService;
use App\Services\QaCoverageMatrixService;
use App\Services\ReleaseReadinessService;
use App\Services\Risk\RiskAnalyzer;
use Illuminate\Support\Collection;

class FullQaReportBuilderService
{
    public const REPORT_PROFILE_EXECUTIVE = 'executive';
    public const REPORT_PROFILE_TECHNICAL = 'technical';

    public const REPORT_PROFILES = [
        self::REPORT_PROFILE_EXECUTIVE,
        self::REPORT_PROFILE_TECHNICAL,
    ];

    public const SECTION_EXECUTIVE_SUMMARY = 'executive_summary';
    public const SECTION_RELEASE_READINESS = 'release_readiness';
    public const SECTION_BLIND_SPOTS = 'blind_spots';
    public const SECTION_RELEASE_GATE = 'release_gate';
    public const SECTION_QA_COVERAGE = 'qa_coverage';
    public const SECTION_TEST_EXECUTION = 'test_execution';
    public const SECTION_TEST_SUITES_CASES = 'test_suites_cases';
    public const SECTION_FINDINGS_EVIDENCE = 'findings_evidence';
    public const SECTION_CONTRACT_VALIDATION = 'contract_validation';
    public const SECTION_CONTRACT_REALITY = 'contract_reality';
    public const SECTION_SCANS_SNAPSHOTS = 'scans_snapshots';
    public const SECTION_ENDPOINT_INVENTORY = 'endpoint_inventory';
    public const SECTION_API_BEHAVIOR = 'api_behavior';
    public const SECTION_EVIDENCE_GRAPH = 'evidence_graph';
    public const SECTION_RECOMMENDATIONS = 'recommendations';
    public const SECTION_APPENDIX = 'appendix';

    public const SECTIONS = [
        self::SECTION_EXECUTIVE_SUMMARY,
        self::SECTION_RELEASE_READINESS,
        self::SECTION_BLIND_SPOTS,
        self::SECTION_RELEASE_GATE,
        self::SECTION_QA_COVERAGE,
        self::SECTION_TEST_EXECUTION,
        self::SECTION_TEST_SUITES_CASES,
        self::SECTION_FINDINGS_EVIDENCE,
        self::SECTION_CONTRACT_VALIDATION,
        self::SECTION_CONTRACT_REALITY,
        self::SECTION_SCANS_SNAPSHOTS,
        self::SECTION_ENDPOINT_INVENTORY,
        self::SECTION_API_BEHAVIOR,
        self::SECTION_EVIDENCE_GRAPH,
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

    /** @return array<string, string> */
    public static function reportProfileOptions(): array
    {
        return [
            self::REPORT_PROFILE_EXECUTIVE => __('messages.report_builder.profiles.executive'),
            self::REPORT_PROFILE_TECHNICAL => __('messages.report_builder.profiles.technical'),
        ];
    }

    /** @return array<string, mixed> */
    public static function profileOptions(string $profile): array
    {
        return match ($profile) {
            self::REPORT_PROFILE_EXECUTIVE => [
                'title' => __('messages.report_builder.profile_titles.executive'),
                'audience' => 'management',
                'decision' => 'conditional',
                'scope_notes' => __('messages.report_builder.profile_scope_notes.executive'),
                'sections' => [
                    self::SECTION_EXECUTIVE_SUMMARY,
                    self::SECTION_RELEASE_READINESS,
                    self::SECTION_BLIND_SPOTS,
                    self::SECTION_RELEASE_GATE,
                    self::SECTION_CONTRACT_REALITY,
                    self::SECTION_EVIDENCE_GRAPH,
                    self::SECTION_RECOMMENDATIONS,
                    self::SECTION_APPENDIX,
                ],
                'problem_endpoints_only' => true,
                'include_evidence_details' => false,
                'include_technical_details' => false,
                'endpoint_limit' => 25,
                'test_case_limit' => 25,
                'finding_limit' => 15,
                'contract_result_limit' => 25,
            ],
            self::REPORT_PROFILE_TECHNICAL => [
                'title' => __('messages.report_builder.profile_titles.technical'),
                'audience' => 'internal',
                'decision' => 'draft',
                'scope_notes' => __('messages.report_builder.profile_scope_notes.technical'),
                'sections' => [
                    self::SECTION_RELEASE_READINESS,
                    self::SECTION_BLIND_SPOTS,
                    self::SECTION_QA_COVERAGE,
                    self::SECTION_TEST_EXECUTION,
                    self::SECTION_TEST_SUITES_CASES,
                    self::SECTION_FINDINGS_EVIDENCE,
                    self::SECTION_CONTRACT_VALIDATION,
                    self::SECTION_CONTRACT_REALITY,
                    self::SECTION_SCANS_SNAPSHOTS,
                    self::SECTION_ENDPOINT_INVENTORY,
                    self::SECTION_API_BEHAVIOR,
                    self::SECTION_EVIDENCE_GRAPH,
                    self::SECTION_RECOMMENDATIONS,
                    self::SECTION_APPENDIX,
                ],
                'problem_endpoints_only' => false,
                'include_evidence_details' => true,
                'include_technical_details' => true,
                'endpoint_limit' => 250,
                'test_case_limit' => 250,
                'finding_limit' => 250,
                'contract_result_limit' => 250,
            ],
            default => self::profileOptions(self::REPORT_PROFILE_TECHNICAL),
        };
    }

    /** @param array<string, mixed> $options */
    public function markdown(Project $project, array $options = []): string
    {
        $project->loadMissing([
            'environments.authProfile',
            'authProfiles',
            'endpoints.environment.authProfile',
            'endpoints.authProfile',
            'endpoints.latestScanResult',
            'endpoints.assertionRules',
            'endpoints.testCases.latestResult',
            'endpoints.findings.evidence.capturedBy',
            'endpoints.producedBehaviorLinks.consumerEndpoint',
            'endpoints.consumedBehaviorLinks.producerEndpoint',
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
            'findings.evidence.capturedBy',
            'findings.lifecycleChangedBy',
            'findings.owner',
            'findings.verifiedBy',
            'findings.linkedReleaseGate',
            'findings.comments.user',
            'qaReleaseGates',
            'latestReleaseDecision.owner',
            'latestApprovedReportVersion.approvedBy',
        ]);
        $project->loadCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'testSuites', 'testCases', 'contractValidationRuns', 'findings', 'qaReleaseGates', 'releaseDecisions']);

        $sections = $this->selectedSections($options['sections'] ?? self::defaultSections());
        $summary = $this->releaseReadiness->summarize($project);
        $coverage = $this->coverageMatrix->summarize($project);
        $latestScan = $project->scanRuns()->with('environment')->latest()->first();
        $latestContract = $project->contractValidationRuns()->with(['scanRun.environment', 'results.endpoint'])->latest()->first();
        $latestSnapshot = $project->snapshots()->with('environment')->latest()->first();
        $latestCompare = $project->compareRuns()->with(['snapshotA', 'snapshotB'])->latest()->first();
        $latestReleaseGate = $project->qaReleaseGates()->latest()->first();
        $latestReleaseDecision = $project->latestReleaseDecision;
        $latestApprovedReport = $project->latestApprovedReportVersion;
        $behaviorMap = app(ApiBehaviorMapService::class)->summarize($project);
        $contractReality = app(ContractRealityService::class)->summarize($project, $latestContract);
        $evidenceGraph = app(EvidenceGraphService::class)->summarize($project);

        $lines = [];
        $lines[] = '# '.$this->md((string) ($options['title'] ?? __('messages.report_builder.default_title')));
        $lines[] = '';
        $lines[] = '**Project:** '.$this->md($project->name);
        $lines[] = '**Base URL:** '.$this->md($project->display_base_url);
        foreach ($this->credits->projectBrandingMarkdownLines($project) as $brandingLine) {
            $lines[] = $this->mdLine($brandingLine);
        }
        if (($disclaimer = $this->credits->projectDisclaimerMarkdown($project)) !== '') {
            $lines[] = '**Disclaimer:** '.$this->md($disclaimer);
        }
        $lines[] = '**Audience:** '.$this->md($this->audienceLabel((string) ($options['audience'] ?? 'internal')));
        $lines[] = '**Release decision:** '.$this->md($this->decisionLabel((string) ($options['decision'] ?? 'draft')));
        $lines[] = '**Generated:** '.now()->format('Y-m-d H:i:s');
        $lines[] = '**Aptoria version:** '.config('aptoria.version');
        if ($latestApprovedReport) {
            $lines[] = '**Latest approved report:** #'.$latestApprovedReport->id.' '.$this->md($latestApprovedReport->title).' · checksum `'.$latestApprovedReport->short_checksum.'`';
        }
        $lines[] = '';

        $scopeNotes = trim((string) ($options['scope_notes'] ?? ''));
        if ($scopeNotes !== '') {
            $lines[] = '## Scope Notes';
            $lines[] = '';
            $lines[] = $this->paragraph($scopeNotes);
            $lines[] = '';
        }

        if (in_array(self::SECTION_EXECUTIVE_SUMMARY, $sections, true)) {
            $this->appendExecutiveSummary($lines, $project, $summary, $coverage['summary'], $latestReleaseDecision);
        }

        if (in_array(self::SECTION_RELEASE_READINESS, $sections, true)) {
            $this->appendReleaseReadiness($lines, $summary);
            $this->appendReleaseDecision($lines, $latestReleaseDecision);
        }

        if (in_array(self::SECTION_BLIND_SPOTS, $sections, true)) {
            $this->appendBlindSpotSummary($lines, $summary['blind_spots'], (bool) ($options['include_technical_details'] ?? false), (int) ($options['finding_limit'] ?? 50));
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

        if (in_array(self::SECTION_CONTRACT_REALITY, $sections, true)) {
            $this->appendContractReality($lines, $contractReality, (int) ($options['contract_result_limit'] ?? 50));
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

        if (in_array(self::SECTION_API_BEHAVIOR, $sections, true)) {
            $this->appendApiBehaviorMap($lines, $behaviorMap, (int) ($options['endpoint_limit'] ?? 50));
        }

        if (in_array(self::SECTION_EVIDENCE_GRAPH, $sections, true)) {
            $this->appendEvidenceGraphSummary($lines, $evidenceGraph, (int) ($options['endpoint_limit'] ?? 50), (int) ($options['finding_limit'] ?? 50));
        }

        if ((bool) ($options['include_technical_details'] ?? false)) {
            $this->appendTechnicalRequestResponseEvidence($lines, $project, (int) ($options['endpoint_limit'] ?? 50));
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
    private function appendExecutiveSummary(array &$lines, Project $project, array $summary, array $coverageSummary, ?ReleaseDecision $latestReleaseDecision = null): void
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
        $lines[] = '| Reopened findings | '.($summary['finding_counts']['reopened'] ?? 0).' |';
        $lines[] = '| Fixed findings | '.($summary['finding_counts']['fixed'] ?? 0).' |';
        $lines[] = '| Fixed but unverified | '.($summary['finding_counts']['fixed_unverified'] ?? 0).' |';
        $lines[] = '| Ready for retest | '.($summary['finding_counts']['ready_for_retest'] ?? 0).' |';
        $lines[] = '| Retest failed | '.($summary['finding_counts']['retest_failed'] ?? 0).' |';
        $lines[] = '| Verified findings | '.($summary['finding_counts']['verified'] ?? 0).' |';
        $lines[] = '| Overdue findings | '.($summary['finding_counts']['overdue'] ?? 0).' |';
        $lines[] = '| False positives | '.($summary['finding_counts']['false_positive'] ?? 0).' |';
        $lines[] = '| Accepted risks | '.($summary['finding_counts']['accepted_risk'] ?? 0).' |';
        $lines[] = '| Active risk acceptances | '.($summary['risk_acceptances']['summary']['active'] ?? 0).' |';
        $lines[] = '| Expired risk acceptances | '.($summary['risk_acceptances']['summary']['expired'] ?? 0).' |';
        $lines[] = '| Accepted risks without expiry | '.($summary['risk_acceptances']['summary']['without_expiry'] ?? 0).' |';
        $lines[] = '| Release readiness | '.$this->md($summary['label']).' |';
        $lines[] = '| Release score | '.$summary['score'].' / 100 |';
        $lines[] = '| Latest release decision | '.$this->md($latestReleaseDecision?->status_label ?: 'n/a').' |';
        $lines[] = '| Release decision packages | '.$project->release_decisions_count.' |';
        $lines[] = '| Blind spots | '.($summary['blind_spots']['summary']['total'] ?? 0).' |';
        $lines[] = '| Release-blocking blind spots | '.($summary['blind_spots']['summary']['release_blockers'] ?? 0).' |';
        $lines[] = '| QA coverage | '.$coverageSummary['coverage_percent'].'% |';
        $lines[] = '| Blocked endpoints | '.$coverageSummary['blocked'].' |';
        $lines[] = '| Test execution coverage | '.$summary['test_execution']['execution_percent'].'% |';
        $lines[] = '| Test pass rate | '.$summary['test_execution']['pass_rate'].'% |';
        $lines[] = '';
    }

    /** @param array<int, string> $lines */
    private function appendEnvironmentMatrix(array &$lines, Project $project): void
    {
        if ($project->environments->isEmpty()) {
            return;
        }

        $lines[] = '## Environment Matrix';
        $lines[] = '';
        $lines[] = '| Environment | Type | Base URL | Auth profile | Production |';
        $lines[] = '|---|---|---|---|---|';
        foreach ($project->environments as $environment) {
            $lines[] = '| '.$this->md($environment->name).' | '.$this->md($environment->environment_type_label).' | '.$this->md($environment->display_base_url).' | '.$this->md($environment->authProfile?->name ?: __('messages.environments.use_project_default_auth')).' | '.($environment->is_production ? __('messages.common.yes') : __('messages.common.no')).' |';
        }
        $lines[] = '';
    }

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
        $lines[] = '| Blind spots | '.($summary['blind_spots']['summary']['total'] ?? 0).' |';
        $lines[] = '| Release-blocking blind spots | '.($summary['blind_spots']['summary']['release_blockers'] ?? 0).' |';
        $lines[] = '';

        $this->appendFindingLifecycleStatusSummary($lines, $summary['finding_counts']);
        $this->appendFindingVerificationSummary($lines, $summary['finding_counts']);
        $this->appendRiskAcceptanceSummary($lines, $summary['risk_acceptances'] ?? []);

        $this->appendIssueList($lines, 'Blocking Issues', $summary['blocking_issues']);
        $this->appendIssueList($lines, 'Warnings', $summary['warnings']);
    }



    /** @param array<int, string> $lines @param array<string, mixed> $blindSpots */
    private function appendBlindSpotSummary(array &$lines, array $blindSpots, bool $includeDetails, int $limit): void
    {
        $counts = $blindSpots['summary'] ?? [];
        $lines[] = '## Blind Spot Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total blind spots | '.($counts['total'] ?? 0).' |';
        $lines[] = '| Critical | '.($counts['critical'] ?? 0).' |';
        $lines[] = '| High | '.($counts['high'] ?? 0).' |';
        $lines[] = '| Medium | '.($counts['medium'] ?? 0).' |';
        $lines[] = '| Release blockers | '.($counts['release_blockers'] ?? 0).' |';
        $lines[] = '| Untested endpoints | '.($counts['untested_endpoints'] ?? 0).' |';
        $lines[] = '| Missing assertions | '.($counts['missing_assertions'] ?? 0).' |';
        $lines[] = '| Missing auth comparisons | '.($counts['missing_auth_comparisons'] ?? 0).' |';
        $lines[] = '| Unverified fixes | '.($counts['unverified_fixes'] ?? 0).' |';
        $lines[] = '| Risk expiry issues | '.(($counts['risk_without_expiry'] ?? 0) + ($counts['expired_accepted_risks'] ?? 0)).' |';
        $lines[] = '| Stale evidence | '.($counts['stale_evidence'] ?? 0).' |';
        $lines[] = '| Missing recent reports | '.($counts['missing_recent_reports'] ?? 0).' |';
        $lines[] = '';

        $items = $blindSpots['items'] ?? collect();
        if (! $items instanceof Collection || $items->isEmpty()) {
            $lines[] = 'No QA blind spots were detected.';
            $lines[] = '';
            return;
        }

        $rows = $includeDetails ? $items->take($this->limit($limit)) : $items->take(5);
        $lines[] = '| Severity | Type | Module | Related | Release blocker | Reason | Suggested action |';
        $lines[] = '|---|---|---|---|---|---|---|';
        foreach ($rows as $item) {
            $lines[] = '| '.$this->md((string) $item['severity_label']).' | '.$this->md((string) $item['type_label']).' | '.$this->md((string) $item['module_label']).' | '.$this->md((string) $item['related_label']).' | '.((bool) $item['release_blocker'] ? 'yes' : 'no').' | '.$this->md((string) $item['reason']).' | '.$this->md((string) $item['suggested_action']).' |';
        }
        $lines[] = '';
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

    /** @param array<int, string> $lines */
    private function appendReleaseDecision(array &$lines, ?ReleaseDecision $latestReleaseDecision): void
    {
        $lines[] = '## Release Decision';
        $lines[] = '';
        if (! $latestReleaseDecision) {
            $lines[] = 'No finalized release decision package has been saved yet.';
            $lines[] = '';

            return;
        }

        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---|';
        $lines[] = '| Decision | '.$this->md($latestReleaseDecision->status_label).' |';
        $lines[] = '| Release | '.$this->md($latestReleaseDecision->release_name ?: 'n/a').' |';
        $lines[] = '| Owner | '.$this->md($latestReleaseDecision->owner?->name ?: 'n/a').' |';
        $lines[] = '| Timestamp | '.$this->md($latestReleaseDecision->decided_at?->format('Y-m-d H:i:s') ?: 'pending').' |';
        $lines[] = '| Score | '.$latestReleaseDecision->release_score.' / 100 |';
        $lines[] = '| Blockers | '.$latestReleaseDecision->blocker_count.' |';
        $lines[] = '| Warnings | '.$latestReleaseDecision->warning_count.' |';
        $lines[] = '| Accepted risks | '.$latestReleaseDecision->accepted_risk_count.' |';
        $lines[] = '| Blind spots | '.$latestReleaseDecision->blind_spot_count.' |';
        $lines[] = '| Package checksum | '.$this->md($latestReleaseDecision->package_checksum ?: 'n/a').' |';
        $lines[] = '';
        if ($latestReleaseDecision->decision_notes) {
            $lines[] = '**Decision notes:** '.$this->md($latestReleaseDecision->decision_notes);
            $lines[] = '';
        }
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
        $statusCounts = collect(Finding::LIFECYCLE_STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => $project->findings->where('status', $status)->count()])
            ->all();
        $this->appendFindingLifecycleStatusSummary($lines, ['status_counts' => $statusCounts]);

        if ($openFindings->isEmpty()) {
            $lines[] = 'No open findings were recorded.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Severity | Status | Verification | Owner | Due | Source | Finding | Endpoint | Test Case | Evidence |';
        $lines[] = '|---|---|---|---|---|---|---|---|---|---:|';
        foreach ($openFindings as $finding) {
            $endpoint = $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : 'n/a';
            $testCase = $finding->testCase?->title ?: 'n/a';
            $owner = $finding->owner?->name ?: __('messages.findings.unassigned');
            $due = $finding->due_date ? $finding->due_date->format('Y-m-d') : 'n/a';
            $lines[] = '| '.$this->md($finding->severity_label).' | '.$this->md($finding->status_label).' | '.$this->md($finding->verification_status_label).' | '.$this->md($owner).' | '.$this->md($due).($finding->is_overdue ? ' overdue' : '').' | '.$this->md($finding->source_label).' | '.$this->md($finding->title).' | '.$this->md($endpoint).' | '.$this->md($testCase).' | '.$finding->evidence->count().' |';
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
            $lines[] = '- **Owner:** '.$this->md($finding->owner?->name ?: __('messages.findings.unassigned'));
            $lines[] = '- **Due date:** '.$this->md($finding->due_date ? $finding->due_date->format('Y-m-d H:i') : 'n/a');
            $lines[] = '- **Verification:** '.$this->md($finding->verification_status_label.' / '.$finding->retest_result_label);
            $lines[] = '- **Verified by:** '.$this->md($finding->verifiedBy?->name ?: 'n/a');
            $lines[] = '- **Expected:** '.$this->md($finding->expected_result ?: 'n/a');
            $lines[] = '- **Actual:** '.$this->md($finding->actual_result ?: 'n/a');
            $lines[] = '- **Recommendation:** '.$this->md($finding->recommendation ?: 'n/a');
            foreach ($finding->evidence as $evidence) {
                $attachment = $evidence->has_attachment ? ' · attachment: '.$evidence->attachment_original_name.' ('.$evidence->attachment_size_label.')' : '';
                $captured = $evidence->captured_at ? ' · captured: '.$evidence->captured_at->format('Y-m-d H:i') : '';
                $by = $evidence->capturedBy ? ' · by: '.$evidence->capturedBy->name : '';
                $lines[] = '- **'.$this->md($evidence->type_label).':** '.$this->md($evidence->source_label ?: 'Evidence').' — '.$this->md($evidence->summary.$attachment.$captured.$by);
                if ($evidence->request_excerpt) {
                    $lines[] = '  - Request excerpt: `'.$this->md(\Illuminate\Support\Str::limit($evidence->request_excerpt, 180)).'`';
                }
                if ($evidence->response_excerpt) {
                    $lines[] = '  - Response excerpt: `'.$this->md(\Illuminate\Support\Str::limit($evidence->response_excerpt, 180)).'`';
                }
                if ($evidence->curl_command) {
                    $lines[] = '  - cURL: `'.$this->md(\Illuminate\Support\Str::limit($evidence->curl_command, 180)).'`';
                }
                if ($evidence->attachment_sha256) {
                    $lines[] = '  - Attachment SHA-256: `'.$this->md($evidence->attachment_sha256).'`';
                }
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

    /** @param array<int, string> $lines @param array<string, mixed> $contractReality */
    private function appendContractReality(array &$lines, array $contractReality, int $limit): void
    {
        $lines[] = '## Contract Reality Check';
        $lines[] = '';

        if (! $contractReality['run']) {
            $lines[] = __('messages.contract_reality.report_empty');
            $lines[] = '';
            return;
        }

        $counts = $contractReality['summary'] ?? [];
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Matches contract | '.($counts['matches_contract'] ?? 0).' |';
        $lines[] = '| Contract drift | '.($counts['contract_drift'] ?? 0).' |';
        $lines[] = '| Auth contract mismatch | '.($counts['auth_contract_mismatch'] ?? 0).' |';
        $lines[] = '| Undocumented response fields | '.($counts['undocumented_response'] ?? 0).' |';
        $lines[] = '| Missing documented endpoints | '.($counts['missing_documented_endpoint'] ?? 0).' |';
        $lines[] = '| Undocumented endpoints | '.($counts['undocumented_endpoint'] ?? 0).' |';
        $lines[] = '| Breaking contract mismatches | '.($counts['breaking_contract_mismatch'] ?? 0).' |';
        $lines[] = '';

        $rows = $contractReality['rows'] ?? collect();
        if (! $rows instanceof Collection || $rows->isEmpty()) {
            return;
        }

        $problems = $rows->filter(fn (array $row): bool => in_array($row['status'], [ContractValidationResult::STATUS_FAIL, ContractValidationResult::STATUS_WARNING], true))->take($this->limit($limit));
        if ($problems->isEmpty()) {
            $lines[] = 'No Contract Reality mismatches were found.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Reality type | Status | Severity | Endpoint | Message | Expected | Actual |';
        $lines[] = '|---|---|---|---|---|---|---|';
        foreach ($problems as $row) {
            $endpoint = trim((string) (($row['method'] ?: '').' '.($row['path'] ?: '')));
            $lines[] = '| '.$this->md((string) $row['reality_label']).' | '.$this->md((string) $row['status_label']).' | '.$this->md((string) $row['severity_label']).' | '.$this->md($endpoint ?: 'n/a').' | '.$this->md((string) $row['message']).' | '.$this->md((string) ($row['expected'] ?: 'n/a')).' | '.$this->md((string) ($row['actual'] ?: 'n/a')).' |';
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


    /** @param array<int, string> $lines @param array<string, mixed> $behaviorMap */
    private function appendApiBehaviorMap(array &$lines, array $behaviorMap, int $limit): void
    {
        $summary = $behaviorMap['summary'] ?? [];
        $links = $behaviorMap['links'] ?? collect();
        $sequences = $behaviorMap['sequences'] ?? collect();

        $lines[] = '## API Behavior Map Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Endpoints | '.($summary['endpoints'] ?? 0).' |';
        $lines[] = '| Producers | '.($summary['producers'] ?? 0).' |';
        $lines[] = '| Consumers | '.($summary['consumers'] ?? 0).' |';
        $lines[] = '| Dependencies | '.($summary['dependencies'] ?? 0).' |';
        $lines[] = '| Destructive endpoints | '.($summary['destructive'] ?? 0).' |';
        $lines[] = '| Auth boundaries | '.($summary['auth_boundaries'] ?? 0).' |';
        $lines[] = '| Sequence candidates | '.($summary['sequence_candidates'] ?? 0).' |';
        $lines[] = '';

        if ($links instanceof Collection && $links->isNotEmpty()) {
            $lines[] = '| Producer | Consumer | Resource | Dependency | Confidence | Suggested sequence |';
            $lines[] = '|---|---|---|---|---:|---|';
            foreach ($links->take($this->limit($limit)) as $link) {
                $producer = $link->producerEndpoint ? $link->producerEndpoint->method.' '.$link->producerEndpoint->path : 'n/a';
                $consumer = $link->consumerEndpoint ? $link->consumerEndpoint->method.' '.$link->consumerEndpoint->path : 'n/a';
                $lines[] = '| '.$this->md($producer).' | '.$this->md($consumer).' | '.$this->md($link->resource_key).' | '.$this->md($link->dependency_type_label).' | '.$link->confidence.' | '.$this->md($link->suggested_sequence ?: 'n/a').' |';
            }
            $lines[] = '';
        }

        if ($sequences instanceof Collection && $sequences->isNotEmpty()) {
            $lines[] = '### Suggested API call sequences';
            $lines[] = '';
            foreach ($sequences->take(10) as $sequence) {
                $lines[] = '- **'.$this->md((string) $sequence['resource']).':** '.$this->md((string) $sequence['summary']);
            }
            $lines[] = '';
        }
    }

    /** @param array<int, string> $lines */
    private function appendTechnicalRequestResponseEvidence(array &$lines, Project $project, int $limit): void
    {
        $rows = $project->endpoints
            ->map(fn (Endpoint $endpoint): array => [$endpoint, $endpoint->latestScanResult])
            ->filter(fn (array $row): bool => $row[1] instanceof ScanResult)
            ->sortBy(fn (array $row): string => sprintf('%s %s', $row[0]->method, $row[0]->path))
            ->take($this->limit($limit));

        $lines[] = '## Technical Request / Response Evidence';
        $lines[] = '';

        if ($rows->isEmpty()) {
            $lines[] = 'No latest scan result evidence is available for endpoint-level request/response review.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Method | Endpoint | URL | HTTP | Time | Content type | Size | Sensitive data | Broken auth | Schema drift | Body preview / error |';
        $lines[] = '|---|---|---|---:|---:|---|---:|---|---|---|---|';
        foreach ($rows as [$endpoint, $result]) {
            $bodyPreview = $result->body_preview ?: $result->error_message ?: 'n/a';
            $sensitive = $result->sensitive_data_detected ? 'yes ('.$result->sensitive_data_count.')' : 'no';
            $brokenAuth = $result->broken_auth_detected ? 'yes' : 'no';
            $schemaDrift = $result->schema_drift_detected ? 'yes ('.$result->schema_drift_count.')' : 'no';
            $lines[] = '| '.$this->md($endpoint->method).' | '.$this->md($endpoint->path).' | '.$this->md($result->url).' | '.$this->md($result->status_code ?: 'n/a').' | '.$this->md($result->response_time_ms !== null ? $result->response_time_ms.' ms' : 'n/a').' | '.$this->md($result->content_type ?: 'n/a').' | '.$this->md($result->response_size !== null ? (string) $result->response_size : 'n/a').' | '.$this->md($sensitive).' | '.$this->md($brokenAuth).' | '.$this->md($schemaDrift).' | '.$this->md(\Illuminate\Support\Str::limit($bodyPreview, 180)).' |';
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

    /** @param array<int, string> $lines @param array<string, mixed> $graph */
    private function appendEvidenceGraphSummary(array &$lines, array $graph, int $endpointLimit, int $findingLimit): void
    {
        $counts = $graph['summary'] ?? [];
        $lines[] = '## Evidence Graph Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Endpoint evidence maps | '.($counts['endpoint_maps'] ?? 0).' |';
        $lines[] = '| Scan results | '.($counts['scan_results'] ?? 0).' |';
        $lines[] = '| Finding evidence items | '.($counts['finding_evidence'] ?? 0).' |';
        $lines[] = '| Release gates | '.($counts['release_gates'] ?? 0).' |';
        $lines[] = '| Release decisions | '.($counts['release_decisions'] ?? 0).' |';
        $lines[] = '| Accepted risks | '.($counts['accepted_risks'] ?? 0).' |';
        $lines[] = '| Blind spots | '.($counts['blind_spots'] ?? 0).' |';
        $lines[] = '| Missing evidence links | '.($counts['missing_links'] ?? 0).' |';
        $lines[] = '';

        $release = $graph['release_graph'] ?? [];
        $lines[] = '### Release Evidence Graph';
        $lines[] = '';
        $lines[] = '| Evidence node | Value |';
        $lines[] = '|---|---|';
        $lines[] = '| Latest scan | '.$this->md((string) (($release['latest_scan']->id ?? null) ?: 'n/a')).' |';
        $lines[] = '| Latest snapshot | '.$this->md((string) (($release['latest_snapshot']->name ?? null) ?: 'n/a')).' |';
        $lines[] = '| Latest compare | '.$this->md((string) (($release['latest_compare']->id ?? null) ?: 'n/a')).' |';
        $lines[] = '| Latest release gate | '.$this->md((string) (($release['latest_gate']->release_name ?? null) ?: 'n/a')).' |';
        $lines[] = '| Latest release decision | '.$this->md((string) (($release['latest_decision']->status_label ?? null) ?: 'n/a')).' |';
        $lines[] = '';

        $endpointMaps = $graph['endpoint_maps'] ?? collect();
        if ($endpointMaps instanceof Collection && $endpointMaps->isNotEmpty()) {
            $lines[] = '### Endpoint Evidence Map';
            $lines[] = '';
            $lines[] = '| Endpoint | Scan results | Assertions | Findings | Evidence | Missing links |';
            $lines[] = '|---|---:|---:|---:|---:|---:|';
            foreach ($endpointMaps->take($this->limit($endpointLimit)) as $map) {
                $endpoint = $map['endpoint'];
                $lines[] = '| '.$this->md($endpoint->method.' '.$endpoint->path).' | '.$map['scan_results_count'].' | '.$map['assertion_rules_count'].' | '.$map['findings_count'].' | '.$map['evidence_count'].' | '.$map['missing_links']->count().' |';
            }
            $lines[] = '';
        }

        $findingChains = $graph['finding_chains'] ?? collect();
        if ($findingChains instanceof Collection && $findingChains->isNotEmpty()) {
            $lines[] = '### Finding Evidence Chain';
            $lines[] = '';
            $lines[] = '| Finding | Status | Severity | Evidence | Retest evidence | Missing links |';
            $lines[] = '|---|---|---|---:|---|---:|';
            foreach ($findingChains->take($this->limit($findingLimit)) as $chain) {
                $finding = $chain['finding'];
                $lines[] = '| '.$this->md($finding->title).' | '.$this->md($finding->status_label).' | '.$this->md($finding->severity_label).' | '.$chain['evidence_count'].' | '.($chain['has_retest_evidence'] ? 'yes' : 'no').' | '.$chain['missing_links']->count().' |';
            }
            $lines[] = '';
        }

        $missing = $graph['missing_links'] ?? collect();
        if ($missing instanceof Collection && $missing->isNotEmpty()) {
            $lines[] = '### Missing Evidence Links';
            $lines[] = '';
            $lines[] = '| Scope | Related item | Missing link |';
            $lines[] = '|---|---|---|';
            foreach ($missing->take(25) as $item) {
                $lines[] = '| '.$this->md($item['scope']).' | '.$this->md($item['related']).' | '.$this->md($item['missing']).' |';
            }
            $lines[] = '';
        }
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

    /** @param array<int, string> $lines @param array<string, mixed> $findingCounts */
    private function appendFindingLifecycleStatusSummary(array &$lines, array $findingCounts): void
    {
        $lines[] = '### Finding Lifecycle Status Summary';
        $lines[] = '';
        $lines[] = '| Status | Count | Release impact |';
        $lines[] = '|---|---:|---|';
        foreach (Finding::LIFECYCLE_STATUSES as $status) {
            $impact = in_array($status, Finding::OPEN_STATUSES, true)
                ? 'Counts as open release risk'
                : 'Does not count as open release risk';
            $lines[] = '| '.$this->md(__('messages.findings.statuses.'.$status)).' | '.($findingCounts['status_counts'][$status] ?? 0).' | '.$this->md($impact).' |';
        }
        $lines[] = '';
    }


    /** @param array<int, string> $lines @param array<string, mixed> $findingCounts */
    private function appendFindingVerificationSummary(array &$lines, array $findingCounts): void
    {
        $lines[] = '### Finding Verification Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Ready for retest | '.($findingCounts['ready_for_retest'] ?? 0).' |';
        $lines[] = '| Retest failed | '.($findingCounts['retest_failed'] ?? 0).' |';
        $lines[] = '| Fixed but not verified | '.($findingCounts['fixed_unverified'] ?? 0).' |';
        $lines[] = '| Verified | '.($findingCounts['verified'] ?? 0).' |';
        $lines[] = '| Overdue | '.($findingCounts['overdue'] ?? 0).' |';
        $lines[] = '| Missing required fix evidence | '.($findingCounts['fix_evidence_missing'] ?? 0).' |';
        $lines[] = '';
    }


    /** @param array<int, string> $lines @param array<string, mixed> $riskAcceptances */
    private function appendRiskAcceptanceSummary(array &$lines, array $riskAcceptances): void
    {
        $counts = $riskAcceptances['summary'] ?? [];
        $lines[] = '### Risk Acceptance Ledger Summary';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total | '.($counts['total'] ?? 0).' |';
        $lines[] = '| Active | '.($counts['active'] ?? 0).' |';
        $lines[] = '| High or critical active | '.($counts['active_high_or_critical'] ?? 0).' |';
        $lines[] = '| Without expiry | '.($counts['without_expiry'] ?? 0).' |';
        $lines[] = '| Expiring soon | '.($counts['expiring_soon'] ?? 0).' |';
        $lines[] = '| Expired | '.($counts['expired'] ?? 0).' |';
        $lines[] = '';

        $items = $riskAcceptances['active_items'] ?? collect();
        if ($items instanceof Collection && $items->isNotEmpty()) {
            $lines[] = '| Finding | Accepted until | Scope | Reason |';
            $lines[] = '|---|---|---|---|';
            foreach ($items->take(10) as $acceptance) {
                $lines[] = '| '.$this->md((string) $acceptance->finding?->title).' | '.$this->md((string) ($acceptance->accepted_until?->format('Y-m-d') ?: 'n/a')).' | '.$this->md((string) ($acceptance->release_scope ?: 'n/a')).' | '.$this->md((string) $acceptance->reason).' |';
            }
            $lines[] = '';
        }
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

    private function mdLine(string $line): string
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
