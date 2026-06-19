<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReleaseGate;
use App\Models\ReleaseGateItem;
use App\Services\AuditLogger;
use App\Services\ReleaseGateWorkflowService;
use App\Services\ReleaseGateDecisionPackageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ReleaseGateController extends Controller
{
    public function index(Project $project, ReleaseGateWorkflowService $service): View
    {
        $gates = $project->releaseGates()
            ->with(['createdBy', 'finalizedBy'])
            ->latest()
            ->get();

        return view('release_gates.index', [
            'project' => $project,
            'gates' => $gates,
            'summary' => $service->summary($project),
        ]);
    }

    public function store(Request $request, Project $project, ReleaseGateWorkflowService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'release_version' => ['nullable', 'string', 'max:120'],
            'target_environment' => ['nullable', 'string', 'max:180'],
            'gate_profile' => ['required', Rule::in(ReleaseGate::PROFILES)],
            'decision_note' => ['nullable', 'string', 'max:3000'],
            'confirm_create_gate' => ['accepted'],
        ]);

        $gate = $service->create($project, $request->user(), $data);

        $auditLogger->record('release_gate_created', __('messages.audit_messages.release_gate_created'), $project, [
            'release_gate_id' => $gate->id,
            'release_readiness_run_id' => $gate->release_readiness_run_id,
            'status' => $gate->status,
            'score' => $gate->score,
            'blockers' => $gate->blocker_count,
            'warnings' => $gate->warning_count,
        ], 'release', $gate->blocker_count > 0 ? 'warning' : 'info');

        return redirect()->route('projects.release-gates.show', [$project, $gate])->with('status', __('messages.release_gates.created'));
    }

    public function show(Project $project, ReleaseGate $releaseGate, ReleaseGateDecisionPackageService $packageService): View
    {
        abort_unless((int) $releaseGate->project_id === (int) $project->id, 404);
        $releaseGate->load(['items.reviewedBy', 'events.user', 'createdBy', 'finalizedBy', 'readinessRun', 'reportVersions.generatedBy', 'reportVersions.reviewedBy', 'reportVersions.approvedBy']);

        return view('release_gates.show', [
            'project' => $project,
            'gate' => $releaseGate,
            'itemsByCategory' => $releaseGate->items->groupBy('category'),
            'latestDecisionPackageReport' => $packageService->latestReportVersion($releaseGate),
        ]);
    }


    public function storeReportVersion(Request $request, Project $project, ReleaseGate $releaseGate, ReleaseGateDecisionPackageService $packageService, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless((int) $releaseGate->project_id === (int) $project->id, 404);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
            'confirm_decision_package' => ['accepted'],
        ]);

        $report = $packageService->createReportVersion($releaseGate, $request->user(), $data['notes'] ?? null);

        $auditLogger->record('release_gate_decision_package_created', __('messages.audit_messages.release_gate_decision_package_created'), $project, [
            'release_gate_id' => $releaseGate->id,
            'report_version_id' => $report->id,
            'checksum' => $report->checksum,
            'final_decision' => $releaseGate->final_decision,
        ], 'report');

        return redirect()
            ->route('projects.reports.show', [$project, $report])
            ->with('status', __('messages.release_gates.package.created'));
    }

    public function download(Project $project, ReleaseGate $releaseGate, string $format, ReleaseGateDecisionPackageService $packageService): Response
    {
        abort_unless((int) $releaseGate->project_id === (int) $project->id, 404);
        abort_unless(in_array($format, ['html', 'pdf', 'json', 'zip', 'md'], true), 404);

        $releaseGate->load(['project', 'createdBy', 'finalizedBy', 'readinessRun', 'items.reviewedBy', 'events.user']);
        $report = $packageService->latestReportVersion($releaseGate);
        $slug = Str::slug($project->name.'-'.$releaseGate->title.'-decision-package');

        if ($format === 'html') {
            return response($packageService->exportHtml($releaseGate, $report), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.html"',
            ]);
        }

        if ($format === 'pdf') {
            return response($packageService->exportPdf($releaseGate, $report), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.pdf"',
            ]);
        }

        if ($format === 'json') {
            return response($packageService->exportJson($releaseGate, $report), 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.json"',
            ]);
        }

        if ($format === 'zip') {
            return response($packageService->zipBinary($releaseGate, $report), 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.zip"',
            ]);
        }

        $data = $report && is_array($report->data_json) ? $report->data_json : $packageService->packageData($releaseGate);
        return response($report?->content_markdown ?: $packageService->markdown($releaseGate, $data), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$slug.'.md"',
        ]);
    }

    public function updateItem(Request $request, Project $project, ReleaseGate $releaseGate, ReleaseGateItem $item, ReleaseGateWorkflowService $service, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless((int) $releaseGate->project_id === (int) $project->id, 404);
        abort_unless((int) $item->release_gate_id === (int) $releaseGate->id, 404);

        $data = $request->validate([
            'manual_state' => ['nullable', Rule::in(ReleaseGateItem::STATES)],
            'reviewer_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $service->updateItem($item, $request->user(), $data);

        $auditLogger->record('release_gate_item_reviewed', __('messages.audit_messages.release_gate_item_reviewed'), $project, [
            'release_gate_id' => $releaseGate->id,
            'release_gate_item_id' => $item->id,
            'manual_state' => $data['manual_state'] ?? null,
        ], 'release');

        return back()->with('status', __('messages.release_gates.item_updated'));
    }

    public function finalize(Request $request, Project $project, ReleaseGate $releaseGate, ReleaseGateWorkflowService $service, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless((int) $releaseGate->project_id === (int) $project->id, 404);

        $data = $request->validate([
            'final_decision' => ['required', Rule::in(['go', 'conditional_go', 'no_go'])],
            'decision_note' => ['required', 'string', 'max:3000'],
            'confirm_finalize_gate' => ['accepted'],
        ]);

        try {
            $gate = $service->finalize($releaseGate, $request->user(), $data);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['final_decision' => $exception->getMessage()])->withInput(['_release_gate_modal' => 'finalizeGateModal']);
        }

        $auditLogger->record('release_gate_finalized', __('messages.audit_messages.release_gate_finalized'), $project, [
            'release_gate_id' => $gate->id,
            'final_decision' => $gate->final_decision,
            'status' => $gate->status,
        ], 'release', $gate->final_decision === 'no_go' ? 'warning' : 'info');

        return redirect()->route('projects.release-gates.show', [$project, $gate])->with('status', __('messages.release_gates.finalized'));
    }
}
