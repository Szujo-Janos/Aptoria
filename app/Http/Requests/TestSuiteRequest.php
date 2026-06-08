<?php

namespace App\Http\Requests;

use App\Models\TestSuite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestSuiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $testSuite = $this->route('testSuite');

        return [
            'name' => [
                'required',
                'string',
                'max:180',
                Rule::unique('test_suites')
                    ->where('project_id', $project?->id)
                    ->ignore($testSuite?->id),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'string', Rule::in(TestSuite::STATUSES)],
        ];
    }
}
