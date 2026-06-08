<?php

namespace App\Http\Requests;

use App\Models\TestCaseResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestCaseResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(TestCaseResult::STATUSES)],
            'actual_result' => ['nullable', 'string', 'max:20000'],
            'notes' => ['nullable', 'string', 'max:20000'],
            'scan_result_id' => ['nullable', 'integer', 'exists:scan_results,id'],
        ];
    }
}
