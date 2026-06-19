<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\ScanResult;
use App\Services\AuditLogger;
use App\Services\EvidenceRepositoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EvidenceController extends Controller
{
    public function index(Request $request, Project $project, EvidenceRepositoryService $repository): View
    {
        $status = $request->query('status', 'open');
        $type = $request->query('type');
        $integrity = $request->query('integrity');

        $evidenceItems = $project->evidence()
            ->with(['finding', 'endpoint', 'scanResult', 'testCase', 'testRun', 'capturedBy', 'reviewedBy'])
            ->when($status === 'open', function ($query): void {
                $query->whereIn('repository_status', [FindingEvidence::STATUS_ACTIVE, FindingEvidence::STATUS_VERIFIED]);
            })
            ->when(in_array($status, FindingEvidence::STATUSES, true), function ($query) use ($status): void {
                $query->where('repository_status', $status);
            })
            ->when($type && in_array($type, FindingEvidence::TYPES, true), function ($query) use ($type): void {
                $query->where('type', $type);
            })
            ->when(in_array($integrity, [FindingEvidence::INTEGRITY_CURRENT, FindingEvidence::INTEGRITY_CHANGED], true), function ($query) use ($integrity): void {
                $query->where('integrity_status', $integrity);
            })
            ->latest()
            ->get();

        return view('evidence.index', [
            'project' => $project,
            'evidenceItems' => $evidenceItems,
            'findings' => $project->findings()->orderBy('severity')->orderBy('title')->get(),
            'endpoints' => $project->endpoints()->orderBy('method')->orderBy('path')->get(),
            'metrics' => $repository->metrics($project),
            'filters' => [
                'status' => $status,
                'type' => $type,
                'integrity' => $integrity,
            ],
        ]);
    }

    public function create(Project $project): View
    {
        return view('evidence.create', [
            'project' => $project,
            'findings' => $project->findings()->orderBy('severity')->orderBy('title')->get(),
            'endpoints' => $project->endpoints()->orderBy('method')->orderBy('path')->get(),
        ]);
    }

    public function show(Project $project, FindingEvidence $evidence, EvidenceRepositoryService $repository): View
    {
        $this->ensureBelongsToProject($project, $evidence);
        $repository->syncIntegrityState($evidence);

        $evidence->load(['finding', 'endpoint', 'scanResult', 'testCase.suite', 'testRun', 'capturedBy', 'reviewedBy', 'archivedBy', 'lifecycleEvents.user']);

        return view('evidence.show', [
            'project' => $project,
            'evidence' => $evidence,
        ]);
    }

    public function store(Request $request, Project $project, AuditLogger $auditLogger, EvidenceRepositoryService $repository): RedirectResponse
    {
        $data = $this->validated($request, $project);
        $data = $repository->prepareForCreate($data, $project, $request->user());

        $evidence = $project->evidence()->create($data);
        $repository->recordCreated($evidence, $request->user());

        $auditLogger->record('created', __('messages.audit_messages.evidence_created'), $project, [
            'evidence_id' => $evidence->id,
            'finding_id' => $evidence->finding_id,
            'type' => $evidence->type,
            'title' => $evidence->title,
            'sha256' => $evidence->sha256,
            'repository_status' => $evidence->repository_status,
        ], 'evidence');

        return redirect()->route('projects.evidence.show', [$project, $evidence])->with('status', __('messages.evidence.created'));
    }

    public function verify(Request $request, Project $project, FindingEvidence $evidence, AuditLogger $auditLogger, EvidenceRepositoryService $repository): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $evidence);
        $data = $request->validate([
            'repository_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $repository->verify($evidence, $request->user(), $data['repository_notes'] ?? null);

        $auditLogger->record('verified', __('messages.audit_messages.evidence_verified'), $project, [
            'evidence_id' => $evidence->id,
            'title' => $evidence->title,
            'sha256' => $evidence->sha256,
        ], 'evidence');

        return redirect()->route('projects.evidence.show', [$project, $evidence])->with('status', __('messages.evidence.verified'));
    }

    public function archive(Request $request, Project $project, FindingEvidence $evidence, AuditLogger $auditLogger, EvidenceRepositoryService $repository): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $evidence);
        $data = $request->validate([
            'repository_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $repository->archive($evidence, $request->user(), $data['repository_notes'] ?? null);

        $auditLogger->record('archived', __('messages.audit_messages.evidence_archived'), $project, [
            'evidence_id' => $evidence->id,
            'title' => $evidence->title,
            'sha256' => $evidence->sha256,
        ], 'evidence', 'warning');

        return redirect()->route('projects.evidence.show', [$project, $evidence])->with('status', __('messages.evidence.archived'));
    }

    public function restore(Request $request, Project $project, FindingEvidence $evidence, AuditLogger $auditLogger, EvidenceRepositoryService $repository): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $evidence);
        $repository->restore($evidence, $request->user());

        $auditLogger->record('restored', __('messages.audit_messages.evidence_restored'), $project, [
            'evidence_id' => $evidence->id,
            'title' => $evidence->title,
            'sha256' => $evidence->sha256,
        ], 'evidence');

        return redirect()->route('projects.evidence.show', [$project, $evidence])->with('status', __('messages.evidence.restored'));
    }

    public function destroy(Project $project, FindingEvidence $evidence, AuditLogger $auditLogger, EvidenceRepositoryService $repository): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $evidence);
        $repository->archive($evidence, request()->user(), __('messages.evidence.archived_by_delete'));

        $auditLogger->record('archived', __('messages.audit_messages.evidence_archived'), $project, [
            'evidence_id' => $evidence->id,
            'title' => $evidence->title,
            'sha256' => $evidence->sha256,
        ], 'evidence', 'warning');

        return redirect()->route('projects.evidence.index', $project)->with('status', __('messages.evidence.archived'));
    }

    private function validated(Request $request, Project $project): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(FindingEvidence::TYPES)],
            'title' => ['required', 'string', 'max:180'],
            'source_label' => ['nullable', 'string', 'max:180'],
            'content' => ['nullable', 'string', 'max:8000'],
            'url' => ['nullable', 'url', 'max:1200'],
            'request_excerpt' => ['nullable', 'string', 'max:8000'],
            'response_excerpt' => ['nullable', 'string', 'max:8000'],
            'captured_at' => ['nullable', 'date'],
            'repository_notes' => ['nullable', 'string', 'max:2000'],
            'finding_id' => ['nullable', 'integer'],
            'endpoint_id' => ['nullable', 'integer'],
            'scan_result_id' => ['nullable', 'integer'],
        ]);

        $data['finding_id'] = $this->projectFindingId($project, $data['finding_id'] ?? null);
        $data['endpoint_id'] = $this->projectEndpointId($project, $data['endpoint_id'] ?? null);
        $data['scan_result_id'] = $this->projectScanResultId($project, $data['scan_result_id'] ?? null);

        if (! $data['endpoint_id'] && $data['finding_id']) {
            $finding = Finding::query()->where('project_id', $project->id)->find($data['finding_id']);
            $data['endpoint_id'] = $finding?->endpoint_id;
        }

        return $data;
    }

    private function projectFindingId(Project $project, mixed $findingId): ?int
    {
        if (! $findingId) { return null; }
        return Finding::query()->where('project_id', $project->id)->whereKey($findingId)->exists() ? (int) $findingId : null;
    }

    private function projectEndpointId(Project $project, mixed $endpointId): ?int
    {
        if (! $endpointId) { return null; }
        return Endpoint::query()->where('project_id', $project->id)->whereKey($endpointId)->exists() ? (int) $endpointId : null;
    }

    private function projectScanResultId(Project $project, mixed $scanResultId): ?int
    {
        if (! $scanResultId) { return null; }
        return ScanResult::query()->where('project_id', $project->id)->whereKey($scanResultId)->exists() ? (int) $scanResultId : null;
    }

    private function ensureBelongsToProject(Project $project, FindingEvidence $evidence): void
    {
        abort_unless((int) $evidence->project_id === (int) $project->id, 404);
    }
}
