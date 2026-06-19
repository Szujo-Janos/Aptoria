<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportVersion extends Model
{
    use HasFactory;

    public const TYPES = ['full_project', 'release_readiness', 'release_decision', 'evidence_pack', 'technical_summary'];
    public const STATUSES = ['draft', 'reviewed', 'approved', 'archived'];

    protected $fillable = [
        'project_id',
        'generated_by_user_id',
        'reviewed_by_user_id',
        'approved_by_user_id',
        'archived_by_user_id',
        'release_readiness_run_id',
        'release_decision_snapshot_id',
        'release_gate_id',
        'type',
        'status',
        'title',
        'content_markdown',
        'content_html',
        'data_json',
        'checksum',
        'notes',
        'review_note',
        'approval_note',
        'archive_note',
        'approval_signoff_name',
        'approval_signoff_role',
        'approval_signoff_statement',
        'approval_signed_at',
        'approval_context_json',
        'generated_at',
        'reviewed_at',
        'approved_at',
        'archived_at',
        'client_delivery_count',
        'client_download_count',
        'client_last_delivered_at',
        'client_last_downloaded_at',
        'client_delivery_summary_json',
    ];

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approval_signed_at' => 'datetime',
            'approval_context_json' => 'array',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
            'client_delivery_count' => 'integer',
            'client_download_count' => 'integer',
            'client_last_delivered_at' => 'datetime',
            'client_last_downloaded_at' => 'datetime',
            'client_delivery_summary_json' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function releaseReadinessRun(): BelongsTo
    {
        return $this->belongsTo(ReleaseReadinessRun::class);
    }

    public function releaseDecisionSnapshot(): BelongsTo
    {
        return $this->belongsTo(ReleaseDecisionSnapshot::class);
    }

    public function releaseGate(): BelongsTo
    {
        return $this->belongsTo(ReleaseGate::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }

    public function clientPortalAccesses(): HasMany
    {
        return $this->hasMany(ClientPortalAccess::class);
    }

    public function clientPortalAcknowledgements(): HasMany
    {
        return $this->hasMany(ClientPortalAcknowledgement::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.reports.types.'.($this->type ?: 'full_project'));
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.reports.statuses.'.($this->status ?: 'draft'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'reviewed' => 'primary',
            'archived' => 'secondary',
            default => 'warning',
        };
    }

    public function getIsClientDeliverableAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getClientDeliveryStateLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => __('messages.reports.delivery_states.approved'),
            'reviewed' => __('messages.reports.delivery_states.needs_approval'),
            'archived' => __('messages.reports.delivery_states.archived'),
            default => __('messages.reports.delivery_states.draft'),
        };
    }

    public function getClientDeliveryToneAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'reviewed' => 'warning',
            'archived' => 'secondary',
            default => 'warning',
        };
    }


    public function getHasApprovalSignoffAttribute(): bool
    {
        return $this->status === 'approved' && filled($this->approval_signoff_name);
    }

    public function getApprovalSignoffDisplayAttribute(): string
    {
        if (! $this->has_approval_signoff) {
            return __('messages.common.not_available');
        }

        return trim($this->approval_signoff_name.($this->approval_signoff_role ? ' · '.$this->approval_signoff_role : ''));
    }

    public function approvalSummary(): array
    {
        return [
            'status' => $this->status,
            'reviewed_by' => $this->reviewedBy?->name,
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'review_note' => $this->review_note,
            'approved_by' => $this->approvedBy?->name,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'approval_note' => $this->approval_note,
            'approval_signoff_name' => $this->approval_signoff_name,
            'approval_signoff_role' => $this->approval_signoff_role,
            'approval_signoff_statement' => $this->approval_signoff_statement,
            'approval_signed_at' => $this->approval_signed_at?->toDateTimeString(),
            'archived_by' => $this->archivedBy?->name,
            'archived_at' => $this->archived_at?->toDateTimeString(),
            'archive_note' => $this->archive_note,
            'approval_context' => $this->approval_context_json ?: [],
        ];
    }
}
