<?php

namespace App\Http\Requests;

use App\Models\Endpoint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EndpointRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'method' => strtoupper((string) $this->input('method', '')),
            'path' => Endpoint::normalizePath((string) $this->input('path', '')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $endpoint = $this->route('endpoint');

        return [
            'environment_id' => ['nullable', Rule::exists('environments', 'id')->where('project_id', $project?->id)],
            'auth_profile_id' => ['nullable', Rule::exists('auth_profiles', 'id')->where('project_id', $project?->id)],
            'method' => ['required', 'string', Rule::in(Endpoint::METHODS)],
            'path' => [
                'required',
                'string',
                'max:500',
                Rule::unique('endpoints')
                    ->where('project_id', $project?->id)
                    ->where('method', strtoupper((string) $this->input('method')))
                    ->ignore($endpoint?->id),
            ],
            'name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'string', 'max:500'],
            'auth_required' => ['nullable', 'boolean'],
            'expected_status' => ['nullable', 'integer', 'between:100,599'],
            'expected_content_type' => ['nullable', 'string', 'max:120'],
            'risk_level' => ['required', 'string', Rule::in(Endpoint::RISKS)],
            'risk_reason' => ['nullable', 'string', 'max:5000'],
            'qa_notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'excluded_from_scan' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'method.required' => __('messages.validation.endpoint_method_required'),
            'method.in' => __('messages.validation.endpoint_method_invalid'),
            'path.required' => __('messages.validation.endpoint_path_required'),
            'path.unique' => __('messages.validation.endpoint_unique'),
            'expected_status.between' => __('messages.validation.endpoint_expected_status'),
            'risk_level.required' => __('messages.validation.endpoint_risk_required'),
            'risk_level.in' => __('messages.validation.endpoint_risk_invalid'),
        ];
    }
}
