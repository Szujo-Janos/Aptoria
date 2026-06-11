<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ClientPortalAccess extends Model
{
    use HasFactory;

    public const ROLE_CLIENT_VIEWER = 'client_viewer';
    public const ROLE_CLIENT_APPROVER = 'client_approver';
    public const ROLE_REVIEWER = 'reviewer';

    public const ROLES = [
        self::ROLE_CLIENT_VIEWER,
        self::ROLE_CLIENT_APPROVER,
        self::ROLE_REVIEWER,
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_REVOKED,
    ];

    public const PERMISSION_REPORTS = 'reports';
    public const PERMISSION_RELEASE_DECISIONS = 'release_decisions';
    public const PERMISSION_ACCEPTED_RISKS = 'accepted_risks';
    public const PERMISSION_FINDINGS = 'findings';
    public const PERMISSION_EVIDENCE_PACKAGE = 'evidence_package';
    public const PERMISSION_APPROVE_REPORTS = 'approve_reports';
    public const PERMISSION_ACKNOWLEDGE_RELEASE = 'acknowledge_release';
    public const PERMISSION_APPROVE_RISKS = 'approve_risks';

    public const PERMISSIONS = [
        self::PERMISSION_REPORTS,
        self::PERMISSION_RELEASE_DECISIONS,
        self::PERMISSION_ACCEPTED_RISKS,
        self::PERMISSION_FINDINGS,
        self::PERMISSION_EVIDENCE_PACKAGE,
        self::PERMISSION_APPROVE_REPORTS,
        self::PERMISSION_ACKNOWLEDGE_RELEASE,
        self::PERMISSION_APPROVE_RISKS,
    ];

    protected $fillable = [
        'project_id',
        'created_by_user_id',
        'label',
        'contact_name',
        'contact_email',
        'role',
        'status',
        'portal_token',
        'permissions',
        'expires_at',
        'last_viewed_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'expires_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ClientPortalAccess $access): void {
            if (! $access->portal_token) {
                $access->portal_token = Str::random(48);
            }

            if (! $access->status) {
                $access->status = self::STATUS_ACTIVE;
            }

            if (! $access->role) {
                $access->role = self::ROLE_CLIENT_VIEWER;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'portal_token';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(ClientPortalAcknowledgement::class);
    }

    public function allows(string $permission): bool
    {
        $permissions = $this->permissions ?: [];

        return (bool) ($permissions[$permission] ?? false);
    }

    public function isAvailable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function getPortalUrlAttribute(): string
    {
        return route('client-portal.show', $this);
    }

    public function getRoleLabelAttribute(): string
    {
        return __('messages.client_portal.roles.'.($this->role ?: self::ROLE_CLIENT_VIEWER));
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.client_portal.statuses.'.($this->status ?: self::STATUS_ACTIVE));
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => $this->isAvailable() ? 'success' : 'warning',
            self::STATUS_REVOKED => 'danger',
            default => 'default',
        };
    }
}
