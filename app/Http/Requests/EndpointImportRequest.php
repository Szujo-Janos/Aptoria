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
            'format' => ['required', Rule::in(['csv', 'json', 'openapi'])],
            'import_source' => ['required', Rule::in(['paste', 'url'])],
            'source_url' => ['nullable', 'required_if:import_source,url', 'url', 'max:1000'],
            'payload' => ['nullable', 'required_if:import_source,paste', 'string', 'max:200000'],
            'environment_id' => ['nullable', 'integer'],
            'auth_profile_id' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('import_source') === 'url' && $this->input('format') !== 'openapi') {
                $validator->errors()->add('format', __('messages.import_preview.reason_url_openapi_only'));
            }
        });
    }
}
