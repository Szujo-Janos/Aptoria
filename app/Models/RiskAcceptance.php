<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAcceptance extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_RENEWED = 'renewed';
    public const STATUS_REVOKED = 'revoked';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
        self::STATUS_RENEWED,
        self::STATUS_REVOKED,
    ];

    public const EXPIRY_ACTION_REVIEW = 'review';
    public const EXPIRY_ACTION_REOPEN_FINDING = 'reopen_finding';
    public const EXPIRY_ACTION_BLOCK_RELEASE = 'block_release';
    public const EXPIRY_ACTION_RENEW_OR_CLOSE = 'renew_or_close';

    public const EXPIRY_ACTIONS = [
        self::EXPIRY_ACTION_REVIEW,
        self::EXPIRY_ACTION_REOPEN_FINDING,
        self::EXPIRY_ACTION_BLOCK_RELEASE,
        self::EXPIRY_ACTION_RENEW_OR_CLOSE,
    ];

    protected $fillable = [
        'project_id',
        'finding_id',
        'accepted_by_user_id',
        'renewed_from_id',
        'accepted_at',
        'accepted_until',
        'status',
        'reason',
        'business_justification',
        'mitigation_note',
        'evidence_requirement',
        'release_scope',
        'expiry_action',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'accepted_until' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RiskAcceptance $acceptance): void {
            if (! $acceptance->accepted_at) {
                $acceptance->accepted_at = now();
            }

            if (! $acceptance->status) {
                $acceptance->status = self::STATUS_ACTIVE;
            }

            if (! $acceptance->expiry_action) {
                $acceptance->expiry_action = self::EXPIRY_ACTION_REVIEW;
            }
        });

        static::saved(function (RiskAcceptance $acceptance): void {
            $finding = $acceptance->finding;
            if (! $finding) {
                return;
            }

            if ($acceptance->status === self::STATUS_ACTIVE) {
                $finding->forceFill([
                    'status' => Finding::STATUS_ACCEPTED_RISK,
                    'accepted_risk_expires_at' => $acceptance->accepted_until,
                    'accepted_risk_note' => trim((string) $acceptance->reason),
                    'lifecycle_changed_at' => $acceptance->accepted_at ?: now(),
                    'lifecycle_changed_by_user_id' => $acceptance->accepted_by_user_id,
                ])->saveQuietly();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.risk_acceptances.statuses.'.($this->status ?: self::STATUS_ACTIVE));
    }

    public function getExpiryActionLabelAttribute(): string
    {
        return __('messages.risk_acceptances.expiry_actions.'.($this->expiry_action ?: self::EXPIRY_ACTION_REVIEW));
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->computed_status) {
            self::STATUS_EXPIRED => 'danger',
            self::STATUS_RENEWED => 'info',
            self::STATUS_REVOKED => 'default',
            default => $this->accepted_until === null ? 'warning' : ($this->expires_soon ? 'warning' : 'success'),
        };
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return $this->status;
        }

        if ($this->accepted_until && $this->accepted_until->isPast()) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_ACTIVE;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->computed_status === self::STATUS_EXPIRED;
    }

    public function getExpiresSoonAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->accepted_until !== null
            && $this->accepted_until->isFuture()
            && $this->accepted_until->lte(now()->addDays(14));
    }

    public function getHasExpiryAttribute(): bool
    {
        return $this->accepted_until !== null;
    }
}
