<?php

namespace App\Http\Requests;

use App\Models\AuthProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuthProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(AuthProfile::TYPES)],
            'token' => ['nullable', 'string', 'max:5000'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:5000'],
            'header_name' => ['nullable', 'string', 'max:255'],
            'header_value' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('messages.validation.auth_name_required'),
            'type.required' => __('messages.validation.auth_type_required'),
            'type.in' => __('messages.validation.auth_type_invalid'),
        ];
    }
}
