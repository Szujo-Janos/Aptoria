<?php

namespace App\Services\ClientPortal;

use App\Models\ClientPortalAccess;
use App\Models\ClientPortalAcknowledgement;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Models\ReportVersion;
use App\Models\RiskAcceptance;
use App\Models\User;
use App\Services\Reports\QaEvidencePackService;
use App\Services\ReleaseReadinessService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClientAuditPortalService
{
    public function __construct(
        private readonly QaEvidencePackService $evidencePack,
        private readonly ReleaseReadinessService $readiness
    ) {
    }

    /** @param array<string, mixed> $data */
    public function create(Project $project, array $data, ?User $user = null): ClientPortalAccess
    {
        $role = (string) ($data['role'] ?? ClientPortalAccess::ROLE_CLIENT_VIEWER);
        $permissions = $this->permissionsForRole($role);

        foreach (ClientPortalAccess::PERMISSIONS as $permission) {
            if (array_key_exists($permission, $data)) {
                $permissions[$permission] = (bool) $data[$permission];
            }
        }

        return ClientPortalAccess::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $user?->id,
            'label' => trim((string) ($data['label'] ?? 'Client audit portal')),
            'contact_name' => trim((string) ($data['contact_name'] ?? '')) ?: null,
            'contact_email' => trim((string) ($data['contact_email'] ?? '')) ?: null,
            'role' => in_array($role, ClientPortalAccess::ROLES, true) ? $role : ClientPortalAccess::ROLE_CLIENT_VIEWER,
            'status' => ClientPortalAccess::STATUS_ACTIVE,
            'portal_token' => Str::random(48),
            'permissions' => $permissions,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
    }

    public function revoke(ClientPortalAccess $access): ClientPortalAccess
    {
        $access->forceFill([
            'status' => ClientPortalAccess::STATUS_REVOKED,
            'revoked_at' => now(),
        ])->save();

        return $access;
    }

    /** @return array<string, bool> */
    public function permissionsForRole(string $role): array
    {
        $base = [
            ClientPortalAccess::PERMISSION_REPORTS => true,
            ClientPortalAccess::PERMISSION_RELEASE_DECISIONS => true,
            ClientPortalAccess::PERMISSION_ACCEPTED_RISKS => true,
            ClientPortalAccess::PERMISSION_FINDINGS => true,
            ClientPortalAccess::PERMISSION_EVIDENCE_PACKAGE => true,
            ClientPortalAccess::PERMISSION_APPROVE_REPORTS => false,
            ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE => false,
            ClientPortalAccess::PERMISSION_APPROVE_RISKS => false,
        ];

        if ($role === ClientPortalAccess::ROLE_CLIENT_APPROVER) {
            $base[ClientPortalAccess::PERMISSION_APPROVE_REPORTS] = true;
            $base[ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE] = true;
            $base[ClientPortalAccess::PERMISSION_APPROVE_RISKS] = true;
        }

        if ($role === ClientPortalAccess::ROLE_REVIEWER) {
            $base[ClientPortalAccess::PERMISSION_APPROVE_REPORTS] = true;
        }

        return $base;
    }

    /** @return array<string, mixed> */
    public function dashboard(ClientPortalAccess $access): array
    {
        $project = $access->project()->firstOrFail();
        $project->loadCount(['endpoints', 'findings', 'scanRuns', 'reportVersions', 'releaseDecisions', 'riskAcceptances']);

        $approvedReports = $this->approvedReports($project);
        $releaseDecisions = $this->releaseDecisions($project);
        $acceptedRisks = $this->acceptedRisks($project);
        $findingSummary = $this->findingSummary($project);
        $readinessSummary = $this->readiness->summarize($project);
        $currentSnapshot = $this->currentSnapshot($project, $readinessSummary, $approvedReports, $releaseDecisions, $acceptedRisks);
        $acknowledgements = $access->acknowledgements()->latest('acknowledged_at')->limit(20)->get();
        $roleCapabilities = $this->roleCapabilities($access);
        $visibleSectionCount = collect($roleCapabilities)->where('enabled', true)->where('approval', false)->count();

        return [
            'project' => $project,
            'approved_reports' => $approvedReports,
            'release_decisions' => $releaseDecisions,
            'accepted_risks' => $acceptedRisks,
            'finding_summary' => $findingSummary,
            'readiness_summary' => $readinessSummary,
            'current_snapshot' => $currentSnapshot,
            'acknowledgements' => $acknowledgements,
            'role_capabilities' => $roleCapabilities,
            'metrics' => [
                'approved_reports' => $approvedReports->count(),
                'release_decisions' => $releaseDecisions->count(),
                'accepted_risks' => $acceptedRisks->count(),
                'open_findings' => $findingSummary['open'],
                'evidence_exports' => $access->allows(ClientPortalAccess::PERMISSION_EVIDENCE_PACKAGE) ? 1 : 0,
                'visible_sections' => $visibleSectionCount,
            ],
        ];
    }

    /** @return array<int, array{permission: string, label: string, enabled: bool, approval: bool}> */
    public function roleCapabilities(ClientPortalAccess $access): array
    {
        $approvalPermissions = [
            ClientPortalAccess::PERMISSION_APPROVE_REPORTS,
            ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE,
            ClientPortalAccess::PERMISSION_APPROVE_RISKS,
        ];

        return collect(ClientPortalAccess::PERMISSIONS)
            ->map(fn (string $permission): array => [
                'permission' => $permission,
                'label' => __('messages.client_portal.permission_labels.'.$permission),
                'enabled' => $access->allows($permission),
                'approval' => in_array($permission, $approvalPermissions, true),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, array<string, bool>> */
    public function roleDefaultMatrix(): array
    {
        return collect(ClientPortalAccess::ROLES)
            ->mapWithKeys(fn (string $role): array => [$role => $this->permissionsForRole($role)])
            ->all();
    }

    /** @param array<string, mixed> $readinessSummary @param Collection<int, ReportVersion> $approvedReports @param Collection<int, ReleaseDecision> $releaseDecisions @param Collection<int, RiskAcceptance> $acceptedRisks @return array<string, mixed> */
    private function currentSnapshot(Project $project, array $readinessSummary, Collection $approvedReports, Collection $releaseDecisions, Collection $acceptedRisks): array
    {
        $latestScan = $readinessSummary['latest_scan'] ?? null;
        $latestReport = $project->reportVersions()->latest()->first();
        $latestApprovedReport = $approvedReports->first();
        $latestDecision = $releaseDecisions->first();

        $gaps = [];
        if ($approvedReports->isEmpty()) {
            $gaps[] = __('messages.client_portal.gaps.no_approved_reports');
        }
        if ($releaseDecisions->isEmpty()) {
            $gaps[] = __('messages.client_portal.gaps.no_release_decision');
        }
        if (! $latestScan) {
            $gaps[] = __('messages.client_portal.gaps.no_scan_evidence');
        }
        if (($readinessSummary['blind_spots']['summary']['release_blockers'] ?? 0) > 0) {
            $gaps[] = __('messages.client_portal.gaps.release_blocking_blind_spots', [
                'count' => (int) $readinessSummary['blind_spots']['summary']['release_blockers'],
            ]);
        }

        return [
            'status' => (string) ($readinessSummary['status'] ?? ReleaseReadinessService::STATUS_IDLE),
            'label' => (string) ($readinessSummary['label'] ?? __('messages.common.not_available')),
            'css' => (string) ($readinessSummary['css'] ?? 'default'),
            'score' => (int) ($readinessSummary['score'] ?? 0),
            'grade' => (string) ($readinessSummary['grade'] ?? '—'),
            'coverage_percent' => (int) ($readinessSummary['coverage_percent'] ?? 0),
            'blocker_count' => count($readinessSummary['blocking_issues'] ?? []),
            'warning_count' => count($readinessSummary['warnings'] ?? []),
            'blind_spot_count' => (int) ($readinessSummary['blind_spots']['summary']['total'] ?? 0),
            'release_blocking_blind_spots' => (int) ($readinessSummary['blind_spots']['summary']['release_blockers'] ?? 0),
            'latest_scan' => $latestScan,
            'latest_report' => $latestReport,
            'latest_approved_report' => $latestApprovedReport,
            'latest_decision' => $latestDecision,
            'accepted_risk_count' => $acceptedRisks->count(),
            'gaps' => $gaps,
            'is_client_handoff_ready' => empty($gaps) && (int) ($readinessSummary['score'] ?? 0) >= 70,
        ];
    }

    public function markViewed(ClientPortalAccess $access): void
    {
        $access->forceFill(['last_viewed_at' => now()])->save();
    }

    public function evidenceSummaryJson(ClientPortalAccess $access): string
    {
        abort_unless($access->allows(ClientPortalAccess::PERMISSION_EVIDENCE_PACKAGE), 403);

        $project = $access->project()->firstOrFail();
        $selection = $this->evidencePack->defaultSelection($project);

        return $this->evidencePack->summaryJson($project, $selection);
    }

    public function evidenceZipPath(ClientPortalAccess $access): string
    {
        abort_unless($access->allows(ClientPortalAccess::PERMISSION_EVIDENCE_PACKAGE), 403);

        $project = $access->project()->firstOrFail();
        $selection = $this->evidencePack->defaultSelection($project);

        return $this->evidencePack->buildZip($project, $selection);
    }

    /** @param array<string, mixed> $data */
    public function acknowledge(ClientPortalAccess $access, string $type, array $data): ClientPortalAcknowledgement
    {
        abort_unless(in_array($type, ClientPortalAcknowledgement::TYPES, true), 422);

        if ($type === ClientPortalAcknowledgement::TYPE_REPORT_APPROVAL) {
            abort_unless($access->allows(ClientPortalAccess::PERMISSION_APPROVE_REPORTS), 403);
        } elseif ($type === ClientPortalAcknowledgement::TYPE_RELEASE_ACKNOWLEDGEMENT) {
            abort_unless($access->allows(ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE), 403);
        } elseif ($type === ClientPortalAcknowledgement::TYPE_RISK_ACCEPTANCE_ACKNOWLEDGEMENT) {
            abort_unless($access->allows(ClientPortalAccess::PERMISSION_APPROVE_RISKS), 403);
        }

        return ClientPortalAcknowledgement::query()->create([
            'project_id' => $access->project_id,
            'client_portal_access_id' => $access->id,
            'report_version_id' => $data['report_version_id'] ?? null,
            'release_decision_id' => $data['release_decision_id'] ?? null,
            'risk_acceptance_id' => $data['risk_acceptance_id'] ?? null,
            'acknowledgement_type' => $type,
            'actor_name' => trim((string) ($data['actor_name'] ?? $access->contact_name ?? '')) ?: null,
            'actor_email' => trim((string) ($data['actor_email'] ?? $access->contact_email ?? '')) ?: null,
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'acknowledged_at' => now(),
        ]);
    }

    /** @return Collection<int, ReportVersion> */
    public function approvedReports(Project $project): Collection
    {
        return $project->reportVersions()
            ->with(['generatedBy', 'approvedBy'])
            ->where('status', ReportVersion::STATUS_APPROVED)
            ->latest('approved_at')
            ->latest()
            ->limit(20)
            ->get();
    }

    /** @return Collection<int, ReleaseDecision> */
    public function releaseDecisions(Project $project): Collection
    {
        return $project->releaseDecisions()
            ->with('owner')
            ->latest('decided_at')
            ->latest()
            ->limit(20)
            ->get();
    }

    /** @return Collection<int, RiskAcceptance> */
    public function acceptedRisks(Project $project): Collection
    {
        return $project->riskAcceptances()
            ->with(['finding', 'acceptedBy'])
            ->latest('accepted_at')
            ->latest()
            ->limit(50)
            ->get();
    }

    /** @return array<string, mixed> */
    private function findingSummary(Project $project): array
    {
        $query = $project->findings();
        $openStatuses = [Finding::STATUS_OPEN, Finding::STATUS_CONFIRMED, Finding::STATUS_IN_PROGRESS, Finding::STATUS_READY_FOR_RETEST, Finding::STATUS_RETEST_FAILED];

        $severityCounts = [];
        foreach (Finding::SEVERITIES as $severity) {
            $severityCounts[$severity] = (clone $query)->where('severity', $severity)->count();
        }

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->whereIn('status', $openStatuses)->count(),
            'verified' => (clone $query)->where('status', Finding::STATUS_VERIFIED)->count(),
            'accepted_risk' => (clone $query)->where('status', Finding::STATUS_ACCEPTED_RISK)->count(),
            'severity_counts' => $severityCounts,
            'recent' => (clone $query)->with('endpoint')->latest()->limit(10)->get(),
        ];
    }
}
