<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ClientPortalAccess extends Model
{
    use HasFactory;

    public const ROLES = ['client_viewer', 'client_approver', 'external_reviewer'];

    public const PERMISSIONS = ['decision_package', 'reports', 'readiness', 'findings', 'evidence'];

    protected $fillable = [
        'project_id',
        'report_version_id',
        'created_by_user_id',
        'name',
        'token',
        'role',
        'permissions_json',
        'is_active',
        'acknowledge_required',
        'acknowledgement_status',
        'acknowledgement_decision',
        'acknowledgement_comment',
        'latest_acknowledgement_id',
        'expires_at',
        'last_viewed_at',
        'acknowledged_at',
        'acknowledged_by_name',
        'acknowledged_by_email',
    ];

    protected function casts(): array
    {
        return [
            'permissions_json' => 'array',
            'is_active' => 'boolean',
            'acknowledge_required' => 'boolean',
            'expires_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $access): void {
            if (! $access->token) {
                $access->token = Str::random(56);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reportVersion(): BelongsTo
    {
        return $this->belongsTo(ReportVersion::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(ClientPortalAcknowledgement::class);
    }

    public function latestAcknowledgement(): BelongsTo
    {
        return $this->belongsTo(ClientPortalAcknowledgement::class, 'latest_acknowledgement_id');
    }

    public function getPermissionsAttribute(): array
    {
        $permissions = is_array($this->permissions_json) ? $this->permissions_json : [];

        return array_values(array_intersect($permissions, self::PERMISSIONS));
    }

    public function allows(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }

    public function getIsUsableAttribute(): bool
    {
        return $this->is_active && ! $this->is_expired;
    }

    public function getRoleLabelAttribute(): string
    {
        return __('messages.client_portal.roles.'.($this->role ?: 'client_viewer'));
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return __('messages.client_portal.statuses.inactive');
        }

        if ($this->is_expired) {
            return __('messages.client_portal.statuses.expired');
        }

        return __('messages.client_portal.statuses.active');
    }

    public function getStatusToneAttribute(): string
    {
        if (! $this->is_active) {
            return 'secondary';
        }

        if ($this->is_expired) {
            return 'danger';
        }

        return 'success';
    }

    public function getAcknowledgementStateLabelAttribute(): string
    {
        if (! $this->acknowledge_required) {
            return __('messages.client_portal.ack_states.not_required');
        }

        if ($this->acknowledged_at) {
            return __('messages.client_portal.ack_states.acknowledged');
        }

        return __('messages.client_portal.ack_states.pending');
    }

    public function getAcknowledgementStateToneAttribute(): string
    {
        if (! $this->acknowledge_required) {
            return 'secondary';
        }

        if ($this->acknowledged_at) {
            return 'success';
        }

        return 'warning';
    }

    public function getAcknowledgementDecisionLabelAttribute(): string
    {
        if (! $this->acknowledgement_decision) {
            return '—';
        }

        return __('messages.client_portal.ack_decisions.'.$this->acknowledgement_decision);
    }

    public function getAcknowledgementDecisionToneAttribute(): string
    {
        return match ($this->acknowledgement_decision) {
            'approved' => 'success',
            'needs_changes' => 'warning',
            'rejected' => 'danger',
            'reviewed' => 'primary',
            default => 'secondary',
        };
    }

    public function getPublicUrlAttribute(): string
    {
        return route('client-portal.show', $this->token);
    }
}
