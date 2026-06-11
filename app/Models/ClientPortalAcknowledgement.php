<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPortalAcknowledgement extends Model
{
    use HasFactory;

    public const TYPE_REPORT_APPROVAL = 'report_approval';
    public const TYPE_RELEASE_ACKNOWLEDGEMENT = 'release_acknowledgement';
    public const TYPE_RISK_ACCEPTANCE_ACKNOWLEDGEMENT = 'risk_acceptance_acknowledgement';

    public const TYPES = [
        self::TYPE_REPORT_APPROVAL,
        self::TYPE_RELEASE_ACKNOWLEDGEMENT,
        self::TYPE_RISK_ACCEPTANCE_ACKNOWLEDGEMENT,
    ];

    protected $fillable = [
        'project_id',
        'client_portal_access_id',
        'report_version_id',
        'release_decision_id',
        'risk_acceptance_id',
        'acknowledgement_type',
        'actor_name',
        'actor_email',
        'note',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ClientPortalAcknowledgement $acknowledgement): void {
            if (! $acknowledgement->acknowledged_at) {
                $acknowledgement->acknowledged_at = now();
            }
        });
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

    public function releaseDecision(): BelongsTo
    {
        return $this->belongsTo(ReleaseDecision::class);
    }

    public function riskAcceptance(): BelongsTo
    {
        return $this->belongsTo(RiskAcceptance::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.client_portal.acknowledgement_types.'.($this->acknowledgement_type ?: self::TYPE_REPORT_APPROVAL));
    }
}
