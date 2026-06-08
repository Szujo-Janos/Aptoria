<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointAssertionRule extends Model
{
    use HasFactory;

    public const RULE_STATUS_CODE = 'status_code';
    public const RULE_MAX_RESPONSE_TIME_MS = 'max_response_time_ms';
    public const RULE_REQUIRED_HEADER = 'required_header';
    public const RULE_FORBIDDEN_HEADER = 'forbidden_header';
    public const RULE_HTTPS_REQUIRED = 'https_required';
    public const RULE_MAX_RISK_SCORE = 'max_risk_score';
    public const RULE_MAX_RESPONSE_SIZE_KB = 'max_response_size_kb';
    public const RULE_NO_REDIRECT = 'no_redirect';
    public const RULE_RESPONSE_BODY_CONTAINS = 'response_body_contains';
    public const RULE_JSON_PATH_VALUE = 'json_path_value';
    public const RULE_JSON_PATH_TYPE = 'json_path_type';
    public const RULE_JSON_PATH_COUNT = 'json_path_count';

    public const RULE_KEYS = [
        self::RULE_STATUS_CODE,
        self::RULE_MAX_RESPONSE_TIME_MS,
        self::RULE_REQUIRED_HEADER,
        self::RULE_FORBIDDEN_HEADER,
        self::RULE_HTTPS_REQUIRED,
        self::RULE_MAX_RISK_SCORE,
        self::RULE_MAX_RESPONSE_SIZE_KB,
        self::RULE_NO_REDIRECT,
        self::RULE_RESPONSE_BODY_CONTAINS,
        self::RULE_JSON_PATH_VALUE,
        self::RULE_JSON_PATH_TYPE,
        self::RULE_JSON_PATH_COUNT,
    ];

    public const REPEATABLE_RULE_KEYS = [
        self::RULE_REQUIRED_HEADER,
        self::RULE_FORBIDDEN_HEADER,
        self::RULE_RESPONSE_BODY_CONTAINS,
        self::RULE_JSON_PATH_VALUE,
        self::RULE_JSON_PATH_TYPE,
        self::RULE_JSON_PATH_COUNT,
    ];

    public const OPERATOR_EQUALS = 'equals';
    public const OPERATOR_NOT_EQUALS = 'not_equals';
    public const OPERATOR_LESS_THAN = 'less_than';
    public const OPERATOR_LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    public const OPERATOR_GREATER_THAN = 'greater_than';
    public const OPERATOR_GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';
    public const OPERATOR_CONTAINS = 'contains';
    public const OPERATOR_NOT_CONTAINS = 'not_contains';
    public const OPERATOR_EXISTS = 'exists';
    public const OPERATOR_NOT_EXISTS = 'not_exists';

    public const OPERATORS = [
        self::OPERATOR_EQUALS,
        self::OPERATOR_NOT_EQUALS,
        self::OPERATOR_LESS_THAN,
        self::OPERATOR_LESS_THAN_OR_EQUAL,
        self::OPERATOR_GREATER_THAN,
        self::OPERATOR_GREATER_THAN_OR_EQUAL,
        self::OPERATOR_CONTAINS,
        self::OPERATOR_NOT_CONTAINS,
        self::OPERATOR_EXISTS,
        self::OPERATOR_NOT_EXISTS,
    ];

    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_FAIL = 'fail';

    public const SEVERITIES = [
        self::SEVERITY_WARNING,
        self::SEVERITY_FAIL,
    ];

    protected $fillable = [
        'project_id',
        'endpoint_id',
        'rule_key',
        'operator',
        'target_path',
        'expected_value',
        'severity',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function getRuleLabelAttribute(): string
    {
        return __('messages.assertions.rule_keys.'.$this->rule_key);
    }

    public function getOperatorLabelAttribute(): string
    {
        return __('messages.assertions.operators.'.$this->operator);
    }

    public function getSeverityLabelAttribute(): string
    {
        return __('messages.assertions.severities.'.$this->severity);
    }

    public function getSeverityCssAttribute(): string
    {
        return $this->severity === self::SEVERITY_FAIL ? 'danger' : 'warning';
    }

    public function getScopeLabelAttribute(): string
    {
        return $this->endpoint
            ? __('messages.assertions.endpoint_scope', ['method' => $this->endpoint->method, 'path' => $this->endpoint->path])
            : __('messages.assertions.project_default_scope');
    }

    public function getRuleHelpAttribute(): string
    {
        return __('messages.assertions.rule_help.'.$this->rule_key);
    }

    public function getExampleValueAttribute(): string
    {
        return __('messages.assertions.example_values.'.$this->rule_key);
    }

    public static function isRepeatable(string $ruleKey): bool
    {
        return in_array($ruleKey, self::REPEATABLE_RULE_KEYS, true);
    }

    public static function requiresExpectedValue(string $ruleKey, string $operator): bool
    {
        if (in_array($ruleKey, [self::RULE_REQUIRED_HEADER, self::RULE_FORBIDDEN_HEADER], true)) {
            return true;
        }

        if (in_array($operator, [self::OPERATOR_EXISTS, self::OPERATOR_NOT_EXISTS], true)) {
            return false;
        }

        if ($ruleKey === self::RULE_RESPONSE_BODY_CONTAINS) {
            return true;
        }

        return ! in_array($ruleKey, [self::RULE_NO_REDIRECT], true);
    }

    public static function requiresTargetPath(string $ruleKey): bool
    {
        return in_array($ruleKey, [self::RULE_JSON_PATH_VALUE, self::RULE_JSON_PATH_TYPE, self::RULE_JSON_PATH_COUNT], true);
    }

    public static function isBodyAssertion(string $ruleKey): bool
    {
        return in_array($ruleKey, [self::RULE_RESPONSE_BODY_CONTAINS, self::RULE_JSON_PATH_VALUE, self::RULE_JSON_PATH_TYPE, self::RULE_JSON_PATH_COUNT], true);
    }

    public static function defaultOperator(string $ruleKey): string
    {
        return match ($ruleKey) {
            self::RULE_MAX_RESPONSE_TIME_MS,
            self::RULE_MAX_RISK_SCORE,
            self::RULE_MAX_RESPONSE_SIZE_KB => self::OPERATOR_LESS_THAN_OR_EQUAL,
            self::RULE_REQUIRED_HEADER => self::OPERATOR_EXISTS,
            self::RULE_FORBIDDEN_HEADER => self::OPERATOR_NOT_EXISTS,
            self::RULE_RESPONSE_BODY_CONTAINS => self::OPERATOR_CONTAINS,
            self::RULE_JSON_PATH_VALUE,
            self::RULE_JSON_PATH_TYPE,
            self::RULE_JSON_PATH_COUNT,
            self::RULE_HTTPS_REQUIRED,
            self::RULE_NO_REDIRECT,
            self::RULE_STATUS_CODE => self::OPERATOR_EQUALS,
            default => self::OPERATOR_EQUALS,
        };
    }
}
