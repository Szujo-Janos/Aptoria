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

    public const ROLES = [
        self::ROLE_PROJECT_ADMIN,
        self::ROLE_QA_ENGINEER,
        self::ROLE_REVIEWER,
        self::ROLE_RELEASE_APPROVER,
        self::ROLE_READ_ONLY_VIEWER,
    ];

    public const ROLE_LABELS = [
        self::ROLE_PROJECT_ADMIN => 'Project admin',
        self::ROLE_QA_ENGINEER => 'QA engineer',
        self::ROLE_REVIEWER => 'Reviewer',
        self::ROLE_RELEASE_APPROVER => 'Release approver',
        self::ROLE_READ_ONLY_VIEWER => 'Read-only viewer',
    ];

    /** @var array<string, array<int, string>> */
    public const ROLE_PERMISSIONS = [
        self::ROLE_PROJECT_ADMIN => [
            'project.view',
            'project.manage',
            'members.manage',
            'settings.manage',
            'endpoints.manage',
            'scans.run',
            'monitors.manage',
            'tests.manage',
            'findings.manage',
            'findings.review',
            'evidence.manage',
            'risk.accept',
            'release.finalize',
            'report.generate',
            'report.review',
            'report.approve',
            'portal.manage',
            'exports.download',
        ],
        self::ROLE_QA_ENGINEER => [
            'project.view',
            'endpoints.manage',
            'scans.run',
            'tests.manage',
            'findings.manage',
            'findings.review',
            'evidence.manage',
            'report.generate',
            'exports.download',
        ],
        self::ROLE_REVIEWER => [
            'project.view',
            'findings.review',
            'evidence.manage',
            'risk.accept',
            'report.generate',
            'report.review',
            'exports.download',
        ],
        self::ROLE_RELEASE_APPROVER => [
            'project.view',
            'risk.accept',
            'release.finalize',
            'report.review',
            'report.approve',
            'portal.manage',
            'exports.download',
        ],
        self::ROLE_READ_ONLY_VIEWER => [
            'project.view',
        ],
    ];

    protected $fillable = [
        'project_id',
        'user_id',
        'invited_by_user_id',
        'role',
        'notes',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
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
        return self::translatedRoleLabel((string) $this->role);
    }

    public function grants(string $ability): bool
    {
        return in_array($ability, self::ROLE_PERMISSIONS[$this->role] ?? [], true);
    }

    public static function translatedRoleLabel(string $role): string
    {
        $key = 'messages.project_members.roles.'.$role;
        $label = __($key);

        return $label === $key ? (self::ROLE_LABELS[$role] ?? ucfirst(str_replace('_', ' ', $role))) : $label;
    }

    public static function translatedPermissionLabel(string $ability): string
    {
        $key = 'messages.project_members.permission_labels.'.$ability;
        $label = __($key);

        return $label === $key ? ucfirst(str_replace(['.', '_'], [' / ', ' '], $ability)) : $label;
    }

    /** @return array<string, string> */
    public static function translatedRoleOptions(): array
    {
        return collect(self::ROLES)
            ->mapWithKeys(fn (string $role): array => [$role => self::translatedRoleLabel($role)])
            ->all();
    }

    public function getTranslatedRoleLabelAttribute(): string
    {
        return self::translatedRoleLabel((string) $this->role);
    }

    /** @return array<string, string> */
    public static function roleOptions(): array
    {
        return self::ROLE_LABELS;
    }

    /** @return array<int, string> */
    public function permissions(): array
    {
        return self::ROLE_PERMISSIONS[$this->role] ?? [];
    }
}
