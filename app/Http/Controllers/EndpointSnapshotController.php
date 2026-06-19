<?php

namespace App\Http\Controllers;

use App\Models\EndpointSnapshot;
use App\Models\EndpointSnapshotCompare;
use App\Models\EndpointTestBatch;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\EndpointSnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EndpointSnapshotController extends Controller
{
    public function index(Project $project): View
    {
        return view('snapshots.index', [
            'project' => $project,
            'snapshots' => $project->endpointSnapshots()
                ->with(['batch', 'createdBy'])
                ->withCount('items')
                ->latest('captured_at')
                ->latest()
                ->get(),
            'compares' => $project->endpointSnapshotCompares()
                ->with(['baselineSnapshot', 'targetSnapshot'])
                ->latest('compared_at')
                ->latest()
                ->take(10)
                ->get(),
            'batches' => $project->endpointTestBatches()
                ->whereNotNull('completed_at')
                ->withCount('testRuns')
                ->latest('completed_at')
                ->latest()
                ->take(12)
                ->get(),
        ]);
    }

    public function store(Request $request, Project $project, EndpointSnapshotService $snapshotService, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'endpoint_test_batch_id' => ['required', 'integer'],
            'title' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'confirm_snapshot' => ['accepted'],
        ]);

        $batch = $project->endpointTestBatches()->withCount('testRuns')->findOrFail($data['endpoint_test_batch_id']);

        if ($batch->test_runs_count < 1) {
            return back()->with('error', __('messages.snapshots.batch_without_runs'));
        }

        $snapshot = $snapshotService->createFromBatch(
            $project,
            $batch,
            $request->user(),
            $data['title'] ?? null,
            $data['notes'] ?? null,
        );

        $auditLogger->record('endpoint_snapshot_created', __('messages.audit_messages.endpoint_snapshot_created'), $project, [
            'endpoint_snapshot_id' => $snapshot->id,
            'endpoint_test_batch_id' => $batch->id,
            'total' => $snapshot->total,
            'checksum' => $snapshot->checksum,
        ], 'snapshot');

        return redirect()->route('projects.snapshots.show', [$project, $snapshot])->with('status', __('messages.snapshots.created'));
    }

    public function show(Project $project, EndpointSnapshot $endpointSnapshot, EndpointSnapshotService $snapshotService): View
    {
        $this->ensureBelongsToProject($project, $endpointSnapshot);

        return view('snapshots.show', [
            'project' => $project,
            'snapshot' => $endpointSnapshot->load(['batch', 'createdBy', 'items.endpoint', 'baselineCompares.targetSnapshot', 'targetCompares.baselineSnapshot']),
            'items' => $endpointSnapshot->items()->with('endpoint')->orderBy('endpoint_signature')->get(),
            'snapshotMarkdown' => $snapshotService->snapshotMarkdown($endpointSnapshot),
        ]);
    }

    public function compare(Request $request, Project $project, EndpointSnapshotService $snapshotService, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'baseline_snapshot_id' => ['required', 'integer', 'different:target_snapshot_id'],
            'target_snapshot_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'confirm_compare' => ['accepted'],
        ]);

        $baseline = $project->endpointSnapshots()->findOrFail($data['baseline_snapshot_id']);
        $target = $project->endpointSnapshots()->findOrFail($data['target_snapshot_id']);

        $compare = $snapshotService->compare($project, $baseline, $target, $request->user(), $data['notes'] ?? null);

        $auditLogger->record('endpoint_snapshot_compare_created', __('messages.audit_messages.endpoint_snapshot_compare_created'), $project, [
            'endpoint_snapshot_compare_id' => $compare->id,
            'baseline_snapshot_id' => $baseline->id,
            'target_snapshot_id' => $target->id,
            'status' => $compare->status,
            'regressed_count' => $compare->regressed_count,
        ], 'snapshot', $compare->status === 'blocked' ? 'warning' : 'info');

        return redirect()->route('projects.snapshot-compares.show', [$project, $compare])->with('status', __('messages.snapshots.compare_created'));
    }

    public function compareShow(Project $project, EndpointSnapshotCompare $endpointSnapshotCompare, EndpointSnapshotService $snapshotService): View
    {
        $this->ensureBelongsToProject($project, $endpointSnapshotCompare);

        return view('snapshots.compare-show', [
            'project' => $project,
            'compare' => $endpointSnapshotCompare->load(['baselineSnapshot', 'targetSnapshot', 'comparedBy', 'items', 'regressionFindings']),
            'items' => $endpointSnapshotCompare->items()->orderByRaw("case change_type when 'regressed' then 1 when 'removed' then 2 when 'changed' then 3 when 'added' then 4 when 'improved' then 5 else 6 end")->orderBy('endpoint_signature')->get(),
            'compareMarkdown' => $snapshotService->compareMarkdown($endpointSnapshotCompare),
        ]);
    }


    public function generateRegressionFindings(Project $project, EndpointSnapshotCompare $endpointSnapshotCompare, EndpointSnapshotService $snapshotService, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $endpointSnapshotCompare);

        $result = $snapshotService->generateRegressionFindings($project, $endpointSnapshotCompare, request()->user());

        $auditLogger->record('endpoint_regression_findings_generated', __('messages.audit_messages.endpoint_regression_findings_generated'), $project, [
            'endpoint_snapshot_compare_id' => $endpointSnapshotCompare->id,
            'candidate_count' => $result['candidate_count'],
            'created_count' => $result['created']->count(),
            'linked_count' => $result['linked_count'],
        ], 'finding', $result['created']->count() > 0 ? 'warning' : 'info');

        return redirect()
            ->route('projects.snapshot-compares.show', [$project, $endpointSnapshotCompare])
            ->with('status', __('messages.snapshots.regression_findings_created', [
                'created' => $result['created']->count(),
                'linked' => $result['linked_count'],
            ]));
    }

    private function ensureBelongsToProject(Project $project, object $model): void
    {
        abort_unless((int) $model->project_id === (int) $project->id, 404);
    }
}
