<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->id : $project;

        return [
            'name' => ['required', 'string', 'max:80'],
            'base_url' => ['required', 'url', 'max:500'],
            'auth_profile_id' => [
                'nullable',
                Rule::exists('auth_profiles', 'id')->where('project_id', $projectId),
            ],
            'is_production' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.validation.environment_name_required'),
            'base_url.required' => __('messages.validation.base_url_required'),
            'base_url.url' => __('messages.validation.environment_base_url_url'),
        ];
    }
}
