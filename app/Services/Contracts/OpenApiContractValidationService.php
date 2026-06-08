<?php

namespace App\Services\Contracts;

use App\Models\ContractValidationResult;
use App\Models\ContractValidationRun;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Services\Endpoints\EndpointImportService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class OpenApiContractValidationService
{
    public function __construct(private readonly EndpointImportService $openApi)
    {
    }

    public function validate(Project $project, string $payload, ?ScanRun $scanRun = null, ?string $sourceName = null): ContractValidationRun
    {
        $startedAt = now();
        $run = ContractValidationRun::query()->create([
            'project_id' => $project->id,
            'scan_run_id' => $scanRun?->id,
            'source_name' => $sourceName ?: __('messages.contract_validations.manual_source'),
            'contract_hash' => hash('sha256', $payload),
            'status' => ContractValidationRun::STATUS_COMPLETED,
            'started_at' => $startedAt,
        ]);

        try {
            $document = $this->openApi->decodeOpenApiDocument($payload);
            $operations = $this->operations($document);

            if ($operations === []) {
                throw new \RuntimeException(__('messages.contract_validations.no_operations_detected'));
            }

            $endpoints = $project->endpoints()
                ->with(['latestScanResult'])
                ->get()
                ->keyBy(fn (Endpoint $endpoint): string => $this->key($endpoint->method, $endpoint->path));

            $scanResults = $this->scanResultsByEndpoint($project, $scanRun);

            foreach ($operations as $key => $operation) {
                /** @var Endpoint|null $endpoint */
                $endpoint = $endpoints->get($key);
                if (! $endpoint) {
                    $this->result($run, null, null, $operation['method'], $operation['path'], ContractValidationResult::CHECK_OPERATION_IMPLEMENTED, ContractValidationResult::STATUS_FAIL, ContractValidationResult::SEVERITY_HIGH, __('messages.contract_validations.messages.missing_endpoint'), $operation['method'].' '.$operation['path'], null, ['operation' => $operation['operation_id']]);
                    continue;
                }

                $this->result($run, $endpoint, null, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_OPERATION_DOCUMENTED, ContractValidationResult::STATUS_PASS, ContractValidationResult::SEVERITY_LOW, __('messages.contract_validations.messages.operation_documented'), $operation['method'].' '.$operation['path'], $endpoint->method.' '.$endpoint->path);

                /** @var ScanResult|null $scanResult */
                $scanResult = $scanResults->get($endpoint->id) ?: $endpoint->latestScanResult;
                if (! $scanResult) {
                    $this->result($run, $endpoint, null, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_SCAN_EVIDENCE, ContractValidationResult::STATUS_WARNING, ContractValidationResult::SEVERITY_MEDIUM, __('messages.contract_validations.messages.no_scan_evidence'), __('messages.contract_validations.expected.latest_scan'), null);
                    continue;
                }

                $this->validateStatusCode($run, $endpoint, $scanResult, $operation);
                $this->validateContentType($run, $endpoint, $scanResult, $operation);
                $this->validateResponseSchema($run, $endpoint, $scanResult, $operation, $document);
            }

            foreach ($endpoints as $key => $endpoint) {
                if (isset($operations[$key])) {
                    continue;
                }

                $this->result($run, $endpoint, null, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_OPERATION_DOCUMENTED, ContractValidationResult::STATUS_WARNING, ContractValidationResult::SEVERITY_MEDIUM, __('messages.contract_validations.messages.undocumented_endpoint'), __('messages.contract_validations.expected.operation_in_contract'), $endpoint->method.' '.$endpoint->path);
            }

            return $this->completeRun($run, $operations);
        } catch (Throwable $exception) {
            $run->update([
                'status' => ContractValidationRun::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            return $run->refresh();
        }
    }

    /** @return array<string,array<string,mixed>> */
    public function operations(array $document): array
    {
        $paths = $document['paths'] ?? [];
        if (! is_array($paths)) {
            return [];
        }

        $operations = [];
        foreach ($paths as $path => $pathItem) {
            if (! is_string($path) || ! is_array($pathItem)) {
                continue;
            }

            foreach (['get', 'post', 'put', 'patch', 'delete', 'head', 'options'] as $method) {
                $operation = $pathItem[$method] ?? null;
                if (! is_array($operation)) {
                    continue;
                }

                $upperMethod = strtoupper($method);
                $responses = is_array($operation['responses'] ?? null) ? $operation['responses'] : [];
                $statuses = $this->responseStatuses($responses);
                $primaryStatus = $this->primarySuccessStatus($statuses);
                $contentTypes = $this->contentTypesForStatus($responses, $primaryStatus);
                $schema = $this->schemaForStatus($responses, $primaryStatus, $contentTypes[0] ?? null);

                $operations[$this->key($upperMethod, $path)] = [
                    'method' => $upperMethod,
                    'path' => Endpoint::normalizePath($path),
                    'operation_id' => $this->nullableString($operation['operationId'] ?? null),
                    'summary' => $this->nullableString($operation['summary'] ?? null),
                    'statuses' => $statuses,
                    'primary_status' => $primaryStatus,
                    'content_types' => $contentTypes,
                    'schema' => $schema,
                ];
            }
        }

        return $operations;
    }

    /** @return Collection<int, ScanResult> */
    private function scanResultsByEndpoint(Project $project, ?ScanRun $scanRun): Collection
    {
        if ($scanRun && $scanRun->project_id === $project->id) {
            return $scanRun->results()->whereNotNull('endpoint_id')->get()->keyBy('endpoint_id');
        }

        $latestScan = $project->scanRuns()->latest()->first();
        if (! $latestScan) {
            return collect();
        }

        return $latestScan->results()->whereNotNull('endpoint_id')->get()->keyBy('endpoint_id');
    }

    /** @param array<string,mixed> $operation */
    private function validateStatusCode(ContractValidationRun $run, Endpoint $endpoint, ScanResult $scanResult, array $operation): void
    {
        $statuses = $operation['statuses'];
        if ($statuses === []) {
            $this->result($run, $endpoint, $scanResult, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_STATUS_CODE, ContractValidationResult::STATUS_SKIPPED, ContractValidationResult::SEVERITY_LOW, __('messages.contract_validations.messages.no_status_contract'), null, (string) ($scanResult->status_code ?? 'n/a'));
            return;
        }

        $actual = $scanResult->status_code;
        $expected = implode(', ', $statuses);
        $passes = $actual !== null && in_array((string) $actual, $statuses, true);

        $this->result($run, $endpoint, $scanResult, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_STATUS_CODE, $passes ? ContractValidationResult::STATUS_PASS : ContractValidationResult::STATUS_FAIL, $passes ? ContractValidationResult::SEVERITY_LOW : ContractValidationResult::SEVERITY_HIGH, $passes ? __('messages.contract_validations.messages.status_matches') : __('messages.contract_validations.messages.status_mismatch'), $expected, $actual !== null ? (string) $actual : 'n/a');
    }

    /** @param array<string,mixed> $operation */
    private function validateContentType(ContractValidationRun $run, Endpoint $endpoint, ScanResult $scanResult, array $operation): void
    {
        $expectedTypes = $operation['content_types'];
        if ($expectedTypes === []) {
            $this->result($run, $endpoint, $scanResult, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_CONTENT_TYPE, ContractValidationResult::STATUS_SKIPPED, ContractValidationResult::SEVERITY_LOW, __('messages.contract_validations.messages.no_content_type_contract'), null, $scanResult->content_type ?: 'n/a');
            return;
        }

        $actual = strtolower((string) $scanResult->content_type);
        $passes = $actual !== '' && collect($expectedTypes)->contains(fn (string $expected): bool => str_contains($actual, strtolower($expected)));

        $this->result($run, $endpoint, $scanResult, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_CONTENT_TYPE, $passes ? ContractValidationResult::STATUS_PASS : ContractValidationResult::STATUS_FAIL, $passes ? ContractValidationResult::SEVERITY_LOW : ContractValidationResult::SEVERITY_MEDIUM, $passes ? __('messages.contract_validations.messages.content_type_matches') : __('messages.contract_validations.messages.content_type_mismatch'), implode(', ', $expectedTypes), $scanResult->content_type ?: 'n/a');
    }

    /** @param array<string,mixed> $operation */
    private function validateResponseSchema(ContractValidationRun $run, Endpoint $endpoint, ScanResult $scanResult, array $operation, array $document): void
    {
        $schema = $operation['schema'];
        if (! is_array($schema) || $schema === []) {
            $this->result($run, $endpoint, $scanResult, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_RESPONSE_SCHEMA, ContractValidationResult::STATUS_SKIPPED, ContractValidationResult::SEVERITY_LOW, __('messages.contract_validations.messages.no_schema_contract'), null, null);
            return;
        }

        $body = trim((string) $scanResult->body_preview);
        if ($body === '') {
            $this->result($run, $endpoint, $scanResult, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_RESPONSE_SCHEMA, ContractValidationResult::STATUS_WARNING, ContractValidationResult::SEVERITY_MEDIUM, __('messages.contract_validations.messages.no_body_evidence'), __('messages.contract_validations.expected.body_preview'), null);
            return;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->result($run, $endpoint, $scanResult, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_RESPONSE_SCHEMA, ContractValidationResult::STATUS_WARNING, ContractValidationResult::SEVERITY_MEDIUM, __('messages.contract_validations.messages.body_not_json'), __('messages.contract_validations.expected.valid_json_body'), Str::limit($body, 160));
            return;
        }

        $resolved = $this->resolveSchema($schema, $document);
        $problems = $this->schemaProblems($resolved, $decoded, '$', $document);
        $passes = $problems === [];

        $this->result($run, $endpoint, $scanResult, $endpoint->method, $endpoint->path, ContractValidationResult::CHECK_RESPONSE_SCHEMA, $passes ? ContractValidationResult::STATUS_PASS : ContractValidationResult::STATUS_FAIL, $passes ? ContractValidationResult::SEVERITY_LOW : ContractValidationResult::SEVERITY_HIGH, $passes ? __('messages.contract_validations.messages.schema_matches') : __('messages.contract_validations.messages.schema_mismatch'), $this->schemaSummary($resolved), $passes ? __('messages.contract_validations.actual.schema_valid') : implode('; ', array_slice($problems, 0, 5)), ['problems' => $problems]);
    }

    /** @return array<int,string> */
    private function responseStatuses(array $responses): array
    {
        $statuses = [];
        foreach (array_keys($responses) as $status) {
            if (is_int($status) || (is_string($status) && preg_match('/^\d{3}$/', $status))) {
                $statuses[] = (string) $status;
            }
        }

        sort($statuses);

        return $statuses;
    }

    /** @param array<int,string> $statuses */
    private function primarySuccessStatus(array $statuses): ?string
    {
        foreach (['200', '201', '202', '204'] as $preferred) {
            if (in_array($preferred, $statuses, true)) {
                return $preferred;
            }
        }

        foreach ($statuses as $status) {
            if (str_starts_with($status, '2')) {
                return $status;
            }
        }

        return $statuses[0] ?? null;
    }

    /** @return array<int,string> */
    private function contentTypesForStatus(array $responses, ?string $status): array
    {
        if ($status === null) {
            return [];
        }

        $response = $responses[$status] ?? $responses[(int) $status] ?? null;
        if (! is_array($response) || ! is_array($response['content'] ?? null)) {
            return [];
        }

        return array_values(array_map('strval', array_keys($response['content'])));
    }

    /** @return array<string,mixed>|null */
    private function schemaForStatus(array $responses, ?string $status, ?string $contentType): ?array
    {
        if ($status === null || $contentType === null) {
            return null;
        }

        $response = $responses[$status] ?? $responses[(int) $status] ?? null;
        $schema = Arr::get($response, 'content.'.$contentType.'.schema');

        return is_array($schema) ? $schema : null;
    }

    /** @return array<string,mixed> */
    private function resolveSchema(array $schema, array $document): array
    {
        $ref = $schema['$ref'] ?? null;
        if (is_string($ref) && str_starts_with($ref, '#/')) {
            $resolved = Arr::get($document, str_replace('/', '.', substr($ref, 2)));
            if (is_array($resolved)) {
                return $this->resolveSchema($resolved, $document);
            }
        }

        if (is_array($schema['allOf'] ?? null)) {
            $merged = ['type' => 'object', 'properties' => [], 'required' => []];
            foreach ($schema['allOf'] as $part) {
                if (! is_array($part)) {
                    continue;
                }
                $resolved = $this->resolveSchema($part, $document);
                $merged['properties'] = array_merge($merged['properties'], is_array($resolved['properties'] ?? null) ? $resolved['properties'] : []);
                $merged['required'] = array_values(array_unique(array_merge($merged['required'], is_array($resolved['required'] ?? null) ? $resolved['required'] : [])));
            }
            return $merged;
        }

        return $schema;
    }

    /** @return array<int,string> */
    private function schemaProblems(array $schema, mixed $data, string $path, array $document): array
    {
        $schema = $this->resolveSchema($schema, $document);
        $type = $this->schemaType($schema);
        $problems = [];

        if (! $this->matchesType($type, $data)) {
            return [__('messages.contract_validations.schema_problem_type', ['path' => $path, 'type' => $type])];
        }

        if ($type === 'object') {
            $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
            foreach ($required as $field) {
                if (is_string($field) && (! is_array($data) || ! array_key_exists($field, $data))) {
                    $problems[] = __('messages.contract_validations.schema_problem_required', ['path' => $path, 'field' => $field]);
                }
            }

            $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
            foreach ($properties as $field => $propertySchema) {
                if (! is_string($field) || ! is_array($propertySchema) || ! is_array($data) || ! array_key_exists($field, $data)) {
                    continue;
                }

                $problems = array_merge($problems, $this->schemaProblems($propertySchema, $data[$field], $path.'.'.$field, $document));
            }
        }

        if ($type === 'array' && is_array($data) && is_array($schema['items'] ?? null) && array_is_list($data) && $data !== []) {
            $problems = array_merge($problems, $this->schemaProblems($schema['items'], $data[0], $path.'[0]', $document));
        }

        return $problems;
    }

    private function schemaType(array $schema): string
    {
        $type = $schema['type'] ?? null;
        if (is_string($type)) {
            return $type;
        }
        if (is_array($schema['properties'] ?? null) || is_array($schema['required'] ?? null)) {
            return 'object';
        }
        if (is_array($schema['items'] ?? null)) {
            return 'array';
        }

        return 'mixed';
    }

    private function matchesType(string $type, mixed $data): bool
    {
        return match ($type) {
            'object' => is_array($data) && ! array_is_list($data),
            'array' => is_array($data) && array_is_list($data),
            'string' => is_string($data),
            'integer' => is_int($data),
            'number' => is_int($data) || is_float($data),
            'boolean' => is_bool($data),
            default => true,
        };
    }

    private function schemaSummary(array $schema): string
    {
        $type = $this->schemaType($schema);
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];

        if ($required !== []) {
            return $type.' required: '.implode(', ', array_map('strval', $required));
        }

        return $type;
    }

    private function result(ContractValidationRun $run, ?Endpoint $endpoint, ?ScanResult $scanResult, ?string $method, ?string $path, string $checkType, string $status, string $severity, string $message, ?string $expected = null, ?string $actual = null, ?array $evidence = null): ContractValidationResult
    {
        return $run->results()->create([
            'project_id' => $run->project_id,
            'endpoint_id' => $endpoint?->id,
            'scan_result_id' => $scanResult?->id,
            'method' => $method,
            'path' => $path,
            'check_type' => $checkType,
            'severity' => $severity,
            'status' => $status,
            'message' => $message,
            'expected' => $expected,
            'actual' => $actual,
            'evidence_json' => $evidence,
        ]);
    }

    /** @param array<string,array<string,mixed>> $operations */
    private function completeRun(ContractValidationRun $run, array $operations): ContractValidationRun
    {
        $results = $run->results()->get();
        $run->update([
            'status' => ContractValidationRun::STATUS_COMPLETED,
            'finished_at' => now(),
            'total_checks' => $results->count(),
            'passed_count' => $results->where('status', ContractValidationResult::STATUS_PASS)->count(),
            'warning_count' => $results->where('status', ContractValidationResult::STATUS_WARNING)->count(),
            'failed_count' => $results->where('status', ContractValidationResult::STATUS_FAIL)->count(),
            'breaking_count' => $results->where('status', ContractValidationResult::STATUS_FAIL)->whereIn('severity', [ContractValidationResult::SEVERITY_HIGH, ContractValidationResult::SEVERITY_CRITICAL])->count(),
            'missing_endpoint_count' => $results->where('check_type', ContractValidationResult::CHECK_OPERATION_IMPLEMENTED)->where('status', ContractValidationResult::STATUS_FAIL)->count(),
            'undocumented_endpoint_count' => $results->where('check_type', ContractValidationResult::CHECK_OPERATION_DOCUMENTED)->where('status', ContractValidationResult::STATUS_WARNING)->count(),
            'schema_checked_count' => $results->where('check_type', ContractValidationResult::CHECK_RESPONSE_SCHEMA)->whereIn('status', [ContractValidationResult::STATUS_PASS, ContractValidationResult::STATUS_FAIL])->count(),
            'summary_json' => [
                'contract_operations' => count($operations),
                'checked_at' => now()->toIso8601String(),
            ],
        ]);

        return $run->refresh();
    }

    private function key(string $method, string $path): string
    {
        return strtoupper($method).' '.strtolower(Endpoint::normalizePath($path));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
