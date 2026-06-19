<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\ReleaseGate;
use App\Models\ReleaseGateEvent;
use App\Models\ReleaseGateItem;
use App\Models\ReleaseReadinessRun;
use App\Models\TestRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReleaseGateWorkflowService
{
    public function __construct(private readonly ReleaseReadinessService $readinessService)
    {
    }

    public function summary(Project $project): array
    {
        $latest = Schema::hasTable('release_gates')
            ? $project->releaseGates()->with(['createdBy', 'finalizedBy'])->latest()->first()
            : null;

        return [
            'latest_gate' => $latest,
            'gate_count' => Schema::hasTable('release_gates') ? $project->releaseGates()->count() : 0,
            'open_gate_count' => Schema::hasTable('release_gates') ? $project->releaseGates()->whereIn('status', ['needs_review', 'blocked', 'ready', 'conditional_go'])->count() : 0,
            'approved_count' => Schema::hasTable('release_gates') ? $project->releaseGates()->where('status', 'approved')->count() : 0,
        ];
    }

    public function create(Project $project, ?User $user, array $data): ReleaseGate
    {
        return DB::transaction(function () use ($project, $user, $data): ReleaseGate {
            $readinessRun = $this->readinessService->createRun($project, $user, $data['decision_note'] ?? null);
            $sourceState = $this->sourceState($project, $readinessRun);

            $gate = $project->releaseGates()->create([
                'release_readiness_run_id' => $readinessRun->id,
                'created_by_user_id' => $user?->id,
                'title' => $data['title'],
                'release_version' => $data['release_version'] ?? null,
                'target_environment' => $data['target_environment'] ?? null,
                'gate_profile' => $data['gate_profile'] ?? 'standard',
                'score' => $readinessRun->score,
                'grade' => $readinessRun->grade,
                'evidence_count' => $sourceState['evidence']['total'] ?? 0,
                'verified_evidence_count' => $sourceState['evidence']['verified'] ?? 0,
                'test_run_count' => $sourceState['tests']['total'] ?? 0,
                'failed_test_run_count' => $sourceState['tests']['failed'] ?? 0,
                'open_finding_count' => $sourceState['findings']['open'] ?? 0,
                'high_critical_open_count' => $sourceState['findings']['high_critical'] ?? 0,
                'source_state_json' => $sourceState,
                'decision_note' => $data['decision_note'] ?? null,
                'evaluated_at' => now(),
            ]);

            $this->createItemsFromRun($gate, $readinessRun, $sourceState);
            $this->recalculate($gate->fresh(['items']));
            $this->recordEvent($gate->fresh(), 'created', __('messages.release_gates.events.created'), $user, 'info');

            return $gate->fresh(['items', 'readinessRun', 'events']);
        });
    }

    public function updateItem(ReleaseGateItem $item, ?User $user, array $data): ReleaseGateItem
    {
        return DB::transaction(function () use ($item, $user, $data): ReleaseGateItem {
            $manualState = $data['manual_state'] ?: null;
            $item->update([
                'manual_state' => $manualState,
                'effective_state' => $this->effectiveState($item->automated_state, $manualState),
                'reviewer_note' => $data['reviewer_note'] ?? null,
                'reviewed_by_user_id' => $user?->id,
                'reviewed_at' => now(),
            ]);

            $gate = $item->gate()->with('items')->first();
            if ($gate) {
                $this->recalculate($gate);
                $this->recordEvent($gate, 'item_reviewed', __('messages.release_gates.events.item_reviewed', ['item' => $item->label]), $user, $item->effective_state === 'blocked' ? 'warning' : 'info', $item);
            }

            return $item->fresh(['reviewedBy']);
        });
    }

    public function finalize(ReleaseGate $gate, ?User $user, array $data): ReleaseGate
    {
        return DB::transaction(function () use ($gate, $user, $data): ReleaseGate {
            $gate->loadMissing('items');
            $finalDecision = $data['final_decision'];
            $blockedCount = $gate->items->where('effective_state', 'blocked')->count();

            if ($finalDecision === 'go' && $blockedCount > 0) {
                throw new \RuntimeException(__('messages.release_gates.errors.go_blocked'));
            }

            $gate->update([
                'final_decision' => $finalDecision,
                'status' => match ($finalDecision) {
                    'go' => 'approved',
                    'conditional_go' => 'conditional_go',
                    'no_go' => 'rejected',
                    default => 'needs_review',
                },
                'decision_note' => $data['decision_note'] ?? null,
                'finalized_by_user_id' => $user?->id,
                'finalized_at' => now(),
            ]);

            $this->recordEvent($gate->fresh(), 'finalized', __('messages.release_gates.events.finalized', [
                'decision' => __('messages.release_gates.final_decisions.'.$finalDecision),
            ]), $user, $finalDecision === 'no_go' ? 'warning' : 'info');

            return $gate->fresh(['items', 'events', 'finalizedBy']);
        });
    }

    public function recalculate(ReleaseGate $gate): ReleaseGate
    {
        $items = $gate->items;
        $blockers = $items->where('effective_state', 'blocked')->count();
        $warnings = $items->whereIn('effective_state', ['warning', 'waived'])->count();
        $passed = $items->where('effective_state', 'pass')->count();
        $total = $items->count();

        $automatedDecision = $blockers > 0 ? 'blocked' : ($warnings > 0 ? 'warning' : 'pass');
        $status = $gate->final_decision !== 'pending'
            ? $gate->status
            : ($blockers > 0 ? 'blocked' : ($warnings > 0 ? 'needs_review' : 'ready'));

        $gate->update([
            'status' => $status,
            'automated_decision' => $automatedDecision,
            'blocker_count' => $blockers,
            'warning_count' => $warnings,
            'passed_item_count' => $passed,
            'total_item_count' => $total,
            'summary_json' => [
                'headline' => __('messages.release_gates.summary.'.$automatedDecision),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        return $gate->fresh(['items']);
    }

    private function createItemsFromRun(ReleaseGate $gate, ReleaseReadinessRun $run, array $sourceState): void
    {
        $sort = 10;
        foreach ($run->checks as $check) {
            $state = ($check['passed'] ?? false) ? 'pass' : (($check['level'] ?? 'warning') === 'blocker' ? 'blocked' : 'warning');
            $gate->items()->create([
                'project_id' => $gate->project_id,
                'item_key' => 'readiness_'.$check['key'],
                'category' => $this->categoryForCheck((string) ($check['key'] ?? 'readiness'), (string) ($check['rule_category'] ?? '')),
                'label' => (string) ($check['label'] ?? $check['key']),
                'icon' => $this->iconForCheck((string) ($check['key'] ?? ''), (string) ($check['icon'] ?? 'workflow')),
                'automated_state' => $state,
                'effective_state' => $state,
                'severity' => ($check['level'] ?? 'warning') === 'blocker' ? 'blocker' : 'warning',
                'source_type' => 'release_readiness_check',
                'source_id' => $run->id,
                'required_action' => (string) ($check['hint'] ?? ''),
                'sort_order' => $sort,
                'metadata_json' => $check,
            ]);
            $sort += 10;
        }

        foreach ($this->sourceStateItems($gate, $sourceState) as $item) {
            $gate->items()->create($item + ['sort_order' => $sort]);
            $sort += 10;
        }
    }

    private function sourceState(Project $project, ReleaseReadinessRun $run): array
    {
        $evidenceQuery = Schema::hasTable('finding_evidence') ? $project->evidence() : null;
        $testQuery = Schema::hasTable('test_runs') ? $project->testRuns() : null;
        $findingQuery = Schema::hasTable('findings') ? $project->findings() : null;

        $evidenceTotal = $evidenceQuery ? (clone $evidenceQuery)->count() : 0;
        $verifiedEvidence = $evidenceQuery && Schema::hasColumn('finding_evidence', 'repository_status')
            ? (clone $evidenceQuery)->where('repository_status', FindingEvidence::STATUS_VERIFIED)->count()
            : 0;
        $archivedEvidence = $evidenceQuery && Schema::hasColumn('finding_evidence', 'repository_status')
            ? (clone $evidenceQuery)->where('repository_status', FindingEvidence::STATUS_ARCHIVED)->count()
            : 0;

        $testTotal = $testQuery ? (clone $testQuery)->count() : 0;
        $testFailed = $testQuery ? (clone $testQuery)->where('status', 'fail')->count() : 0;
        $testBlocked = $testQuery ? (clone $testQuery)->where('status', 'blocked')->count() : 0;

        $openStatuses = ['open', 'confirmed', 'triaged', 'in_progress', 'ready_for_retest', 'retest_failed'];
        $openFindings = $findingQuery ? (clone $findingQuery)->whereIn('status', $openStatuses)->count() : 0;
        $highCritical = $findingQuery ? (clone $findingQuery)->whereIn('status', $openStatuses)->whereIn('severity', ['high', 'critical'])->count() : 0;

        return [
            'readiness' => [
                'run_id' => $run->id,
                'status' => $run->status,
                'score' => $run->score,
                'grade' => $run->grade,
                'blockers' => $run->blocker_count,
                'warnings' => $run->warning_count,
            ],
            'evidence' => [
                'total' => $evidenceTotal,
                'verified' => $verifiedEvidence,
                'archived' => $archivedEvidence,
                'verification_rate' => $evidenceTotal > 0 ? round(($verifiedEvidence / max(1, $evidenceTotal - $archivedEvidence)) * 100) : 0,
            ],
            'tests' => [
                'total' => $testTotal,
                'failed' => $testFailed,
                'blocked' => $testBlocked,
                'clean' => $testFailed === 0 && $testBlocked === 0,
            ],
            'findings' => [
                'open' => $openFindings,
                'high_critical' => $highCritical,
                'clean' => $highCritical === 0,
            ],
        ];
    }

    private function sourceStateItems(ReleaseGate $gate, array $state): array
    {
        $items = [];
        $verified = (int) ($state['evidence']['verified'] ?? 0);
        $evidence = (int) ($state['evidence']['total'] ?? 0);
        $verificationRate = (int) ($state['evidence']['verification_rate'] ?? 0);
        $failedTests = (int) ($state['tests']['failed'] ?? 0) + (int) ($state['tests']['blocked'] ?? 0);
        $highCritical = (int) ($state['findings']['high_critical'] ?? 0);

        $items[] = [
            'project_id' => $gate->project_id,
            'item_key' => 'repository_verified_evidence',
            'category' => 'evidence',
            'label' => __('messages.release_gates.generated_items.verified_evidence'),
            'icon' => 'fingerprint',
            'automated_state' => $evidence === 0 ? 'warning' : ($verified > 0 ? 'pass' : 'warning'),
            'effective_state' => $evidence === 0 ? 'warning' : ($verified > 0 ? 'pass' : 'warning'),
            'severity' => 'warning',
            'evidence_count' => $verified,
            'required_action' => __('messages.release_gates.generated_items.verified_evidence_action'),
            'metadata_json' => ['verified' => $verified, 'total' => $evidence, 'verification_rate' => $verificationRate],
        ];

        $items[] = [
            'project_id' => $gate->project_id,
            'item_key' => 'native_test_runs_clean',
            'category' => 'tests',
            'label' => __('messages.release_gates.generated_items.native_tests'),
            'icon' => 'flask-conical',
            'automated_state' => $failedTests > 0 ? 'blocked' : ((int) ($state['tests']['total'] ?? 0) > 0 ? 'pass' : 'warning'),
            'effective_state' => $failedTests > 0 ? 'blocked' : ((int) ($state['tests']['total'] ?? 0) > 0 ? 'pass' : 'warning'),
            'severity' => $failedTests > 0 ? 'blocker' : 'warning',
            'required_action' => __('messages.release_gates.generated_items.native_tests_action'),
            'metadata_json' => $state['tests'] ?? [],
        ];

        $items[] = [
            'project_id' => $gate->project_id,
            'item_key' => 'open_high_critical_findings',
            'category' => 'findings',
            'label' => __('messages.release_gates.generated_items.high_critical_findings'),
            'icon' => 'octagon-alert',
            'automated_state' => $highCritical > 0 ? 'blocked' : 'pass',
            'effective_state' => $highCritical > 0 ? 'blocked' : 'pass',
            'severity' => 'blocker',
            'required_action' => __('messages.release_gates.generated_items.high_critical_findings_action'),
            'metadata_json' => $state['findings'] ?? [],
        ];

        return $items;
    }

    private function effectiveState(string $automatedState, ?string $manualState): string
    {
        if (! $manualState) {
            return $automatedState;
        }

        return in_array($manualState, ReleaseGateItem::STATES, true) ? $manualState : $automatedState;
    }

    private function categoryForCheck(string $key, string $ruleCategory = ''): string
    {
        if ($ruleCategory && in_array($ruleCategory, ReleaseGateItem::CATEGORIES, true)) {
            return $ruleCategory;
        }

        return match (true) {
            str_contains($key, 'evidence'), str_contains($key, 'retest') => 'evidence',
            str_contains($key, 'test'), str_contains($key, 'batch') => 'tests',
            str_contains($key, 'finding') => 'findings',
            str_contains($key, 'import') => 'imports',
            str_contains($key, 'contract') => 'contract',
            str_contains($key, 'risk') => 'risk',
            default => 'readiness',
        };
    }

    private function iconForCheck(string $key, string $fallback): string
    {
        return match (true) {
            str_contains($key, 'evidence') => 'folder-check',
            str_contains($key, 'test'), str_contains($key, 'batch') => 'flask-conical',
            str_contains($key, 'finding') => 'bug',
            str_contains($key, 'import') => 'brackets-contain',
            str_contains($key, 'contract') => 'file-check-2',
            str_contains($key, 'risk') => 'shield-alert',
            str_contains($key, 'scan') => 'radar',
            str_contains($key, 'snapshot') => 'camera',
            default => $fallback ?: 'workflow',
        };
    }

    private function recordEvent(ReleaseGate $gate, string $type, string $summary, ?User $user, string $severity = 'info', ?ReleaseGateItem $item = null): void
    {
        if (! Schema::hasTable('release_gate_events')) {
            return;
        }

        ReleaseGateEvent::create([
            'project_id' => $gate->project_id,
            'release_gate_id' => $gate->id,
            'release_gate_item_id' => $item?->id,
            'user_id' => $user?->id,
            'event_type' => $type,
            'summary' => $summary,
            'severity' => $severity,
            'metadata_json' => ['status' => $gate->status, 'final_decision' => $gate->final_decision],
            'occurred_at' => now(),
        ]);
    }
}
