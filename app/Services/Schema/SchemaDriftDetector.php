<?php

namespace App\Services\Schema;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\ScanResult;
use App\Services\Settings\SettingService;
use Illuminate\Support\Str;

class SchemaDriftDetector
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * @return array{checked:bool,detected:bool,count:int,summary:string,changes:array<int,array<string,mixed>>,schema:array<string,string>,baseline_scan_result_id:int|null,highest_severity:string}
     */
    public function compareEndpointResponse(Endpoint $endpoint, ?string $bodyPreview, ?string $contentType = null): array
    {
        $schema = $this->schemaFromPreview($bodyPreview, $contentType);
        $empty = $this->emptyResult($schema);

        if (! $this->settings->boolean('security.schema_drift_detector_enabled', true)) {
            return $empty;
        }

        if ($schema === []) {
            return [
                ...$empty,
                'summary' => __('messages.schema_drift.not_json_or_empty'),
            ];
        }

        $baseline = ScanResult::query()
            ->where('endpoint_id', $endpoint->id)
            ->where('status', ScanResult::STATUS_COMPLETED)
            ->where(function ($query): void {
                $query->whereNotNull('response_schema_json')
                    ->orWhereNotNull('body_preview');
            })
            ->latest('created_at')
            ->latest('id')
            ->first();

        if (! $baseline) {
            return [
                ...$empty,
                'checked' => true,
                'summary' => __('messages.schema_drift.no_baseline'),
            ];
        }

        $baselineSchema = is_array($baseline->response_schema_json)
            ? $baseline->response_schema_json
            : $this->schemaFromPreview($baseline->body_preview, $baseline->content_type);

        if ($baselineSchema === []) {
            return [
                ...$empty,
                'checked' => true,
                'baseline_scan_result_id' => $baseline->id,
                'summary' => __('messages.schema_drift.no_baseline_schema'),
            ];
        }

        $changes = $this->compareSchemas($baselineSchema, $schema);
        $limit = max(1, $this->settings->integer('security.schema_drift_max_changes', 50));
        $limited = array_slice($changes, 0, $limit);
        $highest = $this->highestSeverity($limited);

        return [
            'checked' => true,
            'detected' => $limited !== [],
            'count' => count($limited),
            'summary' => $limited === []
                ? __('messages.schema_drift.clean')
                : $this->summary($limited, count($changes)),
            'changes' => $limited,
            'schema' => $schema,
            'baseline_scan_result_id' => $baseline->id,
            'highest_severity' => $highest,
        ];
    }

    /** @return array<string,string> */
    public function schemaFromPreview(?string $bodyPreview, ?string $contentType = null): array
    {
        if (! is_string($bodyPreview) || trim($bodyPreview) === '') {
            return [];
        }

        $looksJson = str_contains(strtolower((string) $contentType), 'json')
            || str_starts_with(trim($bodyPreview), '{')
            || str_starts_with(trim($bodyPreview), '[');

        if (! $looksJson) {
            return [];
        }

        try {
            $decoded = json_decode($bodyPreview, true, 128, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        $schema = [];
        $this->collectSchemaPaths($decoded, '$', $schema, 0);
        ksort($schema);

        return $schema;
    }

    /** @param array<string,string> $old @param array<string,string> $new @return array<int,array<string,mixed>> */
    public function compareSchemas(array $old, array $new): array
    {
        $paths = collect(array_keys($old))->merge(array_keys($new))->unique()->sort()->values();
        $changes = [];

        foreach ($paths as $path) {
            $oldType = $old[$path] ?? null;
            $newType = $new[$path] ?? null;

            if ($oldType === $newType) {
                continue;
            }

            if ($oldType === null) {
                $changes[] = $this->change('added', $path, null, $newType, Finding::SEVERITY_LOW);
                continue;
            }

            if ($newType === null) {
                $changes[] = $this->change('removed', $path, $oldType, null, Finding::SEVERITY_HIGH);
                continue;
            }

            if ($oldType === 'null' || $newType === 'null') {
                $changes[] = $this->change('nullability_changed', $path, $oldType, $newType, Finding::SEVERITY_HIGH);
                continue;
            }

            $changes[] = $this->change('type_changed', $path, $oldType, $newType, Finding::SEVERITY_HIGH);
        }

        return $changes;
    }

    /** @param array<int,array<string,mixed>> $changes */
    public function summary(array $changes, ?int $total = null): string
    {
        $counts = collect($changes)->countBy('kind');
        $parts = [];

        foreach (['removed', 'type_changed', 'nullability_changed', 'added'] as $kind) {
            $count = (int) ($counts[$kind] ?? 0);
            if ($count > 0) {
                $parts[] = __('messages.schema_drift.change_counts.'.$kind, ['count' => $count]);
            }
        }

        $summary = $parts !== [] ? implode(', ', $parts) : __('messages.schema_drift.clean');

        if ($total !== null && $total > count($changes)) {
            $summary .= ' · '.__('messages.schema_drift.truncated', ['count' => $total - count($changes)]);
        }

        return $summary;
    }

    /** @param array<int,array<string,mixed>> $changes */
    public function highestSeverity(array $changes): string
    {
        $rank = [
            Finding::SEVERITY_LOW => 1,
            Finding::SEVERITY_MEDIUM => 2,
            Finding::SEVERITY_HIGH => 3,
            Finding::SEVERITY_CRITICAL => 4,
        ];

        $highest = Finding::SEVERITY_LOW;
        foreach ($changes as $change) {
            $severity = (string) ($change['severity'] ?? Finding::SEVERITY_LOW);
            if (($rank[$severity] ?? 0) > ($rank[$highest] ?? 0)) {
                $highest = $severity;
            }
        }

        return $highest;
    }

    /** @return array{checked:bool,detected:bool,count:int,summary:string,changes:array<int,array<string,mixed>>,schema:array<string,string>,baseline_scan_result_id:int|null,highest_severity:string} */
    private function emptyResult(array $schema = []): array
    {
        return [
            'checked' => false,
            'detected' => false,
            'count' => 0,
            'summary' => __('messages.schema_drift.not_checked'),
            'changes' => [],
            'schema' => $schema,
            'baseline_scan_result_id' => null,
            'highest_severity' => Finding::SEVERITY_LOW,
        ];
    }

    /** @return array{kind:string,path:string,old_type:?string,new_type:?string,severity:string,label:string} */
    private function change(string $kind, string $path, ?string $oldType, ?string $newType, string $severity): array
    {
        return [
            'kind' => $kind,
            'path' => $path,
            'old_type' => $oldType,
            'new_type' => $newType,
            'severity' => $severity,
            'label' => __('messages.schema_drift.change_labels.'.$kind, [
                'path' => $path,
                'old' => $oldType ?: __('messages.common.not_available'),
                'new' => $newType ?: __('messages.common.not_available'),
            ]),
        ];
    }

    /** @param array<string,string> $schema */
    private function collectSchemaPaths(mixed $value, string $path, array &$schema, int $depth): void
    {
        if ($depth > 8 || count($schema) >= 250) {
            return;
        }

        $schema[$path] = $this->jsonType($value);

        if (! is_array($value)) {
            return;
        }

        if (array_is_list($value)) {
            $schema[$path] = 'array';
            $itemTypes = [];
            foreach (array_slice($value, 0, 5) as $item) {
                $itemTypes[] = $this->jsonType($item);
            }

            $itemTypes = array_values(array_unique($itemTypes));
            if ($itemTypes !== []) {
                sort($itemTypes);
                $schema[$path.'[]'] = count($itemTypes) === 1 ? $itemTypes[0] : 'mixed('.implode('|', $itemTypes).')';
            }

            foreach (array_slice($value, 0, 3) as $item) {
                if (is_array($item) && ! array_is_list($item)) {
                    foreach ($item as $key => $child) {
                        $this->collectSchemaPaths($child, $path.'[].'.$this->sanitizeKey((string) $key), $schema, $depth + 1);
                    }
                }
            }

            return;
        }

        foreach ($value as $key => $child) {
            $this->collectSchemaPaths($child, $path.'.'.$this->sanitizeKey((string) $key), $schema, $depth + 1);
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

    private function sanitizeKey(string $key): string
    {
        return (string) Str::of($key)->replace([' ', "\n", "\r", "\t"], '_')->limit(80, '…');
    }
}
