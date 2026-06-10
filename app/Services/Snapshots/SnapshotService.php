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
            'body_preview_hash' => $this->bodyPreviewHash($result?->body_preview),
            'body_preview_excerpt' => $this->bodyPreviewExcerpt($result?->body_preview),
            'body_schema' => is_array($result?->response_schema_json) ? $result->response_schema_json : $this->bodySchema($result?->body_preview),
            'response_size' => $result?->response_size,
            'sensitive_data_detected' => (bool) ($result?->sensitive_data_detected ?? false),
            'sensitive_data_count' => (int) ($result?->sensitive_data_count ?? 0),
            'sensitive_data_summary' => is_array($result?->sensitive_data_summary_json) ? ($result->sensitive_data_summary_json['summary'] ?? null) : null,
            'broken_auth_detected' => (bool) ($result?->broken_auth_detected ?? false),
            'broken_auth_summary' => is_array($result?->broken_auth_summary_json) ? ($result->broken_auth_summary_json['summary'] ?? null) : null,
            'schema_drift_detected' => (bool) ($result?->schema_drift_detected ?? false),
            'schema_drift_count' => (int) ($result?->schema_drift_count ?? 0),
            'schema_drift_summary' => is_array($result?->schema_drift_summary_json) ? ($result->schema_drift_summary_json['summary'] ?? null) : null,
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

        $this->compareBooleanMetadata($compareRun, $itemB, $metadataA, $metadataB, 'sensitive_data_detected', 'sensitive_data', CompareItem::SEVERITY_HIGH);
        $this->compareBooleanMetadata($compareRun, $itemB, $metadataA, $metadataB, 'broken_auth_detected', 'broken_auth', CompareItem::SEVERITY_CRITICAL);
        $this->compareBooleanMetadata($compareRun, $itemB, $metadataA, $metadataB, 'schema_drift_detected', 'schema_drift', CompareItem::SEVERITY_HIGH);
        $this->compareNumericMetadata($compareRun, $itemB, $metadataA, $metadataB, 'sensitive_data_count', 'sensitive_data_count', CompareItem::SEVERITY_HIGH);
        $this->compareNumericMetadata($compareRun, $itemB, $metadataA, $metadataB, 'schema_drift_count', 'schema_drift_count', CompareItem::SEVERITY_HIGH);
        $this->compareNumericMetadata($compareRun, $itemB, $metadataA, $metadataB, 'response_size', 'response_size', CompareItem::SEVERITY_REVIEW, 512);
        $this->compareBodyPreview($compareRun, $itemB, $metadataA, $metadataB);
        $this->compareBodySchema($compareRun, $itemB, $metadataA, $metadataB);

        $headersA = $this->normalizedHeaders($metadataA['headers'] ?? []);
        $headersB = $this->normalizedHeaders($metadataB['headers'] ?? []);
        $this->compareHeaders($compareRun, $itemB, $headersA, $headersB);
        foreach (['strict-transport-security', 'content-security-policy', 'x-content-type-options'] as $header) {
            if (array_key_exists($header, $headersA) && ! array_key_exists($header, $headersB)) {
                $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, 'security_header', 'security_header', $header, __('messages.snapshots.values.missing'), CompareItem::SEVERITY_HIGH);
            }

            if (! array_key_exists($header, $headersA) && array_key_exists($header, $headersB)) {
                $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, 'security_header', 'security_header', __('messages.snapshots.values.missing'), $header, CompareItem::SEVERITY_INFO);
            }
        }
    }

    private function compareBooleanMetadata(CompareRun $compareRun, SnapshotItem $itemB, array $metadataA, array $metadataB, string $key, string $field, string $escalationSeverity): void
    {
        $old = (bool) ($metadataA[$key] ?? false);
        $new = (bool) ($metadataB[$key] ?? false);

        if ($old === $new) {
            return;
        }

        $severity = $new ? $escalationSeverity : CompareItem::SEVERITY_INFO;
        $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, $field, $field, $this->boolValue($old), $this->boolValue($new), $severity);
    }

    private function compareNumericMetadata(CompareRun $compareRun, SnapshotItem $itemB, array $metadataA, array $metadataB, string $key, string $field, string $severity, int $minimumDelta = 1): void
    {
        $old = $metadataA[$key] ?? null;
        $new = $metadataB[$key] ?? null;

        if (! is_numeric($old) || ! is_numeric($new)) {
            return;
        }

        if (abs((int) $new - (int) $old) < $minimumDelta) {
            return;
        }

        $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, $field, $field, (string) $old, (string) $new, $severity);
    }

    private function compareBodyPreview(CompareRun $compareRun, SnapshotItem $itemB, array $metadataA, array $metadataB): void
    {
        $oldHash = (string) ($metadataA['body_preview_hash'] ?? $this->bodyPreviewHash($metadataA['body_preview'] ?? null));
        $newHash = (string) ($metadataB['body_preview_hash'] ?? $this->bodyPreviewHash($metadataB['body_preview'] ?? null));

        if ($oldHash === '' || $newHash === '' || $oldHash === $newHash) {
            return;
        }

        $oldExcerpt = (string) ($metadataA['body_preview_excerpt'] ?? $this->bodyPreviewExcerpt($metadataA['body_preview'] ?? null));
        $newExcerpt = (string) ($metadataB['body_preview_excerpt'] ?? $this->bodyPreviewExcerpt($metadataB['body_preview'] ?? null));

        $this->recordCompareItem(
            $compareRun,
            CompareItem::TYPE_CHANGED,
            $itemB,
            'body_preview',
            'body_preview',
            $oldExcerpt !== '' ? $oldExcerpt : substr($oldHash, 0, 12),
            $newExcerpt !== '' ? $newExcerpt : substr($newHash, 0, 12),
            CompareItem::SEVERITY_REVIEW
        );
    }

    private function compareBodySchema(CompareRun $compareRun, SnapshotItem $itemB, array $metadataA, array $metadataB): void
    {
        $schemaA = $this->metadataSchema($metadataA);
        $schemaB = $this->metadataSchema($metadataB);

        if ($schemaA === [] && $schemaB === []) {
            return;
        }

        $paths = collect(array_keys($schemaA))->merge(array_keys($schemaB))->unique()->sort()->values();
        $recorded = 0;
        $hasSchemaDrift = false;
        foreach ($paths as $path) {
            $oldType = $schemaA[$path] ?? null;
            $newType = $schemaB[$path] ?? null;

            if ($oldType === $newType) {
                continue;
            }

            if ($oldType === null) {
                $old = __('messages.snapshots.values.missing');
                $new = $path.' ('.$newType.')';
                $severity = CompareItem::SEVERITY_INFO;
            } elseif ($newType === null) {
                $old = $path.' ('.$oldType.')';
                $new = __('messages.snapshots.values.missing');
                $severity = CompareItem::SEVERITY_HIGH;
            } else {
                $old = $path.' ('.$oldType.')';
                $new = $path.' ('.$newType.')';
                $severity = CompareItem::SEVERITY_HIGH;
            }

            $field = $oldType === null ? 'response_schema_added' : ($newType === null ? 'response_schema_removed' : (($oldType === 'null' || $newType === 'null') ? 'response_schema_nullability' : 'response_schema_type'));
            $this->recordCompareItem($compareRun, CompareItem::TYPE_CHANGED, $itemB, $field, 'response_schema', $old, $new, $severity);
            $hasSchemaDrift = true;
            $recorded++;

            if ($recorded >= 25) {
                break;
            }
        }

        if ($hasSchemaDrift) {
            $this->recordCompareItem(
                $compareRun,
                CompareItem::TYPE_CHANGED,
                $itemB,
                'response_schema',
                'response_schema',
                count($schemaA).' fields',
                count($schemaB).' fields',
                CompareItem::SEVERITY_REVIEW
            );
        }
    }

    /** @param array<string, mixed> $headersA @param array<string, mixed> $headersB */
    private function compareHeaders(CompareRun $compareRun, SnapshotItem $itemB, array $headersA, array $headersB): void
    {
        $ignored = ['date', 'server', 'x-powered-by', 'etag', 'last-modified'];
        $keys = collect(array_keys($headersA))->merge(array_keys($headersB))->unique()->sort()->values();
        $recorded = 0;

        foreach ($keys as $header) {
            if (in_array($header, $ignored, true)) {
                continue;
            }

            $old = $this->headerValue($headersA[$header] ?? null);
            $new = $this->headerValue($headersB[$header] ?? null);

            if ($old === $new) {
                continue;
            }

            $severity = str_starts_with($header, 'x-') || str_contains($header, 'security') || in_array($header, ['content-type', 'cache-control', 'location'], true)
                ? CompareItem::SEVERITY_REVIEW
                : CompareItem::SEVERITY_INFO;

            $this->recordCompareItem(
                $compareRun,
                CompareItem::TYPE_CHANGED,
                $itemB,
                'response_header',
                'response_header',
                $header.': '.($old !== '' ? $old : __('messages.snapshots.values.missing')),
                $header.': '.($new !== '' ? $new : __('messages.snapshots.values.missing')),
                $severity
            );
            $recorded++;

            if ($recorded >= 25) {
                break;
            }
        }
    }

    private function bodyPreviewHash(mixed $preview): ?string
    {
        $value = is_string($preview) ? trim($preview) : '';

        return $value !== '' ? hash('sha256', $value) : null;
    }

    private function bodyPreviewExcerpt(mixed $preview): ?string
    {
        $value = is_string($preview) ? trim(preg_replace('/\s+/', ' ', $preview) ?: '') : '';

        return $value !== '' ? Str::limit($value, 180) : null;
    }

    /** @return array<string, string> */
    private function bodySchema(mixed $preview): array
    {
        if (! is_string($preview) || trim($preview) === '') {
            return [];
        }

        try {
            $decoded = json_decode($preview, true, 128, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        $schema = [];
        $this->collectSchemaPaths($decoded, '$', $schema, 0);

        return $schema;
    }

    /** @param array<string, mixed> $metadata @return array<string, string> */
    private function metadataSchema(array $metadata): array
    {
        if (isset($metadata['body_schema']) && is_array($metadata['body_schema'])) {
            return collect($metadata['body_schema'])
                ->filter(fn (mixed $type, mixed $path): bool => is_string($path) && is_string($type))
                ->mapWithKeys(fn (string $type, string $path): array => [$path => $type])
                ->all();
        }

        return $this->bodySchema($metadata['body_preview'] ?? null);
    }

    /** @param array<string, string> $schema */
    private function collectSchemaPaths(mixed $value, string $path, array &$schema, int $depth): void
    {
        if ($depth > 6 || count($schema) >= 150) {
            return;
        }

        $schema[$path] = $this->jsonType($value);

        if (is_array($value)) {
            if (array_is_list($value)) {
                if (array_key_exists(0, $value)) {
                    $this->collectSchemaPaths($value[0], $path.'[]', $schema, $depth + 1);
                }
                return;
            }

            foreach ($value as $key => $child) {
                $this->collectSchemaPaths($child, $path.'.'.$key, $schema, $depth + 1);
            }
        }
    }

    private function jsonType(mixed $value): string
    {
        return match (true) {
            is_array($value) && array_is_list($value) => 'array',
            is_array($value) => 'object',
            is_int($value) => 'integer',
            is_float($value) => 'number',
            is_bool($value) => 'boolean',
            $value === null => 'null',
            default => 'string',
        };
    }

    private function headerValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(fn (mixed $item): string => (string) $item, $value));
        }

        return trim((string) $value);
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
            'breaking_count' => $items->whereIn('severity', [CompareItem::SEVERITY_CRITICAL, CompareItem::SEVERITY_HIGH])->count(),
            'status_code_count' => $items->where('field_changed', 'status_code')->count(),
            'response_time_count' => $items->where('field_changed', 'response_time_ms')->count(),
            'header_count' => $items->filter(fn (CompareItem $item): bool => in_array($item->field_changed, ['response_header', 'security_header'], true))->count(),
            'body_count' => $items->whereIn('field_changed', ['body_preview', 'response_size'])->count(),
            'schema_count' => $items->whereIn('field_changed', ['response_schema', 'response_schema_added', 'response_schema_removed', 'response_schema_type', 'response_schema_nullability'])->count(),
            'sensitive_data_count' => $items->whereIn('field_changed', ['sensitive_data', 'sensitive_data_count'])->count(),
            'broken_auth_count' => $items->where('field_changed', 'broken_auth')->count(),
            'schema_drift_count' => $items->whereIn('field_changed', ['schema_drift', 'schema_drift_count'])->count(),
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
