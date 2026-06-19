<?php

namespace App\Services;

use App\Models\EvidenceLifecycleEvent;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EvidenceRepositoryService
{
    public function checksumForPayload(array $payload): string
    {
        $material = $this->checksumMaterial($payload);

        return hash('sha256', json_encode($material, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function checksumForEvidence(FindingEvidence $evidence): string
    {
        return $this->checksumForPayload($evidence->only([
            'project_id',
            'finding_id',
            'endpoint_id',
            'scan_result_id',
            'test_case_id',
            'test_run_id',
            'type',
            'title',
            'source_label',
            'content',
            'url',
            'request_excerpt',
            'response_excerpt',
            'captured_at',
            'captured_by_user_id',
        ]));
    }

    public function prepareForCreate(array $data, Project $project, ?User $user): array
    {
        $data['project_id'] = $project->id;
        $data['captured_by_user_id'] = $user?->id;
        $data['captured_at'] = $data['captured_at'] ?? now();
        $data['repository_status'] = FindingEvidence::STATUS_ACTIVE;
        $data['integrity_status'] = FindingEvidence::INTEGRITY_CURRENT;
        $data['checksum_algorithm'] = FindingEvidence::CHECKSUM_ALGORITHM;
        $data['sha256'] = $this->checksumForPayload($data);

        return $data;
    }

    public function syncIntegrityState(FindingEvidence $evidence): bool
    {
        if (! $evidence->sha256) {
            $evidence->forceFill([
                'sha256' => $this->checksumForEvidence($evidence),
                'integrity_status' => FindingEvidence::INTEGRITY_CURRENT,
                'checksum_algorithm' => FindingEvidence::CHECKSUM_ALGORITHM,
            ])->save();

            return true;
        }

        $expected = $this->checksumForEvidence($evidence);
        $status = hash_equals($expected, (string) $evidence->sha256)
            ? FindingEvidence::INTEGRITY_CURRENT
            : FindingEvidence::INTEGRITY_CHANGED;

        if ($evidence->integrity_status !== $status) {
            $evidence->forceFill(['integrity_status' => $status])->save();
        }

        return $status === FindingEvidence::INTEGRITY_CURRENT;
    }

    public function recordCreated(FindingEvidence $evidence, ?User $user): void
    {
        $this->recordLifecycle($evidence, 'created', $user, [], [
            'repository_status' => $evidence->repository_status,
            'integrity_status' => $evidence->integrity_status,
            'sha256' => $evidence->sha256,
        ], __('messages.evidence.lifecycle.created'));
    }

    public function verify(FindingEvidence $evidence, User $user, ?string $notes = null): void
    {
        $this->syncIntegrityState($evidence);
        $before = $evidence->only(['repository_status', 'integrity_status', 'reviewed_by_user_id', 'reviewed_at', 'repository_notes']);

        $evidence->forceFill([
            'repository_status' => FindingEvidence::STATUS_VERIFIED,
            'reviewed_by_user_id' => $user->id,
            'reviewed_at' => now(),
            'repository_notes' => $notes ?: $evidence->repository_notes,
        ])->save();

        $this->recordLifecycle($evidence, 'verified', $user, $before, $evidence->only(['repository_status', 'integrity_status', 'reviewed_by_user_id', 'reviewed_at', 'repository_notes']), __('messages.evidence.lifecycle.verified'));
    }

    public function archive(FindingEvidence $evidence, User $user, ?string $reason = null): void
    {
        $before = $evidence->only(['repository_status', 'archived_by_user_id', 'archived_at', 'repository_notes']);

        $evidence->forceFill([
            'repository_status' => FindingEvidence::STATUS_ARCHIVED,
            'archived_by_user_id' => $user->id,
            'archived_at' => now(),
            'repository_notes' => $reason ?: $evidence->repository_notes,
        ])->save();

        $this->recordLifecycle($evidence, 'archived', $user, $before, $evidence->only(['repository_status', 'archived_by_user_id', 'archived_at', 'repository_notes']), __('messages.evidence.lifecycle.archived'));
    }

    public function restore(FindingEvidence $evidence, User $user): void
    {
        $before = $evidence->only(['repository_status', 'archived_by_user_id', 'archived_at']);

        $evidence->forceFill([
            'repository_status' => FindingEvidence::STATUS_ACTIVE,
            'archived_by_user_id' => null,
            'archived_at' => null,
        ])->save();

        $this->recordLifecycle($evidence, 'restored', $user, $before, $evidence->only(['repository_status', 'archived_by_user_id', 'archived_at']), __('messages.evidence.lifecycle.restored'));
    }

    /** @return array<string,int> */
    public function metrics(Project $project): array
    {
        $items = $project->evidence()->get();

        return [
            'total' => $items->count(),
            'active' => $items->where('repository_status', FindingEvidence::STATUS_ACTIVE)->count(),
            'verified' => $items->where('repository_status', FindingEvidence::STATUS_VERIFIED)->count(),
            'archived' => $items->where('repository_status', FindingEvidence::STATUS_ARCHIVED)->count(),
            'integrity_current' => $items->where('integrity_status', FindingEvidence::INTEGRITY_CURRENT)->count(),
            'integrity_changed' => $items->where('integrity_status', FindingEvidence::INTEGRITY_CHANGED)->count(),
            'linked' => $items->whereNotNull('finding_id')->count(),
            'http' => $items->whereIn('type', ['http', 'json_response', 'request_response'])->count(),
            'retest' => $items->where('type', 'retest')->count(),
        ];
    }

    public function recordLifecycle(FindingEvidence $evidence, string $action, ?User $user, array $before = [], array $after = [], ?string $summary = null, array $metadata = []): void
    {
        if (! Schema::hasTable('evidence_lifecycle_events')) {
            return;
        }

        EvidenceLifecycleEvent::create([
            'project_id' => $evidence->project_id,
            'finding_evidence_id' => $evidence->id,
            'user_id' => $user?->id,
            'action' => $action,
            'summary' => $summary ?: Str::headline($action),
            'before_values' => $before ?: null,
            'after_values' => $after ?: null,
            'metadata_json' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }

    private function checksumMaterial(array $payload): array
    {
        $keys = [
            'project_id',
            'finding_id',
            'endpoint_id',
            'scan_result_id',
            'test_case_id',
            'test_run_id',
            'type',
            'title',
            'source_label',
            'content',
            'url',
            'request_excerpt',
            'response_excerpt',
            'captured_at',
            'captured_by_user_id',
        ];

        $material = Arr::only($payload, $keys);

        foreach ($keys as $key) {
            $material[$key] = $this->normalizeValue($material[$key] ?? null);
        }

        ksort($material);

        return $material;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value)) {
            return trim(str_replace("\r\n", "\n", $value));
        }

        return $value;
    }
}
