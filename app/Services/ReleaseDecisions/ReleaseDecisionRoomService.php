<?php

namespace App\Services\ReleaseDecisions;

use App\Models\Finding;
use App\Models\Project;
use App\Models\QaReleaseGate;
use App\Models\ReleaseDecision;
use App\Models\RiskAcceptance;
use App\Models\ScanResult;
use App\Models\User;
use App\Services\Exports\ExportCreditService;
use App\Services\ReleaseReadinessService;
use App\Services\Security\SensitiveValueMasker;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReleaseDecisionRoomService
{
    public function __construct(
        private readonly ReleaseReadinessService $readiness,
        private readonly SensitiveValueMasker $masker,
        private readonly ExportCreditService $credits,
    ) {
    }

    /** @return array<string, mixed> */
    public function summarize(Project $project): array
    {
        $project->loadMissing(['latestReleaseDecision.owner', 'latestQaReleaseGate']);
        $summary = $this->readiness->summarize($project);
        $latestDecision = $project->latestReleaseDecision;
        $latestGate = $project->latestQaReleaseGate;
        $package = $this->buildPackage($project, $summary, $latestGate);
        $recommendedStatus = $this->recommendedStatus($summary, $latestGate);

        return [
            'summary' => $summary,
            'latest_decision' => $latestDecision,
            'latest_release_gate' => $latestGate,
            'current_package' => $package,
            'package_checksum' => $this->checksum($package),
            'recommended_status' => $recommendedStatus,
            'recommended_status_label' => __('messages.release_decisions.statuses.'.$recommendedStatus),
            'recommended_status_css' => $this->statusCss($recommendedStatus),
            'can_go' => $recommendedStatus === ReleaseDecision::STATUS_GO,
            'needs_decision' => ! $latestDecision || optional($latestDecision->decided_at)->lt(now()->subDays(7)),
            'status_options' => ReleaseDecision::STATUSES,
        ];
    }

    /** @param array<string, mixed> $data */
    public function createDecision(Project $project, array $data, ?User $owner = null): ReleaseDecision
    {
        $summary = $this->readiness->summarize($project);
        $latestGate = $project->latestQaReleaseGate;
        $package = $this->buildPackage($project, $summary, $latestGate);
        $status = (string) ($data['decision_status'] ?? $this->recommendedStatus($summary, $latestGate));

        if (! in_array($status, ReleaseDecision::STATUSES, true)) {
            $status = ReleaseDecision::STATUS_PENDING_EVIDENCE;
        }

        $decision = $project->releaseDecisions()->create([
            'decision_owner_user_id' => $owner?->id,
            'qa_release_gate_id' => $latestGate?->id,
            'release_name' => $data['release_name'] ?? ($project->name.' '.now()->format('Y-m-d').' release decision'),
            'target_environment' => $data['target_environment'] ?? ($project->defaultEnvironment()?->name),
            'decision_status' => $status,
            'decided_at' => $status === ReleaseDecision::STATUS_PENDING_EVIDENCE ? null : now(),
            'decision_notes' => $data['decision_notes'] ?? null,
            'release_score' => $summary['score'] ?? 0,
            'readiness_status' => $summary['status'] ?? null,
            'blocker_count' => count($summary['blocking_issues'] ?? []),
            'warning_count' => count($summary['warnings'] ?? []),
            'accepted_risk_count' => (int) ($summary['risk_acceptances']['summary']['active'] ?? 0),
            'blind_spot_count' => (int) ($summary['blind_spots']['summary']['total'] ?? 0),
            'decision_package_json' => $package,
            'package_checksum' => $this->checksum($package),
        ]);

        return $decision->load(['project', 'owner', 'releaseGate']);
    }

    /** @return array<string, mixed> */
    public function buildPackage(Project $project, array $summary, ?QaReleaseGate $latestGate = null): array
    {
        $latestScan = $summary['latest_scan'] ?? $project->scanRuns()->latest()->first();
        $latestSnapshot = $summary['latest_snapshot'] ?? $project->snapshots()->latest()->first();
        $latestCompare = $summary['latest_compare'] ?? $project->compareRuns()->latest()->first();
        $latestContract = $summary['latest_contract_validation'] ?? $project->contractValidationRuns()->latest()->first();

        return [
            'package_version' => '1.1.24',
            'generated_at' => now()->toIso8601String(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'base_url' => $project->display_base_url,
            ],
            'release_readiness' => [
                'status' => $summary['status'] ?? null,
                'label' => $summary['label'] ?? null,
                'score' => $summary['score'] ?? 0,
                'grade' => $summary['grade'] ?? null,
                'blocker_count' => count($summary['blocking_issues'] ?? []),
                'warning_count' => count($summary['warnings'] ?? []),
                'blocking_issues' => array_values($summary['blocking_issues'] ?? []),
                'warnings' => array_values($summary['warnings'] ?? []),
            ],
            'evidence_ids' => [
                'latest_scan_run_id' => $latestScan?->id,
                'latest_snapshot_id' => $latestSnapshot?->id,
                'latest_compare_run_id' => $latestCompare?->id,
                'latest_contract_validation_run_id' => $latestContract?->id,
                'latest_release_gate_id' => $latestGate?->id,
                'scan_run_ids' => $project->scanRuns()->latest()->limit(10)->pluck('id')->all(),
                'snapshot_ids' => $project->snapshots()->latest()->limit(10)->pluck('id')->all(),
                'finding_evidence_ids' => $project->findingEvidence()->latest()->limit(50)->pluck('id')->all(),
            ],
            'latest_evidence' => [
                'scan' => $latestScan ? [
                    'id' => $latestScan->id,
                    'status' => $latestScan->status,
                    'created_at' => optional($latestScan->created_at)->toIso8601String(),
                    'scanned_count' => $latestScan->scanned_count,
                    'error_count' => $latestScan->error_count,
                ] : null,
                'snapshot' => $latestSnapshot ? [
                    'id' => $latestSnapshot->id,
                    'name' => $latestSnapshot->name,
                    'created_at' => optional($latestSnapshot->created_at)->toIso8601String(),
                ] : null,
                'schema_drift' => $this->schemaDriftEvidence($project),
                'no_auth_comparison' => $this->noAuthEvidence($project),
                'release_gate' => $latestGate ? [
                    'id' => $latestGate->id,
                    'release_name' => $latestGate->release_name,
                    'automated_status' => $latestGate->automated_status,
                    'final_decision' => $latestGate->final_decision,
                    'score' => $latestGate->score,
                    'blocker_count' => $latestGate->blocker_count,
                    'warning_count' => $latestGate->warning_count,
                ] : null,
            ],
            'finding_state_snapshot' => $this->findingStateSnapshot($project),
            'blind_spot_summary' => $summary['blind_spots']['summary'] ?? [],
            'blind_spots' => $this->blindSpotRows($summary['blind_spots']['top_items'] ?? collect()),
            'accepted_risk_ledger' => $this->riskAcceptanceRows($project),
            'accepted_risk_summary' => $summary['risk_acceptances']['summary'] ?? [],
            'score_components' => collect($summary['score_components'] ?? [])->map(fn (array $component): array => [
                'key' => $component['key'] ?? null,
                'label' => $component['label'] ?? null,
                'earned_points' => $component['earned_points'] ?? 0,
                'max_points' => $component['max_points'] ?? 0,
                'status' => $component['status'] ?? null,
            ])->values()->all(),
        ];
    }

    public function markdown(ReleaseDecision $decision): string
    {
        $decision->loadMissing(['project', 'owner', 'releaseGate']);
        $package = $decision->decision_package_json ?? [];
        $readiness = $package['release_readiness'] ?? [];
        $evidence = $package['latest_evidence'] ?? [];
        $ids = $package['evidence_ids'] ?? [];
        $findingState = $package['finding_state_snapshot'] ?? [];
        $acceptedRiskSummary = $package['accepted_risk_summary'] ?? [];
        $blindSpotSummary = $package['blind_spot_summary'] ?? [];

        $lines = [];
        $lines[] = '# Aptoria Release Decision Package';
        $lines[] = '';
        $lines[] = '**Project:** '.$this->md($decision->project->name);
        foreach ($this->credits->projectBrandingMarkdownLines($decision->project) as $brandingLine) {
            $lines[] = $this->mdBrandingLine($brandingLine);
        }
        if (($disclaimer = $this->credits->projectDisclaimerMarkdown($decision->project)) !== '') {
            $lines[] = '**Disclaimer:** '.$this->md($disclaimer);
        }
        $lines[] = '**Release:** '.$this->md($decision->release_name ?: 'n/a');
        $lines[] = '**Target environment:** '.$this->md($decision->target_environment ?: 'n/a');
        $lines[] = '**Decision:** '.$this->md($decision->status_label);
        $lines[] = '**Decision owner:** '.$this->md($decision->owner?->name ?: 'n/a');
        $lines[] = '**Decision timestamp:** '.$this->md($decision->decided_at?->format('Y-m-d H:i:s') ?: 'pending');
        $lines[] = '**Package checksum:** '.$this->md($decision->package_checksum ?: 'n/a');
        $lines[] = '**Aptoria version:** '.config('aptoria.version');
        $lines[] = '';

        if ($decision->decision_notes) {
            $lines[] = '## Decision Notes';
            $lines[] = '';
            $lines[] = $this->paragraph($decision->decision_notes);
            $lines[] = '';
        }

        $lines[] = '## Release Decision Summary';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Readiness status | '.$this->md((string) ($readiness['label'] ?? $decision->readiness_status ?? 'n/a')).' |';
        $lines[] = '| Release score | '.($readiness['score'] ?? $decision->release_score).' / 100 |';
        $lines[] = '| Grade | '.$this->md((string) ($readiness['grade'] ?? 'n/a')).' |';
        $lines[] = '| Blockers | '.($readiness['blocker_count'] ?? $decision->blocker_count).' |';
        $lines[] = '| Warnings | '.($readiness['warning_count'] ?? $decision->warning_count).' |';
        $lines[] = '| Blind spots | '.($blindSpotSummary['total'] ?? $decision->blind_spot_count).' |';
        $lines[] = '| Accepted risks | '.($acceptedRiskSummary['active'] ?? $decision->accepted_risk_count).' |';
        $lines[] = '| Expired accepted risks | '.($acceptedRiskSummary['expired'] ?? 0).' |';
        $lines[] = '';

        $this->appendList($lines, 'Blocking Items', $readiness['blocking_issues'] ?? []);
        $this->appendList($lines, 'Warnings', $readiness['warnings'] ?? []);

        $lines[] = '## Evidence Chain';
        $lines[] = '';
        $lines[] = '| Evidence | Value |';
        $lines[] = '|---|---|';
        $lines[] = '| Latest scan run | '.$this->md((string) ($ids['latest_scan_run_id'] ?? 'n/a')).' |';
        $lines[] = '| Latest snapshot | '.$this->md((string) ($ids['latest_snapshot_id'] ?? 'n/a')).' |';
        $lines[] = '| Latest compare run | '.$this->md((string) ($ids['latest_compare_run_id'] ?? 'n/a')).' |';
        $lines[] = '| Latest contract validation | '.$this->md((string) ($ids['latest_contract_validation_run_id'] ?? 'n/a')).' |';
        $lines[] = '| Latest release gate | '.$this->md((string) ($ids['latest_release_gate_id'] ?? 'n/a')).' |';
        $lines[] = '| Schema drift evidence | '.$this->md($this->evidenceLabel($evidence['schema_drift'] ?? null)).' |';
        $lines[] = '| No-auth comparison evidence | '.$this->md($this->evidenceLabel($evidence['no_auth_comparison'] ?? null)).' |';
        $lines[] = '';

        $lines[] = '## Finding State Snapshot';
        $lines[] = '';
        $lines[] = '| Status | Count |';
        $lines[] = '|---|---:|';
        foreach ($findingState as $status => $count) {
            $label = __('messages.findings.statuses.'.$status);
            $lines[] = '| '.$this->md($label).' | '.$count.' |';
        }
        $lines[] = '';

        $this->appendBlindSpotRows($lines, $package['blind_spots'] ?? []);
        $this->appendRiskRows($lines, $package['accepted_risk_ledger'] ?? []);

        $lines[] = '_This decision package is a saved evidence snapshot. It does not execute HTTP requests._';
        $lines[] = '';
        $this->credits->appendMarkdownFooter($lines, 'release_decision_package', $decision->project);

        return implode("\n", $lines)."\n";
    }

    /** @return array<string, mixed> */
    private function schemaDriftEvidence(Project $project): array
    {
        $result = $project->scanResults()
            ->whereNotNull('schema_drift_summary_json')
            ->latest('scan_results.created_at')
            ->first();

        return [
            'present' => $result instanceof ScanResult,
            'scan_result_id' => $result?->id,
            'detected' => (bool) ($result?->schema_drift_detected ?? false),
            'summary' => $result?->schema_drift_summary_label,
        ];
    }

    /** @return array<string, mixed> */
    private function noAuthEvidence(Project $project): array
    {
        $result = $project->scanResults()
            ->whereNotNull('broken_auth_summary_json')
            ->latest('scan_results.created_at')
            ->first();

        return [
            'present' => $result instanceof ScanResult,
            'scan_result_id' => $result?->id,
            'detected' => (bool) ($result?->broken_auth_detected ?? false),
            'summary' => $result?->broken_auth_summary_label,
        ];
    }

    /** @return array<string, int> */
    private function findingStateSnapshot(Project $project): array
    {
        $counts = $project->findings()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(Finding::LIFECYCLE_STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    /** @param Collection<int, array<string, mixed>>|mixed $items @return array<int, array<string, mixed>> */
    private function blindSpotRows(mixed $items): array
    {
        if (! $items instanceof Collection) {
            return [];
        }

        return $items->take(25)->map(fn (array $item): array => [
            'severity' => $item['severity'] ?? null,
            'severity_label' => $item['severity_label'] ?? null,
            'type' => $item['type'] ?? null,
            'type_label' => $item['type_label'] ?? null,
            'related_label' => $item['related_label'] ?? null,
            'reason' => $item['reason'] ?? null,
            'suggested_action' => $item['suggested_action'] ?? null,
            'release_blocker' => (bool) ($item['release_blocker'] ?? false),
        ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function riskAcceptanceRows(Project $project): array
    {
        return $project->riskAcceptances()
            ->with(['finding', 'acceptedBy'])
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (RiskAcceptance $acceptance): array => [
                'id' => $acceptance->id,
                'finding' => $acceptance->finding?->title,
                'severity' => $acceptance->finding?->severity,
                'status' => $acceptance->computed_status,
                'accepted_by' => $acceptance->acceptedBy?->name,
                'accepted_until' => optional($acceptance->accepted_until)->format('Y-m-d'),
                'reason' => $acceptance->reason,
                'release_scope' => $acceptance->release_scope,
                'expiry_action' => $acceptance->expiry_action,
            ])->values()->all();
    }

    private function recommendedStatus(array $summary, ?QaReleaseGate $latestGate): string
    {
        if (count($summary['blocking_issues'] ?? []) > 0 || ($summary['status'] ?? null) === ReleaseReadinessService::STATUS_FAIL || ($latestGate?->automated_status === QaReleaseGate::STATUS_BLOCKED)) {
            return ReleaseDecision::STATUS_BLOCKED;
        }

        if (count($summary['warnings'] ?? []) > 0 || ($summary['status'] ?? null) === ReleaseReadinessService::STATUS_WARNING || ($latestGate?->automated_status === QaReleaseGate::STATUS_WARNING)) {
            return ReleaseDecision::STATUS_CONDITIONAL_GO;
        }

        if (($summary['score'] ?? 0) >= 90) {
            return ReleaseDecision::STATUS_GO;
        }

        return ReleaseDecision::STATUS_PENDING_EVIDENCE;
    }

    private function statusCss(string $status): string
    {
        return match ($status) {
            ReleaseDecision::STATUS_GO => 'success',
            ReleaseDecision::STATUS_CONDITIONAL_GO => 'warning',
            ReleaseDecision::STATUS_NO_GO, ReleaseDecision::STATUS_BLOCKED => 'danger',
            ReleaseDecision::STATUS_PENDING_EVIDENCE => 'info',
            default => 'default',
        };
    }

    /** @param array<string, mixed> $package */
    private function checksum(array $package): string
    {
        return hash('sha256', json_encode($package, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }

    private function evidenceLabel(?array $evidence): string
    {
        if (! is_array($evidence) || ! ($evidence['present'] ?? false)) {
            return 'not captured';
        }

        return '#'.($evidence['scan_result_id'] ?? 'n/a').' '.((bool) ($evidence['detected'] ?? false) ? 'issue detected' : 'captured');
    }

    /** @param array<int, string> $lines @param array<int, string> $items */
    private function appendList(array &$lines, string $title, array $items): void
    {
        $lines[] = '## '.$title;
        $lines[] = '';
        if ($items === []) {
            $lines[] = 'No items.';
            $lines[] = '';
            return;
        }

        foreach ($items as $item) {
            $lines[] = '- '.$this->md((string) $item);
        }
        $lines[] = '';
    }

    /** @param array<int, string> $lines @param array<int, array<string, mixed>> $rows */
    private function appendBlindSpotRows(array &$lines, array $rows): void
    {
        $lines[] = '## Blind Spots in Decision Package';
        $lines[] = '';
        if ($rows === []) {
            $lines[] = 'No blind spots captured in this package.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Severity | Type | Related | Release blocker | Suggested action |';
        $lines[] = '|---|---|---|---|---|';
        foreach ($rows as $row) {
            $lines[] = '| '.$this->md((string) ($row['severity_label'] ?? '')).' | '.$this->md((string) ($row['type_label'] ?? '')).' | '.$this->md((string) ($row['related_label'] ?? '')).' | '.((bool) ($row['release_blocker'] ?? false) ? 'yes' : 'no').' | '.$this->md((string) ($row['suggested_action'] ?? '')).' |';
        }
        $lines[] = '';
    }

    /** @param array<int, string> $lines @param array<int, array<string, mixed>> $rows */
    private function appendRiskRows(array &$lines, array $rows): void
    {
        $lines[] = '## Accepted Risk Ledger in Decision Package';
        $lines[] = '';
        if ($rows === []) {
            $lines[] = 'No accepted risks captured in this package.';
            $lines[] = '';
            return;
        }

        $lines[] = '| Status | Severity | Finding | Accepted until | Reason |';
        $lines[] = '|---|---|---|---|---|';
        foreach ($rows as $row) {
            $lines[] = '| '.$this->md((string) ($row['status'] ?? '')).' | '.$this->md((string) ($row['severity'] ?? 'n/a')).' | '.$this->md((string) ($row['finding'] ?? 'n/a')).' | '.$this->md((string) ($row['accepted_until'] ?? 'n/a')).' | '.$this->md((string) ($row['reason'] ?? '')).' |';
        }
        $lines[] = '';
    }

    private function mdBrandingLine(string $line): string
    {
        if (! str_contains($line, ':** ')) {
            return $this->md($line);
        }

        [$label, $value] = explode(':** ', $line, 2);

        return $label.':** '.$this->md($value);
    }

    private function paragraph(?string $value): string
    {
        return str_replace("\n", "\n\n", $this->md($value));
    }

    private function md(?string $value): string
    {
        return str_replace('|', '\\|', $this->masker->maskForExport((string) ($value ?? '')));
    }
}
