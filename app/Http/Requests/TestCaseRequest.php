<?php

namespace App\Http\Requests;

use App\Models\TestCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'test_suite_id' => ['required', Rule::exists('test_suites', 'id')->where('project_id', $project?->id)],
            'endpoint_id' => ['nullable', Rule::exists('endpoints', 'id')->where('project_id', $project?->id)],
            'title' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:10000'],
            'preconditions' => ['nullable', 'string', 'max:10000'],
            'steps' => ['required', 'string', 'max:20000'],
            'expected_result' => ['required', 'string', 'max:20000'],
            'actual_result' => ['nullable', 'string', 'max:20000'],
            'type' => ['required', 'string', Rule::in(TestCase::TYPES)],
            'priority' => ['required', 'string', Rule::in(TestCase::PRIORITIES)],
            'status' => ['required', 'string', Rule::in(TestCase::STATUSES)],
        ];
    }
}
