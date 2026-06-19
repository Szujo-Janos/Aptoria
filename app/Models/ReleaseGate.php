<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReleaseGate extends Model
{
    use HasFactory;

    public const PROFILES = ['standard', 'strict', 'hotfix'];
    public const STATUSES = ['needs_review', 'blocked', 'ready', 'approved', 'rejected', 'conditional_go'];
    public const AUTOMATED_DECISIONS = ['pass', 'warning', 'blocked'];
    public const FINAL_DECISIONS = ['pending', 'go', 'no_go', 'conditional_go'];

    protected $fillable = [
        'project_id',
        'release_readiness_run_id',
        'release_decision_snapshot_id',
        'created_by_user_id',
        'finalized_by_user_id',
        'title',
        'release_version',
        'target_environment',
        'gate_profile',
        'status',
        'automated_decision',
        'final_decision',
        'score',
        'grade',
        'blocker_count',
        'warning_count',
        'passed_item_count',
        'total_item_count',
        'evidence_count',
        'verified_evidence_count',
        'test_run_count',
        'failed_test_run_count',
        'open_finding_count',
        'high_critical_open_count',
        'summary_json',
        'source_state_json',
        'decision_note',
        'evaluated_at',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'blocker_count' => 'integer',
            'warning_count' => 'integer',
            'passed_item_count' => 'integer',
            'total_item_count' => 'integer',
            'evidence_count' => 'integer',
            'verified_evidence_count' => 'integer',
            'test_run_count' => 'integer',
            'failed_test_run_count' => 'integer',
            'open_finding_count' => 'integer',
            'high_critical_open_count' => 'integer',
            'summary_json' => 'array',
            'source_state_json' => 'array',
            'evaluated_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function readinessRun(): BelongsTo
    {
        return $this->belongsTo(ReleaseReadinessRun::class, 'release_readiness_run_id');
    }

    public function decisionSnapshot(): BelongsTo
    {
        return $this->belongsTo(ReleaseDecisionSnapshot::class, 'release_decision_snapshot_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReleaseGateItem::class)->orderBy('sort_order')->orderBy('id');
    }


    public function reportVersions(): HasMany
    {
        return $this->hasMany(ReportVersion::class)->latest('generated_at')->latest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReleaseGateEvent::class)->latest('occurred_at')->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.release_gates.statuses.'.($this->status ?: 'needs_review'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'approved', 'ready' => 'success',
            'conditional_go', 'needs_review' => 'warning',
            'rejected', 'blocked' => 'danger',
            default => 'secondary',
        };
    }

    public function getFinalDecisionLabelAttribute(): string
    {
        return __('messages.release_gates.final_decisions.'.($this->final_decision ?: 'pending'));
    }

    public function getFinalDecisionToneAttribute(): string
    {
        return match ($this->final_decision) {
            'go' => 'success',
            'conditional_go' => 'warning',
            'no_go' => 'danger',
            default => 'secondary',
        };
    }

    public function getProfileLabelAttribute(): string
    {
        return __('messages.release_gates.profiles.'.($this->gate_profile ?: 'standard'));
    }

    public function getAutomatedDecisionLabelAttribute(): string
    {
        return __('messages.release_gates.automated_decisions.'.($this->automated_decision ?: 'warning'));
    }

    public function getAutomatedDecisionToneAttribute(): string
    {
        return match ($this->automated_decision) {
            'pass' => 'success',
            'blocked' => 'danger',
            default => 'warning',
        };
    }
}
