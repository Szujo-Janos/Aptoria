<?php

namespace App\Services\Access;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Builder;

class ProjectAccessService
{
    public function isSystemAdmin(?User $user): bool
    {
        return $user instanceof User && ($user->role ?? null) === 'admin';
    }

    public function hasAnyWorkspaceAccess(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user->projectMemberships()->exists()
            || Project::query()->where('user_id', $user->id)->exists();
    }

    public function membership(Project $project, ?User $user): ?ProjectMembership
    {
        if (! $user instanceof User) {
            return null;
        }

        return $project->memberships()
            ->where('user_id', $user->id)
            ->first();
    }

    public function role(Project $project, ?User $user): string
    {
        if ($this->isSystemAdmin($user)) {
            return 'system_admin';
        }

        if ($user instanceof User && (int) $project->user_id === (int) $user->id) {
            return ProjectMembership::ROLE_PROJECT_ADMIN;
        }

        return (string) ($this->membership($project, $user)?->role ?? '');
    }

    public function roleLabel(Project $project, ?User $user): string
    {
        $role = $this->role($project, $user);

        if ($role === 'system_admin') {
            return (string) __('messages.project_members.roles.system_admin');
        }

        return $role !== '' ? ProjectMembership::translatedRoleLabel($role) : (string) __('messages.project_members.roles.no_project_access');
    }

    public function can(Project $project, ?User $user, string $ability): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if (! $user instanceof User) {
            return false;
        }

        if ((int) $project->user_id === (int) $user->id) {
            return true;
        }

        return (bool) $this->membership($project, $user)?->grants($ability);
    }

    public function deny(Project $project, ?User $user, string $ability, ?string $summary = null): never
    {
        $this->recordPermissionDenied($project, $user, $ability, $summary ?? (string) __('messages.project_members.audit.permission_denied_summary'));

        abort(403, __('messages.project_members.permission_denied'));
    }

    public function authorize(Project $project, ?User $user, string $ability, ?string $summary = null): void
    {
        if (! $this->can($project, $user, $ability)) {
            $summary ??= (string) __('messages.project_members.audit.project_action_denied', [
                'ability' => ProjectMembership::translatedPermissionLabel($ability),
            ]);

            $this->deny($project, $user, $ability, $summary);
        }
    }

    /** @param Builder<Project> $query */
    public function scopeVisibleProjects(Builder $query, ?User $user): Builder
    {
        if ($this->isSystemAdmin($user)) {
            return $query;
        }

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $inner) use ($user): void {
            $inner->where('user_id', $user->id)
                ->orWhereHas('memberships', fn (Builder $membershipQuery) => $membershipQuery->where('user_id', $user->id));
        });
    }

    /** @return array<string, bool> */
    public function permissionMap(Project $project, ?User $user): array
    {
        $abilities = [
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
        ];

        return collect($abilities)
            ->mapWithKeys(fn (string $ability): array => [$ability => $this->can($project, $user, $ability)])
            ->all();
    }

    public function recordPermissionDenied(Project $project, ?User $user, string $ability, string $summary): void
    {
        app(AuditLogService::class)->record([
            'project_id' => $project->id,
            'user_id' => $user?->id,
            'event_type' => AuditLog::EVENT_SYSTEM,
            'action' => AuditLog::ACTION_REQUESTED,
            'severity' => AuditLog::SEVERITY_WARNING,
            'auditable_type' => Project::class,
            'auditable_id' => $project->id,
            'subject_label' => (string) __('messages.project_members.audit.project_permission'),
            'subject_name' => $project->name,
            'summary' => $summary,
            'metadata' => [
                'ability' => $ability,
                'role' => $this->role($project, $user),
                'user_email' => $user?->email,
            ],
        ]);
    }

    public function recordMembershipEvent(Project $project, ?User $actor, string $action, User $target, string $role, ?string $previousRole = null): void
    {
        app(AuditLogService::class)->record([
            'project_id' => $project->id,
            'user_id' => $actor?->id,
            'event_type' => AuditLog::EVENT_SYSTEM,
            'action' => $action,
            'severity' => AuditLog::SEVERITY_NOTICE,
            'auditable_type' => ProjectMembership::class,
            'auditable_id' => null,
            'subject_label' => (string) __('messages.project_members.audit.project_member'),
            'subject_name' => $target->email,
            'summary' => match ($action) {
                'member_added' => __('messages.project_members.audit.member_added', ['email' => $target->email]),
                'member_removed' => __('messages.project_members.audit.member_removed', ['email' => $target->email]),
                'member_role_changed' => __('messages.project_members.audit.member_role_changed', ['email' => $target->email]),
                default => __('messages.project_members.audit.member_changed', ['email' => $target->email]),
            },
            'metadata' => [
                'target_user_id' => $target->id,
                'target_email' => $target->email,
                'role' => $role,
                'previous_role' => $previousRole,
            ],
        ]);
    }
}
