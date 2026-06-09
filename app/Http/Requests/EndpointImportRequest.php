<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EndpointImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'import_source' => $this->input('import_source', 'paste'),
        ]);
    }

    public function rules(): array
    {
        return [
            'format' => ['required', Rule::in(['csv', 'json', 'openapi', 'postman'])],
            'import_source' => ['required', Rule::in(['paste', 'url'])],
            'source_url' => ['nullable', 'required_if:import_source,url', 'url', 'max:1000'],
            'payload' => ['nullable', 'string', 'max:200000'],
            'payload_encoded' => ['nullable', 'string', 'max:300000'],
            'postman_environment_payload' => ['nullable', 'string', 'max:200000'],
            'postman_environment_payload_encoded' => ['nullable', 'string', 'max:300000'],
            'postman_globals_payload' => ['nullable', 'string', 'max:200000'],
            'postman_globals_payload_encoded' => ['nullable', 'string', 'max:300000'],
            'postman_create_environment' => ['nullable', 'boolean'],
            'postman_create_auth_profile' => ['nullable', 'boolean'],
            'postman_create_test_suites' => ['nullable', 'boolean'],
            'postman_create_assertions' => ['nullable', 'boolean'],
            'environment_id' => ['nullable', 'integer'],
            'auth_profile_id' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('import_source') === 'url' && ! in_array($this->input('format'), ['openapi', 'postman'], true)) {
                $validator->errors()->add('format', __('messages.import_preview.reason_url_collection_only'));
            }

            if ($this->input('import_source') === 'paste' && trim((string) $this->input('payload')) === '' && trim((string) $this->input('payload_encoded')) === '') {
                $validator->errors()->add('payload', __('validation.required', ['attribute' => 'payload']));
            }
        });
    }
}
