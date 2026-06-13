<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Services\Access\ProjectAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $access = app(ProjectAccessService::class);
        $user = $request->user();

        abort_if(! $user, 403, __('messages.auth.authentication_required'));

        if ($access->isSystemAdmin($user)) {
            return $next($request);
        }

        $project = $request->route('project');
        if (! $project instanceof Project && $project) {
            $project = Project::query()->find($project);
        }

        if ($project instanceof Project) {
            $ability = $this->abilityForProjectRoute($request);

            if ($access->can($project, $user, $ability)) {
                return $next($request);
            }

            $access->recordPermissionDenied($project, $user, $ability, __('messages.project_members.audit.project_action_denied', [
                'ability' => \App\Models\ProjectMembership::translatedPermissionLabel($ability),
            ]));
            abort(403, __('messages.project_members.permission_denied'));
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        $allowedWorkspaceRoutes = [
            'dashboard',
            'projects.index',
            'reports.index',
            'release-readiness.index',
            'how-it-works',
            'help.index',
        ];

        if (in_array($routeName, $allowedWorkspaceRoutes, true) && $access->hasAnyWorkspaceAccess($user)) {
            return $next($request);
        }

        abort(403, __('messages.auth.admin_required'));
    }

    private function abilityForProjectRoute(Request $request): string
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        $method = strtoupper($request->method());

        if ($routeName === 'projects.show' || $method === 'GET') {
            return $this->getAbilityForProjectRoute($routeName);
        }

        return $this->writeAbilityForProjectRoute($routeName);
    }

    private function getAbilityForProjectRoute(string $routeName): string
    {
        if ($routeName === 'projects.edit') {
            return 'project.manage';
        }

        if ($this->matches(['projects.members.index'], $routeName)) {
            return 'project.view';
        }

        if ($this->matches(['projects.members.*'], $routeName)) {
            return 'members.manage';
        }

        if ($this->matches(['projects.settings.*', 'projects.environments.create', 'projects.environments.edit', 'projects.auth-profiles.create', 'projects.auth-profiles.edit'], $routeName)) {
            return 'settings.manage';
        }

        if ($this->matches(['projects.endpoints.create', 'projects.endpoints.edit', 'projects.endpoints.import.*', 'projects.assertion-rules.*'], $routeName)) {
            return 'endpoints.manage';
        }

        if ($this->matches(['projects.scans.create'], $routeName)) {
            return 'scans.run';
        }

        if ($this->matches(['projects.monitors.create', 'projects.monitors.edit'], $routeName)) {
            return 'monitors.manage';
        }

        if ($this->matches(['projects.test-suites.create', 'projects.test-suites.edit', 'projects.test-suites.builder', 'projects.test-cases.create', 'projects.test-cases.edit', 'projects.newman-import.*'], $routeName)) {
            return 'tests.manage';
        }

        if ($this->matches(['projects.findings.create', 'projects.findings.edit'], $routeName)) {
            return 'findings.manage';
        }

        if ($this->matches(['projects.release-gates.create'], $routeName)) {
            return 'report.generate';
        }

        if ($this->matches(['projects.reports.builder.create'], $routeName)) {
            return 'report.generate';
        }

        if ($this->matches(['projects.report-versions.markdown', 'projects.report-versions.html', 'projects.report-versions.pdf', 'projects.report-versions.json', 'projects.reports.*', 'projects.qa-evidence.notes', 'projects.qa-evidence.summary', 'projects.qa-evidence.zip', 'projects.release-gates.markdown', 'projects.release-gates.html', 'projects.release-gates.pdf', 'projects.release-decisions.markdown', 'projects.release-decisions.html', 'projects.release-decisions.pdf', 'projects.release-decisions.json', 'projects.findings.evidence.download'], $routeName)) {
            return 'exports.download';
        }

        return 'project.view';
    }

    /** @param array<int, string> $patterns */
    private function matches(array $patterns, string $routeName): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    private function writeAbilityForProjectRoute(string $routeName): string
    {
        if ($this->matches(['projects.update'], $routeName)) {
            return 'project.manage';
        }

        if ($this->matches(['projects.members.*'], $routeName)) {
            return 'members.manage';
        }

        if ($this->matches(['projects.settings.*', 'projects.environments.*', 'projects.auth-profiles.*'], $routeName)) {
            return 'settings.manage';
        }

        if ($this->matches(['projects.assertion-rules.*', 'projects.endpoints.*', 'projects.api-behavior.refresh'], $routeName)) {
            return 'endpoints.manage';
        }

        if ($this->matches(['projects.scans.store', 'projects.endpoints.probe', 'projects.scans.snapshots.store', 'projects.snapshots.compare'], $routeName)) {
            return 'scans.run';
        }

        if ($this->matches(['projects.monitors.*'], $routeName)) {
            return 'monitors.manage';
        }

        if ($this->matches(['projects.test-suites.*', 'projects.test-cases.*', 'projects.test-execution.results.store', 'projects.newman-import.*'], $routeName)) {
            return 'tests.manage';
        }

        if ($this->matches(['projects.findings.store', 'projects.findings.update', 'projects.findings.destroy'], $routeName)) {
            return 'findings.manage';
        }

        if ($this->matches(['projects.findings.lifecycle.update', 'projects.findings.comments.store'], $routeName)) {
            return 'findings.review';
        }

        if ($this->matches(['projects.findings.evidence.store', 'projects.findings.evidence.destroy'], $routeName)) {
            return 'evidence.manage';
        }

        if ($this->matches(['projects.findings.risk-acceptances.store', 'projects.risk-acceptances.update'], $routeName)) {
            return 'risk.accept';
        }

        if ($this->matches(['projects.release-gates.store', 'projects.report-versions.store', 'projects.reports.builder.*'], $routeName)) {
            return 'report.generate';
        }

        if ($this->matches(['projects.report-versions.review'], $routeName)) {
            return 'report.review';
        }

        if ($this->matches(['projects.report-versions.approve', 'projects.report-versions.archive'], $routeName)) {
            return 'report.approve';
        }

        if ($this->matches(['projects.release-gates.decision.update', 'projects.release-decisions.store'], $routeName)) {
            return 'release.finalize';
        }

        if ($this->matches(['projects.client-portal.store', 'projects.client-portal.revoke'], $routeName)) {
            return 'portal.manage';
        }

        return 'project.manage';
    }
}
