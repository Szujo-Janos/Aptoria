<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Reports\QaEvidencePackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class QaEvidencePackController extends Controller
{
    public function index(Project $project, QaEvidencePackService $evidence): View
    {
        $snapshots = $project->snapshots()
            ->with('environment')
            ->latest()
            ->limit(50)
            ->get();

        $compareRuns = $project->compareRuns()
            ->with(['snapshotA', 'snapshotB'])
            ->latest()
            ->limit(50)
            ->get();

        $defaults = $evidence->defaultSelection($project);
        $context = $evidence->buildContext($project, $defaults);

        return view('qa_evidence.index', compact('project', 'snapshots', 'compareRuns', 'defaults', 'context'));
    }

    public function notes(Request $request, Project $project, QaEvidencePackService $evidence): Response
    {
        $selection = $evidence->selectionFromInput($project, $request->query());

        return response($evidence->notesMarkdown($project, $selection), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$evidence->filename($project, 'md').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function summary(Request $request, Project $project, QaEvidencePackService $evidence): Response
    {
        $selection = $evidence->selectionFromInput($project, $request->query());

        return response($evidence->summaryJson($project, $selection), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$evidence->filename($project, 'json').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function zip(Request $request, Project $project, QaEvidencePackService $evidence)
    {
        $selection = $evidence->selectionFromInput($project, $request->query());

        try {
            $zipPath = $evidence->buildZip($project, $selection);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('projects.qa-evidence.index', $project)
                ->with('error', $exception->getMessage());
        }

        return response()
            ->download($zipPath, $evidence->filename($project, 'zip'), [
                'Content-Type' => 'application/zip',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->deleteFileAfterSend(true);
    }
}
