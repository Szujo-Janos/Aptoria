<?php

namespace App\Services\Endpoints;

use App\Models\Endpoint;
use App\Models\Project;
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
    public function preview(Project $project, string $format, string $payload): array
    {
        $items = $this->parse($format, $payload);
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
                'risk_level' => $risk,
                'risk_reason' => $this->nullableString(Arr::get($item, 'risk_reason')),
                'qa_notes' => $this->nullableString(Arr::get($item, 'qa_notes')),
                'path_parameters' => $this->pathParameters->extractNames($path),
                'status' => $status,
                'exists' => $exists,
                'reasons' => $reasons,
            ];
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
        ];
    }

    /**
     * @return array{created:int,updated:int,skipped:int,duplicates:int,invalid:int,total:int,valid:int}
     */
    public function import(Project $project, string $format, string $payload, ?int $environmentId = null, ?int $authProfileId = null): array
    {
        $preview = $this->preview($project, $format, $payload);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $environmentId = $this->validProjectId($project, 'environments', $environmentId);
        $authProfileId = $this->validProjectId($project, 'authProfiles', $authProfileId);

        foreach ($preview['rows'] as $row) {
            if (! in_array($row['status'], ['create', 'update'], true)) {
                $skipped++;
                continue;
            }

            $data = [
                'environment_id' => $environmentId,
                'auth_profile_id' => $authProfileId,
                'method' => $row['method'],
                'path' => $row['path'],
                'name' => $row['name'],
                'description' => $row['description'],
                'tags' => $row['tags'],
                'auth_required' => $row['auth_required'],
                'expected_status' => $row['expected_status'],
                'expected_content_type' => $row['expected_content_type'],
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
                $updated++;
            } else {
                $project->endpoints()->create($data);
                $this->pathParameters->ensureProjectDefaultsFromPath($project, $row['path']);
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
    private function parse(string $format, string $payload): array
    {
        return match ($format) {
            'json' => $this->parseJsonPayload($payload),
            'openapi' => $this->parseOpenApiPayload($payload),
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
                ->withHeaders(['User-Agent' => 'Aptoria/'.config('aptoria.version', '1.0.74').' OpenAPI Import'])
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
