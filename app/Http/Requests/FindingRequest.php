<?php

namespace App\Http\Requests;

use App\Models\Finding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'endpoint_id' => ['nullable', 'integer'],
            'test_case_id' => ['nullable', 'integer'],
            'scan_run_id' => ['nullable', 'integer'],
            'scan_result_id' => ['nullable', 'integer'],
            'contract_validation_result_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string'],
            'source' => ['required', Rule::in(Finding::SOURCES)],
            'severity' => ['required', Rule::in(Finding::SEVERITIES)],
            'status' => ['required', Rule::in(Finding::STATUSES)],
            'reproduction_steps' => ['nullable', 'string'],
            'expected_result' => ['nullable', 'string'],
            'actual_result' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
        ];
    }
}
