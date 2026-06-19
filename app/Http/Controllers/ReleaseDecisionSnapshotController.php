<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReleaseDecisionSnapshot;
use App\Services\AuditLogger;
use App\Services\ReleaseDecisionSnapshotService;
use App\Services\ReleaseDecisionReportVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReleaseDecisionSnapshotController extends Controller
{
    public function store(Request $request, Project $project, ReleaseDecisionSnapshotService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(ReleaseDecisionSnapshot::DECISIONS)],
            'decision_note' => ['nullable', 'string', 'max:3000'],
            'confirm_decision' => ['accepted'],
        ]);

        $snapshot = $service->createSnapshot($project, $request->user(), $data['decision'], $data['decision_note'] ?? null);

        $auditLogger->record('release_decision_snapshot_created', __('messages.audit_messages.release_decision_snapshot_created'), $project, [
            'release_decision_snapshot_id' => $snapshot->id,
            'release_readiness_run_id' => $snapshot->release_readiness_run_id,
            'decision' => $snapshot->decision,
        ], 'release', $snapshot->decision === 'blocked' ? 'warning' : 'info');

        return redirect()
            ->route('projects.release-decisions.show', [$project, $snapshot])
            ->with('status', __('messages.release_decisions.snapshot_created'));
    }

    public function show(Project $project, ReleaseDecisionSnapshot $releaseDecisionSnapshot, ReleaseDecisionSnapshotService $service): View
    {
        abort_unless((int) $releaseDecisionSnapshot->project_id === (int) $project->id, 404);
        $releaseDecisionSnapshot->load(['decidedBy', 'releaseReadinessRun']);
        $reportVersions = $releaseDecisionSnapshot->reportVersions()->with(['generatedBy'])->latest('generated_at')->latest()->get();

        return view('release_decisions.show', [
            'project' => $project,
            'snapshot' => $releaseDecisionSnapshot,
            'summary' => $releaseDecisionSnapshot->evidence_summary,
            'sourceState' => $releaseDecisionSnapshot->source_state,
            'metrics' => $releaseDecisionSnapshot->readiness_metrics,
            'checks' => $releaseDecisionSnapshot->readiness_checks,
            'reportPreview' => $service->reportPreview($releaseDecisionSnapshot),
            'reportVersions' => $reportVersions,
            'latestReportVersion' => $reportVersions->first(),
        ]);
    }


    public function download(Project $project, ReleaseDecisionSnapshot $releaseDecisionSnapshot, string $format, ReleaseDecisionSnapshotService $service): Response
    {
        abort_unless((int) $releaseDecisionSnapshot->project_id === (int) $project->id, 404);
        abort_unless(in_array($format, ['html', 'pdf', 'md'], true), 404);

        $releaseDecisionSnapshot->load(['project', 'decidedBy', 'releaseReadinessRun']);
        $slug = Str::slug($project->name.'-release-decision-'.$releaseDecisionSnapshot->id);

        if ($format === 'html') {
            return response($service->exportHtml($releaseDecisionSnapshot), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.html"',
            ]);
        }

        if ($format === 'pdf') {
            return response($service->exportPdf($releaseDecisionSnapshot), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.pdf"',
            ]);
        }

        return response($service->exportMarkdown($releaseDecisionSnapshot), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$slug.'.md"',
        ]);
    }

    public function reportPreview(Project $project, ReleaseDecisionSnapshot $releaseDecisionSnapshot, ReleaseDecisionSnapshotService $service): View
    {
        abort_unless((int) $releaseDecisionSnapshot->project_id === (int) $project->id, 404);
        $releaseDecisionSnapshot->load(['decidedBy', 'releaseReadinessRun']);
        $reportVersions = $releaseDecisionSnapshot->reportVersions()->with(['generatedBy'])->latest('generated_at')->latest()->get();

        return view('release_decisions.report-preview', [
            'project' => $project,
            'snapshot' => $releaseDecisionSnapshot,
            'reportPreview' => $service->reportPreview($releaseDecisionSnapshot),
            'reportVersions' => $reportVersions,
            'latestReportVersion' => $reportVersions->first(),
        ]);
    }

    public function storeReportVersion(Request $request, Project $project, ReleaseDecisionSnapshot $releaseDecisionSnapshot, ReleaseDecisionReportVersionService $service, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless((int) $releaseDecisionSnapshot->project_id === (int) $project->id, 404);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
            'confirm_report_version' => ['accepted'],
        ]);

        $report = $service->createFromSnapshot($releaseDecisionSnapshot, $request->user(), $data['notes'] ?? null);

        $auditLogger->record('release_decision_report_version_created', __('messages.audit_messages.release_decision_report_version_created'), $project, [
            'release_decision_snapshot_id' => $releaseDecisionSnapshot->id,
            'report_version_id' => $report->id,
            'checksum' => $report->checksum,
        ], 'report');

        return redirect()
            ->route('projects.reports.show', [$project, $report])
            ->with('status', __('messages.reports.release_decision_version_created'));
    }
}
