<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskAcceptance extends Model
{
    use HasFactory;

    public const STATUSES = ['active', 'expired', 'renewed', 'revoked'];
    public const EXPIRING_SOON_DAYS = 7;

    protected $fillable = [
        'project_id',
        'finding_id',
        'accepted_by_user_id',
        'revoked_by_user_id',
        'renewed_from_id',
        'status',
        'accepted_at',
        'accepted_until',
        'revoked_at',
        'reason',
        'business_justification',
        'mitigation_note',
        'release_scope',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'accepted_until' => 'date',
            'revoked_at' => 'datetime',
            'metadata_json' => 'array',
        ];
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

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewed_from_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.risk_acceptance.statuses.'.$this->display_status);
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'active' && $this->is_expired) {
            return 'expired';
        }

        if ($this->status === 'active' && $this->is_expiring_soon) {
            return 'expiring_soon';
        }

        return $this->status ?: 'active';
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->display_status) {
            'active' => 'success',
            'expiring_soon' => 'warning',
            'expired' => 'danger',
            'renewed' => 'info',
            'revoked' => 'secondary',
            default => 'secondary',
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->accepted_until !== null && $this->accepted_until->isPast() && ! $this->accepted_until->isToday();
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if ($this->accepted_until === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->accepted_until->copy()->startOfDay(), false);
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if ($this->status !== 'active' || $this->is_expired || $this->days_until_expiry === null) {
            return false;
        }

        return $this->days_until_expiry >= 0 && $this->days_until_expiry <= self::EXPIRING_SOON_DAYS;
    }

    public function getIsActiveAndValidAttribute(): bool
    {
        return $this->status === 'active' && ! $this->is_expired;
    }
}
