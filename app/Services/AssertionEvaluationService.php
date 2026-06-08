<?php

namespace App\Services;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\ScanResult;
use App\Models\SnapshotItem;
use App\Services\Risk\RiskAnalyzer;
use App\Services\Settings\SettingService;
use Illuminate\Support\Collection;

class AssertionEvaluationService
{
    public const STATUS_PASS = 'pass';
    public const STATUS_WARNING = 'warning';
    public const STATUS_FAIL = 'fail';
    public const STATUS_NOT_CONFIGURED = 'not_configured';

    public function __construct(
        private readonly RiskAnalyzer $riskAnalyzer,
        private readonly SettingService $settings,
    ) {
    }

    /**
     * @return array{
     *     status: string,
     *     label: string,
     *     css: string,
     *     results: array<int, array<string, mixed>>,
     *     failed_rules: array<int, array<string, mixed>>,
     *     warning_rules: array<int, array<string, mixed>>
     * }
     */
    public function evaluate(Endpoint $endpoint, ?ScanResult $scanResult = null, ?SnapshotItem $snapshotItem = null): array
    {
        if (! $this->settings->boolean('assertions.enabled', true)) {
            return $this->summary(self::STATUS_NOT_CONFIGURED, []);
        }

        $rules = $this->effectiveRules($endpoint);

        if ($rules->isEmpty()) {
            return $this->summary(self::STATUS_NOT_CONFIGURED, []);
        }

        $riskAnalysis = $scanResult
            ? $this->riskAnalyzer->analyze($endpoint, $scanResult)
            : null;

        $results = $rules
            ->map(fn (EndpointAssertionRule $rule): array => $this->evaluateRule($rule, $endpoint, $scanResult, $snapshotItem, $riskAnalysis))
            ->values()
            ->all();

        $status = self::STATUS_PASS;

        foreach ($results as $result) {
            if ($result['status'] === self::STATUS_FAIL) {
                $status = self::STATUS_FAIL;
                break;
            }

            if ($result['status'] === self::STATUS_WARNING) {
                $status = self::STATUS_WARNING;
            }
        }

        if ($status === self::STATUS_WARNING && $this->settings->boolean('assertions.treat_warning_as_failure', false)) {
            $status = self::STATUS_FAIL;
        }

        return $this->summary($status, $results);
    }

    /** @return Collection<int, EndpointAssertionRule> */
    public function effectiveRules(Endpoint $endpoint): Collection
    {
        if (! $this->settings->boolean('assertions.enabled', true)) {
            return collect();
        }

        $rules = EndpointAssertionRule::query()
            ->with('endpoint')
            ->where('project_id', $endpoint->project_id)
            ->where('enabled', true)
            ->where(function ($query) use ($endpoint): void {
                $query->whereNull('endpoint_id')->orWhere('endpoint_id', $endpoint->id);
            })
            ->orderByRaw('CASE WHEN endpoint_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->get();

        if ($rules->isEmpty()) {
            return collect();
        }

        $repeatableRules = $rules
            ->filter(fn (EndpointAssertionRule $rule): bool => EndpointAssertionRule::isRepeatable($rule->rule_key));

        $singletonRules = $rules
            ->reject(fn (EndpointAssertionRule $rule): bool => EndpointAssertionRule::isRepeatable($rule->rule_key))
            ->groupBy('rule_key')
            ->map(fn (Collection $group): EndpointAssertionRule => $group->last());

        return $singletonRules
            ->values()
            ->merge($repeatableRules->values())
            ->sortBy(function (EndpointAssertionRule $rule): string {
                $scopeRank = $rule->endpoint_id === null ? 0 : 1;
                $ruleRank = array_search($rule->rule_key, EndpointAssertionRule::RULE_KEYS, true);

                return sprintf('%02d-%02d-%010d', $scopeRank, $ruleRank === false ? 99 : $ruleRank, (int) ($rule->id ?? 0));
            })
            ->values();
    }

    /** @return Collection<int, EndpointAssertionRule> */
    public function rulesForDisplay(Endpoint $endpoint): Collection
    {
        return EndpointAssertionRule::query()
            ->with('endpoint')
            ->where('project_id', $endpoint->project_id)
            ->where(function ($query) use ($endpoint): void {
                $query->whereNull('endpoint_id')->orWhere('endpoint_id', $endpoint->id);
            })
            ->orderByRaw('CASE WHEN endpoint_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('rule_key')
            ->get();
    }


    /** @return array<string, mixed> */
    private function evaluateRule(EndpointAssertionRule $rule, Endpoint $endpoint, ?ScanResult $scanResult, ?SnapshotItem $snapshotItem, ?array $riskAnalysis): array
    {
        $actual = $this->actualValue($rule, $endpoint, $scanResult, $snapshotItem, $riskAnalysis);
        $expected = $this->expectedValue($rule);
        $ruleLabel = $rule->target_path ? $rule->rule_label.' ['.$rule->target_path.']' : $rule->rule_label;
        $passed = $this->compare($actual['value'], $expected, $rule->operator);
        $status = $passed
            ? self::STATUS_PASS
            : ($rule->severity === EndpointAssertionRule::SEVERITY_FAIL ? self::STATUS_FAIL : self::STATUS_WARNING);

        return [
            'rule_id' => $rule->id,
            'rule_key' => $rule->rule_key,
            'rule_label' => $ruleLabel,
            'target_path' => $rule->target_path,
            'operator' => $rule->operator,
            'operator_label' => $rule->operator_label,
            'expected' => $this->displayValue($expected),
            'actual' => $actual['display'],
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'status_css' => $this->statusCss($status),
            'severity' => $rule->severity,
            'severity_label' => $rule->severity_label,
            'severity_css' => $rule->severity_css,
            'message' => $passed
                ? __('messages.assertions.messages.passed', ['rule' => $ruleLabel, 'actual' => $actual['display']])
                : __('messages.assertions.messages.failed', [
                    'rule' => $ruleLabel,
                    'operator' => $rule->operator_label,
                    'expected' => $this->displayValue($expected),
                    'actual' => $actual['display'],
                ]),
        ];
    }

    /** @return array{value: mixed, display: string} */
    private function actualValue(EndpointAssertionRule $rule, Endpoint $endpoint, ?ScanResult $scanResult, ?SnapshotItem $snapshotItem, ?array $riskAnalysis): array
    {
        $metadata = $snapshotItem?->metadata_json ?: [];
        $headers = $scanResult?->headers_json ?: ($metadata['headers'] ?? []);
        $url = $scanResult?->url ?: ($metadata['url'] ?? $endpoint->full_url);
        $riskScore = $riskAnalysis['score'] ?? ($metadata['risk_score'] ?? null);
        $responseSize = $scanResult?->response_size ?? ($metadata['response_size'] ?? null);
        $headerName = trim((string) $rule->expected_value);
        $bodyPreview = $scanResult?->body_preview ?: ($metadata['body_preview'] ?? null);

        return match ($rule->rule_key) {
            EndpointAssertionRule::RULE_STATUS_CODE => $this->statusCodeValue($endpoint, $rule, $scanResult, $snapshotItem),
            EndpointAssertionRule::RULE_MAX_RESPONSE_TIME_MS => $this->value($scanResult?->response_time_ms ?? $snapshotItem?->response_time_ms, ' ms'),
            EndpointAssertionRule::RULE_REQUIRED_HEADER,
            EndpointAssertionRule::RULE_FORBIDDEN_HEADER => $this->headerValue($headers, $headerName),
            EndpointAssertionRule::RULE_HTTPS_REQUIRED => $this->booleanValue(strtolower((string) parse_url((string) $url, PHP_URL_SCHEME)) === 'https'),
            EndpointAssertionRule::RULE_MAX_RISK_SCORE => $this->value($riskScore),
            EndpointAssertionRule::RULE_MAX_RESPONSE_SIZE_KB => $this->value($responseSize === null ? null : round(((int) $responseSize) / 1024, 2), ' KB'),
            EndpointAssertionRule::RULE_NO_REDIRECT => $this->booleanValue(! $this->hasRedirect($scanResult, $snapshotItem, $headers)),
            EndpointAssertionRule::RULE_RESPONSE_BODY_CONTAINS => $this->responseBodyValue($bodyPreview),
            EndpointAssertionRule::RULE_JSON_PATH_VALUE => $this->jsonPathValue($bodyPreview, (string) $rule->target_path),
            EndpointAssertionRule::RULE_JSON_PATH_TYPE => $this->jsonPathType($bodyPreview, (string) $rule->target_path),
            EndpointAssertionRule::RULE_JSON_PATH_COUNT => $this->jsonPathCount($bodyPreview, (string) $rule->target_path),
            default => $this->value(null),
        };
    }

    private function expectedValue(EndpointAssertionRule $rule): mixed
    {
        if ($rule->rule_key === EndpointAssertionRule::RULE_HTTPS_REQUIRED) {
            return $this->toBool($rule->expected_value ?? 'true');
        }

        if ($rule->rule_key === EndpointAssertionRule::RULE_NO_REDIRECT) {
            return true;
        }

        if ($rule->rule_key === EndpointAssertionRule::RULE_JSON_PATH_TYPE) {
            return strtolower(trim((string) $rule->expected_value));
        }

        if (in_array($rule->rule_key, [
            EndpointAssertionRule::RULE_STATUS_CODE,
            EndpointAssertionRule::RULE_MAX_RESPONSE_TIME_MS,
            EndpointAssertionRule::RULE_MAX_RISK_SCORE,
            EndpointAssertionRule::RULE_MAX_RESPONSE_SIZE_KB,
            EndpointAssertionRule::RULE_JSON_PATH_COUNT,
        ], true)) {
            return is_numeric($rule->expected_value) ? (float) $rule->expected_value : null;
        }

        return $rule->expected_value;
    }

    private function compare(mixed $actual, mixed $expected, string $operator): bool
    {
        return match ($operator) {
            EndpointAssertionRule::OPERATOR_EQUALS => $this->comparable($actual) === $this->comparable($expected),
            EndpointAssertionRule::OPERATOR_NOT_EQUALS => $this->comparable($actual) !== $this->comparable($expected),
            EndpointAssertionRule::OPERATOR_LESS_THAN => $actual !== null && $expected !== null && (float) $actual < (float) $expected,
            EndpointAssertionRule::OPERATOR_LESS_THAN_OR_EQUAL => $actual !== null && $expected !== null && (float) $actual <= (float) $expected,
            EndpointAssertionRule::OPERATOR_GREATER_THAN => $actual !== null && $expected !== null && (float) $actual > (float) $expected,
            EndpointAssertionRule::OPERATOR_GREATER_THAN_OR_EQUAL => $actual !== null && $expected !== null && (float) $actual >= (float) $expected,
            EndpointAssertionRule::OPERATOR_CONTAINS => str_contains(strtolower((string) $actual), strtolower((string) $expected)),
            EndpointAssertionRule::OPERATOR_NOT_CONTAINS => ! str_contains(strtolower((string) $actual), strtolower((string) $expected)),
            EndpointAssertionRule::OPERATOR_EXISTS => $actual !== null && $actual !== false && $actual !== '',
            EndpointAssertionRule::OPERATOR_NOT_EXISTS => $actual === null || $actual === false || $actual === '',
            default => false,
        };
    }

    /** @return array{value: mixed, display: string} */
    private function statusCodeValue(Endpoint $endpoint, EndpointAssertionRule $rule, ?ScanResult $scanResult, ?SnapshotItem $snapshotItem): array
    {
        $statusCode = $scanResult?->status_code ?? $snapshotItem?->status_code;

        if ($endpoint->auth_required
            && $this->settings->boolean('assertions.allow_401_403_for_protected_endpoints', true)
            && in_array((int) $statusCode, [401, 403], true)) {
            return $this->value($rule->expected_value);
        }

        return $this->value($statusCode);
    }

    /** @return array{value: mixed, display: string} */
    private function headerValue(array $headers, string $headerName): array
    {
        foreach ($headers as $name => $values) {
            if (strtolower((string) $name) === strtolower($headerName)) {
                $value = is_array($values) ? implode(', ', $values) : (string) $values;

                return ['value' => $value === '' ? true : $value, 'display' => $value === '' ? __('messages.common.yes') : $value];
            }
        }

        return ['value' => null, 'display' => __('messages.common.not_available')];
    }

    /** @return array{value: mixed, display: string} */
    private function responseBodyValue(mixed $bodyPreview): array
    {
        if (! is_string($bodyPreview) || $bodyPreview === '') {
            return ['value' => null, 'display' => __('messages.assertions.body_unavailable')];
        }

        return ['value' => $bodyPreview, 'display' => __('messages.assertions.body_preview_available')];
    }

    /** @return array{value: mixed, display: string} */
    private function jsonPathValue(mixed $bodyPreview, string $path): array
    {
        $resolved = $this->resolveJsonPath($bodyPreview, $path);

        if (! $resolved['found']) {
            return ['value' => null, 'display' => $resolved['display']];
        }

        return ['value' => $resolved['value'], 'display' => $this->displayJsonValue($resolved['value'])];
    }

    /** @return array{value: mixed, display: string} */
    private function jsonPathType(mixed $bodyPreview, string $path): array
    {
        $resolved = $this->resolveJsonPath($bodyPreview, $path);

        if (! $resolved['found']) {
            return ['value' => null, 'display' => $resolved['display']];
        }

        $type = $this->jsonValueType($resolved['value']);

        return ['value' => $type, 'display' => $type];
    }

    /** @return array{value: mixed, display: string} */
    private function jsonPathCount(mixed $bodyPreview, string $path): array
    {
        $resolved = $this->resolveJsonPath($bodyPreview, $path);

        if (! $resolved['found']) {
            return ['value' => null, 'display' => $resolved['display']];
        }

        $value = $resolved['value'];
        $count = is_array($value) ? count($value) : null;

        return ['value' => $count, 'display' => $count === null ? __('messages.assertions.value_not_countable') : (string) $count];
    }

    /** @return array{found: bool, value: mixed, display: string} */
    private function resolveJsonPath(mixed $bodyPreview, string $path): array
    {
        if (! is_string($bodyPreview) || trim($bodyPreview) === '') {
            return ['found' => false, 'value' => null, 'display' => __('messages.assertions.body_unavailable')];
        }

        $decoded = json_decode($bodyPreview, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['found' => false, 'value' => null, 'display' => __('messages.assertions.invalid_json_body')];
        }

        $segments = $this->jsonPathSegments($path);
        $current = $decoded;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
                continue;
            }

            return ['found' => false, 'value' => null, 'display' => __('messages.assertions.path_not_found', ['path' => $path])];
        }

        return ['found' => true, 'value' => $current, 'display' => $this->displayJsonValue($current)];
    }

    /** @return array<int, string|int> */
    private function jsonPathSegments(string $path): array
    {
        $normalized = trim($path);
        $normalized = preg_replace('/^\$\.?/', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\[(\d+)\]/', '.$1', $normalized) ?? $normalized;

        if ($normalized === '') {
            return [];
        }

        return collect(explode('.', $normalized))
            ->map(fn (string $segment): string|int => ctype_digit($segment) ? (int) $segment : $segment)
            ->values()
            ->all();
    }

    private function jsonValueType(mixed $value): string
    {
        if (is_array($value)) {
            return array_is_list($value) ? 'array' : 'object';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'number';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if ($value === null) {
            return 'null';
        }

        return 'string';
    }

    private function displayJsonValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: __('messages.common.not_available');
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }

    private function hasRedirect(?ScanResult $scanResult, ?SnapshotItem $snapshotItem, array $headers): bool
    {
        $status = $scanResult?->status_code ?? $snapshotItem?->status_code;

        if ($status !== null && $status >= 300 && $status < 400) {
            return true;
        }

        return collect(array_keys($headers))->contains(fn (string $name): bool => strtolower($name) === 'location');
    }

    /** @return array{value: mixed, display: string} */
    private function value(mixed $value, string $suffix = ''): array
    {
        return [
            'value' => $value,
            'display' => $value === null || $value === '' ? __('messages.common.not_available') : $value.$suffix,
        ];
    }

    /** @return array{value: bool, display: string} */
    private function booleanValue(bool $value): array
    {
        return ['value' => $value, 'display' => $value ? __('messages.common.yes') : __('messages.common.no')];
    }

    private function comparable(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) ((float) $value);
        }

        if (is_array($value)) {
            return strtolower(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
        }

        return strtolower(trim((string) $value));
    }

    private function toBool(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function displayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return __('messages.common.not_available');
        }

        if (is_bool($value)) {
            return $value ? __('messages.common.yes') : __('messages.common.no');
        }

        return (string) $value;
    }

    /** @param array<int, array<string, mixed>> $results */
    private function summary(string $status, array $results): array
    {
        return [
            'status' => $status,
            'label' => $this->statusLabel($status),
            'css' => $this->statusCss($status),
            'results' => $results,
            'failed_rules' => array_values(array_filter($results, fn (array $result): bool => $result['status'] === self::STATUS_FAIL)),
            'warning_rules' => array_values(array_filter($results, fn (array $result): bool => $result['status'] === self::STATUS_WARNING)),
        ];
    }

    private function statusLabel(string $status): string
    {
        return __('messages.assertions.statuses.'.$status);
    }

    private function statusCss(string $status): string
    {
        return match ($status) {
            self::STATUS_PASS => 'success',
            self::STATUS_WARNING => 'warning',
            self::STATUS_FAIL => 'danger',
            default => 'default',
        };
    }
}
