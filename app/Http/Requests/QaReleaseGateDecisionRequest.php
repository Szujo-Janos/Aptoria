<?php

namespace App\Http\Requests;

use App\Models\QaReleaseGate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QaReleaseGateDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'final_decision' => ['required', Rule::in(QaReleaseGate::DECISIONS)],
            'reviewed_by' => ['nullable', 'string', 'max:160'],
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
