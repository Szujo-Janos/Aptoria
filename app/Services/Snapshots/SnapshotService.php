<?php

namespace App\Services\Snapshots;

use App\Models\CompareItem;
use App\Models\CompareRun;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Models\SnapshotItem;
use App\Models\User;
use App\Services\AssertionEvaluationService;
use App\Services\Risk\RiskAnalyzer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SnapshotService
{
    public function __construct(
        private readonly RiskAnalyzer $riskAnalyzer,
        private readonly AssertionEvaluationService $assertions
    ) {
    }

    public function createFromScanRun(ScanRun $scanRun, ?User $user = null, ?string $name = null, ?string $description = null): Snapshot
    {
        $scanRun->loadMissing(['project.endpoints', 'environment', 'results.endpoint']);
        $project = $scanRun->project;

        $snapshot = Snapshot::query()->create([
            'project_id' => $project->id,
            'environment_id' => $scanRun->environment_id,
            'scan_run_id' => $scanRun->id,
            'created_by' => $user?->id,
            'name' => $name ?: $this->defaultSnapshotName($scanRun),
            'description' => $description,
            'endpoint_count' => 0,
            'summary_json' => [],
        ]);

        $latestResultsByEndpoint = $scanRun->results
            ->filter(fn (ScanResult $result): bool => $result->endpoint_id !== null)
            ->keyBy('endpoint_id');

        $itemHashes = [];
        foreach ($project->endpoints()->with(['environment', 'authProfile'])->orderBy('method')->orderBy('path')->get() as $endpoint) {
            $result = $latestResultsByEndpoint->get($endpoint->id);
            $payload = $this->buildSnapshotItemPayload($snapshot, $endpoint, $result);
            $itemHashes[] = $payload['source_hash'];
            SnapshotItem::query()->create($payload);
        }

        $summary = $this->summarizeSnapshot($snapshot->fresh('items'));
        $snapshot->update([
            'endpoint_count' => count($itemHashes),
            'snapshot_hash' => hash('sha256', implode('|', $itemHashes)),
            'summary_json' => $summary,
        ]);

        return $snapshot->fresh(['project', 'environment', 'scanRun', 'items']);
    }

    public function compare(Snapshot $snapshotA, Snapshot $snapshotB, ?User $user = null): CompareRun
    {
        if ($snapshotA->project_id !== $snapshotB->project_id) {
            throw new InvalidArgumentException('Snapshots must belong to the same project.');
        }

        $snapshotA->loadMissing('items');
        $snapshotB->loadMissing('items');

        $compareRun = CompareRun::query()->create([
            'project_id' => $snapshotA->project_id,
            'snapshot_a_id' => $snapshotA->id,
            'snapshot_b_id' => $snapshotB->id,
            'created_by' => $user?->id,
            'summary_json' => [],
        ]);

        $itemsA = $snapshotA->items->keyBy(fn (SnapshotItem $item): string => $this->itemKey($item));
        $itemsB = $snapshotB->items->keyBy(fn (SnapshotItem $item): string => $this->itemKey($item));
        $allKeys = $itemsA->keys()->merge($itemsB->keys())->unique()->sort()->values();

        foreach ($allKeys as $key) {
            $itemA = $itemsA->get($key);
            $itemB = $itemsB->get($key);

            if (! $itemA && $itemB) {
                $this->recordCompareItem($compareRun, CompareItem::TYPE_NEW, $itemB, null, null, __('messages.snapshots.values.missing'), $itemB->risk_label, $this->severityForRisk($itemB->risk_level));
                continue;
            }

            if ($itemA && ! $itemB) {
                $this->recordCompareItem($compareRun, CompareItem::TYPE_REMOVED, $itemA, null, null, $itemA->risk_label, __('messages.snapshots.values.missing'), CompareItem::SEVERITY_HIGH);
                continue;
            }

            if (! $itemA || ! $itemB) {
                continue;
            }

            $this->compareField($compareRun, $itemA, $itemB, 'risk_level', $itemA->risk_label, $itemB->risk_label, $this->severityForRiskChange($itemA->risk_level, $itemB->risk_level));
            $this->compareField($compareRun, $itemA, $itemB, 'status_code', $this->nullableValue($itemA->status_code), $this->nullableValue($itemB->status_code), $this->severityForStatusChange($itemA->status_code, $itemB->status_code));
            $this->compareField($compareRun, $itemA, $itemB, 'content_type', $this->nullableValue($itemA->content_type), $this->nullableValue($itemB->content_type), CompareItem::SEVERITY_REVIEW);
            $this->compareField($compareRun, $itemA, $itemB, 'auth_required', $this->boolValue($itemA->auth_required), $this->boolValue($itemB->auth_required), CompareItem::SEVERITY_HIGH);
            $this->compareResponseTime($compareRun, $itemA, $itemB);
            $this->compareSnapshotMetadata($compareRun, $itemA, $itemB);
        }

        $compareRun->update(['summary_json' => $this->summarizeCompare($compareRun->fresh('items'))]);

        return $compareRun->fresh(['snapshotA', 'snapshotB', 'items']);
    }

    private function buildSnapshotItemPayload(Snapshot $snapshot, Endpoint $endpoint, ?ScanResult $result): array
    {
        $riskAnalysis = $this->riskAnalyzer->analyze($endpoint, $result);
        $assertionEvaluation = $this->assertions->evaluate($endpoint, $result);
        $metadata = [
            'endpoint_name' => $endpoint->name,
            'tags' => $endpoint->tag_list,
            'is_active' => $endpoint->is_active,
            'excluded_from_scan' => $endpoint->excluded_from_scan,
            'scan_result_status' => $result?->status,
            'scan_result_error' => $result?->error_message,
            'auth_profile' => $result?->auth_summary ?: ($endpoint->authProfile?->masked_summary ?: null),
            'auth_applied' => (bool) ($result?->auth_applied ?? false),
            'url' => $result?->url ?: $endpoint->full_url,
            'headers' => $result?->headers_json ?: [],
            'body_preview' => $result?->body_preview,
            'response_size' => $result?->response_size,
            'risk_score' => $riskAnalysis['score'],
            'assertion_status' => $assertionEvaluation['status'],
            'failed_assertion_rules' => array_column($assertionEvaluation['failed_rules'], 'rule_label'),
            'warning_assertion_rules' => array_column($assertionEvaluation['warning_rules'], 'rule_label'),
        ];

        $source = [
            'method' => strtoupper($endpoint->method),
            'path' => $endpoint->path,
            'auth_required' => (bool) $endpoint->auth_required,
            'risk_level' => $result?->risk_level ?: $endpoint->risk_level,
            'status_code' => $result?->status_code,
            'content_type' => $result?->content_type,
            'response_time_ms' => $result?->response_time_ms,
            'expected_status' => $endpoint->expected_status,
            'expected_content_type' => $endpoint->expected_content_type,
            'metadata' => $metadata,
        ];

        return [
            'snapshot_id' => $snapshot->id,
            'endpoint_id' => $endpoint->id,
            'method' => $source['method'],
            'path' => $source['path'],
            'auth_required' => $source['auth_required'],
            'risk_level' => $source['risk_level'],
            'status_code' => $source['status_code'],
            'content_type' => $source['content_type'],
            'response_time_ms' => $source['response_time_ms'],
            'expected_status' => $source['expected_status'],
            'expected_content_type' => $source['expected_content_type'],
            'source_hash' => hash('sha256', json_encode($source, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'metadata_json' => $metadata,
        ];
    }

    private function compareField(CompareRun $compareRun, SnapshotItem $itemA, SnapshotItem $itemB, string $field, string $oldValue, string $newValue, string $severity): void
    {
        if ($oldValue === $newValue) {
            return;
        }

        $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, $field, $field, $oldValue, $newValue, $severity);
    }

    private function compareResponseTime(CompareRun $compareRun, SnapshotItem $itemA, SnapshotItem $itemB): void
    {
        if ($itemA->response_time_ms === null || $itemB->response_time_ms === null) {
            return;
        }

        $old = (int) $itemA->response_time_ms;
        $new = (int) $itemB->response_time_ms;

        if ($old === $new) {
            return;
        }

        $delta = $new - $old;
        if (abs($delta) < 100 && ($old === 0 || abs($delta) / max($old, 1) < 0.25)) {
            return;
        }

        $severity = $delta > 0 ? CompareItem::SEVERITY_REVIEW : CompareItem::SEVERITY_INFO;
        if ($old > 0 && $new >= ($old * 2)) {
            $severity = CompareItem::SEVERITY_HIGH;
        }

        $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, 'response_time_ms', 'response_time_ms', $old.' ms', $new.' ms', $severity);
    }

    private function compareSnapshotMetadata(CompareRun $compareRun, SnapshotItem $itemA, SnapshotItem $itemB): void
    {
        $metadataA = $itemA->metadata_json ?: [];
        $metadataB = $itemB->metadata_json ?: [];


        $oldAuthProfile = (string) ($metadataA['auth_profile'] ?? '');
        $newAuthProfile = (string) ($metadataB['auth_profile'] ?? '');
        if ($oldAuthProfile !== $newAuthProfile) {
            $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, 'auth_profile', 'auth_profile', $oldAuthProfile ?: __('messages.common.none'), $newAuthProfile ?: __('messages.common.none'), CompareItem::SEVERITY_REVIEW);
        }

        $oldScheme = strtolower((string) parse_url((string) ($metadataA['url'] ?? ''), PHP_URL_SCHEME));
        $newScheme = strtolower((string) parse_url((string) ($metadataB['url'] ?? ''), PHP_URL_SCHEME));
        if ($oldScheme !== '' && $newScheme !== '' && $oldScheme !== $newScheme) {
            $severity = $oldScheme === 'https' && $newScheme === 'http'
                ? CompareItem::SEVERITY_HIGH
                : CompareItem::SEVERITY_INFO;
            $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, 'scheme', 'scheme', $oldScheme, $newScheme, $severity);
        }

        $oldRiskScore = $metadataA['risk_score'] ?? null;
        $newRiskScore = $metadataB['risk_score'] ?? null;
        if (is_numeric($oldRiskScore) && is_numeric($newRiskScore) && (float) $newRiskScore > (float) $oldRiskScore) {
            $severity = ((float) $newRiskScore - (float) $oldRiskScore) >= 20
                ? CompareItem::SEVERITY_HIGH
                : CompareItem::SEVERITY_REVIEW;
            $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, 'risk_score', 'risk_score', (string) $oldRiskScore, (string) $newRiskScore, $severity);
        }

        $headersA = $this->normalizedHeaders($metadataA['headers'] ?? []);
        $headersB = $this->normalizedHeaders($metadataB['headers'] ?? []);
        foreach (['strict-transport-security', 'content-security-policy', 'x-content-type-options'] as $header) {
            if (array_key_exists($header, $headersA) && ! array_key_exists($header, $headersB)) {
                $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, 'security_header', 'security_header', $header, __('messages.snapshots.values.missing'), CompareItem::SEVERITY_HIGH);
            }

            if (! array_key_exists($header, $headersA) && array_key_exists($header, $headersB)) {
                $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, 'security_header', 'security_header', __('messages.snapshots.values.missing'), $header, CompareItem::SEVERITY_INFO);
            }
        }
    }

    private function recordCompareItem(CompareRun $compareRun, string $changeType, SnapshotItem $item, ?string $fieldChanged, ?string $fieldLabel, ?string $oldValue, ?string $newValue, string $severity): void
    {
        CompareItem::query()->create([
            'compare_run_id' => $compareRun->id,
            'change_type' => $changeType,
            'method' => strtoupper($item->method),
            'path' => $item->path,
            'field_changed' => $fieldChanged,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'severity' => $severity,
        ]);
    }

    private function summarizeSnapshot(Snapshot $snapshot): array
    {
        $items = $snapshot->items;

        return [
            'endpoint_count' => $items->count(),
            'critical_count' => $items->where('risk_level', Endpoint::RISK_CRITICAL)->count(),
            'high_count' => $items->where('risk_level', Endpoint::RISK_HIGH)->count(),
            'review_count' => $items->where('risk_level', Endpoint::RISK_REVIEW)->count(),
            'public_count' => $items->where('risk_level', Endpoint::RISK_PUBLIC)->count(),
            'low_count' => $items->where('risk_level', Endpoint::RISK_LOW)->count(),
            'status_2xx_count' => $items->filter(fn (SnapshotItem $item): bool => $item->status_code !== null && $item->status_code >= 200 && $item->status_code < 300)->count(),
            'status_4xx_count' => $items->filter(fn (SnapshotItem $item): bool => $item->status_code !== null && $item->status_code >= 400 && $item->status_code < 500)->count(),
            'status_5xx_count' => $items->filter(fn (SnapshotItem $item): bool => $item->status_code !== null && $item->status_code >= 500)->count(),
        ];
    }

    private function summarizeCompare(CompareRun $compareRun): array
    {
        $items = $compareRun->items;

        return [
            'total_changes' => $items->count(),
            'new_count' => $items->where('change_type', CompareItem::TYPE_NEW)->count(),
            'removed_count' => $items->where('change_type', CompareItem::TYPE_REMOVED)->count(),
            'changed_count' => $items->where('change_type', CompareItem::TYPE_CHANGED)->count(),
            'critical_count' => $items->where('severity', CompareItem::SEVERITY_CRITICAL)->count(),
            'high_count' => $items->where('severity', CompareItem::SEVERITY_HIGH)->count(),
            'review_count' => $items->where('severity', CompareItem::SEVERITY_REVIEW)->count(),
            'info_count' => $items->where('severity', CompareItem::SEVERITY_INFO)->count(),
        ];
    }

    private function severityForRisk(?string $riskLevel): string
    {
        return match ($riskLevel) {
            Endpoint::RISK_CRITICAL => CompareItem::SEVERITY_CRITICAL,
            Endpoint::RISK_HIGH => CompareItem::SEVERITY_HIGH,
            Endpoint::RISK_LOW => CompareItem::SEVERITY_LOW,
            Endpoint::RISK_PUBLIC => CompareItem::SEVERITY_INFO,
            default => CompareItem::SEVERITY_REVIEW,
        };
    }

    private function severityForRiskChange(?string $oldRisk, ?string $newRisk): string
    {
        $rank = [
            Endpoint::RISK_LOW => 1,
            Endpoint::RISK_PUBLIC => 2,
            Endpoint::RISK_REVIEW => 3,
            Endpoint::RISK_HIGH => 4,
            Endpoint::RISK_CRITICAL => 5,
        ];

        if (($rank[$newRisk] ?? 0) > ($rank[$oldRisk] ?? 0)) {
            return $this->severityForRisk($newRisk);
        }

        return CompareItem::SEVERITY_INFO;
    }

    private function severityForStatusChange(?int $oldStatus, ?int $newStatus): string
    {
        if ($newStatus === null) {
            return CompareItem::SEVERITY_REVIEW;
        }

        if ($newStatus >= 500) {
            return CompareItem::SEVERITY_HIGH;
        }

        if ($newStatus >= 400) {
            return CompareItem::SEVERITY_REVIEW;
        }

        if ($oldStatus !== null && $oldStatus >= 400 && $newStatus < 400) {
            return CompareItem::SEVERITY_INFO;
        }

        return CompareItem::SEVERITY_REVIEW;
    }

    private function defaultSnapshotName(ScanRun $scanRun): string
    {
        $date = ($scanRun->finished_at ?: $scanRun->created_at)->format('Y-m-d H:i');

        return __('messages.snapshots.default_name', ['date' => $date]);
    }

    private function itemKey(SnapshotItem $item): string
    {
        return strtoupper($item->method).' '.Str::lower($item->path);
    }

    private function nullableValue(mixed $value): string
    {
        return $value === null || $value === '' ? __('messages.common.not_available') : (string) $value;
    }

    private function boolValue(bool $value): string
    {
        return $value ? __('messages.common.yes') : __('messages.common.no');
    }

    /** @return array<string, mixed> */
    private function normalizedHeaders(mixed $headers): array
    {
        if (! is_array($headers)) {
            return [];
        }

        return collect($headers)
            ->mapWithKeys(fn (mixed $value, string $key): array => [strtolower($key) => $value])
            ->all();
    }
}
