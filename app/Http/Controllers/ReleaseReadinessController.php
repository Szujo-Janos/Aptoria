<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReleaseReadinessRun;
use App\Services\AuditLogger;
use App\Services\ReleaseReadinessService;
use App\Services\ReleaseDecisionSnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReleaseReadinessController extends Controller
{
    public function index(Project $project, ReleaseReadinessService $service, ReleaseDecisionSnapshotService $decisionService): View
    {
        $evaluation = $service->evaluate($project);
        $runs = $project->releaseReadinessRuns()->with('generatedBy')->latest()->limit(25)->get();
        $decisionSummary = $decisionService->currentSummary($project, $evaluation);
        $decisionSnapshots = $project->releaseDecisionSnapshots()
            ->with(['decidedBy', 'releaseReadinessRun', 'reportVersions'])
            ->latest('decided_at')
            ->latest()
            ->limit(12)
            ->get();

        return view('release_readiness.index', [
            'project' => $project,
            'evaluation' => $evaluation,
            'runs' => $runs,
            'latestRun' => $runs->first(),
            'decisionSummary' => $decisionSummary,
            'decisionSnapshots' => $decisionSnapshots,
            'latestDecisionSnapshot' => $decisionSnapshots->first(),
        ]);
    }

    public function store(Request $request, Project $project, ReleaseReadinessService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:2000'],
            'confirm_evaluation' => ['accepted'],
        ]);

        $run = $service->createRun($project, $request->user(), $data['decision_note'] ?? null);

        $auditLogger->record('release_readiness_evaluated', __('messages.audit_messages.release_readiness_evaluated'), $project, [
            'release_readiness_run_id' => $run->id,
            'status' => $run->status,
            'score' => $run->score,
            'blockers' => $run->blocker_count,
            'warnings' => $run->warning_count,
        ], 'release', $run->status === 'blocked' ? 'warning' : 'info');

        return redirect()->route('projects.release-readiness.show', [$project, $run])->with('status', __('messages.release_readiness.snapshot_created'));
    }

    public function show(Project $project, ReleaseReadinessRun $releaseReadinessRun): View
    {
        abort_unless((int) $releaseReadinessRun->project_id === (int) $project->id, 404);
        $releaseReadinessRun->load('generatedBy');

        return view('release_readiness.show', [
            'project' => $project,
            'run' => $releaseReadinessRun,
            'checks' => $releaseReadinessRun->checks,
            'metrics' => $releaseReadinessRun->metrics,
            'summary' => $releaseReadinessRun->summary,
            'riskAcceptance' => $releaseReadinessRun->risk_acceptance,
            'contractValidation' => $releaseReadinessRun->contract_validation,
        ]);
    }
}
