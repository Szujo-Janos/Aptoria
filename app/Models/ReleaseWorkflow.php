<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReleaseWorkflow extends Model
{
    use HasFactory;

    public const STATE_NOT_STARTED = 'not_started';
    public const STATE_IN_PROGRESS = 'in_progress';
    public const STATE_BLOCKED = 'blocked';
    public const STATE_NEEDS_REVIEW = 'needs_review';
    public const STATE_READY = 'ready';
    public const STATE_COMPLETED = 'completed';
    public const STATE_SKIPPED = 'skipped_with_reason';

    public const STATES = [
        self::STATE_NOT_STARTED,
        self::STATE_IN_PROGRESS,
        self::STATE_BLOCKED,
        self::STATE_NEEDS_REVIEW,
        self::STATE_READY,
        self::STATE_COMPLETED,
        self::STATE_SKIPPED,
    ];

    protected $fillable = [
        'project_id',
        'release_decision_id',
        'overall_state',
        'progress_percent',
        'completed_steps',
        'blocked_steps',
        'needs_review_steps',
        'ready_steps',
        'not_started_steps',
        'skipped_steps',
        'blocker_count',
        'missing_evidence_count',
        'next_step_key',
        'snapshot_json',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_json' => 'array',
            'evaluated_at' => 'datetime',
            'progress_percent' => 'integer',
            'completed_steps' => 'integer',
            'blocked_steps' => 'integer',
            'needs_review_steps' => 'integer',
            'ready_steps' => 'integer',
            'not_started_steps' => 'integer',
            'skipped_steps' => 'integer',
            'blocker_count' => 'integer',
            'missing_evidence_count' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function releaseDecision(): BelongsTo
    {
        return $this->belongsTo(ReleaseDecision::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ReleaseWorkflowStep::class)->orderBy('id');
    }

    public function getOverallStateLabelAttribute(): string
    {
        return __('messages.release_workflow.states.'.($this->overall_state ?: self::STATE_NOT_STARTED));
    }

    public function getOverallStateCssAttribute(): string
    {
        return match ($this->overall_state) {
            self::STATE_COMPLETED, self::STATE_READY => 'success',
            self::STATE_NEEDS_REVIEW, self::STATE_IN_PROGRESS, self::STATE_SKIPPED => 'warning',
            self::STATE_BLOCKED => 'danger',
            default => 'default',
        };
    }
}
