<?php

namespace App\Http\Controllers;

use App\Models\EvidencePack;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\EvidencePackService;
use App\Services\ReportVisualStandardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvidencePackController extends Controller
{
    public function index(Project $project): View
    {
        return view('evidence_packs.index', [
            'project' => $project,
            'packs' => $project->evidencePacks()->with(['createdBy', 'releaseReadinessRun', 'reportVersion'])->latest()->get(),
            'readinessRuns' => $project->releaseReadinessRuns()->latest()->limit(20)->get(),
            'reportVersions' => $project->reportVersions()->latest()->limit(20)->get(),
        ]);
    }

    public function store(Request $request, Project $project, EvidencePackService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'pack_type' => ['required', 'string', 'in:'.implode(',', EvidencePack::TYPES)],
            'release_readiness_run_id' => ['nullable', 'integer'],
            'report_version_id' => ['nullable', 'integer'],
            'sections' => ['required', 'array'],
            'sections.*' => ['string', 'in:'.implode(',', EvidencePack::SECTIONS)],
        ]);

        $pack = $service->create($project, $data, $request->user());
        $auditLogger->record('evidence_pack_generated', __('messages.audit_messages.evidence_pack_generated'), $project, ['evidence_pack_id' => $pack->id, 'checksum' => $pack->checksum], 'report', 'info');

        return redirect()->route('projects.evidence-packs.show', [$project, $pack])->with('status', __('messages.evidence_packs.generated'));
    }

    public function show(Project $project, EvidencePack $evidencePack): View
    {
        abort_unless((int) $evidencePack->project_id === (int) $project->id, 404);
        $evidencePack->load(['createdBy', 'releaseReadinessRun', 'reportVersion']);

        return view('evidence_packs.show', ['project' => $project, 'pack' => $evidencePack]);
    }

    public function download(Project $project, EvidencePack $evidencePack, string $format, EvidencePackService $service, ReportVisualStandardService $reportVisualStandardService)
    {
        abort_unless((int) $evidencePack->project_id === (int) $project->id, 404);
        $name = 'aptoria-evidence-pack-'.$evidencePack->id.'.'.$format;

        return match ($format) {
            'html' => response($reportVisualStandardService->exportEvidencePackHtml($evidencePack), 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.$name.'"']),
            'pdf' => response($reportVisualStandardService->exportEvidencePackPdf($evidencePack), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'"']),
            'json' => response(json_encode($evidencePack->manifest_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.$name.'"']),
            'zip' => response($service->zipBinary($evidencePack), 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="'.$name.'"',
                'X-Content-Type-Options' => 'nosniff',
            ]),
            default => abort(404),
        };
    }
}
