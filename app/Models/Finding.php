<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Finding extends Model
{
    use HasFactory;

    public const SOURCES = ['manual', 'scan', 'assertion', 'contract', 'test_case', 'regression'];
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];
    public const STATUSES = ['open', 'confirmed', 'triaged', 'in_progress', 'fixed', 'ready_for_retest', 'retest_failed', 'verified'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];
    public const RETEST_STATUSES = ['not_required', 'required', 'ready_for_retest', 'passed', 'failed'];

    protected $fillable = [
        'project_id',
        'endpoint_id',
        'scan_run_id',
        'scan_result_id',
        'endpoint_snapshot_compare_id',
        'endpoint_snapshot_compare_item_id',
        'title',
        'source',
        'severity',
        'status',
        'priority',
        'owner_name',
        'due_date',
        'summary',
        'reproduction_steps',
        'expected_result',
        'actual_result',
        'recommendation',
        'evidence_required',
        'retest_required',
        'retest_status',
        'retest_note',
        'retest_requested_at',
        'ready_for_retest_at',
        'retested_at',
        'retested_by_user_id',
        'retest_evidence_id',
        'merged_into_finding_id',
        'duplicate_group_key',
        'merged_at',
        'merged_by_user_id',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'evidence_required' => 'boolean',
            'retest_required' => 'boolean',
            'retest_requested_at' => 'datetime',
            'ready_for_retest_at' => 'datetime',
            'retested_at' => 'datetime',
            'merged_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function scanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class);
    }

    public function scanResult(): BelongsTo
    {
        return $this->belongsTo(ScanResult::class);
    }

    public function endpointSnapshotCompare(): BelongsTo
    {
        return $this->belongsTo(EndpointSnapshotCompare::class);
    }

    public function endpointSnapshotCompareItem(): BelongsTo
    {
        return $this->belongsTo(EndpointSnapshotCompareItem::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(FindingEvidence::class);
    }

    public function retestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retested_by_user_id');
    }

    public function retestEvidence(): BelongsTo
    {
        return $this->belongsTo(FindingEvidence::class, 'retest_evidence_id');
    }

    public function riskAcceptances(): HasMany
    {
        return $this->hasMany(RiskAcceptance::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Finding::class, 'merged_into_finding_id');
    }

    public function mergedDuplicates(): HasMany
    {
        return $this->hasMany(Finding::class, 'merged_into_finding_id');
    }

    public function duplicateCandidatesAsPrimary(): HasMany
    {
        return $this->hasMany(FindingDuplicateCandidate::class, 'primary_finding_id');
    }

    public function activeRiskAcceptance(): HasMany
    {
        return $this->riskAcceptances()->where('status', 'active')->latest('accepted_at');
    }

    public function getSourceLabelAttribute(): string
    {
        return __('messages.findings.sources.'.($this->source ?: 'manual'));
    }

    public function getSeverityLabelAttribute(): string
    {
        return __('messages.findings.severities.'.($this->severity ?: 'low'));
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.findings.statuses.'.($this->status ?: 'open'));
    }

    public function getPriorityLabelAttribute(): string
    {
        return __('messages.findings.priorities.'.($this->priority ?: 'normal'));
    }

    public function getRetestStatusLabelAttribute(): string
    {
        return __('messages.findings.retest_statuses.'.($this->retest_status ?: 'not_required'));
    }

    public function getRetestStatusToneAttribute(): string
    {
        return match ($this->retest_status) {
            'passed' => 'success',
            'failed' => 'danger',
            'ready_for_retest' => 'primary',
            'required' => 'warning',
            default => 'secondary',
        };
    }

    public function getActiveRiskAcceptanceAttribute(): ?RiskAcceptance
    {
        return $this->relationLoaded('riskAcceptances')
            ? $this->riskAcceptances->first(fn (RiskAcceptance $acceptance) => $acceptance->is_active_and_valid)
            : $this->activeRiskAcceptance()->get()->first(fn (RiskAcceptance $acceptance) => $acceptance->is_active_and_valid);
    }

    public function getLatestRiskAcceptanceAttribute(): ?RiskAcceptance
    {
        return $this->relationLoaded('riskAcceptances')
            ? $this->riskAcceptances->sortByDesc('accepted_at')->first()
            : $this->riskAcceptances()->latest('accepted_at')->latest()->first();
    }

    public function getRiskAcceptanceStateLabelAttribute(): string
    {
        if ($this->active_risk_acceptance) {
            return $this->active_risk_acceptance->status_label;
        }

        if ($this->latest_risk_acceptance?->display_status === 'expired') {
            return __('messages.risk_acceptance.statuses.expired');
        }

        return __('messages.risk_acceptance.not_accepted');
    }

    public function getRiskAcceptanceToneAttribute(): string
    {
        if ($this->active_risk_acceptance) {
            return $this->active_risk_acceptance->status_tone;
        }

        return $this->latest_risk_acceptance?->display_status === 'expired' ? 'danger' : 'secondary';
    }

    public function getSeverityToneAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            default => 'success',
        };
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'verified' => 'success',
            'fixed', 'ready_for_retest' => 'primary',
            'retest_failed' => 'danger',
            'in_progress', 'triaged' => 'warning',
            'confirmed' => 'info',
            default => 'secondary',
        };
    }

    public function getPriorityToneAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'low' => 'secondary',
            default => 'primary',
        };
    }
}
