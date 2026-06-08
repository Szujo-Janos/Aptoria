<?php

namespace App\Http\Requests;

use App\Models\FindingEvidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'url' => ['nullable', 'url', 'max:1000'],
        ];
    }
}
