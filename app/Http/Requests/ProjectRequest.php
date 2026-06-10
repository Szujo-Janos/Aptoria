<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_url' => ['required', 'url', 'max:500'],
            'report_client_name' => ['nullable', 'string', 'max:160'],
            'report_organization' => ['nullable', 'string', 'max:160'],
            'report_prepared_by' => ['nullable', 'string', 'max:120'],
            'report_role_title' => ['nullable', 'string', 'max:160'],
            'report_confidentiality_label' => ['nullable', 'string', 'max:120'],
            'report_disclaimer' => ['nullable', 'string', 'max:3000'],
            'report_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_report_logo' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.validation.project_name_required'),
            'base_url.required' => __('messages.validation.base_url_required'),
            'base_url.url' => __('messages.validation.base_url_url'),
        ];
    }
}
