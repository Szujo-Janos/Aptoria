<?php

namespace App\Http\Requests;

use App\Models\EndpointAssertionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssertionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', Rule::in([(int) $this->route('project')->id])],
            'endpoint_id' => ['nullable', 'integer'],
            'rule_key' => ['required', Rule::in(EndpointAssertionRule::RULE_KEYS)],
            'operator' => ['required', Rule::in(EndpointAssertionRule::OPERATORS)],
            'target_path' => ['nullable', 'string', 'max:500'],
            'expected_value' => ['nullable', 'string', 'max:2000'],
            'severity' => ['required', Rule::in(EndpointAssertionRule::SEVERITIES)],
            'enabled' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $project = $this->route('project');
                $endpointId = $this->input('endpoint_id');
                $ruleKey = (string) $this->input('rule_key');
                $operator = (string) $this->input('operator');
                $expected = trim((string) $this->input('expected_value'));
                $targetPath = trim((string) $this->input('target_path'));

                if ($endpointId && ! $project->endpoints()->whereKey($endpointId)->exists()) {
                    $validator->errors()->add('endpoint_id', __('messages.assertions.validation.endpoint_project'));
                }

                if (EndpointAssertionRule::requiresTargetPath($ruleKey) && $targetPath === '') {
                    $validator->errors()->add('target_path', __('messages.assertions.validation.target_path_required'));
                }

                if (EndpointAssertionRule::requiresExpectedValue($ruleKey, $operator) && $expected === '') {
                    $validator->errors()->add('expected_value', __('messages.assertions.validation.expected_required'));
                }

                if (in_array($ruleKey, [
                    EndpointAssertionRule::RULE_STATUS_CODE,
                    EndpointAssertionRule::RULE_MAX_RESPONSE_TIME_MS,
                    EndpointAssertionRule::RULE_MAX_RISK_SCORE,
                    EndpointAssertionRule::RULE_MAX_RESPONSE_SIZE_KB,
                    EndpointAssertionRule::RULE_JSON_PATH_COUNT,
                ], true) && $expected !== '' && ! is_numeric($expected)) {
                    $validator->errors()->add('expected_value', __('messages.assertions.validation.expected_numeric'));
                }

                if ($ruleKey === EndpointAssertionRule::RULE_HTTPS_REQUIRED && $expected !== '' && ! in_array(strtolower($expected), ['1', '0', 'true', 'false', 'yes', 'no'], true)) {
                    $validator->errors()->add('expected_value', __('messages.assertions.validation.expected_boolean'));
                }

                if ($ruleKey === EndpointAssertionRule::RULE_JSON_PATH_TYPE && $expected !== '' && ! in_array(strtolower($expected), ['string', 'number', 'integer', 'boolean', 'array', 'object', 'null'], true)) {
                    $validator->errors()->add('expected_value', __('messages.assertions.validation.expected_json_type'));
                }
            },
        ];
    }
}
