<?php

namespace App\Services;

use App\Models\ContractValidationRun;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OpenApiContractValidationService
{
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    public function validate(Project $project, array $data, ?User $user = null): ContractValidationRun
    {
        [$document, $documentWarnings] = $this->decodeContract((string) ($data['contract_content'] ?? ''));
        $contractOperations = $this->contractOperations($document);
        $inventoryOperations = $this->inventoryOperations($project);

        $results = [];
        $matched = 0;
        $undocumented = 0;
        $missingInventory = 0;
        $blockers = 0;
        $warnings = $this->sourceWarningImpactCount($documentWarnings);

        foreach ($inventoryOperations as $key => $operation) {
            if (isset($contractOperations[$key])) {
                $matched++;
                $contract = $contractOperations[$key];
                $results[] = [
                    'endpoint_id' => $operation['endpoint_id'],
                    'result_type' => 'matched',
                    'severity' => 'info',
                    'method' => $operation['method'],
                    'path' => $operation['path'],
                    'operation_id' => $contract['operation_id'] ?? null,
                    'summary' => __('messages.contract_validation.results.matched', [
                        'method' => $operation['method'],
                        'path' => $operation['path'],
                    ]),
                    'details_json' => [
                        'inventory_name' => $operation['name'],
                        'contract_summary' => $contract['summary'] ?? null,
                        'risk_level' => $operation['risk_level'],
                        'auth_required' => $operation['auth_required'],
                    ],
                ];

                continue;
            }

            $undocumented++;
            $severity = $this->undocumentedSeverity($operation);
            $severity === 'blocker' ? $blockers++ : $warnings++;
            $results[] = [
                'endpoint_id' => $operation['endpoint_id'],
                'result_type' => 'undocumented_endpoint',
                'severity' => $severity,
                'method' => $operation['method'],
                'path' => $operation['path'],
                'operation_id' => null,
                'summary' => __('messages.contract_validation.results.undocumented_endpoint', [
                    'method' => $operation['method'],
                    'path' => $operation['path'],
                ]),
                'details_json' => [
                    'inventory_name' => $operation['name'],
                    'risk_level' => $operation['risk_level'],
                    'auth_required' => $operation['auth_required'],
                    'reason' => $severity === 'blocker' ? 'high_risk_or_authenticated_inventory_missing_from_contract' : 'inventory_missing_from_contract',
                ],
            ];
        }

        foreach ($contractOperations as $key => $operation) {
            if (isset($inventoryOperations[$key])) {
                continue;
            }

            $missingInventory++;
            $warnings++;
            $results[] = [
                'endpoint_id' => null,
                'result_type' => 'missing_inventory',
                'severity' => 'warning',
                'method' => $operation['method'],
                'path' => $operation['path'],
                'operation_id' => $operation['operation_id'] ?? null,
                'summary' => __('messages.contract_validation.results.missing_inventory', [
                    'method' => $operation['method'],
                    'path' => $operation['path'],
                ]),
                'details_json' => [
                    'contract_summary' => $operation['summary'] ?? null,
                    'tags' => $operation['tags'] ?? [],
                    'reason' => 'contract_operation_missing_from_inventory',
                ],
            ];
        }

        $status = $blockers > 0 ? 'blocked' : ($warnings > 0 ? 'warning' : 'passed');
        $summary = [
            'source_name' => $data['source_name'] ?? null,
            'source_version' => $data['source_version'] ?? null,
            'openapi_version' => (string) ($document['openapi'] ?? $document['swagger'] ?? 'unknown'),
            'status' => $status,
            'documented_operations' => count($contractOperations),
            'inventory_operations' => count($inventoryOperations),
            'matched_operations' => $matched,
            'undocumented_inventory_operations' => $undocumented,
            'missing_inventory_operations' => $missingInventory,
            'blocker_count' => $blockers,
            'warning_count' => $warnings,
            'validated_at' => now()->toDateTimeString(),
            'source_warnings' => $documentWarnings,
            'source_warning_count' => count($documentWarnings),
        ];

        $run = $project->contractValidationRuns()->create([
            'validated_by_user_id' => $user?->id,
            'source_name' => $data['source_name'] ?? null,
            'source_version' => $data['source_version'] ?? null,
            'openapi_version' => $summary['openapi_version'],
            'status' => $status,
            'documented_operations' => count($contractOperations),
            'inventory_operations' => count($inventoryOperations),
            'matched_operations' => $matched,
            'undocumented_inventory_operations' => $undocumented,
            'missing_inventory_operations' => $missingInventory,
            'blocker_count' => $blockers,
            'warning_count' => $warnings,
            'summary_json' => $summary,
            'contract_json' => json_encode($this->compactContract($document), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'validated_at' => now(),
        ]);

        foreach ($results as $result) {
            $run->results()->create(array_merge($result, ['project_id' => $project->id]));
        }

        return $run->load(['results.endpoint', 'validatedBy']);
    }

    public function summary(Project $project): array
    {
        if (! Schema::hasTable('contract_validation_runs')) {
            return $this->emptySummary();
        }

        $latest = $project->contractValidationRuns()->latest('validated_at')->latest()->first();

        if (! $latest) {
            return $this->emptySummary();
        }

        return [
            'has_run' => true,
            'latest_run_id' => $latest->id,
            'latest_status' => $latest->status,
            'latest_status_label' => $latest->status_label,
            'latest_status_tone' => $latest->status_tone,
            'source_name' => $latest->source_name,
            'source_version' => $latest->source_version,
            'openapi_version' => $latest->openapi_version,
            'documented_operations' => $latest->documented_operations,
            'inventory_operations' => $latest->inventory_operations,
            'matched_operations' => $latest->matched_operations,
            'undocumented_inventory_operations' => $latest->undocumented_inventory_operations,
            'missing_inventory_operations' => $latest->missing_inventory_operations,
            'blocker_count' => $latest->blocker_count,
            'warning_count' => $latest->warning_count,
            'validated_at' => $latest->validated_at?->toDateTimeString(),
            'source_warnings' => Arr::get($latest->summary, 'source_warnings', []),
            'source_warning_count' => (int) Arr::get($latest->summary, 'source_warning_count', 0),
        ];
    }

    public function markdownEvidence(ContractValidationRun $run): string
    {
        $lines = [
            '# '.__('messages.contract_validation.markdown_title'),
            '',
            '- '.__('messages.contract_validation.source_name').': '.($run->source_name ?: __('messages.common.not_available')),
            '- '.__('messages.contract_validation.source_version').': '.($run->source_version ?: __('messages.common.not_available')),
            '- '.__('messages.contract_validation.openapi_version').': '.($run->openapi_version ?: __('messages.common.not_available')),
            '- '.__('messages.common.status').': '.$run->status_label,
            '- '.__('messages.contract_validation.documented_operations').': '.$run->documented_operations,
            '- '.__('messages.contract_validation.inventory_operations').': '.$run->inventory_operations,
            '- '.__('messages.contract_validation.matched_operations').': '.$run->matched_operations,
            '- '.__('messages.contract_validation.undocumented_inventory_operations').': '.$run->undocumented_inventory_operations,
            '- '.__('messages.contract_validation.missing_inventory_operations').': '.$run->missing_inventory_operations,
            '- '.__('messages.release_readiness.blockers').': '.$run->blocker_count,
            '- '.__('messages.release_readiness.warnings').': '.$run->warning_count,
            '- '.__('messages.contract_validation.validated_at').': '.($run->validated_at?->toDateTimeString() ?? __('messages.common.not_available')),
        ];

        $sourceWarnings = Arr::wrap(Arr::get($run->summary, 'source_warnings', []));
        if ($sourceWarnings !== []) {
            $lines[] = '';
            $lines[] = '## '.__('messages.contract_validation.source_warnings_title');
            foreach ($sourceWarnings as $warning) {
                $lines[] = '- '.__('messages.contract_validation.source_warnings.'.(string) $warning);
            }
        }

        $lines[] = '';
        $lines[] = '## '.__('messages.contract_validation.results_title');

        foreach ($run->results()->orderByRaw("CASE severity WHEN 'blocker' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")->orderBy('method')->orderBy('path')->limit(50)->get() as $result) {
            $lines[] = '- ['.$result->severity_label.'] '.$result->type_label.' — '.$result->method.' '.$result->path.' — '.$result->summary;
        }

        return implode("\n", $lines);
    }

    private function emptySummary(): array
    {
        return [
            'has_run' => false,
            'latest_run_id' => null,
            'latest_status' => 'missing',
            'latest_status_label' => __('messages.contract_validation.statuses.missing'),
            'latest_status_tone' => 'secondary',
            'source_name' => null,
            'source_version' => null,
            'openapi_version' => null,
            'documented_operations' => 0,
            'inventory_operations' => 0,
            'matched_operations' => 0,
            'undocumented_inventory_operations' => 0,
            'missing_inventory_operations' => 0,
            'blocker_count' => 0,
            'warning_count' => 0,
            'validated_at' => null,
            'source_warnings' => [],
            'source_warning_count' => 0,
        ];
    }

    /**
     * @param array<int, string> $warnings
     */
    private function sourceWarningImpactCount(array $warnings): int
    {
        return count(array_filter($warnings, static fn (string $warning): bool => in_array($warning, ['missing_paths_normalized', 'empty_paths'], true)));
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function decodeContract(string $content): array
    {
        $content = $this->sanitizeJsonContent($content);

        if ($content === '') {
            throw ValidationException::withMessages(['contract_content' => __('messages.contract_validation.errors.empty_contract')]);
        }

        $document = json_decode($content, true);

        if (! is_array($document)) {
            throw ValidationException::withMessages(['contract_content' => __('messages.contract_validation.errors.invalid_json')]);
        }

        [$document, $warnings] = $this->resolveOpenApiDocument($document);

        if (! array_key_exists('paths', $document) || ! is_array($document['paths'])) {
            $document['paths'] = [];
            $warnings[] = 'missing_paths_normalized';
        } elseif ($document['paths'] === []) {
            $warnings[] = 'empty_paths';
        }

        return [$document, array_values(array_unique($warnings))];
    }

    private function sanitizeJsonContent(string $content): string
    {
        $content = trim(preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $content, $matches)) {
            return trim($matches[1]);
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $document
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function resolveOpenApiDocument(array $document, int $depth = 0): array
    {
        if ($this->looksLikePathMap($document)) {
            return [[
                'openapi' => 'unknown',
                'info' => ['title' => __('messages.contract_validation.path_map_title'), 'version' => 'unknown'],
                'paths' => $document,
            ], ['paths_fragment_wrapped']];
        }

        if ($this->looksLikeOpenApiDocument($document)) {
            return [$document, []];
        }

        if ($depth >= 3) {
            return [$document, []];
        }

        foreach (['document', 'spec', 'schema', 'contract', 'content', 'openapi_document', 'openapiDocument', 'api'] as $key) {
            if (! array_key_exists($key, $document)) {
                continue;
            }

            $candidate = $document[$key];
            if (is_string($candidate)) {
                $decoded = json_decode($this->sanitizeJsonContent($candidate), true);
                $candidate = is_array($decoded) ? $decoded : $candidate;
            }

            if (is_array($candidate)) {
                [$resolved, $warnings] = $this->resolveOpenApiDocument($candidate, $depth + 1);
                if ($this->looksLikeOpenApiDocument($resolved) || $this->looksLikePathMap($resolved)) {
                    array_unshift($warnings, 'wrapped_document_detected');

                    return [$resolved, $warnings];
                }
            }
        }

        if (isset($document['data']) && is_array($document['data'])) {
            [$resolved, $warnings] = $this->resolveOpenApiDocument($document['data'], $depth + 1);
            if ($this->looksLikeOpenApiDocument($resolved) || $this->looksLikePathMap($resolved)) {
                array_unshift($warnings, 'wrapped_document_detected');

                return [$resolved, $warnings];
            }
        }

        return [$document, []];
    }

    /**
     * @param array<string, mixed> $document
     */
    private function looksLikeOpenApiDocument(array $document): bool
    {
        return array_key_exists('paths', $document)
            || array_key_exists('openapi', $document)
            || array_key_exists('swagger', $document);
    }

    /**
     * @param array<string, mixed> $document
     */
    private function looksLikePathMap(array $document): bool
    {
        foreach ($document as $path => $pathDefinition) {
            if (! is_string($path) || ! str_starts_with($path, '/') || ! is_array($pathDefinition)) {
                continue;
            }

            foreach (array_keys($pathDefinition) as $method) {
                if (in_array(strtoupper((string) $method), self::METHODS, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function contractOperations(array $document): array
    {
        $operations = [];

        foreach (($document['paths'] ?? []) as $path => $pathDefinition) {
            if (! is_array($pathDefinition)) {
                continue;
            }

            foreach ($pathDefinition as $method => $operation) {
                $method = strtoupper((string) $method);

                if (! in_array($method, self::METHODS, true)) {
                    continue;
                }

                $normalizedPath = $this->normalizePath((string) $path);
                $key = $method.' '.$normalizedPath;
                $operations[$key] = [
                    'method' => $method,
                    'path' => $normalizedPath,
                    'operation_id' => is_array($operation) ? Arr::get($operation, 'operationId') : null,
                    'summary' => is_array($operation) ? Arr::get($operation, 'summary') : null,
                    'tags' => is_array($operation) ? Arr::wrap(Arr::get($operation, 'tags', [])) : [],
                ];
            }
        }

        return $operations;
    }

    private function inventoryOperations(Project $project): array
    {
        return $project->endpoints()
            ->orderBy('method')
            ->orderBy('path')
            ->get()
            ->mapWithKeys(function (Endpoint $endpoint): array {
                $method = strtoupper((string) $endpoint->method);
                $path = $this->normalizePath((string) $endpoint->path);

                return [$method.' '.$path => [
                    'endpoint_id' => $endpoint->id,
                    'method' => $method,
                    'path' => $path,
                    'name' => $endpoint->name,
                    'risk_level' => $endpoint->risk_level,
                    'auth_required' => (bool) $endpoint->auth_required,
                    'is_active' => (bool) $endpoint->is_active,
                ]];
            })
            ->all();
    }

    private function undocumentedSeverity(array $operation): string
    {
        if (($operation['auth_required'] ?? false) || in_array($operation['risk_level'] ?? 'low', ['high', 'critical'], true)) {
            return 'blocker';
        }

        return 'warning';
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        $path = preg_replace('#^https?://[^/]+#i', '', $path) ?: $path;
        $path = preg_replace('#/+#', '/', $path) ?: $path;
        $path = '/'.ltrim($path, '/');

        return $path !== '/' ? rtrim($path, '/') : '/';
    }

    private function compactContract(array $document): array
    {
        return [
            'openapi' => $document['openapi'] ?? null,
            'swagger' => $document['swagger'] ?? null,
            'info' => $document['info'] ?? [],
            'paths' => $document['paths'] ?? [],
        ];
    }
}
