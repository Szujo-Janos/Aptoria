<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ReleaseReadinessRun;
use App\Models\ReleaseReadinessRule;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ReleaseReadinessService
{
    public function __construct(
        private readonly RetestClosureService $retestClosureService,
        private readonly RiskAcceptanceService $riskAcceptanceService,
        private readonly OpenApiContractValidationService $contractValidationService,
        private readonly ExternalQaImportService $externalQaImportService,
        private readonly ReleaseReadinessProfileService $profileService,
    ) {
    }

    public function evaluate(Project $project, ?array $ruleOverrides = null): array
    {
        $metrics = $this->metrics($project);
        $retestClosure = $this->retestClosureService->summary($project);
        $riskAcceptance = $this->riskAcceptanceService->summary($project);
        $contractValidation = $this->contractValidationService->summary($project);
        $externalImport = $this->externalQaImportService->summary($project);
        $metrics = array_merge($metrics, $this->retestClosureMetrics($retestClosure), $this->riskAcceptanceMetrics($riskAcceptance), $this->contractValidationMetrics($contractValidation), $this->externalImportMetrics($externalImport));
        $checks = $this->checks($metrics, $project, $ruleOverrides);
        $profile = $this->profileService->summary($project);

        $blockerCount = collect($checks)->where('level', 'blocker')->count();
        $warningCount = collect($checks)->where('level', 'warning')->count();
        $passedCount = collect($checks)->where('level', 'pass')->count();
        $score = $this->score($checks);
        $status = $blockerCount > 0 ? 'blocked' : ($warningCount > 0 ? 'warning' : 'ready');

        return [
            'status' => $status,
            'score' => $score,
            'grade' => $this->grade($score, $status),
            'blocker_count' => $blockerCount,
            'warning_count' => $warningCount,
            'check_count' => count($checks),
            'passed_check_count' => $passedCount,
            'metrics' => $metrics,
            'checks' => $checks,
            'rules' => $this->ruleSummary($project),
            'profile' => $profile,
            'summary' => [
                'headline' => __('messages.release_readiness.summary_'.$status),
                'decision' => __('messages.release_readiness.decision_'.$status),
            ],
            'retest_closure' => $retestClosure,
            'risk_acceptance' => $riskAcceptance,
            'contract_validation' => $contractValidation,
            'external_import' => $externalImport,
        ];
    }

    public function createRun(Project $project, ?User $user = null, ?string $decisionNote = null): ReleaseReadinessRun
    {
        $evaluation = $this->evaluate($project);

        return $project->releaseReadinessRuns()->create([
            'generated_by_user_id' => $user?->id,
            'status' => $evaluation['status'],
            'score' => $evaluation['score'],
            'grade' => $evaluation['grade'],
            'blocker_count' => $evaluation['blocker_count'],
            'warning_count' => $evaluation['warning_count'],
            'check_count' => $evaluation['check_count'],
            'passed_check_count' => $evaluation['passed_check_count'],
            'metrics_json' => $evaluation['metrics'],
            'checks_json' => $evaluation['checks'],
            'rules_json' => $evaluation['rules'] ?? null,
            'readiness_profile_key' => $evaluation['profile']['profile_key'] ?? null,
            'rule_deviations_json' => $evaluation['profile']['deviations'] ?? null,
            'summary_json' => $evaluation['summary'],
            'retest_closure_json' => $evaluation['retest_closure'] ?? null,
            'risk_acceptance_json' => $evaluation['risk_acceptance'] ?? null,
            'contract_validation_json' => $evaluation['contract_validation'] ?? null,
            'decision_note' => $decisionNote,
            'generated_at' => now(),
        ]);
    }

    public function simulate(Project $project, array $ruleOverrides): array
    {
        $current = $this->evaluate($project);
        $preview = $this->evaluate($project, $ruleOverrides);

        return [
            'current_status' => $current['status'],
            'preview_status' => $preview['status'],
            'current_score' => $current['score'],
            'preview_score' => $preview['score'],
            'score_delta' => $preview['score'] - $current['score'],
            'current_blockers' => $current['blocker_count'],
            'preview_blockers' => $preview['blocker_count'],
            'blocker_delta' => $preview['blocker_count'] - $current['blocker_count'],
            'current_warnings' => $current['warning_count'],
            'preview_warnings' => $preview['warning_count'],
            'warning_delta' => $preview['warning_count'] - $current['warning_count'],
            'preview_checks' => $preview['checks'],
        ];
    }

    private function metrics(Project $project): array
    {
        $endpointCount = Schema::hasTable('endpoints') ? $project->endpoints()->count() : 0;
        $safeEndpointCount = Schema::hasTable('endpoints') ? $project->endpoints()->whereIn('method', ['GET', 'HEAD'])->where('is_active', true)->where('excluded_from_scan', false)->count() : 0;
        $environmentCount = Schema::hasTable('environments') ? $project->environments()->count() : 0;
        $defaultEnvironment = Schema::hasTable('environments') ? $project->defaultEnvironment() : null;
        $authProfileCount = Schema::hasTable('auth_profiles') ? $project->authProfiles()->count() : 0;
        $scanRunCount = Schema::hasTable('scan_runs') ? $project->scanRuns()->count() : 0;
        $latestScan = Schema::hasTable('scan_runs') ? $project->scanRuns()->where('status', 'completed')->latest()->first() : null;
        $scanSummary = is_array($latestScan?->summary_json) ? $latestScan->summary_json : [];
        $failedResults = Schema::hasTable('scan_results') ? $project->scanResults()->where('status', 'failed')->count() : 0;
        $warningResults = Schema::hasTable('scan_results') ? $project->scanResults()->where('status', 'warning')->count() : 0;
        $findingCount = Schema::hasTable('findings') ? $project->findings()->count() : 0;
        $openFindings = Schema::hasTable('findings') ? $project->findings()->whereNotIn('status', ['verified'])->count() : 0;
        $acceptedFindingIds = Schema::hasTable('risk_acceptances') ? $project->riskAcceptances()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('accepted_until')->orWhereDate('accepted_until', '>=', now()->toDateString());
            })
            ->pluck('finding_id')
            ->unique()
            ->all() : [];
        $criticalFindings = Schema::hasTable('findings') ? $project->findings()->where('severity', 'critical')->whereNotIn('status', ['verified'])->whereNotIn('id', $acceptedFindingIds ?: [0])->count() : 0;
        $highFindings = Schema::hasTable('findings') ? $project->findings()->where('severity', 'high')->whereNotIn('status', ['verified'])->whereNotIn('id', $acceptedFindingIds ?: [0])->count() : 0;
        $acceptedCriticalFindings = Schema::hasTable('findings') && $acceptedFindingIds ? $project->findings()->where('severity', 'critical')->whereNotIn('status', ['verified'])->whereIn('id', $acceptedFindingIds)->count() : 0;
        $acceptedHighFindings = Schema::hasTable('findings') && $acceptedFindingIds ? $project->findings()->where('severity', 'high')->whereNotIn('status', ['verified'])->whereIn('id', $acceptedFindingIds)->count() : 0;
        $evidenceCount = Schema::hasTable('finding_evidence') ? $project->evidence()->count() : 0;
        $findingsMissingEvidence = Schema::hasTable('findings') ? $project->findings()
            ->where('evidence_required', true)
            ->whereNotIn('status', ['verified'])
            ->whereDoesntHave('evidence')
            ->count() : 0;
        $retestNeeded = Schema::hasTable('findings') ? $project->findings()
            ->where('retest_required', true)
            ->whereNotIn('status', ['verified'])
            ->count() : 0;
        $retestReady = Schema::hasTable('findings') && Schema::hasColumn('findings', 'retest_status') ? $project->findings()
            ->where('retest_status', 'ready_for_retest')
            ->count() : 0;
        $retestFailed = Schema::hasTable('findings') && Schema::hasColumn('findings', 'retest_status') ? $project->findings()
            ->where('retest_status', 'failed')
            ->whereNotIn('status', ['verified'])
            ->count() : 0;
        $retestMissingEvidence = Schema::hasTable('findings') && Schema::hasTable('finding_evidence') ? $project->findings()
            ->where('retest_required', true)
            ->whereNotIn('status', ['verified'])
            ->whereDoesntHave('evidence', fn ($query) => $query->where('type', 'retest'))
            ->count() : 0;
        $endpointTestRunCount = Schema::hasTable('endpoint_test_runs') ? $project->endpointTestRuns()->count() : 0;
        $latestEndpointTestRuns = Schema::hasTable('endpoint_test_runs')
            ? $project->endpointTestRuns()->latest('checked_at')->latest()->take(100)->get()->unique('endpoint_id')
            : collect();
        $latestEndpointTestsFailed = $latestEndpointTestRuns->where('state', 'failed')->count();
        $latestEndpointTestsWarning = $latestEndpointTestRuns->whereIn('state', ['warning', 'skipped'])->count();
        $endpointTestBatchCount = Schema::hasTable('endpoint_test_batches') ? $project->endpointTestBatches()->count() : 0;
        $latestEndpointTestBatch = Schema::hasTable('endpoint_test_batches') ? $project->endpointTestBatches()->latest('completed_at')->latest()->first() : null;
        $endpointSnapshotCount = Schema::hasTable('endpoint_snapshots') ? $project->endpointSnapshots()->count() : 0;
        $latestEndpointSnapshot = Schema::hasTable('endpoint_snapshots') ? $project->endpointSnapshots()->latest('captured_at')->latest()->first() : null;
        $endpointSnapshotCompareCount = Schema::hasTable('endpoint_snapshot_compares') ? $project->endpointSnapshotCompares()->count() : 0;
        $latestEndpointSnapshotCompare = Schema::hasTable('endpoint_snapshot_compares') ? $project->endpointSnapshotCompares()->latest('compared_at')->latest()->first() : null;
        $latestEndpointSnapshotCompareFindingCount = (int) ($latestEndpointSnapshotCompare?->regression_finding_count ?? 0);
        $latestEndpointSnapshotCompareNeedsTriage = $latestEndpointSnapshotCompare
            && (((int) ($latestEndpointSnapshotCompare->regressed_count ?? 0) + (int) ($latestEndpointSnapshotCompare->removed_count ?? 0)) > 0)
            && $latestEndpointSnapshotCompareFindingCount === 0;

        return [
            'endpoint_count' => $endpointCount,
            'safe_endpoint_count' => $safeEndpointCount,
            'environment_count' => $environmentCount,
            'default_environment_name' => $defaultEnvironment?->name,
            'auth_profile_count' => $authProfileCount,
            'scan_run_count' => $scanRunCount,
            'latest_scan_id' => $latestScan?->id,
            'latest_scan_at' => $latestScan?->completed_at?->toDateTimeString(),
            'latest_scan_passed' => (int) ($scanSummary['passed'] ?? 0),
            'latest_scan_warning' => (int) ($scanSummary['warning'] ?? 0),
            'latest_scan_failed' => (int) ($scanSummary['failed'] ?? 0),
            'latest_scan_skipped' => (int) ($scanSummary['skipped'] ?? 0),
            'failed_scan_results' => $failedResults,
            'warning_scan_results' => $warningResults,
            'finding_count' => $findingCount,
            'open_findings' => $openFindings,
            'critical_findings' => $criticalFindings,
            'high_findings' => $highFindings,
            'accepted_critical_findings' => $acceptedCriticalFindings,
            'accepted_high_findings' => $acceptedHighFindings,
            'evidence_count' => $evidenceCount,
            'findings_missing_evidence' => $findingsMissingEvidence,
            'retest_needed' => $retestNeeded,
            'retest_ready' => $retestReady,
            'retest_failed' => $retestFailed,
            'retest_missing_evidence' => $retestMissingEvidence,
            'release_goal_present' => filled($project->release_goal),
            'endpoint_test_run_count' => $endpointTestRunCount,
            'latest_endpoint_tests_failed' => $latestEndpointTestsFailed,
            'latest_endpoint_tests_warning' => $latestEndpointTestsWarning,
            'endpoint_test_batch_count' => $endpointTestBatchCount,
            'latest_endpoint_test_batch_id' => $latestEndpointTestBatch?->id,
            'latest_endpoint_test_batch_state' => $latestEndpointTestBatch?->state,
            'latest_endpoint_test_batch_failed' => (int) ($latestEndpointTestBatch?->failed ?? 0),
            'latest_endpoint_test_batch_warning' => (int) (($latestEndpointTestBatch?->warning ?? 0) + ($latestEndpointTestBatch?->skipped ?? 0)),
            'latest_endpoint_test_batch_completed_at' => $latestEndpointTestBatch?->completed_at?->toDateTimeString(),
            'endpoint_snapshot_count' => $endpointSnapshotCount,
            'latest_endpoint_snapshot_id' => $latestEndpointSnapshot?->id,
            'latest_endpoint_snapshot_checksum' => $latestEndpointSnapshot?->checksum,
            'latest_endpoint_snapshot_captured_at' => $latestEndpointSnapshot?->captured_at?->toDateTimeString(),
            'endpoint_snapshot_compare_count' => $endpointSnapshotCompareCount,
            'latest_endpoint_snapshot_compare_id' => $latestEndpointSnapshotCompare?->id,
            'latest_endpoint_snapshot_compare_status' => $latestEndpointSnapshotCompare?->status,
            'latest_endpoint_snapshot_compare_regressions' => (int) ($latestEndpointSnapshotCompare?->regressed_count ?? 0),
            'latest_endpoint_snapshot_compare_removed' => (int) ($latestEndpointSnapshotCompare?->removed_count ?? 0),
            'latest_endpoint_snapshot_compare_regression_finding_count' => $latestEndpointSnapshotCompareFindingCount,
            'latest_endpoint_snapshot_compare_needs_triage' => (bool) $latestEndpointSnapshotCompareNeedsTriage,
        ];
    }

    private function checks(array $metrics, Project $project, ?array $ruleOverrides = null): array
    {
        $checks = [
            $this->check('environment', 'server-cog', $metrics['environment_count'] > 0 && filled($metrics['default_environment_name']), 'blocker', __('messages.release_readiness.checks.environment'), __('messages.release_readiness.hints.environment')),
            $this->check('endpoint_inventory', 'list-tree', $metrics['endpoint_count'] > 0, 'blocker', __('messages.release_readiness.checks.endpoint_inventory'), __('messages.release_readiness.hints.endpoint_inventory')),
            $this->check('safe_endpoint_inventory', 'shield-check', $metrics['safe_endpoint_count'] > 0, 'blocker', __('messages.release_readiness.checks.safe_endpoint_inventory'), __('messages.release_readiness.hints.safe_endpoint_inventory')),
            $this->check('safe_scan', 'radar', $metrics['latest_scan_id'] !== null, 'blocker', __('messages.release_readiness.checks.safe_scan'), __('messages.release_readiness.hints.safe_scan')),
            $this->check('endpoint_quick_test_coverage', 'activity', $metrics['endpoint_test_run_count'] > 0, 'warning', __('messages.release_readiness.checks.endpoint_quick_test_coverage'), __('messages.release_readiness.hints.endpoint_quick_test_coverage')),
            $this->check('endpoint_quick_test_failures', 'zap-off', $metrics['latest_endpoint_tests_failed'] === 0, 'warning', __('messages.release_readiness.checks.endpoint_quick_test_failures'), __('messages.release_readiness.hints.endpoint_quick_test_failures')),
            $this->check('endpoint_batch_evidence', 'layers', $metrics['endpoint_test_batch_count'] > 0, 'warning', __('messages.release_readiness.checks.endpoint_batch_evidence'), __('messages.release_readiness.hints.endpoint_batch_evidence')),
            $this->check('endpoint_batch_failures', 'list-checks', $metrics['latest_endpoint_test_batch_failed'] === 0, 'warning', __('messages.release_readiness.checks.endpoint_batch_failures'), __('messages.release_readiness.hints.endpoint_batch_failures')),
            $this->check('endpoint_snapshot_baseline', 'camera', $metrics['endpoint_snapshot_count'] > 0, 'warning', __('messages.release_readiness.checks.endpoint_snapshot_baseline'), __('messages.release_readiness.hints.endpoint_snapshot_baseline')),
            $this->check('endpoint_regression_compare', 'arrows-diff', $metrics['endpoint_snapshot_compare_count'] > 0, 'warning', __('messages.release_readiness.checks.endpoint_regression_compare'), __('messages.release_readiness.hints.endpoint_regression_compare')),
            $this->check('endpoint_regression_clean', 'git-compare', $metrics['latest_endpoint_snapshot_compare_regressions'] === 0, 'warning', __('messages.release_readiness.checks.endpoint_regression_clean'), __('messages.release_readiness.hints.endpoint_regression_clean')),
            $this->check('endpoint_regression_triage', 'bug', ! $metrics['latest_endpoint_snapshot_compare_needs_triage'], 'warning', __('messages.release_readiness.checks.endpoint_regression_triage'), __('messages.release_readiness.hints.endpoint_regression_triage')),
            $this->check('scan_failures', 'circle-alert', $metrics['latest_scan_failed'] === 0 && $metrics['failed_scan_results'] === 0, 'blocker', __('messages.release_readiness.checks.scan_failures'), __('messages.release_readiness.hints.scan_failures')),
            $this->check('critical_findings', 'bug', $metrics['critical_findings'] === 0, 'blocker', __('messages.release_readiness.checks.critical_findings'), __('messages.release_readiness.hints.critical_findings')),
            $this->check('high_findings', 'triangle-alert', $metrics['high_findings'] === 0, 'warning', __('messages.release_readiness.checks.high_findings'), __('messages.release_readiness.hints.high_findings')),
            $this->check('evidence_repository', 'archive', $metrics['evidence_count'] > 0, 'warning', __('messages.release_readiness.checks.evidence_repository'), __('messages.release_readiness.hints.evidence_repository')),
            $this->check('missing_evidence', 'file-warning', $metrics['findings_missing_evidence'] === 0, 'warning', __('messages.release_readiness.checks.missing_evidence'), __('messages.release_readiness.hints.missing_evidence')),
            $this->check('retest_queue', 'rotate-ccw', $metrics['retest_needed'] === 0, 'blocker', __('messages.release_readiness.checks.retest_queue'), __('messages.release_readiness.hints.retest_queue')),
            $this->check('retest_failures', 'shield-x', $metrics['retest_failed'] === 0, 'blocker', __('messages.release_readiness.checks.retest_failures'), __('messages.release_readiness.hints.retest_failures')),
            $this->check('retest_evidence', 'test-tube', $metrics['retest_missing_evidence'] === 0, 'warning', __('messages.release_readiness.checks.retest_evidence'), __('messages.release_readiness.hints.retest_evidence')),
            $this->check('retest_closure_clean', 'shield-check', $metrics['retest_closure_open'] === 0, 'blocker', __('messages.release_readiness.checks.retest_closure_clean'), __('messages.release_readiness.hints.retest_closure_clean')),
            $this->check('retest_closure_evidence', 'certificate', $metrics['retest_closure_missing_evidence'] === 0, 'warning', __('messages.release_readiness.checks.retest_closure_evidence'), __('messages.release_readiness.hints.retest_closure_evidence')),
            $this->check('regression_retest_closure', 'git-pull-request-closed', $metrics['retest_closure_regression_retest_open'] === 0, 'blocker', __('messages.release_readiness.checks.regression_retest_closure'), __('messages.release_readiness.hints.regression_retest_closure')),
            $this->check('accepted_risk_expiry', 'shield-alert', $metrics['risk_acceptance_expired'] === 0, 'blocker', __('messages.release_readiness.checks.accepted_risk_expiry'), __('messages.release_readiness.hints.accepted_risk_expiry')),
            $this->check('accepted_risk_renewal_window', 'calendar-clock', $metrics['risk_acceptance_expiring_soon'] === 0, 'warning', __('messages.release_readiness.checks.accepted_risk_renewal_window'), __('messages.release_readiness.hints.accepted_risk_renewal_window')),
            $this->check('accepted_risk_ledger', 'shield-check', $metrics['risk_acceptance_unaccepted_high_critical'] === 0, 'warning', __('messages.release_readiness.checks.accepted_risk_ledger'), __('messages.release_readiness.hints.accepted_risk_ledger')),
            $this->check('contract_validation_present', 'file-check-2', $metrics['contract_validation_has_run'], 'warning', __('messages.release_readiness.checks.contract_validation_present'), __('messages.release_readiness.hints.contract_validation_present')),
            $this->check('contract_validation_blockers', 'file-warning', $metrics['contract_validation_blockers'] === 0, 'blocker', __('messages.release_readiness.checks.contract_validation_blockers'), __('messages.release_readiness.hints.contract_validation_blockers')),
            $this->check('contract_validation_clean', 'file-search', $metrics['contract_validation_warnings'] === 0, 'warning', __('messages.release_readiness.checks.contract_validation_clean'), __('messages.release_readiness.hints.contract_validation_clean')),
            $this->check('external_qa_import_present', 'brackets-contain', $metrics['external_import_has_run'], 'warning', __('messages.release_readiness.checks.external_qa_import_present'), __('messages.release_readiness.hints.external_qa_import_present')),
            $this->check('external_qa_import_applied', 'save', $metrics['external_import_status'] === 'applied', 'warning', __('messages.release_readiness.checks.external_qa_import_applied'), __('messages.release_readiness.hints.external_qa_import_applied')),
            $this->check('external_qa_import_blockers', 'file-warning', $metrics['external_import_blockers'] === 0, 'warning', __('messages.release_readiness.checks.external_qa_import_blockers'), __('messages.release_readiness.hints.external_qa_import_blockers')),
            $this->check('external_qa_import_conflicts', 'shield-alert', $metrics['external_import_conflicts'] === 0, 'blocker', __('messages.release_readiness.checks.external_qa_import_conflicts'), __('messages.release_readiness.hints.external_qa_import_conflicts')),
            $this->check('release_goal', 'target', $metrics['release_goal_present'], 'warning', __('messages.release_readiness.checks.release_goal'), __('messages.release_readiness.hints.release_goal')),
        ];

        return $this->applyRuleBuilder($project, $checks, $ruleOverrides);
    }


    private function applyRuleBuilder(Project $project, array $checks, ?array $ruleOverrides = null): array
    {
        if (! Schema::hasTable('release_readiness_rules')) {
            return $checks;
        }

        ReleaseReadinessRule::syncDefaults($project);
        $rules = $project->releaseReadinessRules()->get()->keyBy('rule_key');

        return collect($checks)->map(function (array $check) use ($rules, $ruleOverrides): ?array {
            /** @var ReleaseReadinessRule|null $rule */
            $rule = $rules->get($check['key'] ?? '');
            if (! $rule) {
                return $check;
            }
            $override = $ruleOverrides[$rule->id] ?? $ruleOverrides[$rule->rule_key] ?? null;
            $enabled = array_key_exists('enabled', (array) $override) ? (bool) $override['enabled'] : (bool) $rule->enabled;
            $failureLevel = (string) ($override['failure_level'] ?? $rule->failure_level ?? $check['level']);
            if (! $enabled) {
                return null;
            }
            if (! ($check['passed'] ?? false)) {
                $check['level'] = $failureLevel ?: $check['level'];
                $check['tone'] = $check['level'] === 'blocker' ? 'danger' : 'warning';
            }
            $check['rule_level'] = $failureLevel;
            $check['rule_category'] = $rule->category;
            $check['rule_enabled'] = $enabled;

            return $check;
        })->filter()->values()->all();
    }

    private function ruleSummary(Project $project): array
    {
        if (! Schema::hasTable('release_readiness_rules')) {
            return [];
        }

        ReleaseReadinessRule::syncDefaults($project);
        $rules = $project->releaseReadinessRules()->orderBy('sort_order')->get();

        return [
            'enabled_count' => $rules->where('enabled', true)->count(),
            'disabled_count' => $rules->where('enabled', false)->count(),
            'blocker_count' => $rules->where('enabled', true)->where('failure_level', 'blocker')->count(),
            'warning_count' => $rules->where('enabled', true)->where('failure_level', 'warning')->count(),
            'captured_at' => now()->toDateTimeString(),
            'profile' => $this->profileService->summary($project),
            'rules' => $rules->map(fn (ReleaseReadinessRule $rule): array => [
                'key' => $rule->rule_key,
                'enabled' => $rule->enabled,
                'failure_level' => $rule->failure_level,
                'category' => $rule->category,
            ])->values()->all(),
        ];
    }

    private function retestClosureMetrics(array $closure): array
    {
        return [
            'retest_closure_status' => (string) ($closure['status'] ?? 'closed'),
            'retest_closure_rate' => (int) ($closure['closure_rate'] ?? 100),
            'retest_closure_total' => (int) ($closure['total'] ?? 0),
            'retest_closure_open' => (int) ($closure['open'] ?? 0),
            'retest_closure_pending' => (int) ($closure['pending'] ?? 0),
            'retest_closure_failed' => (int) ($closure['failed'] ?? 0),
            'retest_closure_missing_evidence' => (int) ($closure['missing_evidence'] ?? 0),
            'retest_closure_stale_ready' => (int) ($closure['stale_ready'] ?? 0),
            'retest_closure_regression_open' => (int) ($closure['regression_open'] ?? 0),
            'retest_closure_regression_retest_open' => (int) ($closure['regression_retest_open'] ?? 0),
        ];
    }

    private function riskAcceptanceMetrics(array $acceptance): array
    {
        return [
            'risk_acceptance_active' => (int) ($acceptance['active'] ?? 0),
            'risk_acceptance_expiring_soon' => (int) ($acceptance['expiring_soon'] ?? 0),
            'risk_acceptance_expired' => (int) ($acceptance['expired'] ?? 0),
            'risk_acceptance_revoked' => (int) ($acceptance['revoked'] ?? 0),
            'risk_acceptance_renewed' => (int) ($acceptance['renewed'] ?? 0),
            'risk_acceptance_accepted_high_critical' => (int) ($acceptance['open_high_critical_accepted'] ?? 0),
            'risk_acceptance_unaccepted_high_critical' => (int) ($acceptance['open_high_critical_unaccepted'] ?? 0),
            'risk_acceptance_next_expiry_at' => $acceptance['next_expiry_at'] ?? null,
        ];
    }

    private function contractValidationMetrics(array $contract): array
    {
        return [
            'contract_validation_has_run' => (bool) ($contract['has_run'] ?? false),
            'contract_validation_latest_run_id' => $contract['latest_run_id'] ?? null,
            'contract_validation_status' => (string) ($contract['latest_status'] ?? 'missing'),
            'contract_validation_documented_operations' => (int) ($contract['documented_operations'] ?? 0),
            'contract_validation_inventory_operations' => (int) ($contract['inventory_operations'] ?? 0),
            'contract_validation_matched_operations' => (int) ($contract['matched_operations'] ?? 0),
            'contract_validation_undocumented' => (int) ($contract['undocumented_inventory_operations'] ?? 0),
            'contract_validation_missing_inventory' => (int) ($contract['missing_inventory_operations'] ?? 0),
            'contract_validation_blockers' => (int) ($contract['blocker_count'] ?? 0),
            'contract_validation_warnings' => (int) ($contract['warning_count'] ?? 0),
            'contract_validation_validated_at' => $contract['validated_at'] ?? null,
        ];
    }


    private function externalImportMetrics(array $import): array
    {
        return [
            'external_import_has_run' => (bool) ($import['has_run'] ?? false),
            'external_import_latest_run_id' => $import['latest_run_id'] ?? null,
            'external_import_source_type' => $import['source_type'] ?? null,
            'external_import_status' => (string) ($import['status'] ?? 'missing'),
            'external_import_items' => (int) ($import['item_count'] ?? 0),
            'external_import_endpoints' => (int) ($import['endpoint_count'] ?? 0),
            'external_import_assertions' => (int) ($import['assertion_count'] ?? 0),
            'external_import_findings' => (int) ($import['finding_count'] ?? 0),
            'external_import_evidence' => (int) ($import['evidence_count'] ?? 0),
            'external_import_warnings' => (int) ($import['warning_count'] ?? 0),
            'external_import_blockers' => (int) ($import['blocker_count'] ?? 0),
            'external_import_new' => (int) ($import['new_count'] ?? 0),
            'external_import_updates' => (int) ($import['update_count'] ?? 0),
            'external_import_duplicates' => (int) ($import['duplicate_count'] ?? 0),
            'external_import_conflicts' => (int) ($import['conflict_count'] ?? 0),
            'external_import_needs_review' => (int) ($import['needs_review_count'] ?? 0),
            'external_import_applied_at' => $import['applied_at'] ?? null,
        ];
    }

    private function check(string $key, string $icon, bool $passed, string $failureLevel, string $label, string $hint): array
    {
        return [
            'key' => $key,
            'icon' => $icon,
            'label' => $label,
            'hint' => $hint,
            'passed' => $passed,
            'level' => $passed ? 'pass' : $failureLevel,
            'tone' => $passed ? 'success' : ($failureLevel === 'blocker' ? 'danger' : 'warning'),
        ];
    }

    private function score(array $checks): int
    {
        $score = 100;
        foreach ($checks as $check) {
            if ($check['level'] === 'blocker') {
                $score -= 15;
            } elseif ($check['level'] === 'warning') {
                $score -= 7;
            }
        }

        return max(0, min(100, $score));
    }

    private function grade(int $score, string $status): string
    {
        if ($status === 'blocked') {
            return $score >= 70 ? 'C' : 'D';
        }

        return match (true) {
            $score >= 95 => 'A+',
            $score >= 85 => 'A',
            $score >= 75 => 'B',
            $score >= 65 => 'C',
            default => 'D',
        };
    }
}
