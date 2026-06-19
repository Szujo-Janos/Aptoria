<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPortalAcknowledgement extends Model
{
    use HasFactory;

    public const DECISIONS = ['reviewed', 'approved', 'needs_changes', 'rejected'];

    protected $fillable = [
        'project_id',
        'client_portal_access_id',
        'report_version_id',
        'decision_status',
        'acknowledged_by_name',
        'acknowledged_by_email',
        'comment',
        'acknowledge_terms',
        'evidence_summary_json',
        'acknowledged_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'acknowledge_terms' => 'boolean',
            'evidence_summary_json' => 'array',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function access(): BelongsTo
    {
        return $this->belongsTo(ClientPortalAccess::class, 'client_portal_access_id');
    }

    public function reportVersion(): BelongsTo
    {
        return $this->belongsTo(ReportVersion::class);
    }

    public function getDecisionLabelAttribute(): string
    {
        return __('messages.client_portal.ack_decisions.'.($this->decision_status ?: 'reviewed'));
    }

    public function getDecisionToneAttribute(): string
    {
        return match ($this->decision_status) {
            'approved' => 'success',
            'needs_changes' => 'warning',
            'rejected' => 'danger',
            default => 'primary',
        };
    }
}
