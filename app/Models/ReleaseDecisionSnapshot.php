<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReleaseDecisionSnapshot extends Model
{
    public const DECISIONS = ['ready', 'blocked', 'needs_review'];

    protected $fillable = [
        'project_id',
        'release_readiness_run_id',
        'decided_by_user_id',
        'decision',
        'title',
        'evidence_summary_markdown',
        'evidence_summary_json',
        'readiness_metrics_json',
        'readiness_checks_json',
        'source_state_json',
        'decision_note',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_summary_json' => 'array',
            'readiness_metrics_json' => 'array',
            'readiness_checks_json' => 'array',
            'source_state_json' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function releaseReadinessRun(): BelongsTo
    {
        return $this->belongsTo(ReleaseReadinessRun::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function reportVersions(): HasMany
    {
        return $this->hasMany(ReportVersion::class);
    }

    public function latestReportVersion(): HasMany
    {
        return $this->reportVersions()->latest('generated_at')->latest();
    }

    public function getDecisionLabelAttribute(): string
    {
        return __('messages.release_decisions.decisions.'.($this->decision ?: 'needs_review'));
    }

    public function getDecisionToneAttribute(): string
    {
        return match ($this->decision) {
            'ready' => 'success',
            'blocked' => 'danger',
            default => 'warning',
        };
    }

    public function getEvidenceSummaryAttribute(): array
    {
        return is_array($this->evidence_summary_json) ? $this->evidence_summary_json : [];
    }

    public function getReadinessMetricsAttribute(): array
    {
        return is_array($this->readiness_metrics_json) ? $this->readiness_metrics_json : [];
    }

    public function getReadinessChecksAttribute(): array
    {
        return is_array($this->readiness_checks_json) ? $this->readiness_checks_json : [];
    }

    public function getSourceStateAttribute(): array
    {
        return is_array($this->source_state_json) ? $this->source_state_json : [];
    }
}
