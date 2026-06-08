<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindingEvidenceRequest;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class FindingEvidenceController extends Controller
{
    public function store(FindingEvidenceRequest $request, Project $project, Finding $finding): RedirectResponse
    {
        $this->ensureFindingBelongsToProject($project, $finding);

        $finding->evidence()->create([
            ...$request->validated(),
            'project_id' => $project->id,
        ]);

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.evidence_created'));
    }

    public function destroy(Project $project, Finding $finding, FindingEvidence $evidence): RedirectResponse
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        abort_unless($evidence->finding_id === $finding->id && $evidence->project_id === $project->id, 404);
        $evidence->delete();

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.evidence_deleted'));
    }

    private function ensureFindingBelongsToProject(Project $project, Finding $finding): void
    {
        abort_unless($finding->project_id === $project->id, 404);
    }
}
