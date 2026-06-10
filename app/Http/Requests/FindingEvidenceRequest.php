<?php

namespace App\Http\Requests;

use App\Models\FindingEvidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FindingEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(FindingEvidence::TYPES)],
            'source_label' => ['nullable', 'string', 'max:160'],
            'content' => ['nullable', 'string'],
            'request_excerpt' => ['nullable', 'string'],
            'response_excerpt' => ['nullable', 'string'],
            'curl_command' => ['nullable', 'string'],
            'url' => ['nullable', 'url', 'max:1000'],
            'captured_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasEvidence = collect([
                    $this->input('content'),
                    $this->input('request_excerpt'),
                    $this->input('response_excerpt'),
                    $this->input('curl_command'),
                    $this->input('url'),
                ])->contains(fn ($value): bool => filled($value)) || $this->hasFile('attachment');

                if (! $hasEvidence) {
                    $validator->errors()->add('content', __('messages.findings.evidence_required'));
                }
            },
        ];
    }
}
