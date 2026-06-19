<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectAccessService
{
    /** @var array<string, list<string>> */
    private array $rolePermissions = [
        ProjectMembership::ROLE_PROJECT_ADMIN => ['*'],
        ProjectMembership::ROLE_QA_ENGINEER => [
            'project.view',
            'environments.view', 'environments.manage',
            'auth_profiles.view', 'auth_profiles.manage',
            'endpoints.view', 'endpoints.manage',
            'scans.view', 'scans.run',
            'assertions.view', 'assertions.manage',
            'snapshots.view', 'snapshots.manage',
            'imports.view', 'imports.manage',
            'qa.view',
            'tests.view', 'tests.manage',
            'findings.view', 'findings.manage', 'findings.review',
            'evidence.view', 'evidence.manage', 'evidence.review',
            'release.view',
            'reports.view', 'reports.generate',
            'calendar.view', 'calendar.manage',
            'audit.view',
        ],
        ProjectMembership::ROLE_REVIEWER => [
            'project.view',
            'environments.view', 'auth_profiles.view', 'endpoints.view', 'scans.view',
            'assertions.view', 'snapshots.view', 'imports.view',
            'qa.view',
            'tests.view',
            'findings.view', 'findings.review',
            'evidence.view', 'evidence.review',
            'release.view',
            'reports.view',
            'calendar.view',
            'audit.view',
        ],
        ProjectMembership::ROLE_RELEASE_APPROVER => [
            'project.view',
            'endpoints.view', 'scans.view', 'assertions.view', 'snapshots.view', 'qa.view', 'tests.view', 'findings.view', 'evidence.view',
            'evidence.review',
            'release.view', 'release.manage', 'release.approve',
            'reports.view', 'reports.generate', 'reports.approve',
            'client_portal.view', 'client_portal.manage',
            'calendar.view',
            'audit.view',
        ],
        ProjectMembership::ROLE_READ_ONLY_VIEWER => [
            'project.view',
            'environments.view', 'auth_profiles.view', 'endpoints.view', 'scans.view',
            'assertions.view', 'snapshots.view', 'imports.view',
            'qa.view',
            'tests.view',
            'findings.view', 'evidence.view',
            'release.view', 'reports.view', 'client_portal.view', 'calendar.view', 'audit.view',
        ],
    ];

    public function hasMembershipTable(): bool
    {
        return Schema::hasTable('project_memberships');
    }

    public function visibleProjectsQuery(User $user): Builder
    {
        $query = Project::query();

        if ($user->isAdmin()) {
            return $query;
        }

        if (! $this->hasMembershipTable()) {
            return $query->where('user_id', $user->id);
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('user_id', $user->id)
                ->orWhereHas('memberships', function (Builder $membershipQuery) use ($user): void {
                    $membershipQuery->where('user_id', $user->id)
                        ->where('status', ProjectMembership::STATUS_ACTIVE);
                });
        });
    }

    public function can(User $user, Project $project, string $permission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $project->user_id === (int) $user->id) {
            return true;
        }

        $role = $this->roleFor($user, $project);

        if ($role === null) {
            return false;
        }

        $permissions = $this->rolePermissions[$role] ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function roleFor(User $user, Project $project): ?string
    {
        if ($user->isAdmin()) {
            return 'system_admin';
        }

        if ((int) $project->user_id === (int) $user->id) {
            return ProjectMembership::ROLE_PROJECT_ADMIN;
        }

        if (! $this->hasMembershipTable()) {
            return null;
        }

        $membership = $project->memberships()
            ->where('user_id', $user->id)
            ->where('status', ProjectMembership::STATUS_ACTIVE)
            ->first();

        return $membership?->role;
    }

    public function roleLabel(?string $role): string
    {
        if ($role === 'system_admin') {
            return __('messages.project_members.roles.system_admin');
        }

        return __('messages.project_members.roles.'.($role ?: ProjectMembership::ROLE_READ_ONLY_VIEWER));
    }

    public function permissionsFor(User $user, Project $project): array
    {
        if ($user->isAdmin() || (int) $project->user_id === (int) $user->id) {
            return ['*'];
        }

        $role = $this->roleFor($user, $project);

        return $role ? ($this->rolePermissions[$role] ?? []) : [];
    }

    public function ensureOwnerMembership(Project $project): void
    {
        if (! $this->hasMembershipTable() || ! $project->user_id) {
            return;
        }

        DB::table('project_memberships')->updateOrInsert(
            ['project_id' => $project->id, 'user_id' => $project->user_id],
            [
                'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
                'status' => ProjectMembership::STATUS_ACTIVE,
                'invited_by_user_id' => $project->user_id,
                'added_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function permissionForRoute(?string $routeName, string $method): string
    {
        $routeName = $routeName ?? '';
        $isRead = in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true);

        if (str_starts_with($routeName, 'projects.members.')) {
            return $isRead ? 'members.view' : 'members.manage';
        }

        if (str_starts_with($routeName, 'projects.settings.')) {
            return $isRead ? 'settings.view' : 'settings.manage';
        }

        if (str_starts_with($routeName, 'projects.environments.')) {
            return $isRead ? 'environments.view' : 'environments.manage';
        }

        if (str_starts_with($routeName, 'projects.auth-profiles.')) {
            return $isRead ? 'auth_profiles.view' : 'auth_profiles.manage';
        }

        if (str_starts_with($routeName, 'projects.endpoints.') || str_starts_with($routeName, 'projects.endpoint-test-')) {
            return $isRead ? 'endpoints.view' : 'endpoints.manage';
        }

        if (str_starts_with($routeName, 'projects.qa-cockpit.')) {
            return 'qa.view';
        }

        if (str_starts_with($routeName, 'projects.safe-scans.')) {
            return $isRead ? 'scans.view' : 'scans.run';
        }

        if (str_starts_with($routeName, 'projects.assertions.')) {
            return $isRead ? 'assertions.view' : 'assertions.manage';
        }

        if (str_starts_with($routeName, 'projects.snapshots.') || str_starts_with($routeName, 'projects.snapshot-compares.')) {
            return $isRead ? 'snapshots.view' : 'snapshots.manage';
        }

        if (str_starts_with($routeName, 'projects.import-center.')) {
            if (str_ends_with($routeName, '.create')) {
                return 'imports.manage';
            }
            return $isRead ? 'imports.view' : 'imports.manage';
        }


        if (str_starts_with($routeName, 'projects.tests.')) {
            return $isRead ? 'tests.view' : 'tests.manage';
        }

        if (str_starts_with($routeName, 'projects.findings.dedup.')) {
            return $isRead ? 'findings.view' : 'findings.manage';
        }

        if (str_starts_with($routeName, 'projects.findings.')) {
            if (str_contains($routeName, 'risk-acceptance')) {
                return 'risk.accept';
            }
            return $isRead ? 'findings.view' : 'findings.manage';
        }

        if (str_starts_with($routeName, 'projects.evidence.')) {
            if (str_ends_with($routeName, '.verify')) {
                return 'evidence.review';
            }
            if (str_ends_with($routeName, '.create')) {
                return 'evidence.manage';
            }
            return $isRead ? 'evidence.view' : 'evidence.manage';
        }

        if (str_starts_with($routeName, 'projects.evidence-packs.')) {
            return $isRead ? 'evidence.view' : 'evidence.manage';
        }

        if (str_starts_with($routeName, 'projects.release-gates.')) {
            if (str_ends_with($routeName, '.finalize')) {
                return 'release.approve';
            }
            return $isRead ? 'release.view' : 'release.manage';
        }

        if (str_starts_with($routeName, 'projects.release-readiness.') || str_starts_with($routeName, 'projects.release-decisions.')) {
            return $isRead ? 'release.view' : 'release.manage';
        }

        if (str_starts_with($routeName, 'projects.reports.')) {
            if (str_ends_with($routeName, '.status')) {
                return 'reports.approve';
            }
            if (str_ends_with($routeName, '.delivery-link')) {
                return 'client_portal.manage';
            }
            return $isRead ? 'reports.view' : 'reports.generate';
        }

        if (str_starts_with($routeName, 'projects.client-portal.')) {
            return $isRead ? 'client_portal.view' : 'client_portal.manage';
        }

        if (str_starts_with($routeName, 'projects.calendar.')) {
            return $isRead ? 'calendar.view' : 'calendar.manage';
        }

        if (in_array($routeName, ['projects.edit', 'projects.update', 'projects.destroy'], true)) {
            return 'project.manage';
        }

        return 'project.view';
    }

    public function relatedModelBelongsToProject(object $model, Project $project): bool
    {
        if (property_exists($model, 'project_id') || isset($model->project_id)) {
            return (int) $model->project_id === (int) $project->id;
        }

        if (method_exists($model, 'project')) {
            $related = $model->project;

            return $related ? (int) $related->id === (int) $project->id : true;
        }

        return true;
    }
}
