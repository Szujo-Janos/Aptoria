<?php

namespace App\Services\Endpoints;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Environment;
use App\Models\AuthProfile;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\TestSuite;
use App\Services\Security\NetworkTargetGuard;
use Illuminate\Support\Arr;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EndpointImportService
{
    public function __construct(
        private readonly PathParameterResolver $pathParameters,
        private readonly NetworkTargetGuard $networkGuard,
    ) {
    }

    /** @var array<string,string> */
    private const OPENAPI_METHODS = [
        'get' => Endpoint::METHOD_GET,
        'post' => Endpoint::METHOD_POST,
        'put' => Endpoint::METHOD_PUT,
        'patch' => Endpoint::METHOD_PATCH,
        'delete' => Endpoint::METHOD_DELETE,
        'head' => Endpoint::METHOD_HEAD,
        'options' => Endpoint::METHOD_OPTIONS,
    ];

    public function fetchRemotePayload(string $url): string
    {
        $url = trim($url);
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'source_url' => __('messages.import_preview.reason_remote_url_invalid'),
            ]);
        }

        if (! $this->networkGuard->isValidHttpUrl($url)) {
            throw ValidationException::withMessages([
                'source_url' => __('messages.import_preview.reason_remote_url_invalid'),
            ]);
        }

        $this->assertRemoteUrlIsAllowed($url);

        try {
            $response = $this->fetchWithGuardedRedirects($url);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'source_url' => __('messages.import_preview.reason_remote_fetch_failed'),
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'source_url' => __('messages.import_preview.reason_remote_fetch_status', ['status' => $response->status()]),
            ]);
        }

        $body = $response->body();
        if (trim($body) === '') {
            throw ValidationException::withMessages([
                'source_url' => __('messages.import_preview.reason_remote_fetch_empty'),
            ]);
        }

        if (strlen($body) > 200000) {
            throw ValidationException::withMessages([
                'source_url' => __('messages.import_preview.reason_remote_fetch_too_large'),
            ]);
        }

        return $body;
    }

    /**
     * @return array{total:int,valid:int,created:int,updated:int,skipped:int,duplicates:int,invalid:int,rows:array<int,array<string,mixed>>}
     */
    public function preview(Project $project, string $format, string $payload, ?string $postmanEnvironmentPayload = null, array $options = []): array
    {
        $context = $this->buildImportContext($format, $payload, $postmanEnvironmentPayload, $options);
        $items = $this->parse($format, $payload, $context);
        $rows = [];
        $seen = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $duplicates = 0;
        $invalid = 0;

        foreach ($items as $index => $item) {
            $rowNumber = (int) Arr::get($item, '_row_number', $index + 1);
            $method = strtoupper(trim((string) Arr::get($item, 'method', 'GET')));
            $rawPath = trim((string) Arr::get($item, 'path', ''));
            $path = Endpoint::normalizePath($rawPath);
            $reasons = [];

            $parseError = $this->nullableString(Arr::get($item, '_parse_error'));
            if ($parseError !== null) {
                $reasons[] = $parseError;
            }

            if (! in_array($method, Endpoint::METHODS, true)) {
                $reasons[] = __('messages.import_preview.reason_invalid_method');
            }

            if ($rawPath === '' || $path === '/') {
                $reasons[] = __('messages.import_preview.reason_invalid_path');
            }

            $risk = strtolower((string) Arr::get($item, 'risk_level', Endpoint::RISK_REVIEW));
            if (! in_array($risk, Endpoint::RISKS, true)) {
                $risk = Endpoint::RISK_REVIEW;
            }

            $key = $method.' '.$path;
            if ($reasons === [] && isset($seen[$key])) {
                $reasons[] = __('messages.import_preview.reason_duplicate_payload');
                $duplicates++;
            }

            $exists = $reasons === []
                ? $project->endpoints()->where('method', $method)->where('path', $path)->exists()
                : false;

            $status = 'skipped';
            if ($reasons !== []) {
                $skipped++;
                $invalid++;
            } elseif ($exists) {
                $status = 'update';
                $updated++;
                $seen[$key] = true;
            } else {
                $status = 'create';
                $created++;
                $seen[$key] = true;
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'method' => $method,
                'path' => $path,
                'name' => $this->nullableString(Arr::get($item, 'name'), 150),
                'description' => $this->nullableString(Arr::get($item, 'description')),
                'tags' => $this->nullableString(Arr::get($item, 'tags'), 500),
                'auth_required' => $this->toBool(Arr::get($item, 'auth_required', false)),
                'expected_status' => $this->nullableInt(Arr::get($item, 'expected_status')),
                'expected_content_type' => $this->nullableString(Arr::get($item, 'expected_content_type'), 120),
                'request_headers' => $this->normalizeRequestHeaders(Arr::get($item, 'request_headers')),
                'request_body_type' => $this->nullableString(Arr::get($item, 'request_body_type'), 50),
                'request_body_preview' => $this->nullableString(Arr::get($item, 'request_body_preview'), 4000),
                'risk_level' => $risk,
                'risk_reason' => $this->nullableString(Arr::get($item, 'risk_reason')),
                'qa_notes' => $this->nullableString(Arr::get($item, 'qa_notes')),
                'postman_folders' => is_array(Arr::get($item, 'postman_folders')) ? Arr::get($item, 'postman_folders') : [],
                'postman_suite_name' => $this->nullableString(Arr::get($item, 'postman_suite_name'), 180),
                'postman_collection_name' => $this->nullableString(Arr::get($item, 'postman_collection_name'), 120),
                'postman_unresolved_variables' => is_array(Arr::get($item, 'postman_unresolved_variables')) ? Arr::get($item, 'postman_unresolved_variables') : [],
                'postman_response_examples_count' => $this->nullableInt(Arr::get($item, 'postman_response_examples_count')) ?? 0,
                'postman_assertions' => is_array(Arr::get($item, 'postman_assertions')) ? Arr::get($item, 'postman_assertions') : [],
                'postman_auth_profile' => is_array(Arr::get($item, 'postman_auth_profile')) ? Arr::get($item, 'postman_auth_profile') : null,
                'path_parameters' => $this->pathParameters->extractNames($path),
                'status' => $status,
                'exists' => $exists,
                'reasons' => $reasons,
            ];
        }

        $metadata = $context['metadata'] ?? [];
        if (($metadata['format'] ?? null) === 'postman') {
            $metadata['postman_schema'] = $context['compatibility']['schema'] ?? ($metadata['postman_schema'] ?? null);
            $metadata['compatibility_warnings'] = $context['compatibility']['warnings'] ?? [];
            $metadata['response_examples_count'] = array_sum(array_map(fn (array $row): int => (int) ($row['postman_response_examples_count'] ?? 0), $rows));
            $metadata['assertions_count'] = array_sum(array_map(fn (array $row): int => count($row['postman_assertions'] ?? []), $rows));
            $metadata['test_suites_count'] = count(array_unique(array_filter(array_map(fn (array $row): ?string => $row['postman_suite_name'] ?? null, $rows))));
            $metadata['auth_profiles_count'] = count(array_unique(array_filter(array_map(fn (array $row): ?string => is_array($row['postman_auth_profile'] ?? null) ? ($row['postman_auth_profile']['name'] ?? null) : null, $rows))));
            $metadata['unsupported_auth_types'] = array_values(array_unique(array_merge(...array_map(fn (array $row): array => $row['postman_unsupported_auth_types'] ?? [], $rows ?: [[]]))));
            $metadata['unsupported_scripts_count'] = array_sum(array_map(fn (array $row): int => (int) ($row['postman_unsupported_scripts_count'] ?? 0), $rows));
            $metadata['unresolved_variables'] = array_values(array_unique(array_merge(...array_map(fn (array $row): array => $row['postman_unresolved_variables'] ?? [], $rows ?: [[]]))));
        }

        return [
            'total' => count($rows),
            'valid' => $created + $updated,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'duplicates' => $duplicates,
            'invalid' => $invalid,
            'rows' => $rows,
            'metadata' => $metadata,
        ];
    }

    /**
     * @return array{created:int,updated:int,skipped:int,duplicates:int,invalid:int,total:int,valid:int}
     */
    public function import(Project $project, string $format, string $payload, ?int $environmentId = null, ?int $authProfileId = null, ?string $postmanEnvironmentPayload = null, array $options = []): array
    {
        $preview = $this->preview($project, $format, $payload, $postmanEnvironmentPayload, $options);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $environmentId = $this->validProjectId($project, 'environments', $environmentId);
        $authProfileId = $this->validProjectId($project, 'authProfiles', $authProfileId);
        $metadata = $preview['metadata'] ?? [];
        $createdEnvironmentId = $environmentId ?: $this->createPostmanEnvironmentIfRequested($project, $metadata, $options);
        $authProfileCache = [];

        foreach ($preview['rows'] as $row) {
            if (! in_array($row['status'], ['create', 'update'], true)) {
                $skipped++;
                continue;
            }

            $data = [
                'environment_id' => $createdEnvironmentId ?: $environmentId,
                'auth_profile_id' => $authProfileId ?: $this->createPostmanAuthProfileIfRequested($project, $row, $options, $authProfileCache),
                'method' => $row['method'],
                'path' => $row['path'],
                'name' => $row['name'],
                'description' => $row['description'],
                'tags' => $row['tags'],
                'auth_required' => $row['auth_required'],
                'expected_status' => $row['expected_status'],
                'expected_content_type' => $row['expected_content_type'],
                'request_headers' => $row['request_headers'],
                'request_body_type' => $row['request_body_type'],
                'request_body_preview' => $row['request_body_preview'],
                'risk_level' => $row['risk_level'],
                'risk_reason' => $row['risk_reason'],
                'qa_notes' => $row['qa_notes'],
                'is_active' => true,
                'excluded_from_scan' => false,
            ];

            $endpoint = $project->endpoints()->where('method', $row['method'])->where('path', $row['path'])->first();
            if ($endpoint instanceof Endpoint) {
                $endpoint->update($data);
                $this->pathParameters->ensureProjectDefaultsFromPath($project, $row['path']);
                $this->createPostmanArtifactsIfRequested($project, $endpoint, $row, $options);
                $updated++;
            } else {
                $endpoint = $project->endpoints()->create($data);
                $this->pathParameters->ensureProjectDefaultsFromPath($project, $row['path']);
                $this->createPostmanArtifactsIfRequested($project, $endpoint, $row, $options);
                $created++;
            }
        }

        return [
            'total' => $preview['total'],
            'valid' => $created + $updated,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'duplicates' => $preview['duplicates'],
            'invalid' => $preview['invalid'],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function parse(string $format, string $payload, array $context = []): array
    {
        return match ($format) {
            'json' => $this->parseJsonPayload($payload),
            'openapi' => $this->parseOpenApiPayload($payload),
            'postman' => $this->parsePostmanPayload($payload, $context),
            default => $this->parseCsvPayload($payload),
        };
    }

    /** @return array<int,array<string,mixed>> */
    private function parseJsonPayload(string $payload): array
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return [];
        }

        $items = array_is_list($decoded) ? $decoded : ($decoded['endpoints'] ?? []);
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, 'is_array'));
    }

    /** @return array<int,array<string,mixed>> */
    private function parseCsvPayload(string $payload): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $payload) ?: [];
        $lines = array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));
        if ($lines === []) {
            return [];
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn (string $value): string => Str::snake(trim($value)), $header);

        $items = [];
        foreach ($lines as $index => $line) {
            $values = str_getcsv($line);
            $values = array_pad($values, count($header), null);
            $row = array_combine($header, array_slice($values, 0, count($header)));
            if (! is_array($row)) {
                continue;
            }
            $row['_row_number'] = $index + 2;
            $items[] = $row;
        }

        return $items;
    }

    /** @return array<string,mixed> */
    public function decodeOpenApiDocument(string $payload): array
    {
        [$decoded, $parseError] = $this->decodeOpenApiPayload($payload);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'contract_payload' => $parseError ?? __('messages.import_preview.reason_openapi_parse_failed'),
            ]);
        }

        return $decoded;
    }

    /** @return array<int,array<string,mixed>> */
    private function parseOpenApiPayload(string $payload): array
    {
        [$decoded, $parseError] = $this->decodeOpenApiPayload($payload);

        if (! is_array($decoded)) {
            return $this->openApiErrorRow($parseError ?? __('messages.import_preview.reason_openapi_parse_failed'));
        }

        return $this->openApiDocumentToRows($decoded);
    }

    /** @return array{0:?array<string,mixed>,1:?string} */
    private function decodeOpenApiPayload(string $payload): array
    {
        $decoded = json_decode($payload, true);
        if (is_array($decoded)) {
            return [$decoded, null];
        }

        if (function_exists('yaml_parse')) {
            try {
                $yaml = yaml_parse($payload);
                if (is_array($yaml)) {
                    return [$yaml, null];
                }
            } catch (Throwable) {
                // Fall through to the lightweight parser below.
            }
        }

        try {
            $yaml = $this->parseSimpleYamlDocument($payload);
            if (is_array($yaml)) {
                return [$yaml, null];
            }
        } catch (Throwable) {
            // Return a user-facing parse message below.
        }

        return [null, __('messages.import_preview.reason_openapi_parse_failed')];
    }

    /** @return array<int,array<string,mixed>> */
    private function openApiDocumentToRows(array $decoded): array
    {
        $paths = $decoded['paths'] ?? null;
        if (! is_array($paths) || $paths === []) {
            return $this->openApiErrorRow(__('messages.import_preview.reason_invalid_openapi'));
        }

        $items = [];
        foreach ($paths as $path => $pathItem) {
            if (! is_string($path) || ! is_array($pathItem)) {
                continue;
            }

            foreach (self::OPENAPI_METHODS as $openApiMethod => $method) {
                $operation = $pathItem[$openApiMethod] ?? null;
                if (! is_array($operation)) {
                    continue;
                }

                $items[] = $this->openApiOperationToEndpointRow($decoded, $path, $method, $operation, count($items) + 1);
            }
        }

        if ($items === []) {
            return $this->openApiErrorRow(__('messages.import_preview.reason_openapi_no_operations'));
        }

        return $items;
    }

    /** @return array<string,mixed>|null */
    private function parseSimpleYamlDocument(string $payload): ?array
    {
        $rawLines = preg_split('/\r\n|\r|\n/', str_replace("\t", '  ', $payload)) ?: [];
        $lines = [];

        foreach ($rawLines as $rawLine) {
            $rawLine = rtrim($this->stripYamlComment($rawLine));
            if (trim($rawLine) === '') {
                continue;
            }

            preg_match('/^(\s*)(.*)$/', $rawLine, $matches);
            $lines[] = [
                'indent' => strlen($matches[1] ?? ''),
                'text' => $matches[2] ?? '',
            ];
        }

        if ($lines === []) {
            return null;
        }

        $index = 0;
        $parsed = $this->parseYamlBlock($lines, $index, $lines[0]['indent']);

        return is_array($parsed) ? $parsed : null;
    }

    private function stripYamlComment(string $line): string
    {
        $inSingle = false;
        $inDouble = false;
        $length = strlen($line);

        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];
            if ($char === "'" && ! $inDouble) {
                $inSingle = ! $inSingle;
            } elseif ($char === '"' && ! $inSingle) {
                $inDouble = ! $inDouble;
            } elseif ($char === '#' && ! $inSingle && ! $inDouble && ($i === 0 || ctype_space($line[$i - 1]))) {
                return substr($line, 0, $i);
            }
        }

        return $line;
    }

    /**
     * @param array<int,array{indent:int,text:string}> $lines
     * @return array<mixed>|string|int|float|bool|null
     */
    private function parseYamlBlock(array $lines, int &$index, int $indent): mixed
    {
        $isList = isset($lines[$index])
            && $lines[$index]['indent'] === $indent
            && str_starts_with($lines[$index]['text'], '- ');

        if ($isList) {
            $items = [];
            while (isset($lines[$index]) && $lines[$index]['indent'] >= $indent) {
                if ($lines[$index]['indent'] < $indent || ! str_starts_with($lines[$index]['text'], '- ')) {
                    break;
                }
                if ($lines[$index]['indent'] > $indent) {
                    $index++;
                    continue;
                }

                $text = trim(substr($lines[$index]['text'], 2));
                $index++;

                if ($text === '') {
                    $items[] = isset($lines[$index]) ? $this->parseYamlBlock($lines, $index, $lines[$index]['indent']) : null;
                    continue;
                }

                $parts = $this->splitYamlKeyValue($text);
                if ($parts !== null) {
                    [$key, $value] = $parts;
                    $item = [$key => $value === null ? (isset($lines[$index]) ? $this->parseYamlBlock($lines, $index, $lines[$index]['indent']) : null) : $this->parseYamlScalar($value)];

                    while (isset($lines[$index]) && $lines[$index]['indent'] > $indent) {
                        $nested = $this->parseYamlBlock($lines, $index, $lines[$index]['indent']);
                        if (is_array($nested)) {
                            $item = array_merge($item, $nested);
                        }
                    }
                    $items[] = $item;
                } else {
                    $items[] = $this->parseYamlScalar($text);
                }
            }

            return $items;
        }

        $map = [];
        while (isset($lines[$index]) && $lines[$index]['indent'] >= $indent) {
            if ($lines[$index]['indent'] < $indent) {
                break;
            }
            if ($lines[$index]['indent'] > $indent) {
                $index++;
                continue;
            }

            $parts = $this->splitYamlKeyValue($lines[$index]['text']);
            if ($parts === null) {
                $index++;
                continue;
            }

            [$key, $value] = $parts;
            $index++;
            if ($value === null) {
                $map[$key] = isset($lines[$index]) && $lines[$index]['indent'] > $indent
                    ? $this->parseYamlBlock($lines, $index, $lines[$index]['indent'])
                    : null;
            } else {
                $map[$key] = $this->parseYamlScalar($value);
            }
        }

        return $map;
    }

    /** @return array{0:string,1:?string}|null */
    private function splitYamlKeyValue(string $text): ?array
    {
        if (! preg_match('/^([^:]+):(.*)$/', $text, $matches)) {
            return null;
        }

        $key = $this->unquoteYamlString(trim($matches[1]));
        $value = trim((string) $matches[2]);

        return [$key, $value === '' ? null : $value];
    }

    private function parseYamlScalar(string $value): mixed
    {
        $value = trim($value);

        if ($value === '{}') {
            return [];
        }
        if ($value === '[]') {
            return [];
        }
        if (preg_match('/^\[(.*)]$/', $value, $matches)) {
            $inner = trim($matches[1]);
            if ($inner === '') {
                return [];
            }

            return array_map(fn (string $item): mixed => $this->parseYamlScalar($item), str_getcsv($inner));
        }

        $lower = strtolower($value);
        if (in_array($lower, ['true', 'false'], true)) {
            return $lower === 'true';
        }
        if (in_array($lower, ['null', '~'], true)) {
            return null;
        }
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $this->unquoteYamlString($value);
    }

    private function unquoteYamlString(string $value): string
    {
        $value = trim($value);
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    private function assertRemoteUrlIsAllowed(string $url): void
    {
        if ($this->networkGuard->isBlocked($url, false, false)) {
            throw ValidationException::withMessages([
                'source_url' => __('messages.import_preview.reason_remote_url_blocked'),
            ]);
        }
    }

    private function fetchWithGuardedRedirects(string $url): Response
    {
        $currentUrl = $url;
        $maxRedirects = 3;

        for ($redirects = 0; $redirects <= $maxRedirects; $redirects++) {
            $this->assertRemoteUrlIsAllowed($currentUrl);

            $response = Http::timeout(20)
                ->connectTimeout(10)
                ->withHeaders(['User-Agent' => 'Aptoria/'.config('aptoria.version', '1.1.1').' Collection Import'])
                ->withOptions([
                    'allow_redirects' => false,
                    'http_errors' => false,
                ])
                ->get($currentUrl);

            if (! in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                return $response;
            }

            $location = $response->header('Location');
            if (! is_string($location) || trim($location) === '') {
                return $response;
            }

            $currentUrl = $this->networkGuard->resolveRedirectUrl($currentUrl, $location);
        }

        throw ValidationException::withMessages([
            'source_url' => __('messages.import_preview.reason_remote_fetch_failed'),
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    private function openApiErrorRow(string $message): array
    {
        return [[
            '_row_number' => 1,
            '_parse_error' => $message,
            'method' => '',
            'path' => '',
        ]];
    }

    /** @return array<string,mixed> */
    private function openApiOperationToEndpointRow(array $document, string $path, string $method, array $operation, int $rowNumber): array
    {
        $responses = is_array($operation['responses'] ?? null) ? $operation['responses'] : [];
        $expectedStatus = $this->openApiExpectedStatus($responses);
        $expectedContentType = $this->openApiContentType($document, $operation, $responses, $expectedStatus);
        $authRequired = $this->openApiAuthRequired($document, $operation);
        $summary = $this->nullableString($operation['summary'] ?? null);
        $description = $this->nullableString($operation['description'] ?? null);
        $operationId = $this->nullableString($operation['operationId'] ?? null);
        $tags = $this->openApiTags($operation);
        $qaNotes = [];

        if (! in_array($method, [Endpoint::METHOD_GET, Endpoint::METHOD_HEAD], true)) {
            $qaNotes[] = __('messages.endpoints.openapi_state_changing_note');
        }

        if (str_contains($path, '{')) {
            $qaNotes[] = __('messages.endpoints.openapi_path_params_note');
            $qaNotes[] = __('messages.endpoints.openapi_path_params_auto_defaults_note');
        }
        return [
            '_row_number' => $rowNumber,
            'method' => $method,
            'path' => $path,
            'name' => $this->nullableString($operationId ?? $summary ?? $method.' '.$path, 150),
            'description' => $description ?? $summary,
            'tags' => $tags,
            'auth_required' => $authRequired,
            'expected_status' => $expectedStatus,
            'expected_content_type' => $expectedContentType,
            'risk_level' => $this->openApiRiskLevel($method, $path, $authRequired),
            'risk_reason' => __('messages.endpoints.openapi_imported_risk_reason'),
            'qa_notes' => $qaNotes === [] ? null : implode(' ', $qaNotes),
        ];
    }

    private function openApiExpectedStatus(array $responses): ?int
    {
        foreach ([200, 201, 202, 204] as $preferred) {
            if (array_key_exists((string) $preferred, $responses) || array_key_exists($preferred, $responses)) {
                return $preferred;
            }
        }

        $candidates = [];
        foreach (array_keys($responses) as $status) {
            if (is_string($status) && preg_match('/^2\d\d$/', $status)) {
                $candidates[] = (int) $status;
            } elseif (is_int($status) && $status >= 200 && $status < 300) {
                $candidates[] = $status;
            }
        }

        sort($candidates);

        return $candidates[0] ?? null;
    }

    private function openApiContentType(array $document, array $operation, array $responses, ?int $status): ?string
    {
        if ($status !== null) {
            $response = $responses[(string) $status] ?? $responses[$status] ?? null;
            if (is_array($response) && is_array($response['content'] ?? null)) {
                $contentTypes = array_keys($response['content']);
                if ($contentTypes !== []) {
                    return $this->nullableString((string) $contentTypes[0], 120);
                }
            }
        }

        $produces = $operation['produces'] ?? $document['produces'] ?? null;
        if (is_array($produces) && $produces !== []) {
            return $this->nullableString((string) $produces[0], 120);
        }

        return $status === 204 ? null : 'application/json';
    }

    private function openApiAuthRequired(array $document, array $operation): bool
    {
        if (array_key_exists('security', $operation)) {
            return is_array($operation['security']) && $operation['security'] !== [];
        }

        return is_array($document['security'] ?? null) && $document['security'] !== [];
    }

    private function openApiRiskLevel(string $method, string $path, bool $authRequired): string
    {
        $lowerPath = strtolower($path);

        if ($method === Endpoint::METHOD_DELETE || str_contains($lowerPath, 'admin') || str_contains($lowerPath, 'internal')) {
            return Endpoint::RISK_HIGH;
        }

        if (in_array($method, [Endpoint::METHOD_POST, Endpoint::METHOD_PUT, Endpoint::METHOD_PATCH], true)) {
            return Endpoint::RISK_REVIEW;
        }

        if ($authRequired) {
            return Endpoint::RISK_REVIEW;
        }

        return Endpoint::RISK_PUBLIC;
    }

    private function openApiTags(array $operation): ?string
    {
        $tags = $operation['tags'] ?? null;
        if (! is_array($tags)) {
            return null;
        }

        return $this->nullableString(implode(',', array_map('strval', $tags)), 500);
    }


    /** @return array<int,array<string,mixed>> */
    private function parsePostmanPayload(string $payload, array $context = []): array
    {
        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            return $this->openApiErrorRow(__('messages.import_preview.reason_postman_parse_failed'));
        }

        if (! is_array($decoded['item'] ?? null)) {
            return $this->openApiErrorRow(__('messages.import_preview.reason_invalid_postman'));
        }

        $compatibility = $this->postmanCompatibility($decoded);

        $items = [];
        $context['compatibility'] = $compatibility;
        $this->collectPostmanItems($decoded['item'], [], $decoded['auth'] ?? null, $items, $context, $this->postmanEvents($decoded['event'] ?? []));

        if ($items === []) {
            return $this->openApiErrorRow(__('messages.import_preview.reason_postman_no_requests'));
        }

        return $items;
    }

    /**
     * @param array<int,mixed> $nodes
     * @param array<int,string> $folders
     * @param array<int,array<string,mixed>> $items
     */
    private function collectPostmanItems(array $nodes, array $folders, mixed $inheritedAuth, array &$items, array $context = [], array $inheritedEvents = []): void
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $name = $this->postmanText($node['name'] ?? null) ?? __('messages.common.not_available');
            $nodeAuth = $node['auth'] ?? $inheritedAuth;
            $nodeEvents = [...$inheritedEvents, ...$this->postmanEvents($node['event'] ?? [])];

            if (is_array($node['item'] ?? null)) {
                $this->collectPostmanItems($node['item'], [...$folders, $name], $nodeAuth, $items, $context, $nodeEvents);
                continue;
            }

            if (array_key_exists('request', $node)) {
                $row = $this->postmanRequestToEndpointRow($node, $folders, $nodeAuth, count($items) + 1, $context, $nodeEvents);
                if ($row !== null) {
                    $items[] = $row;
                }
            }
        }
    }

    /** @param array<int,string> $folders */
    private function postmanRequestToEndpointRow(array $item, array $folders, mixed $inheritedAuth, int $rowNumber, array $context = [], array $events = []): ?array
    {
        $request = $item['request'] ?? null;
        if (is_string($request)) {
            $request = ['method' => Endpoint::METHOD_GET, 'url' => $request];
        }
        if (! is_array($request)) {
            return null;
        }

        $method = strtoupper((string) ($request['method'] ?? Endpoint::METHOD_GET));
        $pathResult = $this->postmanUrlToPath($request['url'] ?? '', $context);
        $path = $pathResult['path'];
        $headers = $this->postmanHeaders($request['header'] ?? [], $context);
        $rawHeaders = $this->postmanHeadersRaw($request['header'] ?? [], $context);
        $body = $this->postmanBody($request['body'] ?? null, $context);
        $auth = $request['auth'] ?? $inheritedAuth;
        $authDescriptor = $this->postmanAuthDescriptor($auth, $rawHeaders, $context);
        $authRequired = $this->postmanAuthRequired($auth, $headers);
        $expectedStatus = $this->postmanExpectedStatus($item['response'] ?? null);
        $expectedContentType = $this->postmanExpectedContentType($item['response'] ?? null, $headers);
        $name = $this->postmanText($item['name'] ?? null) ?? $method.' '.$path;
        $description = $this->postmanText($request['description'] ?? ($item['description'] ?? null));
        $events = [...$events, ...$this->postmanEvents($request['event'] ?? [])];
        $assertions = $this->postmanAssertionsFromEvents($events, $expectedStatus);
        $examplesCount = is_array($item['response'] ?? null) ? count($item['response']) : 0;
        $qaNotes = [__('messages.endpoints.postman_imported_note')];

        if (! in_array($method, [Endpoint::METHOD_GET, Endpoint::METHOD_HEAD], true)) {
            $qaNotes[] = __('messages.endpoints.postman_state_changing_note');
        }
        if ($headers !== []) {
            $qaNotes[] = __('messages.endpoints.postman_headers_note', ['count' => count($headers)]);
        }
        if ($body['preview'] !== null) {
            $qaNotes[] = __('messages.endpoints.postman_body_preview_note');
        }
        if (str_contains($path, '{')) {
            $qaNotes[] = __('messages.endpoints.openapi_path_params_note');
            $qaNotes[] = __('messages.endpoints.openapi_path_params_auto_defaults_note');
        }
        if ($examplesCount > 0) {
            $qaNotes[] = __('messages.endpoints.postman_examples_note', ['count' => $examplesCount]);
        }
        if ($assertions !== []) {
            $qaNotes[] = __('messages.endpoints.postman_tests_note', ['count' => count($assertions)]);
        }
        if (($pathResult['unresolved'] ?? []) !== []) {
            $qaNotes[] = __('messages.endpoints.postman_unresolved_variables_note', ['variables' => implode(', ', $pathResult['unresolved'])]);
        }

        $tags = array_values(array_filter(['postman', ...$folders], fn (string $value): bool => trim($value) !== ''));

        return [
            '_row_number' => $rowNumber,
            'method' => $method,
            'path' => $path,
            'name' => $this->nullableString($name, 150),
            'description' => $description,
            'tags' => $this->nullableString(implode(',', $tags), 500),
            'auth_required' => $authRequired,
            'expected_status' => $expectedStatus,
            'expected_content_type' => $expectedContentType,
            'request_headers' => $headers,
            'request_body_type' => $body['type'],
            'request_body_preview' => $body['preview'],
            'risk_level' => $this->openApiRiskLevel($method, $path, $authRequired),
            'risk_reason' => __('messages.endpoints.postman_imported_risk_reason'),
            'qa_notes' => implode(' ', $qaNotes),
            'postman_folders' => $folders,
            'postman_suite_name' => $this->postmanSuiteName($context, $folders),
            'postman_collection_name' => $context['collection_name'] ?? null,
            'postman_unresolved_variables' => array_values(array_unique(array_merge($pathResult['unresolved'] ?? [], $body['unresolved'] ?? []))),
            'postman_unsupported_auth_types' => $this->postmanUnsupportedAuthTypes($auth),
            'postman_unsupported_scripts_count' => $this->postmanUnsupportedScriptsCount($events),
            'postman_response_examples_count' => $examplesCount,
            'postman_assertions' => $assertions,
            'postman_auth_profile' => $authDescriptor,
        ];
    }

    /** @return array<string,mixed> */
    private function postmanCompatibility(array $collection): array
    {
        $schema = (string) ($collection['info']['schema'] ?? '');
        $warnings = [];

        if ($schema !== '' && ! str_contains($schema, 'v2.1.0') && ! str_contains($schema, 'v2.0.0')) {
            $warnings[] = __('messages.import_preview.postman_warning_schema', ['schema' => $schema]);
        }
        if (! isset($collection['info']['name'])) {
            $warnings[] = __('messages.import_preview.postman_warning_missing_name');
        }

        return [
            'schema' => $schema !== '' ? $schema : __('messages.common.not_available'),
            'warnings' => $warnings,
        ];
    }

    /** @return array<int,string> */
    private function postmanUnsupportedAuthTypes(mixed $auth): array
    {
        if (! is_array($auth)) {
            return [];
        }
        $type = strtolower((string) ($auth['type'] ?? ''));
        if ($type === '' || in_array($type, ['noauth', 'bearer', 'basic', 'apikey'], true)) {
            return [];
        }

        return [$type];
    }

    private function postmanUnsupportedScriptsCount(array $events): int
    {
        $count = 0;
        foreach ($events as $script) {
            $known = preg_match('/pm\.response\.to\.have\.status|responseTime|pm\.response\.to\.have\.header|pm\.expect\(\s*jsonData/i', $script) === 1;
            if (! $known) {
                $count++;
            }
        }

        return $count;
    }

    private function postmanUrlToPath(mixed $url, array $context = []): array
    {
        if (is_string($url)) {
            return $this->normalizePostmanRawUrl($url, $context);
        }

        if (! is_array($url)) {
            return ['path' => '', 'unresolved' => []];
        }

        $raw = $this->postmanText($url['raw'] ?? null);
        if ($raw !== null) {
            return $this->normalizePostmanRawUrl($raw, $context);
        }

        $path = $url['path'] ?? '';
        if (is_array($path)) {
            $path = '/'.implode('/', array_map(fn (mixed $segment): string => trim((string) $segment, '/'), $path));
        } else {
            $path = '/'.ltrim((string) $path, '/');
        }

        $query = $this->postmanQueryString($url['query'] ?? [], $context);
        $combined = $path.($query !== '' ? '?'.$query : '');
        $resolved = $this->resolvePostmanVariables($combined, $context, false);

        return ['path' => $this->normalizePostmanPathParameterSyntax($resolved['value']), 'unresolved' => $resolved['unresolved']];
    }

    private function normalizePostmanRawUrl(string $raw, array $context = []): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['path' => '', 'unresolved' => []];
        }

        $resolved = $this->resolvePostmanUrlVariables($raw, $context);
        $raw = $resolved['value'];

        if (Str::startsWith($raw, ['http://', 'https://'])) {
            $parts = parse_url($raw);
            $path = $parts['path'] ?? '/';
            if (! empty($parts['query'])) {
                $path .= '?'.$parts['query'];
            }

            return ['path' => $this->normalizePostmanPathParameterSyntax($path), 'unresolved' => $resolved['unresolved']];
        }

        $raw = preg_replace('/^\s*\{\{[^}]+\}\}\s*/', '', $raw) ?? $raw;
        $raw = $raw === '' ? '/' : $raw;

        if (! Str::startsWith($raw, '/')) {
            $raw = '/'.$raw;
        }

        return ['path' => $this->normalizePostmanPathParameterSyntax($raw), 'unresolved' => $resolved['unresolved']];
    }

    private function normalizePostmanPathParameterSyntax(string $path): string
    {
        $path = preg_replace('~(?<=/):([A-Za-z_][A-Za-z0-9_]*)~', '{$1}', $path) ?? $path;
        $path = preg_replace('~\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}~', '{$1}', $path) ?? $path;

        return $path;
    }

    private function postmanQueryString(mixed $query, array $context = []): string
    {
        if (! is_array($query)) {
            return '';
        }

        $parts = [];
        foreach ($query as $item) {
            if (! is_array($item) || ($item['disabled'] ?? false)) {
                continue;
            }
            $key = trim((string) ($item['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $value = array_key_exists('value', $item) ? (string) $item['value'] : '';
            $resolved = $this->resolvePostmanVariables($value, $context, false);
            $parts[] = $value === '' ? $key : $key.'='.$resolved['value'];
        }

        return implode('&', $parts);
    }


    /** @return array<int,array{key:string,value:string}> */
    private function postmanHeadersRaw(mixed $headers, array $context = []): array
    {
        if (! is_array($headers)) {
            return [];
        }

        $rows = [];
        foreach ($headers as $header) {
            if (! is_array($header) || ($header['disabled'] ?? false)) {
                continue;
            }
            $key = trim((string) ($header['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $rows[] = [
                'key' => $this->nullableString($key, 120) ?? $key,
                'value' => $this->resolvePostmanVariables((string) ($header['value'] ?? ''), $context)['value'],
            ];
        }

        return $rows;
    }

    /** @return array<int,array{key:string,value:string}> */
    private function postmanHeaders(mixed $headers, array $context = []): array
    {
        if (! is_array($headers)) {
            return [];
        }

        $rows = [];
        foreach ($headers as $header) {
            if (! is_array($header) || ($header['disabled'] ?? false)) {
                continue;
            }
            $key = trim((string) ($header['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $rows[] = [
                'key' => $this->nullableString($key, 120) ?? $key,
                'value' => $this->maskSensitiveHeaderValue($key, $this->resolvePostmanVariables((string) ($header['value'] ?? ''), $context)['value']),
            ];
        }

        return $rows;
    }

    /** @return array{type:?string,preview:?string} */
    private function postmanBody(mixed $body, array $context = []): array
    {
        if (! is_array($body)) {
            return ['type' => null, 'preview' => null];
        }

        $mode = $this->nullableString($body['mode'] ?? null, 50);
        $preview = null;

        if ($mode === 'raw') {
            $resolved = $this->resolvePostmanVariables((string) ($body['raw'] ?? ''), $context);
            $preview = $this->nullableString($this->maskSensitiveText($resolved['value']), 4000);
        } elseif ($mode === 'urlencoded' && is_array($body['urlencoded'] ?? null)) {
            $preview = $this->nullableString($this->postmanKeyValuePreview($body['urlencoded'], $context), 4000);
        } elseif ($mode === 'formdata' && is_array($body['formdata'] ?? null)) {
            $preview = $this->nullableString($this->postmanKeyValuePreview($body['formdata'], $context), 4000);
        } elseif ($mode === 'graphql' && is_array($body['graphql'] ?? null)) {
            $graphql = $this->resolvePostmanVariables(json_encode($body['graphql'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '', $context);
            $preview = $this->nullableString($this->maskSensitiveText($graphql['value']), 4000);
        }

        return ['type' => $mode, 'preview' => $preview, 'unresolved' => $resolved['unresolved'] ?? []];
    }

    private function postmanKeyValuePreview(array $rows, array $context = []): string
    {
        $parts = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ($row['disabled'] ?? false)) {
                continue;
            }
            $key = (string) ($row['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $resolved = $this->resolvePostmanVariables((string) ($row['value'] ?? ''), $context);
            $parts[] = $key.'='.$this->maskSensitiveText($resolved['value']);
        }

        return implode("\n", $parts);
    }

    private function postmanAuthRequired(mixed $auth, array $headers): bool
    {
        if (is_array($auth)) {
            $type = strtolower((string) ($auth['type'] ?? ''));
            if ($type !== '' && $type !== 'noauth') {
                return true;
            }
        }

        foreach ($headers as $header) {
            $key = strtolower((string) ($header['key'] ?? ''));
            if (in_array($key, ['authorization', 'x-api-key', 'x-auth-token'], true) || str_contains($key, 'token') || str_contains($key, 'secret')) {
                return true;
            }
        }

        return false;
    }

    private function postmanExpectedStatus(mixed $responses): ?int
    {
        if (! is_array($responses)) {
            return null;
        }

        foreach ($responses as $response) {
            if (! is_array($response)) {
                continue;
            }
            $code = $this->nullableInt($response['code'] ?? null);
            if ($code !== null && $code >= 200 && $code < 300) {
                return $code;
            }
        }

        return null;
    }

    private function postmanExpectedContentType(mixed $responses, array $headers): ?string
    {
        if (is_array($responses)) {
            foreach ($responses as $response) {
                if (! is_array($response) || ! is_array($response['header'] ?? null)) {
                    continue;
                }
                foreach ($response['header'] as $header) {
                    if (is_array($header) && strtolower((string) ($header['key'] ?? '')) === 'content-type') {
                        return $this->nullableString((string) ($header['value'] ?? ''), 120);
                    }
                }
            }
        }

        foreach ($headers as $header) {
            if (strtolower((string) ($header['key'] ?? '')) === 'accept') {
                return $this->nullableString((string) ($header['value'] ?? ''), 120);
            }
        }

        return null;
    }

    private function postmanText(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['content'] ?? $value['description'] ?? null;
        }

        return $this->nullableString($value);
    }


    /** @return array<string,mixed> */
    private function buildImportContext(string $format, string $payload, ?string $postmanEnvironmentPayload, array $options): array
    {
        if ($format !== 'postman') {
            return ['metadata' => []];
        }

        $collection = json_decode($payload, true);
        $environment = $this->decodePostmanEnvironmentPayload($postmanEnvironmentPayload);
        $collectionVariables = is_array($collection) ? $this->postmanVariableMap($collection['variable'] ?? []) : [];
        $environmentVariables = $this->postmanVariableMap($environment['values'] ?? []);
        $globals = $this->decodePostmanEnvironmentPayload(is_string($options['postman_globals_payload'] ?? null) ? $options['postman_globals_payload'] : null);
        $globalVariables = $this->postmanVariableMap($globals['values'] ?? []);
        $variables = [...$globalVariables, ...$collectionVariables, ...$environmentVariables];
        $collectionName = is_array($collection) ? $this->nullableString($collection['info']['name'] ?? null, 120) : null;
        $environmentName = $this->nullableString($environment['name'] ?? null, 100)
            ?? $this->nullableString(($collectionName ? $collectionName.' Environment' : null), 100)
            ?? 'Postman Environment';
        $baseUrl = $this->detectPostmanBaseUrl($variables, is_array($collection) ? $collection : []);
        $compatibility = is_array($collection) ? $this->postmanCompatibility($collection) : ['schema' => null, 'warnings' => []];

        return [
            'collection_name' => $collectionName,
            'environment_name' => $environmentName,
            'variables' => $variables,
            'collection_variables' => $collectionVariables,
            'environment_variables' => $environmentVariables,
            'global_variables' => $globalVariables,
            'base_url' => $baseUrl,
            'compatibility' => $compatibility,
            'options' => $options,
            'metadata' => [
                'format' => 'postman',
                'collection_name' => $collectionName,
                'environment_name' => $environmentName,
                'environment_base_url' => $baseUrl,
                'variables_count' => count($variables),
                'environment_variables_count' => count($environmentVariables),
                'collection_variables_count' => count($collectionVariables),
                'global_variables_count' => count($globalVariables),
                'postman_schema' => $compatibility['schema'] ?? null,
                'compatibility_warnings' => $compatibility['warnings'] ?? [],
                'masked_variables' => $this->maskedVariablePreview($variables),
                'create_environment' => $this->optionEnabled($options, 'postman_create_environment', true) && $baseUrl !== null,
                'create_auth_profile' => $this->optionEnabled($options, 'postman_create_auth_profile', true),
                'create_test_suites' => $this->optionEnabled($options, 'postman_create_test_suites', false),
                'create_assertions' => $this->optionEnabled($options, 'postman_create_assertions', true),
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function decodePostmanEnvironmentPayload(?string $payload): array
    {
        $payload = trim((string) $payload);
        if ($payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string,string> */
    private function postmanVariableMap(mixed $variables): array
    {
        if (! is_array($variables)) {
            return [];
        }

        $map = [];
        foreach ($variables as $row) {
            if (! is_array($row) || ($row['disabled'] ?? false) || ($row['enabled'] ?? true) === false) {
                continue;
            }
            $key = trim((string) ($row['key'] ?? $row['name'] ?? ''));
            if ($key === '') {
                continue;
            }
            $value = $row['currentValue'] ?? $row['value'] ?? $row['initialValue'] ?? '';
            if (is_scalar($value) || $value === null) {
                $map[$key] = (string) $value;
            }
        }

        return $map;
    }

    private function detectPostmanBaseUrl(array $variables, array $collection): ?string
    {
        foreach (['baseUrl', 'base_url', 'BASE_URL', 'apiUrl', 'api_url', 'API_URL', 'url', 'host'] as $key) {
            $candidate = $variables[$key] ?? null;
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_URL)) {
                return $this->nullableString($candidate, 500);
            }
        }

        $firstRawUrl = $this->findFirstPostmanRawUrl($collection['item'] ?? []);
        if ($firstRawUrl !== null) {
            $resolved = $this->resolvePostmanVariables($firstRawUrl, ['variables' => $variables]);
            $parts = parse_url($resolved['value']);
            if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
                $base = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
                return $this->nullableString($base, 500);
            }
        }

        return null;
    }

    private function findFirstPostmanRawUrl(mixed $nodes): ?string
    {
        if (! is_array($nodes)) {
            return null;
        }

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            if (isset($node['request'])) {
                $request = $node['request'];
                if (is_string($request)) {
                    return $request;
                }
                if (is_array($request)) {
                    $url = $request['url'] ?? null;
                    if (is_string($url)) {
                        return $url;
                    }
                    if (is_array($url) && is_string($url['raw'] ?? null)) {
                        return $url['raw'];
                    }
                }
            }
            $nested = $this->findFirstPostmanRawUrl($node['item'] ?? []);
            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }


    /** @return array{value:string,unresolved:array<int,string>} */
    private function resolvePostmanUrlVariables(string $value, array $context): array
    {
        $variables = is_array($context['variables'] ?? null) ? $context['variables'] : [];
        $unresolved = [];
        $resolved = preg_replace_callback('/\{\{\s*([^}\s]+)\s*\}\}/', function (array $matches) use ($variables, &$unresolved): string {
            $key = (string) $matches[1];
            if (! array_key_exists($key, $variables) || (string) $variables[$key] === '') {
                $unresolved[] = $key;
                return '{{'.$key.'}}';
            }

            $raw = (string) $variables[$key];
            if (filter_var($raw, FILTER_VALIDATE_URL) || in_array($key, ['baseUrl', 'base_url', 'BASE_URL', 'apiUrl', 'api_url', 'API_URL'], true)) {
                return $raw;
            }

            return '{{'.$key.'}}';
        }, $value) ?? $value;

        return ['value' => $resolved, 'unresolved' => array_values(array_unique($unresolved))];
    }

    /** @return array{value:string,unresolved:array<int,string>} */
    private function resolvePostmanVariables(string $value, array $context, bool $mask = false): array
    {
        $variables = is_array($context['variables'] ?? null) ? $context['variables'] : [];
        $unresolved = [];
        $resolved = preg_replace_callback('/\{\{\s*([^}\s]+)\s*\}\}/', function (array $matches) use ($variables, &$unresolved, $mask): string {
            $key = (string) $matches[1];
            if (! array_key_exists($key, $variables) || (string) $variables[$key] === '') {
                $unresolved[] = $key;
                return '{{'.$key.'}}';
            }

            $raw = (string) $variables[$key];
            return $mask ? $this->maskSensitiveText($raw) : $raw;
        }, $value) ?? $value;

        return ['value' => $resolved, 'unresolved' => array_values(array_unique($unresolved))];
    }

    /** @return array<int,array{key:string,value:string}> */
    private function maskedVariablePreview(array $variables): array
    {
        $rows = [];
        foreach ($variables as $key => $value) {
            $rows[] = [
                'key' => $this->nullableString((string) $key, 120) ?? (string) $key,
                'value' => $this->maskSensitiveHeaderValue((string) $key, (string) $value),
            ];
        }

        return $rows;
    }

    private function optionEnabled(array $options, string $key, bool $default = false): bool
    {
        if (! array_key_exists($key, $options)) {
            return $default;
        }

        return $this->toBool($options[$key]);
    }

    /** @return array<int,string> */
    private function postmanEvents(mixed $events): array
    {
        if (! is_array($events)) {
            return [];
        }

        $scripts = [];
        foreach ($events as $event) {
            if (! is_array($event) || strtolower((string) ($event['listen'] ?? '')) !== 'test') {
                continue;
            }
            $exec = $event['script']['exec'] ?? null;
            if (is_array($exec)) {
                $scripts[] = implode("\n", array_map('strval', $exec));
            } elseif (is_string($exec)) {
                $scripts[] = $exec;
            }
        }

        return $scripts;
    }

    /** @return array<int,array<string,mixed>> */
    private function postmanAssertionsFromEvents(array $events, ?int $expectedStatus): array
    {
        $assertions = [];
        if ($expectedStatus !== null) {
            $assertions[] = [
                'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
                'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
                'target_path' => null,
                'expected_value' => (string) $expectedStatus,
                'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            ];
        }

        foreach ($events as $script) {
            if (preg_match_all('/pm\.response\.to\.have\.status\((\d+)\)/i', $script, $matches)) {
                foreach ($matches[1] as $status) {
                    $assertions[] = [
                        'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
                        'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
                        'target_path' => null,
                        'expected_value' => (string) $status,
                        'severity' => EndpointAssertionRule::SEVERITY_FAIL,
                    ];
                }
            }
            if (preg_match_all('/responseTime[^;\n]+(?:below|lessThan)\((\d+)\)/i', $script, $matches)) {
                foreach ($matches[1] as $limit) {
                    $assertions[] = [
                        'rule_key' => EndpointAssertionRule::RULE_MAX_RESPONSE_TIME_MS,
                        'operator' => EndpointAssertionRule::OPERATOR_LESS_THAN_OR_EQUAL,
                        'target_path' => null,
                        'expected_value' => (string) $limit,
                        'severity' => EndpointAssertionRule::SEVERITY_WARNING,
                    ];
                }
            }
            if (preg_match_all('/pm\.response\.to\.have\.header\([\'\"]([^\'\"]+)[\'\"]\)/i', $script, $matches)) {
                foreach ($matches[1] as $header) {
                    $assertions[] = [
                        'rule_key' => EndpointAssertionRule::RULE_REQUIRED_HEADER,
                        'operator' => EndpointAssertionRule::OPERATOR_EXISTS,
                        'target_path' => null,
                        'expected_value' => (string) $header,
                        'severity' => EndpointAssertionRule::SEVERITY_WARNING,
                    ];
                }
            }
            if (preg_match_all('/pm\.expect\(\s*jsonData((?:\.[A-Za-z_][A-Za-z0-9_]*|\[[\'\"][^\'\"]+[\'\"]\])+?)\s*\)\.to\.exist/i', $script, $matches)) {
                foreach ($matches[1] as $path) {
                    $assertions[] = [
                        'rule_key' => EndpointAssertionRule::RULE_JSON_PATH_VALUE,
                        'operator' => EndpointAssertionRule::OPERATOR_EXISTS,
                        'target_path' => $this->postmanJsonPath($path),
                        'expected_value' => null,
                        'severity' => EndpointAssertionRule::SEVERITY_WARNING,
                    ];
                }
            }
            if (preg_match_all('/pm\.expect\(\s*jsonData((?:\.[A-Za-z_][A-Za-z0-9_]*|\[[\'\"][^\'\"]+[\'\"]\])+?)\s*\)\.to\.(?:eql|equal)\([\'\"]?([^\'\")]+)[\'\"]?\)/i', $script, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $assertions[] = [
                        'rule_key' => EndpointAssertionRule::RULE_JSON_PATH_VALUE,
                        'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
                        'target_path' => $this->postmanJsonPath($match[1]),
                        'expected_value' => (string) $match[2],
                        'severity' => EndpointAssertionRule::SEVERITY_WARNING,
                    ];
                }
            }
        }

        $unique = [];
        foreach ($assertions as $assertion) {
            $key = implode('|', array_map(fn ($value): string => (string) $value, [
                $assertion['rule_key'] ?? '',
                $assertion['operator'] ?? '',
                $assertion['target_path'] ?? '',
                $assertion['expected_value'] ?? '',
            ]));
            $unique[$key] = $assertion;
        }

        return array_values($unique);
    }

    private function postmanJsonPath(string $path): string
    {
        $path = preg_replace('/\[[\'\"]([^\'\"]+)[\'\"]\]/', '.$1', $path) ?? $path;
        $path = trim($path, '.');

        return '$.'.str_replace('..', '.', $path);
    }

    /** @return array<string,mixed>|null */
    private function postmanAuthDescriptor(mixed $auth, array $headers, array $context): ?array
    {
        if (is_array($auth)) {
            $type = strtolower((string) ($auth['type'] ?? ''));
            if ($type === 'bearer') {
                $token = $this->postmanAuthValue($auth['bearer'] ?? [], 'token', $context);
                if ($token !== null) {
                    return [
                        'name' => $this->nullableString(($context['collection_name'] ?? 'Postman').' Bearer', 100),
                        'type' => AuthProfile::TYPE_BEARER,
                        'token' => $token,
                        'masked' => $this->maskSensitiveHeaderValue('authorization', $token),
                        'is_complete' => ! str_contains($token, '{{'),
                    ];
                }
            }
            if ($type === 'basic') {
                $username = $this->postmanAuthValue($auth['basic'] ?? [], 'username', $context);
                $password = $this->postmanAuthValue($auth['basic'] ?? [], 'password', $context);
                if ($username !== null || $password !== null) {
                    return [
                        'name' => $this->nullableString(($context['collection_name'] ?? 'Postman').' Basic', 100),
                        'type' => AuthProfile::TYPE_BASIC,
                        'username' => $username,
                        'password' => $password,
                        'masked' => trim((string) $username).' / '.$this->maskSensitiveHeaderValue('password', (string) $password),
                        'is_complete' => $username !== null && $password !== null && ! str_contains($password, '{{'),
                    ];
                }
            }
            if ($type === 'apikey') {
                $key = $this->postmanAuthValue($auth['apikey'] ?? [], 'key', $context);
                $value = $this->postmanAuthValue($auth['apikey'] ?? [], 'value', $context);
                $in = strtolower((string) ($this->postmanAuthValue($auth['apikey'] ?? [], 'in', $context) ?? 'header'));
                if ($key !== null && $value !== null && $in === 'header') {
                    return [
                        'name' => $this->nullableString(($context['collection_name'] ?? 'Postman').' API Key', 100),
                        'type' => AuthProfile::TYPE_CUSTOM_HEADER,
                        'header_name' => $key,
                        'header_value' => $value,
                        'masked' => $key.': '.$this->maskSensitiveHeaderValue($key, $value),
                        'is_complete' => ! str_contains($value, '{{'),
                    ];
                }
            }
        }

        foreach ($headers as $header) {
            $key = (string) ($header['key'] ?? '');
            $value = (string) ($header['value'] ?? '');
            if (strtolower($key) === 'authorization' && $value !== '') {
                return [
                    'name' => $this->nullableString(($context['collection_name'] ?? 'Postman').' Authorization', 100),
                    'type' => str_starts_with(strtolower($value), 'bearer ') ? AuthProfile::TYPE_BEARER : AuthProfile::TYPE_CUSTOM_HEADER,
                    'token' => str_starts_with(strtolower($value), 'bearer ') ? trim(substr($value, 7)) : null,
                    'header_name' => str_starts_with(strtolower($value), 'bearer ') ? null : 'Authorization',
                    'header_value' => str_starts_with(strtolower($value), 'bearer ') ? null : $value,
                    'masked' => 'Authorization: '.$this->maskSensitiveHeaderValue($key, $value),
                    'is_complete' => ! str_contains($value, '{{'),
                ];
            }
            if (in_array(strtolower($key), ['x-api-key', 'x-auth-token'], true) || str_contains(strtolower($key), 'token') || str_contains(strtolower($key), 'key')) {
                return [
                    'name' => $this->nullableString(($context['collection_name'] ?? 'Postman').' '.$key, 100),
                    'type' => AuthProfile::TYPE_CUSTOM_HEADER,
                    'header_name' => $key,
                    'header_value' => $value,
                    'masked' => $key.': '.$this->maskSensitiveHeaderValue($key, $value),
                    'is_complete' => $value !== '' && ! str_contains($value, '{{'),
                ];
            }
        }

        return null;
    }

    private function postmanAuthValue(mixed $rows, string $key, array $context): ?string
    {
        if (! is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            if (! is_array($row) || (string) ($row['key'] ?? '') !== $key) {
                continue;
            }
            $value = (string) ($row['value'] ?? '');
            return $this->nullableString($this->resolvePostmanVariables($value, $context)['value']);
        }

        return null;
    }

    private function postmanSuiteName(array $context, array $folders): ?string
    {
        $name = $folders[0] ?? ($context['collection_name'] ?? null);
        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        return $this->nullableString('Postman - '.$name, 180);
    }

    private function createPostmanEnvironmentIfRequested(Project $project, array $metadata, array $options): ?int
    {
        if (! $this->optionEnabled($options, 'postman_create_environment', true)) {
            return null;
        }

        $baseUrl = $this->nullableString($metadata['environment_base_url'] ?? null, 500);
        if ($baseUrl === null || ! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        $name = $this->nullableString($metadata['environment_name'] ?? null, 80) ?? 'Postman Environment';
        $environment = Environment::query()->firstOrNew([
            'project_id' => $project->id,
            'name' => $name,
        ]);
        $environment->base_url = $baseUrl;
        $environment->is_production = false;
        $environment->save();

        return $environment->id;
    }

    private function createPostmanAuthProfileIfRequested(Project $project, array $row, array $options, array &$cache): ?int
    {
        if (! $this->optionEnabled($options, 'postman_create_auth_profile', true)) {
            return null;
        }

        $candidate = $row['postman_auth_profile'] ?? null;
        if (! is_array($candidate) || empty($candidate['is_complete'])) {
            return null;
        }

        $name = $this->nullableString($candidate['name'] ?? null, 100) ?? 'Postman Auth';
        if (isset($cache[$name])) {
            return $cache[$name];
        }

        $profile = AuthProfile::query()->firstOrNew([
            'project_id' => $project->id,
            'name' => $name,
        ]);
        $profile->type = (string) ($candidate['type'] ?? AuthProfile::TYPE_NONE);
        $profile->notes = __('messages.auth_profiles.postman_imported_note');
        $profile->is_default = false;

        if ($profile->type === AuthProfile::TYPE_BEARER) {
            $profile->encrypted_token = $candidate['token'] ?? null;
        } elseif ($profile->type === AuthProfile::TYPE_BASIC) {
            $profile->username = $candidate['username'] ?? null;
            $profile->encrypted_password = $candidate['password'] ?? null;
        } elseif ($profile->type === AuthProfile::TYPE_CUSTOM_HEADER) {
            $profile->header_name = $candidate['header_name'] ?? null;
            $profile->encrypted_header_value = $candidate['header_value'] ?? null;
        }
        $profile->save();

        return $cache[$name] = $profile->id;
    }

    private function createPostmanArtifactsIfRequested(Project $project, Endpoint $endpoint, array $row, array $options): void
    {
        if ($this->optionEnabled($options, 'postman_create_test_suites', false)) {
            $this->createPostmanTestCase($project, $endpoint, $row);
        }
        if ($this->optionEnabled($options, 'postman_create_assertions', true)) {
            $this->createPostmanAssertionRules($project, $endpoint, $row);
        }
    }

    private function createPostmanTestCase(Project $project, Endpoint $endpoint, array $row): void
    {
        $suiteName = $this->nullableString($row['postman_suite_name'] ?? null, 180);
        if ($suiteName === null) {
            return;
        }

        $suite = TestSuite::query()->firstOrCreate([
            'project_id' => $project->id,
            'name' => $suiteName,
        ], [
            'description' => __('messages.test_suites.postman_imported_description'),
            'status' => TestSuite::STATUS_ACTIVE,
        ]);

        $title = $this->nullableString(($row['method'] ?? 'GET').' '.($row['path'] ?? '/'), 220) ?? 'Imported Postman request';
        TestCase::query()->firstOrCreate([
            'project_id' => $project->id,
            'test_suite_id' => $suite->id,
            'endpoint_id' => $endpoint->id,
            'title' => $title,
        ], [
            'description' => __('messages.test_cases.postman_imported_description'),
            'preconditions' => $row['auth_required'] ? __('messages.test_cases.postman_auth_precondition') : null,
            'steps' => __('messages.test_cases.postman_imported_steps', ['method' => $row['method'], 'path' => $row['path']]),
            'expected_result' => __('messages.test_cases.postman_imported_expected', ['status' => $row['expected_status'] ?: '2xx/3xx']),
            'type' => TestCase::TYPE_HYBRID,
            'priority' => in_array($row['risk_level'] ?? '', [Endpoint::RISK_CRITICAL, Endpoint::RISK_HIGH], true) ? TestCase::PRIORITY_HIGH : TestCase::PRIORITY_MEDIUM,
            'status' => TestCase::STATUS_READY,
        ]);
    }

    private function createPostmanAssertionRules(Project $project, Endpoint $endpoint, array $row): void
    {
        $assertions = is_array($row['postman_assertions'] ?? null) ? $row['postman_assertions'] : [];
        foreach ($assertions as $assertion) {
            if (! is_array($assertion) || empty($assertion['rule_key'])) {
                continue;
            }
            EndpointAssertionRule::query()->firstOrCreate([
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'rule_key' => $assertion['rule_key'],
                'operator' => $assertion['operator'] ?? EndpointAssertionRule::OPERATOR_EQUALS,
                'target_path' => $assertion['target_path'] ?? null,
                'expected_value' => $assertion['expected_value'] ?? null,
            ], [
                'severity' => $assertion['severity'] ?? EndpointAssertionRule::SEVERITY_WARNING,
                'enabled' => true,
            ]);
        }
    }

    /** @return array<int,array{key:string,value:string}> */
    private function normalizeRequestHeaders(mixed $headers): array
    {
        if (is_string($headers)) {
            $decoded = json_decode($headers, true);
            if (is_array($decoded)) {
                $headers = $decoded;
            } else {
                $rows = [];
                foreach (preg_split('/\r\n|\r|\n/', $headers) ?: [] as $line) {
                    if (! str_contains($line, ':')) {
                        continue;
                    }
                    [$key, $value] = explode(':', $line, 2);
                    $key = trim($key);
                    if ($key !== '') {
                        $rows[] = ['key' => $key, 'value' => $this->maskSensitiveHeaderValue($key, trim($value))];
                    }
                }
                return $rows;
            }
        }

        if (! is_array($headers)) {
            return [];
        }

        $rows = [];
        foreach ($headers as $key => $value) {
            if (is_array($value)) {
                $headerKey = (string) ($value['key'] ?? $key);
                $headerValue = (string) ($value['value'] ?? '');
            } else {
                $headerKey = is_string($key) ? $key : '';
                $headerValue = (string) $value;
            }
            $headerKey = trim($headerKey);
            if ($headerKey === '') {
                continue;
            }
            $rows[] = ['key' => $this->nullableString($headerKey, 120) ?? $headerKey, 'value' => $this->maskSensitiveHeaderValue($headerKey, $headerValue)];
        }

        return $rows;
    }

    private function maskSensitiveHeaderValue(string $key, string $value): string
    {
        $key = strtolower($key);
        if ($value === '') {
            return '';
        }
        if (in_array($key, ['authorization', 'cookie', 'set-cookie', 'x-api-key', 'x-auth-token'], true) || str_contains($key, 'token') || str_contains($key, 'secret') || str_contains($key, 'key')) {
            return Str::limit(substr($value, 0, 4).'…'.substr($value, -4), 40, '…');
        }

        return $this->nullableString($value, 500) ?? '';
    }

    private function maskSensitiveText(string $text): string
    {
        $text = preg_replace('/("?(?:password|token|secret|api[_-]?key|access[_-]?token|refresh[_-]?token)"?\s*[:=]\s*)"?([^",\n}]+)"?/i', '$1"***"', $text) ?? $text;

        return $this->nullableString($text, 4000) ?? '';
    }

    private function validProjectId(Project $project, string $relation, mixed $id): ?int
    {
        if (! $id) {
            return null;
        }

        return $project->{$relation}()->whereKey($id)->exists() ? (int) $id : null;
    }

    private function nullableString(mixed $value, ?int $limit = null): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if ($limit !== null && strlen($value) > $limit) {
            return substr($value, 0, $limit);
        }

        return $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
