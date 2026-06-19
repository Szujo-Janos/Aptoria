<?php

namespace App\Http\Controllers;

use App\Models\ExternalImportRun;
use App\Models\Project;
use Illuminate\Validation\Rule;
use App\Services\AuditLogger;
use App\Services\ExternalQaImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportCenterController extends Controller
{
    public function index(Project $project, ExternalQaImportService $service): View
    {
        $runs = $project->externalImportRuns()
            ->with('createdBy')
            ->withCount('items')
            ->latest('previewed_at')
            ->latest()
            ->limit(30)
            ->get();

        return view('import_center.index', [
            'project' => $project,
            'runs' => $runs,
            'latestRun' => $runs->first(),
            'summary' => $service->summary($project),
            'sourceAdapters' => $service->sourceAdapters(),
        ]);
    }

    public function create(Project $project, ExternalQaImportService $service): View
    {
        return view('import_center.create', [
            'project' => $project,
            'sourceAdapters' => $service->sourceAdapters(),
        ]);
    }

    public function store(Request $request, Project $project, ExternalQaImportService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'source_type' => ['required', 'string', Rule::in(ExternalImportRun::SOURCE_TYPES)],
            'source_name' => ['nullable', 'string', 'max:180'],
            'source_version' => ['nullable', 'string', 'max:120'],
            'import_content' => ['required', 'string', 'max:500000'],
            'confirm_preview' => ['accepted'],
        ]);

        $run = $service->preview($project, $data, $request->user());

        $auditLogger->record('external_import_previewed', __('messages.audit_messages.external_import_previewed'), $project, [
            'external_import_run_id' => $run->id,
            'source_type' => $run->source_type,
            'item_count' => $run->item_count,
            'endpoint_count' => $run->endpoint_count,
            'assertion_count' => $run->assertion_count,
            'finding_count' => $run->finding_count,
            'evidence_count' => $run->evidence_count,
        ], 'import', $run->blocker_count > 0 ? 'warning' : 'info');

        return redirect()->route('projects.import-center.show', [$project, $run])->with('status', __('messages.import_center.preview_created'));
    }

    public function show(Project $project, ExternalImportRun $externalImportRun, ExternalQaImportService $service): View
    {
        abort_unless((int) $externalImportRun->project_id === (int) $project->id, 404);

        $externalImportRun->load(['createdBy', 'revertedBy', 'items.endpoint', 'items.finding']);

        return view('import_center.show', [
            'project' => $project,
            'run' => $externalImportRun,
            'items' => $externalImportRun->items()
                ->orderByRaw("CASE match_status WHEN 'conflict' THEN 0 WHEN 'needs_review' THEN 1 WHEN 'duplicate' THEN 2 ELSE 3 END")
                ->orderByRaw("CASE severity WHEN 'blocker' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
                ->orderByRaw("CASE entity_type WHEN 'endpoint' THEN 0 WHEN 'assertion' THEN 1 WHEN 'finding' THEN 2 ELSE 3 END")
                ->orderBy('id')
                ->get(),
            'markdownEvidence' => $service->markdownEvidence($externalImportRun),
        ]);
    }

    public function apply(Request $request, Project $project, ExternalImportRun $externalImportRun, ExternalQaImportService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $request->validate(['confirm_apply' => ['accepted']]);
        $summary = $service->apply($project, $externalImportRun, $request->user());

        $auditLogger->record('external_import_applied', __('messages.audit_messages.external_import_applied'), $project, [
            'external_import_run_id' => $externalImportRun->id,
            'source_type' => $externalImportRun->source_type,
            'created' => $summary['created'] ?? [],
            'updated' => $summary['updated'] ?? [],
        ], 'import', 'info');

        return redirect()->route('projects.import-center.show', [$project, $externalImportRun->fresh()])->with('status', __('messages.import_center.import_applied'));
    }

    public function undo(Request $request, Project $project, ExternalImportRun $externalImportRun, ExternalQaImportService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $request->validate(['confirm_undo' => ['accepted']]);
        $summary = $service->undo($project, $externalImportRun, $request->user());

        $auditLogger->record('external_import_reverted', __('messages.audit_messages.external_import_reverted'), $project, [
            'external_import_run_id' => $externalImportRun->id,
            'source_type' => $externalImportRun->source_type,
            'revert_summary' => $summary,
        ], 'import', 'warning');

        return redirect()->route('projects.import-center.show', [$project, $externalImportRun->fresh()])->with('status', __('messages.import_center.import_reverted'));
    }

}
