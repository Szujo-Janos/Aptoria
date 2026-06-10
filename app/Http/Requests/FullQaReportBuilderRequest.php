<?php

namespace App\Http\Requests;

use App\Services\Reports\FullQaReportBuilderService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FullQaReportBuilderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:160'],
            'audience' => ['required', Rule::in(array_keys(FullQaReportBuilderService::audienceOptions()))],
            'decision' => ['required', Rule::in(array_keys(FullQaReportBuilderService::decisionOptions()))],
            'scope_notes' => ['nullable', 'string', 'max:4000'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['required', Rule::in(FullQaReportBuilderService::SECTIONS)],
            'problem_endpoints_only' => ['nullable', 'boolean'],
            'include_evidence_details' => ['nullable', 'boolean'],
            'include_technical_details' => ['nullable', 'boolean'],
            'endpoint_limit' => ['required', 'integer', 'min:5', 'max:500'],
            'test_case_limit' => ['required', 'integer', 'min:5', 'max:500'],
            'finding_limit' => ['required', 'integer', 'min:5', 'max:500'],
            'contract_result_limit' => ['required', 'integer', 'min:5', 'max:500'],
        ];
    }

    /** @return array<string, mixed> */
    public function reportOptions(): array
    {
        return [
            'title' => $this->input('title') ?: __('messages.report_builder.default_title'),
            'audience' => (string) $this->input('audience', 'internal'),
            'decision' => (string) $this->input('decision', 'draft'),
            'scope_notes' => (string) $this->input('scope_notes', ''),
            'sections' => $this->input('sections', FullQaReportBuilderService::defaultSections()),
            'problem_endpoints_only' => $this->boolean('problem_endpoints_only'),
            'include_evidence_details' => $this->boolean('include_evidence_details'),
            'include_technical_details' => $this->boolean('include_technical_details'),
            'endpoint_limit' => (int) $this->input('endpoint_limit', 100),
            'test_case_limit' => (int) $this->input('test_case_limit', 100),
            'finding_limit' => (int) $this->input('finding_limit', 100),
            'contract_result_limit' => (int) $this->input('contract_result_limit', 100),
        ];
    }
}
