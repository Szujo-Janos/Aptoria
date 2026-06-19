<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMembership extends Model
{
    use HasFactory;

    public const ROLE_PROJECT_ADMIN = 'project_admin';
    public const ROLE_QA_ENGINEER = 'qa_engineer';
    public const ROLE_REVIEWER = 'reviewer';
    public const ROLE_RELEASE_APPROVER = 'release_approver';
    public const ROLE_READ_ONLY_VIEWER = 'read_only_viewer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'status',
        'invited_by_user_id',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    public static function roles(): array
    {
        return [
            self::ROLE_PROJECT_ADMIN,
            self::ROLE_QA_ENGINEER,
            self::ROLE_REVIEWER,
            self::ROLE_RELEASE_APPROVER,
            self::ROLE_READ_ONLY_VIEWER,
        ];
    }

    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_DISABLED];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function getRoleLabelAttribute(): string
    {
        return __('messages.project_members.roles.'.($this->role ?: self::ROLE_READ_ONLY_VIEWER));
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.project_members.statuses.'.($this->status ?: self::STATUS_ACTIVE));
    }

    public function getStatusToneAttribute(): string
    {
        return $this->status === self::STATUS_ACTIVE ? 'success' : 'secondary';
    }

    public function getRoleToneAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_PROJECT_ADMIN => 'primary',
            self::ROLE_QA_ENGINEER => 'success',
            self::ROLE_RELEASE_APPROVER => 'info',
            self::ROLE_REVIEWER => 'warning',
            default => 'secondary',
        };
    }
}
