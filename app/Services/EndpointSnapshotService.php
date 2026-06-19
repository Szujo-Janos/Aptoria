<?php

namespace App\Services;

use App\Models\EndpointSnapshot;
use App\Models\EndpointSnapshotCompare;
use App\Models\EndpointSnapshotCompareItem;
use App\Models\EndpointSnapshotItem;
use App\Models\Finding;
use App\Models\EndpointTestBatch;
use App\Models\EndpointTestRun;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EndpointSnapshotService
{
    public function createFromBatch(Project $project, EndpointTestBatch $batch, ?User $user = null, ?string $title = null, ?string $notes = null): EndpointSnapshot
    {
        $batch->load(['testRuns.endpoint']);
        $runs = $batch->testRuns->sortBy(fn (EndpointTestRun $run) => $this->signature($run))->values();
        $itemPayloads = $runs->map(fn (EndpointTestRun $run): array => $this->itemPayload($project, $run));
        $snapshotChecksum = $this->snapshotChecksum($itemPayloads);

        $snapshot = $project->endpointSnapshots()->create([
            'endpoint_test_batch_id' => $batch->id,
            'created_by_user_id' => $user?->id,
            'title' => $title ?: __('messages.snapshots.default_title', ['id' => $batch->id, 'date' => now()->format('Y-m-d H:i')]),
            'status' => 'captured',
            'tone' => $batch->tone ?: 'secondary',
            'total' => $runs->count(),
            'passed' => $runs->where('state', 'passed')->count(),
            'warning' => $runs->where('state', 'warning')->count(),
            'failed' => $runs->where('state', 'failed')->count(),
            'skipped' => $runs->where('state', 'skipped')->count(),
            'checksum' => $snapshotChecksum,
            'summary_json' => $this->snapshotSummary($batch, $itemPayloads, $snapshotChecksum),
            'notes' => $notes,
            'captured_at' => now(),
        ]);

        foreach ($itemPayloads as $payload) {
            $snapshot->items()->create($payload);
        }

        return $snapshot->fresh(['items', 'batch']);
    }

    public function compare(Project $project, EndpointSnapshot $baseline, EndpointSnapshot $target, ?User $user = null, ?string $notes = null): EndpointSnapshotCompare
    {
        $baseline->load('items');
        $target->load('items');

        $baselineItems = $baseline->items->keyBy('endpoint_signature');
        $targetItems = $target->items->keyBy('endpoint_signature');
        $signatures = $baselineItems->keys()->merge($targetItems->keys())->unique()->sort()->values();

        $compareItems = $signatures->map(function (string $signature) use ($project, $baselineItems, $targetItems): array {
            /** @var EndpointSnapshotItem|null $baselineItem */
            $baselineItem = $baselineItems->get($signature);
            /** @var EndpointSnapshotItem|null $targetItem */
            $targetItem = $targetItems->get($signature);

            return $this->compareItemPayload($project, $signature, $baselineItem, $targetItem);
        });

        $counts = [
            'unchanged' => $compareItems->where('change_type', 'unchanged')->count(),
            'changed' => $compareItems->where('change_type', 'changed')->count(),
            'added' => $compareItems->where('change_type', 'added')->count(),
            'removed' => $compareItems->where('change_type', 'removed')->count(),
            'regressed' => $compareItems->where('change_type', 'regressed')->count(),
            'improved' => $compareItems->where('change_type', 'improved')->count(),
        ];
        $status = $this->compareStatus($counts);
        $tone = $this->compareTone($status);

        $compare = $project->endpointSnapshotCompares()->create([
            'baseline_snapshot_id' => $baseline->id,
            'target_snapshot_id' => $target->id,
            'compared_by_user_id' => $user?->id,
            'status' => $status,
            'tone' => $tone,
            'total_items' => $compareItems->count(),
            'unchanged_count' => $counts['unchanged'],
            'changed_count' => $counts['changed'],
            'added_count' => $counts['added'],
            'removed_count' => $counts['removed'],
            'regressed_count' => $counts['regressed'],
            'improved_count' => $counts['improved'],
            'summary_json' => [
                'headline' => __('messages.snapshots.compare_headline_'.$status),
                'baseline_snapshot_id' => $baseline->id,
                'target_snapshot_id' => $target->id,
                'baseline_checksum' => $baseline->checksum,
                'target_checksum' => $target->checksum,
                'counts' => $counts,
            ],
            'notes' => $notes,
            'compared_at' => now(),
        ]);

        foreach ($compareItems as $payload) {
            $compare->items()->create($payload);
        }

        return $compare->fresh(['baselineSnapshot', 'targetSnapshot', 'items']);
    }

    public function snapshotMarkdown(EndpointSnapshot $snapshot): string
    {
        $snapshot->loadMissing(['project', 'batch', 'items']);
        $lines = [
            '# '.$snapshot->title,
            '',
            '- Project: '.$snapshot->project->name,
            '- Snapshot ID: #'.$snapshot->id,
            '- Source batch: #'.($snapshot->endpoint_test_batch_id ?: '—'),
            '- Captured at: '.($snapshot->captured_at?->toDateTimeString() ?: '—'),
            '- Checksum: '.($snapshot->checksum ?: '—'),
            '- Totals: '.$snapshot->total.' total, '.$snapshot->passed.' passed, '.$snapshot->warning.' warning, '.$snapshot->failed.' failed, '.$snapshot->skipped.' skipped',
            '',
            '## Endpoint evidence state',
            '',
            '| Endpoint | State | HTTP | Assertions | Response |',
            '|---|---:|---:|---:|---:|',
        ];

        foreach ($snapshot->items as $item) {
            $lines[] = '| '.str_replace('|', '\\|', $item->endpoint_signature).' | '.$item->state.' | '.($item->status_code ?: '—').' | '.($item->assertion_failed.'/'.$item->assertion_total).' | '.($item->response_time_ms !== null ? $item->response_time_ms.' ms' : '—').' |';
        }

        if ($snapshot->notes) {
            $lines[] = '';
            $lines[] = '## Notes';
            $lines[] = $snapshot->notes;
        }

        return implode("\n", $lines);
    }

    public function compareMarkdown(EndpointSnapshotCompare $compare): string
    {
        $compare->loadMissing(['project', 'baselineSnapshot', 'targetSnapshot', 'items']);
        $lines = [
            '# Endpoint Snapshot Regression Compare #'.$compare->id,
            '',
            '- Project: '.$compare->project->name,
            '- Baseline: #'.$compare->baseline_snapshot_id.' · '.$compare->baselineSnapshot->title,
            '- Target: #'.$compare->target_snapshot_id.' · '.$compare->targetSnapshot->title,
            '- Status: '.$compare->status,
            '- Compared at: '.($compare->compared_at?->toDateTimeString() ?: '—'),
            '- Regressions: '.$compare->regressed_count,
            '- Added: '.$compare->added_count,
            '- Removed: '.$compare->removed_count,
            '- Changed: '.$compare->changed_count,
            '- Improved: '.$compare->improved_count,
            '',
            '## Compare items',
            '',
            '| Endpoint | Change | Baseline | Target | HTTP |',
            '|---|---:|---:|---:|---:|',
        ];

        foreach ($compare->items as $item) {
            $lines[] = '| '.str_replace('|', '\\|', $item->endpoint_signature).' | '.$item->change_type.' | '.($item->baseline_state ?: '—').' | '.($item->target_state ?: '—').' | '.($item->baseline_status_code ?: '—').' → '.($item->target_status_code ?: '—').' |';
        }

        if ($compare->notes) {
            $lines[] = '';
            $lines[] = '## Notes';
            $lines[] = $compare->notes;
        }

        return implode("\n", $lines);
    }


    public function generateRegressionFindings(Project $project, EndpointSnapshotCompare $compare, ?User $user = null): array
    {
        $compare->loadMissing(['baselineSnapshot', 'targetSnapshot', 'items.baselineItem.endpoint', 'items.targetItem.endpoint']);

        $candidates = $compare->items
            ->filter(fn (EndpointSnapshotCompareItem $item): bool => in_array($item->change_type, ['regressed', 'removed'], true))
            ->values();

        $created = collect();
        $skipped = collect();

        foreach ($candidates as $item) {
            $existing = $project->findings()
                ->where('source', 'regression')
                ->where('endpoint_snapshot_compare_item_id', $item->id)
                ->first();

            if ($existing) {
                $skipped->push($existing);
                continue;
            }

            $endpoint = $item->targetItem?->endpoint ?: $item->baselineItem?->endpoint;
            $severity = $this->regressionSeverity($item);

            $finding = $project->findings()->create([
                'endpoint_id' => $endpoint?->id,
                'endpoint_snapshot_compare_id' => $compare->id,
                'endpoint_snapshot_compare_item_id' => $item->id,
                'title' => __('messages.snapshots.regression_finding_title', ['endpoint' => Str::limit($item->endpoint_signature, 120, '…')]),
                'source' => 'regression',
                'severity' => $severity,
                'status' => 'confirmed',
                'priority' => in_array($severity, ['critical', 'high'], true) ? 'high' : 'normal',
                'summary' => __('messages.snapshots.regression_finding_summary', [
                    'change' => $item->change_type_label,
                    'baseline' => $compare->baselineSnapshot?->title ?: '#'.$compare->baseline_snapshot_id,
                    'target' => $compare->targetSnapshot?->title ?: '#'.$compare->target_snapshot_id,
                ]),
                'reproduction_steps' => $this->regressionReproductionSteps($item, $compare),
                'expected_result' => __('messages.snapshots.regression_finding_expected', [
                    'baseline_state' => $item->baseline_state ?: '—',
                    'baseline_http' => $item->baseline_status_code ?: '—',
                ]),
                'actual_result' => __('messages.snapshots.regression_finding_actual', [
                    'target_state' => $item->target_state ?: '—',
                    'target_http' => $item->target_status_code ?: '—',
                ]),
                'recommendation' => __('messages.snapshots.regression_finding_recommendation'),
                'evidence_required' => true,
                'retest_required' => true,
                'retest_status' => 'required',
                'retest_requested_at' => now(),
                'metadata_json' => [
                    'source' => 'endpoint_snapshot_compare',
                    'endpoint_snapshot_compare_id' => $compare->id,
                    'endpoint_snapshot_compare_item_id' => $item->id,
                    'baseline_snapshot_id' => $compare->baseline_snapshot_id,
                    'target_snapshot_id' => $compare->target_snapshot_id,
                    'endpoint_signature' => $item->endpoint_signature,
                    'change_type' => $item->change_type,
                    'baseline_state' => $item->baseline_state,
                    'target_state' => $item->target_state,
                    'baseline_status_code' => $item->baseline_status_code,
                    'target_status_code' => $item->target_status_code,
                    'generated_by_user_id' => $user?->id,
                    'generated_at' => now()->toDateTimeString(),
                ],
            ]);

            $created->push($finding);
        }

        $linkedCount = $project->findings()
            ->where('source', 'regression')
            ->where('endpoint_snapshot_compare_id', $compare->id)
            ->count();

        $summary = [
            'candidate_count' => $candidates->count(),
            'created_count' => $created->count(),
            'skipped_existing_count' => $skipped->count(),
            'linked_count' => $linkedCount,
            'finding_ids' => $project->findings()
                ->where('source', 'regression')
                ->where('endpoint_snapshot_compare_id', $compare->id)
                ->pluck('id')
                ->values()
                ->all(),
            'generated_by_user_id' => $user?->id,
            'generated_at' => now()->toDateTimeString(),
        ];

        $compare->forceFill([
            'regression_finding_count' => $linkedCount,
            'regression_findings_generated_at' => now(),
            'regression_finding_summary_json' => $summary,
        ])->save();

        return [
            'created' => $created,
            'skipped' => $skipped,
            'candidate_count' => $candidates->count(),
            'linked_count' => $linkedCount,
            'summary' => $summary,
        ];
    }

    private function regressionSeverity(EndpointSnapshotCompareItem $item): string
    {
        if ($item->change_type === 'removed') {
            return 'high';
        }

        if ((string) $item->target_state === 'failed' || (int) $item->target_status_code >= 500) {
            return 'critical';
        }

        return 'high';
    }

    private function regressionReproductionSteps(EndpointSnapshotCompareItem $item, EndpointSnapshotCompare $compare): string
    {
        return implode("\n", [
            '1. Open endpoint snapshot compare #'.$compare->id.'.',
            '2. Review compare item #'.$item->id.' for '.$item->endpoint_signature.'.',
            '3. Re-run the endpoint quick test or batch test against the target environment.',
            '4. Attach retest evidence before changing this finding to verified.',
        ]);
    }

    private function itemPayload(Project $project, EndpointTestRun $run): array
    {
        $signature = $this->signature($run);
        $payload = [
            'endpoint_id' => $run->endpoint_id,
            'endpoint_signature' => $signature,
            'endpoint_name' => $run->endpoint?->name,
            'method' => $run->method,
            'path' => $run->endpoint?->path ?: $this->pathFromUrl((string) $run->url),
            'url' => $run->url,
            'state' => $run->state ?: 'skipped',
            'tone' => $run->tone ?: 'secondary',
            'status_code' => $run->status_code,
            'content_type' => $run->content_type,
            'response_time_ms' => $run->response_time_ms,
            'response_size' => $run->response_size,
            'assertion_total' => (int) $run->assertion_total,
            'assertion_failed' => (int) $run->assertion_failed,
            'evidence_json' => [
                'test_run_id' => $run->id,
                'checked_at' => $run->checked_at?->toDateTimeString(),
                'expected_status' => $run->expected_status,
                'status_matched' => $run->status_matched,
                'expected_content_type' => $run->expected_content_type,
                'content_type_matched' => $run->content_type_matched,
                'message' => $run->message,
            ],
        ];

        $payload['item_checksum'] = $this->itemChecksum($payload);
        $payload['project_id'] = $project->id;

        return $payload;
    }

    private function compareItemPayload(Project $project, string $signature, ?EndpointSnapshotItem $baselineItem, ?EndpointSnapshotItem $targetItem): array
    {
        $changeType = $this->changeType($baselineItem, $targetItem);
        $tone = match ($changeType) {
            'regressed', 'removed' => 'danger',
            'changed', 'added' => 'warning',
            'improved' => 'success',
            default => 'secondary',
        };

        return [
            'project_id' => $project->id,
            'baseline_item_id' => $baselineItem?->id,
            'target_item_id' => $targetItem?->id,
            'endpoint_signature' => $signature,
            'method' => $targetItem?->method ?: $baselineItem?->method,
            'path' => $targetItem?->path ?: $baselineItem?->path,
            'change_type' => $changeType,
            'tone' => $tone,
            'baseline_state' => $baselineItem?->state,
            'target_state' => $targetItem?->state,
            'baseline_status_code' => $baselineItem?->status_code,
            'target_status_code' => $targetItem?->status_code,
            'baseline_checksum' => $baselineItem?->item_checksum,
            'target_checksum' => $targetItem?->item_checksum,
            'summary_json' => [
                'baseline_assertion_failed' => $baselineItem?->assertion_failed,
                'target_assertion_failed' => $targetItem?->assertion_failed,
                'baseline_response_time_ms' => $baselineItem?->response_time_ms,
                'target_response_time_ms' => $targetItem?->response_time_ms,
            ],
        ];
    }

    private function changeType(?EndpointSnapshotItem $baselineItem, ?EndpointSnapshotItem $targetItem): string
    {
        if (! $baselineItem && $targetItem) {
            return 'added';
        }

        if ($baselineItem && ! $targetItem) {
            return 'removed';
        }

        if (! $baselineItem || ! $targetItem) {
            return 'changed';
        }

        if ($baselineItem->item_checksum === $targetItem->item_checksum) {
            return 'unchanged';
        }

        if ($this->stateRank((string) $targetItem->state) < $this->stateRank((string) $baselineItem->state)) {
            return 'regressed';
        }

        if ((int) $targetItem->assertion_failed > (int) $baselineItem->assertion_failed) {
            return 'regressed';
        }

        if ($this->stateRank((string) $targetItem->state) > $this->stateRank((string) $baselineItem->state)) {
            return 'improved';
        }

        if ((int) $targetItem->assertion_failed < (int) $baselineItem->assertion_failed) {
            return 'improved';
        }

        return 'changed';
    }

    private function stateRank(string $state): int
    {
        return match ($state) {
            'passed' => 4,
            'warning' => 3,
            'skipped' => 2,
            'failed' => 1,
            default => 0,
        };
    }

    private function compareStatus(array $counts): string
    {
        if (($counts['regressed'] ?? 0) > 0 || ($counts['removed'] ?? 0) > 0) {
            return 'blocked';
        }

        if (($counts['changed'] ?? 0) > 0 || ($counts['added'] ?? 0) > 0) {
            return 'warning';
        }

        return 'passed';
    }

    private function compareTone(string $status): string
    {
        return match ($status) {
            'blocked' => 'danger',
            'warning' => 'warning',
            default => 'success',
        };
    }

    private function signature(EndpointTestRun $run): string
    {
        $method = strtoupper((string) ($run->method ?: $run->endpoint?->method ?: 'GET'));
        $path = $run->endpoint?->path ?: $this->pathFromUrl((string) $run->url);

        return trim($method.' '.$path);
    }

    private function pathFromUrl(string $url): string
    {
        if ($url === '') {
            return '/';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $parsed = parse_url($url);
            return ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?'.$parsed['query'] : '');
        }

        return Str::startsWith($url, '/') ? $url : '/'.$url;
    }

    private function itemChecksum(array $payload): string
    {
        return hash('sha256', json_encode([
            'signature' => $payload['endpoint_signature'],
            'state' => $payload['state'],
            'status_code' => $payload['status_code'],
            'content_type' => $payload['content_type'],
            'assertion_total' => $payload['assertion_total'],
            'assertion_failed' => $payload['assertion_failed'],
            'response_size' => $payload['response_size'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function snapshotChecksum(Collection $itemPayloads): string
    {
        return hash('sha256', $itemPayloads->pluck('item_checksum')->sort()->implode('|'));
    }

    private function snapshotSummary(EndpointTestBatch $batch, Collection $itemPayloads, string $snapshotChecksum): array
    {
        return [
            'source' => 'endpoint_test_batch',
            'source_batch_id' => $batch->id,
            'source_state' => $batch->state,
            'source_completed_at' => $batch->completed_at?->toDateTimeString(),
            'checksum' => $snapshotChecksum,
            'endpoint_signatures' => $itemPayloads->pluck('endpoint_signature')->values()->all(),
        ];
    }
}
