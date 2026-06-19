<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Services\AuditLogger;
use App\Services\FindingRetestWorkflowService;
use App\Services\RiskAcceptanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FindingController extends Controller
{
    public function index(Project $project): View
    {
        $findings = $project->findings()
            ->with(['endpoint', 'scanResult', 'evidence', 'retestEvidence', 'riskAcceptances.acceptedBy'])
            ->withCount('evidence')
            ->latest()
            ->get();

        return view('findings.index', [
            'project' => $project,
            'findings' => $findings,
            'endpoints' => $project->endpoints()->orderBy('method')->orderBy('path')->get(),
            'scanResults' => $project->scanResults()->with('endpoint')->latest()->limit(25)->get(),
            'metrics' => [
                'open' => $findings->whereNotIn('status', ['verified'])->count(),
                'critical' => $findings->where('severity', 'critical')->count(),
                'high' => $findings->where('severity', 'high')->count(),
                'needs_evidence' => $findings->filter(fn (Finding $finding) => $finding->evidence_required && (int) $finding->evidence_count === 0)->count(),
                'retest_ready' => $findings->where('retest_status', 'ready_for_retest')->count(),
                'retest_failed' => $findings->where('retest_status', 'failed')->count(),
                'accepted_risk' => $findings->filter(fn (Finding $finding) => $finding->active_risk_acceptance !== null)->count(),
                'accepted_risk_expiring_soon' => $findings->filter(fn (Finding $finding) => $finding->active_risk_acceptance?->is_expiring_soon)->count(),
                'accepted_risk_expired' => $findings->filter(fn (Finding $finding) => $finding->latest_risk_acceptance?->display_status === 'expired')->count(),
            ],
        ]);
    }

    public function store(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $this->validated($request, $project);
        $finding = $project->findings()->create($data);

        $auditLogger->record('created', __('messages.audit_messages.finding_created'), $project, [
            'finding_id' => $finding->id,
            'title' => $finding->title,
            'severity' => $finding->severity,
            'source' => $finding->source,
        ], 'finding', $finding->severity === 'critical' ? 'critical' : 'info');

        if ($request->boolean('create_scan_evidence') && $finding->scanResult) {
            $this->createEvidenceFromScanResult($finding, $request);
        }

        return redirect()->route('projects.findings.show', [$project, $finding])->with('status', __('messages.findings.created'));
    }

    public function show(Project $project, Finding $finding): View
    {
        $this->ensureBelongsToProject($project, $finding);

        $finding->load(['endpoint', 'scanResult', 'scanRun', 'evidence.endpoint', 'evidence.scanResult', 'evidence.capturedBy', 'retestedBy', 'retestEvidence', 'riskAcceptances.acceptedBy', 'riskAcceptances.revokedBy']);

        return view('findings.show', [
            'project' => $project,
            'finding' => $finding,
            'evidenceItems' => $finding->evidence()->with(['endpoint', 'scanResult', 'capturedBy'])->latest()->get(),
            'endpoints' => $project->endpoints()->orderBy('method')->orderBy('path')->get(),
            'riskAcceptances' => $finding->riskAcceptances()->with(['acceptedBy', 'revokedBy'])->latest('accepted_at')->get(),
        ]);
    }

    public function update(Request $request, Project $project, Finding $finding, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $finding);
        $before = $finding->only(['title', 'severity', 'status', 'priority', 'owner_name', 'due_date', 'evidence_required', 'retest_required', 'retest_status']);
        $finding->update($this->validated($request, $project, $finding));

        $auditLogger->record('updated', __('messages.audit_messages.finding_updated'), $project, [
            'finding_id' => $finding->id,
            'before' => $before,
            'after' => $finding->only(['title', 'severity', 'status', 'priority', 'owner_name', 'due_date', 'evidence_required', 'retest_required', 'retest_status']),
        ], 'finding');

        return redirect()->route('projects.findings.show', [$project, $finding])->with('status', __('messages.findings.updated'));
    }


    public function requestRetest(Request $request, Project $project, Finding $finding, FindingRetestWorkflowService $workflow, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $finding);
        $data = $request->validate([
            'retest_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $workflow->requestRetest($project, $finding, $request->user(), $data['retest_note'] ?? null);

        $auditLogger->record('updated', __('messages.audit_messages.finding_retest_requested'), $project, [
            'finding_id' => $finding->id,
            'title' => $finding->title,
            'retest_status' => 'required',
        ], 'finding', 'warning');

        return redirect()->route('projects.findings.show', [$project, $finding])->with('status', __('messages.findings.retest_requested'));
    }

    public function markReadyForRetest(Request $request, Project $project, Finding $finding, FindingRetestWorkflowService $workflow, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $finding);
        $data = $request->validate([
            'retest_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $workflow->markReady($project, $finding, $request->user(), $data['retest_note'] ?? null);

        $auditLogger->record('updated', __('messages.audit_messages.finding_ready_for_retest'), $project, [
            'finding_id' => $finding->id,
            'title' => $finding->title,
            'retest_status' => 'ready_for_retest',
        ], 'finding');

        return redirect()->route('projects.findings.show', [$project, $finding])->with('status', __('messages.findings.ready_for_retest_marked'));
    }

    public function recordRetest(Request $request, Project $project, Finding $finding, FindingRetestWorkflowService $workflow, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $finding);
        $data = $request->validate([
            'result' => ['required', Rule::in(['passed', 'failed'])],
            'retest_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $workflow->recordResult($project, $finding, $data['result'], $request->user(), $data['retest_note'] ?? null);

        $auditLogger->record('updated', __('messages.audit_messages.finding_retest_recorded'), $project, [
            'finding_id' => $finding->id,
            'title' => $finding->title,
            'result' => $data['result'],
        ], 'finding', $data['result'] === 'failed' ? 'warning' : 'info');

        return redirect()->route('projects.findings.show', [$project, $finding])->with('status', __('messages.findings.retest_recorded'));
    }

    public function acceptRisk(Request $request, Project $project, Finding $finding, RiskAcceptanceService $riskAcceptanceService, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $finding);
        $data = $this->validatedRiskAcceptance($request);

        $acceptance = $riskAcceptanceService->accept($project, $finding, $request->user(), $data);

        $auditLogger->record('updated', __('messages.audit_messages.risk_acceptance_created'), $project, [
            'finding_id' => $finding->id,
            'risk_acceptance_id' => $acceptance->id,
            'accepted_until' => $acceptance->accepted_until?->toDateString(),
        ], 'risk_acceptance', 'warning');

        return redirect()->route('projects.findings.show', [$project, $finding])->with('status', __('messages.risk_acceptance.created'));
    }


    public function renewRisk(Request $request, Project $project, Finding $finding, RiskAcceptanceService $riskAcceptanceService, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $finding);
        $data = $this->validatedRiskAcceptance($request);

        $acceptance = $riskAcceptanceService->renewLatest($project, $finding, $request->user(), $data);

        $auditLogger->record('updated', __('messages.audit_messages.risk_acceptance_renewed'), $project, [
            'finding_id' => $finding->id,
            'risk_acceptance_id' => $acceptance->id,
            'renewed_from_id' => $acceptance->renewed_from_id,
            'accepted_until' => $acceptance->accepted_until?->toDateString(),
        ], 'risk_acceptance', 'warning');

        return redirect()->route('projects.findings.show', [$project, $finding])->with('status', __('messages.risk_acceptance.renewed'));
    }

    public function closeRiskAcceptedFinding(Request $request, Project $project, Finding $finding, RiskAcceptanceService $riskAcceptanceService, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $finding);
        $data = $request->validate([
            'closure_note' => ['required', 'string', 'max:3000'],
        ]);

        $riskAcceptanceService->closeFinding($project, $finding, $request->user(), $data['closure_note']);

        $auditLogger->record('updated', __('messages.audit_messages.risk_acceptance_finding_closed'), $project, [
            'finding_id' => $finding->id,
            'title' => $finding->title,
            'closure_note' => $data['closure_note'],
        ], 'finding', 'info');

        return redirect()->route('projects.findings.show', [$project, $finding])->with('status', __('messages.risk_acceptance.finding_closed'));
    }

    public function revokeRisk(Request $request, Project $project, Finding $finding, RiskAcceptanceService $riskAcceptanceService, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $finding);
        $data = $request->validate([
            'revocation_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $acceptance = $riskAcceptanceService->revoke($project, $finding, $request->user(), $data['revocation_note'] ?? null);

        if ($acceptance) {
            $auditLogger->record('updated', __('messages.audit_messages.risk_acceptance_revoked'), $project, [
                'finding_id' => $finding->id,
                'risk_acceptance_id' => $acceptance->id,
            ], 'risk_acceptance', 'warning');
        }

        return redirect()->route('projects.findings.show', [$project, $finding])->with('status', __('messages.risk_acceptance.revoked'));
    }

    public function destroy(Project $project, Finding $finding, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $finding);

        $auditLogger->record('deleted', __('messages.audit_messages.finding_deleted'), $project, [
            'finding_id' => $finding->id,
            'title' => $finding->title,
        ], 'finding', 'warning');

        $finding->delete();

        return redirect()->route('projects.findings.index', $project)->with('status', __('messages.findings.deleted'));
    }


    private function validatedRiskAcceptance(Request $request): array
    {
        return $request->validate([
            'accepted_until' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:3000'],
            'business_justification' => ['required', 'string', 'max:3000'],
            'mitigation_note' => ['nullable', 'string', 'max:3000'],
            'release_scope' => ['nullable', 'string', 'max:180'],
        ]);
    }

    private function validated(Request $request, Project $project, ?Finding $finding = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'source' => ['required', Rule::in(Finding::SOURCES)],
            'severity' => ['required', Rule::in(Finding::SEVERITIES)],
            'status' => ['required', Rule::in(Finding::STATUSES)],
            'priority' => ['required', Rule::in(Finding::PRIORITIES)],
            'owner_name' => ['nullable', 'string', 'max:180'],
            'due_date' => ['nullable', 'date'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'reproduction_steps' => ['nullable', 'string', 'max:5000'],
            'expected_result' => ['nullable', 'string', 'max:5000'],
            'actual_result' => ['nullable', 'string', 'max:5000'],
            'recommendation' => ['nullable', 'string', 'max:5000'],
            'endpoint_id' => ['nullable', 'integer'],
            'scan_result_id' => ['nullable', 'integer'],
        ]);

        $data['evidence_required'] = $request->boolean('evidence_required', true);
        $data['retest_required'] = $request->boolean('retest_required');
        $data['retest_status'] = $data['retest_required'] ? ($finding?->retest_status && $finding->retest_status !== 'not_required' ? $finding->retest_status : 'required') : 'not_required';
        $data['endpoint_id'] = $this->projectEndpointId($project, $data['endpoint_id'] ?? null);
        $scanResult = $this->projectScanResult($project, $data['scan_result_id'] ?? null);
        $data['scan_result_id'] = $scanResult?->id;
        $data['scan_run_id'] = $scanResult?->scan_run_id;

        if (! $data['endpoint_id'] && $scanResult?->endpoint_id) {
            $data['endpoint_id'] = $scanResult->endpoint_id;
        }

        return $data;
    }

    private function createEvidenceFromScanResult(Finding $finding, Request $request): void
    {
        $scanResult = $finding->scanResult;
        if (! $scanResult) {
            return;
        }

        $finding->evidence()->create([
            'project_id' => $finding->project_id,
            'endpoint_id' => $finding->endpoint_id,
            'scan_result_id' => $scanResult->id,
            'type' => 'http',
            'title' => __('messages.evidence.scan_result_evidence'),
            'source_label' => 'Safe Scan #'.$scanResult->scan_run_id,
            'content' => trim(($scanResult->risk_reason ?: '')."\n".($scanResult->error_message ?: '')) ?: null,
            'url' => $scanResult->url,
            'response_excerpt' => $scanResult->body_preview,
            'captured_at' => now(),
            'captured_by_user_id' => $request->user()?->id,
            'sha256' => hash('sha256', implode('|', [$scanResult->url, $scanResult->status_code, $scanResult->body_preview])),
            'metadata_json' => [
                'status' => $scanResult->status,
                'status_code' => $scanResult->status_code,
                'content_type' => $scanResult->content_type,
                'response_time_ms' => $scanResult->response_time_ms,
            ],
        ]);
    }

    private function projectEndpointId(Project $project, mixed $endpointId): ?int
    {
        if (! $endpointId) {
            return null;
        }

        return Endpoint::query()->where('project_id', $project->id)->whereKey($endpointId)->exists() ? (int) $endpointId : null;
    }

    private function projectScanResult(Project $project, mixed $scanResultId): ?ScanResult
    {
        if (! $scanResultId) {
            return null;
        }

        return ScanResult::query()->where('project_id', $project->id)->whereKey($scanResultId)->first();
    }

    private function ensureBelongsToProject(Project $project, Finding $finding): void
    {
        abort_unless((int) $finding->project_id === (int) $project->id, 404);
    }
}
