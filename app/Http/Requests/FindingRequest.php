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
            'owner_user_id' => ['nullable', 'integer'],
            'endpoint_id' => ['nullable', 'integer'],
            'test_case_id' => ['nullable', 'integer'],
            'linked_release_gate_id' => ['nullable', 'integer'],
            'scan_run_id' => ['nullable', 'integer'],
            'scan_result_id' => ['nullable', 'integer'],
            'contract_validation_result_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string'],
            'source' => ['required', Rule::in(Finding::SOURCES)],
            'severity' => ['required', Rule::in(Finding::SEVERITIES)],
            'priority' => ['required', Rule::in(Finding::PRIORITIES)],
            'status' => ['required', Rule::in(Finding::LIFECYCLE_STATUSES)],
            'verification_status' => ['required', Rule::in(Finding::VERIFICATION_STATUSES)],
            'retest_required' => ['nullable', 'boolean'],
            'retest_result' => ['nullable', Rule::in(Finding::RETEST_RESULTS)],
            'fix_evidence_required' => ['nullable', 'boolean'],
            'verified_by_user_id' => ['nullable', 'integer'],
            'verified_at' => ['nullable', 'date'],
            'last_retest_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'reproduction_steps' => ['nullable', 'string'],
            'expected_result' => ['nullable', 'string'],
            'actual_result' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'accepted_risk_expires_at' => ['nullable', 'date'],
            'accepted_risk_note' => ['nullable', 'string'],
        ];
    }
}
