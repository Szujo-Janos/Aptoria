<?php

namespace App\Http\Controllers;

use App\Models\ClientPortalAccess;
use App\Models\ClientPortalAcknowledgement;
use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Models\ReportVersion;
use App\Models\RiskAcceptance;
use App\Services\ClientPortal\ClientAuditPortalService;
use App\Services\Reports\ReportPresentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ClientAuditPortalController extends Controller
{
    public function index(Project $project, ClientAuditPortalService $portal): View
    {
        $accesses = $project->clientPortalAccesses()
            ->with(['createdBy', 'acknowledgements'])
            ->latest()
            ->paginate(25);

        $defaults = $portal->permissionsForRole(ClientPortalAccess::ROLE_CLIENT_VIEWER);
        $roleDefaults = $portal->roleDefaultMatrix();

        return view('client_portal.index', compact('project', 'accesses', 'defaults', 'roleDefaults'));
    }

    public function store(Project $project, Request $request, ClientAuditPortalService $portal): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'role' => ['required', 'string', Rule::in(ClientPortalAccess::ROLES)],
            'expires_at' => ['nullable', 'date'],
            ClientPortalAccess::PERMISSION_REPORTS => ['nullable', 'boolean'],
            ClientPortalAccess::PERMISSION_RELEASE_DECISIONS => ['nullable', 'boolean'],
            ClientPortalAccess::PERMISSION_ACCEPTED_RISKS => ['nullable', 'boolean'],
            ClientPortalAccess::PERMISSION_FINDINGS => ['nullable', 'boolean'],
            ClientPortalAccess::PERMISSION_EVIDENCE_PACKAGE => ['nullable', 'boolean'],
            ClientPortalAccess::PERMISSION_APPROVE_REPORTS => ['nullable', 'boolean'],
            ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE => ['nullable', 'boolean'],
            ClientPortalAccess::PERMISSION_APPROVE_RISKS => ['nullable', 'boolean'],
        ]);

        foreach (ClientPortalAccess::PERMISSIONS as $permission) {
            $validated[$permission] = $request->boolean($permission);
        }

        $access = $portal->create($project, $validated, Auth::user());

        return redirect()
            ->route('projects.client-portal.index', $project)
            ->with('success', __('messages.client_portal.created'))
            ->with('client_portal_url', $access->portal_url);
    }

    public function revoke(Project $project, ClientPortalAccess $clientPortalAccess, ClientAuditPortalService $portal): RedirectResponse
    {
        $this->ensureAccessBelongsToProject($project, $clientPortalAccess);
        $portal->revoke($clientPortalAccess);

        return redirect()
            ->route('projects.client-portal.index', $project)
            ->with('success', __('messages.client_portal.revoked'));
    }

    public function show(ClientPortalAccess $clientPortalAccess, ClientAuditPortalService $portal): View
    {
        $this->ensurePublicAccessIsAvailable($clientPortalAccess);
        $portal->markViewed($clientPortalAccess);

        $dashboard = $portal->dashboard($clientPortalAccess);

        return view('client_portal.public', [
            'access' => $clientPortalAccess->fresh(['project']),
            'dashboard' => $dashboard,
        ]);
    }

    public function reportMarkdown(ClientPortalAccess $clientPortalAccess, ReportVersion $reportVersion): Response
    {
        $this->ensureCanViewReport($clientPortalAccess, $reportVersion);

        return response((string) $reportVersion->markdown_content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="client-report-version-'.$reportVersion->id.'.md"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function reportHtml(ClientPortalAccess $clientPortalAccess, ReportVersion $reportVersion, ReportPresentationService $presentation): Response
    {
        $this->ensureCanViewReport($clientPortalAccess, $reportVersion);
        $project = $clientPortalAccess->project()->firstOrFail();

        return response($presentation->htmlFromMarkdown((string) $reportVersion->markdown_content, $reportVersion->title, $project), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="client-report-version-'.$reportVersion->id.'.html"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function reportJson(ClientPortalAccess $clientPortalAccess, ReportVersion $reportVersion): JsonResponse
    {
        $this->ensureCanViewReport($clientPortalAccess, $reportVersion);

        return response()->json([
            'id' => $reportVersion->id,
            'title' => $reportVersion->title,
            'report_type' => $reportVersion->report_type,
            'status' => $reportVersion->status,
            'content_checksum' => $reportVersion->content_checksum,
            'generated_at' => $reportVersion->generated_at?->toISOString(),
            'approved_at' => $reportVersion->approved_at?->toISOString(),
            'sources' => [
                'scan_ids' => $reportVersion->source_scan_ids ?: [],
                'snapshot_ids' => $reportVersion->source_snapshot_ids ?: [],
                'compare_ids' => $reportVersion->source_compare_ids ?: [],
                'release_gate_ids' => $reportVersion->source_release_gate_ids ?: [],
                'release_decision_ids' => $reportVersion->source_release_decision_ids ?: [],
                'evidence_ids' => $reportVersion->source_evidence_ids ?: [],
            ],
        ], 200, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function releaseDecisionJson(ClientPortalAccess $clientPortalAccess, ReleaseDecision $releaseDecision): JsonResponse
    {
        $this->ensurePublicAccessIsAvailable($clientPortalAccess);
        abort_unless($clientPortalAccess->allows(ClientPortalAccess::PERMISSION_RELEASE_DECISIONS), 403);
        abort_unless($releaseDecision->project_id === $clientPortalAccess->project_id, 404);

        return response()->json([
            'id' => $releaseDecision->id,
            'release_name' => $releaseDecision->release_name,
            'target_environment' => $releaseDecision->target_environment,
            'decision_status' => $releaseDecision->decision_status,
            'release_score' => $releaseDecision->release_score,
            'blocker_count' => $releaseDecision->blocker_count,
            'warning_count' => $releaseDecision->warning_count,
            'package_checksum' => $releaseDecision->package_checksum,
            'decided_at' => $releaseDecision->decided_at?->toISOString(),
        ], 200, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function evidenceSummary(ClientPortalAccess $clientPortalAccess, ClientAuditPortalService $portal): Response
    {
        $this->ensureCanDownloadEvidencePackage($clientPortalAccess);

        return response($portal->evidenceSummaryJson($clientPortalAccess), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="client-evidence-summary.json"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function evidenceZip(ClientPortalAccess $clientPortalAccess, ClientAuditPortalService $portal)
    {
        $this->ensureCanDownloadEvidencePackage($clientPortalAccess);

        try {
            $zipPath = $portal->evidenceZipPath($clientPortalAccess);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('client-portal.show', $clientPortalAccess)
                ->with('error', $exception->getMessage());
        }

        return response()
            ->download($zipPath, 'client-evidence-package.zip', [
                'Content-Type' => 'application/zip',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->deleteFileAfterSend(true);
    }

    public function acknowledge(ClientPortalAccess $clientPortalAccess, Request $request, ClientAuditPortalService $portal): RedirectResponse
    {
        $this->ensurePublicAccessIsAvailable($clientPortalAccess);

        $validated = $request->validate([
            'acknowledgement_type' => ['required', 'string', Rule::in(ClientPortalAcknowledgement::TYPES)],
            'report_version_id' => ['nullable', 'integer'],
            'release_decision_id' => ['nullable', 'integer'],
            'risk_acceptance_id' => ['nullable', 'integer'],
            'actor_name' => ['nullable', 'string', 'max:160'],
            'actor_email' => ['nullable', 'email', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->ensureCanAcknowledgeType($clientPortalAccess, (string) $validated['acknowledgement_type']);

        if (! empty($validated['report_version_id'])) {
            $report = ReportVersion::query()->findOrFail((int) $validated['report_version_id']);
            $this->ensureCanViewReport($clientPortalAccess, $report);
        }
        if (! empty($validated['release_decision_id'])) {
            $decision = ReleaseDecision::query()->findOrFail((int) $validated['release_decision_id']);
            abort_unless($decision->project_id === $clientPortalAccess->project_id, 404);
            abort_unless($clientPortalAccess->allows(ClientPortalAccess::PERMISSION_RELEASE_DECISIONS), 403);
        }
        if (! empty($validated['risk_acceptance_id'])) {
            $risk = RiskAcceptance::query()->findOrFail((int) $validated['risk_acceptance_id']);
            abort_unless($risk->project_id === $clientPortalAccess->project_id, 404);
            abort_unless($clientPortalAccess->allows(ClientPortalAccess::PERMISSION_ACCEPTED_RISKS), 403);
        }

        $portal->acknowledge($clientPortalAccess, (string) $validated['acknowledgement_type'], $validated);

        return redirect()
            ->route('client-portal.show', $clientPortalAccess)
            ->with('success', __('messages.client_portal.acknowledged'));
    }

    private function ensureCanDownloadEvidencePackage(ClientPortalAccess $access): void
    {
        $this->ensurePublicAccessIsAvailable($access);
        abort_unless($access->allows(ClientPortalAccess::PERMISSION_EVIDENCE_PACKAGE), 403);
    }

    private function ensureCanAcknowledgeType(ClientPortalAccess $access, string $type): void
    {
        $requiredPermission = match ($type) {
            ClientPortalAcknowledgement::TYPE_REPORT_APPROVAL => ClientPortalAccess::PERMISSION_APPROVE_REPORTS,
            ClientPortalAcknowledgement::TYPE_RELEASE_ACKNOWLEDGEMENT => ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE,
            ClientPortalAcknowledgement::TYPE_RISK_ACCEPTANCE_ACKNOWLEDGEMENT => ClientPortalAccess::PERMISSION_APPROVE_RISKS,
            default => null,
        };

        abort_unless($requiredPermission !== null && $access->allows($requiredPermission), 403);
    }

    private function ensureCanViewReport(ClientPortalAccess $access, ReportVersion $reportVersion): void
    {
        $this->ensurePublicAccessIsAvailable($access);
        abort_unless($access->allows(ClientPortalAccess::PERMISSION_REPORTS), 403);
        abort_unless($reportVersion->project_id === $access->project_id, 404);
        abort_unless($reportVersion->status === ReportVersion::STATUS_APPROVED, 404);
    }

    private function ensurePublicAccessIsAvailable(ClientPortalAccess $access): void
    {
        abort_unless($access->isAvailable(), 404);
    }

    private function ensureAccessBelongsToProject(Project $project, ClientPortalAccess $access): void
    {
        abort_unless($access->project_id === $project->id, 404);
    }
}
