<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseWorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'release_workflow_id',
        'project_id',
        'step_key',
        'label',
        'state',
        'computed_state',
        'manual_state',
        'manual_reason',
        'blocker_count',
        'missing_evidence_count',
        'required_action',
        'suggested_action_label',
        'suggested_action_url',
        'completion_criteria_json',
        'blocker_reasons_json',
        'evidence_summary_json',
        'completed_at',
        'skipped_at',
        'reopened_at',
    ];

    protected function casts(): array
    {
        return [
            'completion_criteria_json' => 'array',
            'blocker_reasons_json' => 'array',
            'evidence_summary_json' => 'array',
            'completed_at' => 'datetime',
            'skipped_at' => 'datetime',
            'reopened_at' => 'datetime',
            'blocker_count' => 'integer',
            'missing_evidence_count' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ReleaseWorkflow::class, 'release_workflow_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getStateLabelAttribute(): string
    {
        return __('messages.release_workflow.states.'.($this->state ?: ReleaseWorkflow::STATE_NOT_STARTED));
    }

    public function getStateCssAttribute(): string
    {
        return match ($this->state) {
            ReleaseWorkflow::STATE_COMPLETED, ReleaseWorkflow::STATE_READY => 'success',
            ReleaseWorkflow::STATE_NEEDS_REVIEW, ReleaseWorkflow::STATE_IN_PROGRESS, ReleaseWorkflow::STATE_SKIPPED => 'warning',
            ReleaseWorkflow::STATE_BLOCKED => 'danger',
            default => 'default',
        };
    }

    public function isSkipped(): bool
    {
        return $this->state === ReleaseWorkflow::STATE_SKIPPED;
    }
}
