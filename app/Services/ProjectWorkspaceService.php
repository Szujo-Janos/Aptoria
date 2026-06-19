<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Schema;

class ProjectWorkspaceService
{
    public function summary(?Project $project): array
    {
        if (! $project) {
            return [
                'progress' => 0,
                'metrics' => $this->emptyMetrics(),
                'checklist' => $this->emptyChecklist(),
                'modules' => $this->modules(),
                'latest_audit' => collect(),
                'defaults' => $this->emptyDefaults(),
            ];
        }

        $auditCount = Schema::hasTable('audit_logs') ? $project->auditLogs()->count() : 0;
        $latestAudit = Schema::hasTable('audit_logs') ? $project->auditLogs()->latest()->limit(6)->get() : collect();
        $environmentCount = Schema::hasTable('environments') ? $project->environments()->count() : 0;
        $authProfileCount = Schema::hasTable('auth_profiles') ? $project->authProfiles()->count() : 0;
        $endpointCount = Schema::hasTable('endpoints') ? $project->endpoints()->count() : 0;
        $safeEndpointCount = Schema::hasTable('endpoints') ? $project->endpoints()->whereIn('method', ['GET', 'HEAD'])->where('is_active', true)->where('excluded_from_scan', false)->count() : 0;
        $scanRunCount = Schema::hasTable('scan_runs') ? $project->scanRuns()->count() : 0;
        $findingCount = Schema::hasTable('findings') ? $project->findings()->count() : 0;
        $openFindingCount = Schema::hasTable('findings') ? $project->findings()->whereNotIn('status', ['verified'])->count() : 0;
        $evidenceCount = Schema::hasTable('finding_evidence') ? $project->evidence()->count() : 0;
        $releaseReadinessRunCount = Schema::hasTable('release_readiness_runs') ? $project->releaseReadinessRuns()->count() : 0;
        $releaseGateCount = Schema::hasTable('release_gates') ? $project->releaseGates()->count() : 0;
        $reportVersionCount = Schema::hasTable('report_versions') ? $project->reportVersions()->count() : 0;
        $clientPortalAccessCount = Schema::hasTable('client_portal_accesses') ? $project->clientPortalAccesses()->count() : 0;
        $projectMemberCount = Schema::hasTable('project_memberships') ? $project->memberships()->where('status', 'active')->count() : 1;
        $calendarEventCount = Schema::hasTable('calendar_events') ? $project->calendarEvents()->count() : 0;
        $openCalendarEventCount = Schema::hasTable('calendar_events') ? $project->calendarEvents()->whereNotIn('status', ['completed', 'cancelled'])->count() : 0;
        $latestReleaseReadiness = Schema::hasTable('release_readiness_runs') ? $project->releaseReadinessRuns()->latest()->first() : null;
        $lastScanRun = Schema::hasTable('scan_runs') ? $project->scanRuns()->latest()->first() : null;
        $defaultEnvironment = Schema::hasTable('environments') ? $project->defaultEnvironment() : null;
        $defaultAuthProfile = Schema::hasTable('auth_profiles') ? $project->defaultAuthProfile() : null;
        $safeMethodsOnly = ! Schema::hasTable('project_settings') || ProjectSetting::get($project, 'scan.safe_methods_only', '1') !== '0';
        $confirmationRequired = ! Schema::hasTable('project_settings') || ProjectSetting::get($project, 'scan.require_confirmation', '1') !== '0';
        $privateNetworksAllowed = Schema::hasTable('project_settings') && ProjectSetting::get($project, 'scan.allow_private_networks', '0') === '1';

        $checklist = [
            [
                'key' => 'project_profile',
                'label' => __('messages.workspace.check_project_profile'),
                'ready' => filled($project->description),
                'hint' => __('messages.workspace.check_project_profile_hint'),
            ],
            [
                'key' => 'default_environment',
                'label' => __('messages.workspace.check_default_environment'),
                'ready' => (bool) $defaultEnvironment,
                'hint' => __('messages.workspace.check_default_environment_hint'),
            ],
            [
                'key' => 'base_url',
                'label' => __('messages.workspace.check_base_url'),
                'ready' => filled($defaultEnvironment?->base_url ?? $project->base_url),
                'hint' => __('messages.workspace.check_base_url_hint'),
            ],
            [
                'key' => 'endpoint_inventory',
                'label' => __('messages.workspace.check_endpoint_inventory'),
                'ready' => $endpointCount > 0,
                'hint' => __('messages.workspace.check_endpoint_inventory_hint'),
            ],
            [
                'key' => 'safe_endpoint_inventory',
                'label' => __('messages.workspace.check_safe_endpoint_inventory'),
                'ready' => $safeEndpointCount > 0,
                'hint' => __('messages.workspace.check_safe_endpoint_inventory_hint'),
            ],
            [
                'key' => 'scan_safety',
                'label' => __('messages.workspace.check_scan_safety'),
                'ready' => $safeMethodsOnly && $confirmationRequired && ! $privateNetworksAllowed,
                'hint' => __('messages.workspace.check_scan_safety_hint'),
            ],
            [
                'key' => 'evidence_repository',
                'label' => __('messages.workspace.check_evidence_repository'),
                'ready' => $evidenceCount > 0,
                'hint' => __('messages.workspace.check_evidence_repository_hint'),
            ],
            [
                'key' => 'finding_triage',
                'label' => __('messages.workspace.check_finding_triage'),
                'ready' => $findingCount > 0 || $scanRunCount > 0,
                'hint' => __('messages.workspace.check_finding_triage_hint'),
            ],
            [
                'key' => 'audit_trace',
                'label' => __('messages.workspace.check_audit_trace'),
                'ready' => $auditCount > 0,
                'hint' => __('messages.workspace.check_audit_trace_hint'),
            ],
            [
                'key' => 'qa_calendar',
                'label' => __('messages.workspace.check_qa_calendar'),
                'ready' => $calendarEventCount > 0,
                'hint' => __('messages.workspace.check_qa_calendar_hint'),
            ],
            [
                'key' => 'client_portal',
                'label' => __('messages.workspace.check_client_portal'),
                'ready' => $clientPortalAccessCount > 0,
                'hint' => __('messages.workspace.check_client_portal_hint'),
            ],
        ];

        $ready = collect($checklist)->where('ready', true)->count();
        $progress = (int) round(($ready / max(count($checklist), 1)) * 100);

        return [
            'progress' => $progress,
            'metrics' => [
                'endpoints' => $endpointCount,
                'environments' => $environmentCount,
                'auth_profiles' => $authProfileCount,
                'evidence' => $evidenceCount,
                'findings' => $openFindingCount,
                'audit_events' => $auditCount,
                'project_members' => $projectMemberCount,
                'scan_runs' => $scanRunCount,
                'release_readiness_runs' => $releaseReadinessRunCount,
                'release_gates' => $releaseGateCount,
                'report_versions' => $reportVersionCount,
                'client_portal_accesses' => $clientPortalAccessCount,
                'calendar_events' => $calendarEventCount,
                'open_calendar_events' => $openCalendarEventCount,
            ],
            'checklist' => $checklist,
            'modules' => $this->modules($project),
            'latest_audit' => $latestAudit,
            'defaults' => [
                'environment' => $defaultEnvironment,
                'auth_profile' => $defaultAuthProfile,
                'safe_methods_only' => $safeMethodsOnly,
                'confirmation_required' => $confirmationRequired,
                'private_networks_allowed' => $privateNetworksAllowed,
                'last_scan_run' => $lastScanRun,
                'latest_release_readiness' => $latestReleaseReadiness,
            ],
        ];
    }

    public function modules(?Project $project = null): array
    {
        return [
            [
                'slug' => 'environments',
                'icon' => 'globe',
                'tone' => 'success',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.environments'),
                'description' => __('messages.environments.copy'),
                'url' => $project ? route('projects.environments.index', $project) : null,
            ],
            [
                'slug' => 'auth-profiles',
                'icon' => 'key-round',
                'tone' => 'warning',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.auth_profiles'),
                'description' => __('messages.auth_profiles.copy'),
                'url' => $project ? route('projects.auth-profiles.index', $project) : null,
            ],
            [
                'slug' => 'endpoint-inventory',
                'icon' => 'plug-connected',
                'tone' => 'primary',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.endpoint_inventory'),
                'description' => __('messages.modules.endpoint-inventory.description'),
                'url' => $project ? route('projects.endpoints.index', $project) : null,
            ],
            [
                'slug' => 'project-settings',
                'icon' => 'folder-settings',
                'tone' => 'secondary',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.project_settings'),
                'description' => __('messages.project_settings.scan_defaults_copy'),
                'url' => $project ? route('projects.settings.edit', $project) : null,
            ],
            [
                'slug' => 'project-members',
                'icon' => 'shield-check',
                'tone' => 'primary',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.project_members'),
                'description' => __('messages.project_members.foundation_note'),
                'url' => $project ? route('projects.members.index', $project) : null,
            ],
            [
                'slug' => 'qa-cockpit',
                'icon' => 'scan-search',
                'tone' => 'primary',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.qa_cockpit'),
                'description' => __('messages.qa_cockpit.page_copy'),
                'url' => $project ? route('projects.qa-cockpit.show', $project) : null,
            ],
            [
                'slug' => 'safe-scan',
                'icon' => 'radar',
                'tone' => 'warning',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.safe_scan'),
                'description' => __('messages.modules.safe-scan.description'),
                'url' => $project ? route('projects.safe-scans.index', $project) : null,
            ],
            [
                'slug' => 'assertions',
                'icon' => 'checklist',
                'tone' => 'info',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.assertions'),
                'description' => __('messages.modules.assertions.description'),
                'url' => $project ? route('projects.assertions.index', $project) : null,
            ],
            [
                'slug' => 'native-tests',
                'icon' => 'flask-conical',
                'tone' => 'success',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.native_tests'),
                'description' => __('messages.native_tests.pipeline_copy'),
                'url' => $project ? route('projects.tests.index', $project) : null,
            ],
            [
                'slug' => 'evidence',
                'icon' => 'folder-check',
                'tone' => 'success',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.evidence'),
                'description' => __('messages.modules.evidence.description'),
                'url' => $project ? route('projects.evidence.index', $project) : null,
            ],
            [
                'slug' => 'findings',
                'icon' => 'bug',
                'tone' => 'danger',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.findings'),
                'description' => __('messages.modules.findings.description'),
                'url' => $project ? route('projects.findings.index', $project) : null,
            ],
            [
                'slug' => 'release-readiness',
                'icon' => 'shield-chevron',
                'tone' => 'secondary',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.release_readiness'),
                'description' => __('messages.modules.release-readiness.description'),
                'url' => $project ? route('projects.release-readiness.index', $project) : null,
            ],
            [
                'slug' => 'release-gates',
                'icon' => 'workflow',
                'tone' => 'primary',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.release_gates'),
                'description' => __('messages.release_gates.foundation_copy'),
                'url' => $project ? route('projects.release-gates.index', $project) : null,
            ],
            [
                'slug' => 'reports',
                'icon' => 'report-analytics',
                'tone' => 'info',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.reports'),
                'description' => __('messages.modules.reports.description'),
                'url' => $project ? route('projects.reports.index', $project) : null,
            ],
            [
                'slug' => 'calendar',
                'icon' => 'calendar-stats',
                'tone' => 'success',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.calendar'),
                'description' => __('messages.modules.calendar.description'),
                'url' => $project ? route('projects.calendar.index', $project) : null,
            ],
            [
                'slug' => 'client-portal',
                'icon' => 'door-open',
                'tone' => 'primary',
                'phase' => __('messages.workspace.module_phase_active'),
                'title' => __('messages.nav.client_portal'),
                'description' => __('messages.modules.client-portal.description'),
                'url' => $project ? route('projects.client-portal.index', $project) : null,
            ],
        ];
    }

    private function emptyMetrics(): array
    {
        return [
            'endpoints' => 0,
            'environments' => 0,
            'auth_profiles' => 0,
            'evidence' => 0,
            'findings' => 0,
            'audit_events' => 0,
            'project_members' => 0,
            'scan_runs' => 0,
            'release_readiness_runs' => 0,
            'report_versions' => 0,
        ];
    }

    private function emptyDefaults(): array
    {
        return [
            'environment' => null,
            'auth_profile' => null,
            'safe_methods_only' => true,
            'confirmation_required' => true,
            'private_networks_allowed' => false,
            'last_scan_run' => null,
            'latest_release_readiness' => null,
        ];
    }

    private function emptyChecklist(): array
    {
        return [
            [
                'key' => 'first_project',
                'label' => __('messages.workspace.check_first_project'),
                'ready' => false,
                'hint' => __('messages.workspace.check_first_project_hint'),
            ],
            [
                'key' => 'default_environment',
                'label' => __('messages.workspace.check_default_environment'),
                'ready' => false,
                'hint' => __('messages.workspace.check_default_environment_hint'),
            ],
            [
                'key' => 'endpoint_inventory',
                'label' => __('messages.workspace.check_endpoint_inventory'),
                'ready' => false,
                'hint' => __('messages.workspace.check_endpoint_inventory_hint'),
            ],
            [
                'key' => 'scan_safety',
                'label' => __('messages.workspace.check_scan_safety'),
                'ready' => false,
                'hint' => __('messages.workspace.check_scan_safety_hint'),
            ],
            [
                'key' => 'evidence_repository',
                'label' => __('messages.workspace.check_evidence_repository'),
                'ready' => false,
                'hint' => __('messages.workspace.check_evidence_repository_hint'),
            ],
            [
                'key' => 'finding_triage',
                'label' => __('messages.workspace.check_finding_triage'),
                'ready' => false,
                'hint' => __('messages.workspace.check_finding_triage_hint'),
            ],
            [
                'key' => 'qa_calendar',
                'label' => __('messages.workspace.check_qa_calendar'),
                'ready' => false,
                'hint' => __('messages.workspace.check_qa_calendar_hint'),
            ],
            [
                'key' => 'audit_trace',
                'label' => __('messages.workspace.check_audit_trace'),
                'ready' => false,
                'hint' => __('messages.workspace.check_audit_trace_hint'),
            ],
        ];
    }
}
