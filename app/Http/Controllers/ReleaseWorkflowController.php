<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ReleaseWorkflow\WorkflowConsolidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReleaseWorkflowController extends Controller
{
    public function __invoke(Project $project, WorkflowConsolidationService $workflow): View
    {
        return view('release_workflow.show', [
            'project' => $project,
            'workflow' => $workflow->summarize($project),
        ]);
    }

    public function skip(Project $project, string $stepKey, Request $request, WorkflowConsolidationService $workflow): RedirectResponse
    {
        $this->authorizeProject($project, 'release.finalize');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:2000'],
        ]);

        $workflow->skipStep($project, $stepKey, (string) $validated['reason'], Auth::user());

        return redirect()
            ->route('projects.release-workflow.index', $project)
            ->with('success', __('messages.release_workflow.step_skipped'));
    }

    public function reopen(Project $project, string $stepKey, WorkflowConsolidationService $workflow): RedirectResponse
    {
        $this->authorizeProject($project, 'release.finalize');

        $workflow->reopenStep($project, $stepKey, Auth::user());

        return redirect()
            ->route('projects.release-workflow.index', $project)
            ->with('success', __('messages.release_workflow.step_reopened'));
    }
}
