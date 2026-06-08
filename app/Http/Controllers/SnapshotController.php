<?php

namespace App\Http\Controllers;

use App\Models\CompareRun;
use App\Models\Project;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Services\AssertionEvaluationService;
use App\Services\RegressionEvaluationService;
use App\Services\Settings\SettingService;
use App\Services\Snapshots\SnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SnapshotController extends Controller
{
    public function index(Project $project, SettingService $settings): View
    {
        $snapshots = $project->snapshots()
            ->with(['environment', 'scanRun', 'creator'])
            ->withCount('items')
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        $snapshotOptions = $project->snapshots()
            ->latest()
            ->limit(50)
            ->get();

        $compareRuns = $project->compareRuns()
            ->with(['snapshotA', 'snapshotB'])
            ->latest()
            ->limit(10)
            ->get();

        return view('snapshots.index', compact('project', 'snapshots', 'snapshotOptions', 'compareRuns'));
    }

    public function store(Request $request, Project $project, ScanRun $scanRun, SnapshotService $snapshotService): RedirectResponse
    {
        $this->ensureScanBelongsToProject($project, $scanRun);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $snapshot = $snapshotService->createFromScanRun(
            $scanRun,
            $request->user(),
            $validated['name'] ?? null,
            $validated['description'] ?? null,
        );

        return redirect()
            ->route('projects.snapshots.show', [$project, $snapshot])
            ->with('success', __('messages.snapshots.created'));
    }

    public function show(Project $project, Snapshot $snapshot, AssertionEvaluationService $assertions): View
    {
        $this->ensureSnapshotBelongsToProject($project, $snapshot);

        $snapshot->load(['project', 'environment', 'scanRun', 'creator', 'items' => fn ($query) => $query->with('endpoint')->orderBy('method')->orderBy('path')]);
        $assertionEvaluations = $snapshot->items
            ->filter(fn ($item): bool => $item->endpoint !== null)
            ->mapWithKeys(fn ($item): array => [$item->id => $assertions->evaluate($item->endpoint, null, $item)])
            ->all();

        return view('snapshots.show', compact('project', 'snapshot', 'assertionEvaluations'));
    }

    public function compare(Request $request, Project $project, SnapshotService $snapshotService): RedirectResponse
    {
        $validated = $request->validate([
            'snapshot_a_id' => ['required', 'integer', 'different:snapshot_b_id'],
            'snapshot_b_id' => ['required', 'integer'],
        ], [
            'snapshot_a_id.different' => __('messages.snapshots.validation.different'),
        ]);

        $snapshotA = Snapshot::query()
            ->where('project_id', $project->id)
            ->findOrFail($validated['snapshot_a_id']);
        $snapshotB = Snapshot::query()
            ->where('project_id', $project->id)
            ->findOrFail($validated['snapshot_b_id']);

        $compareRun = $snapshotService->compare($snapshotA, $snapshotB, $request->user());

        return redirect()
            ->route('projects.snapshots.compares.show', [$project, $compareRun])
            ->with('success', __('messages.snapshots.compare_created'));
    }

    public function showCompare(
        Project $project,
        CompareRun $compareRun,
        AssertionEvaluationService $assertions,
        RegressionEvaluationService $regressions
    ): View
    {
        $this->ensureCompareBelongsToProject($project, $compareRun);

        $compareRun->load([
            'project',
            'snapshotA',
            'snapshotB.items.endpoint',
            'items' => fn ($query) => $query->orderBy('severity')->orderBy('change_type')->orderBy('method')->orderBy('path'),
        ]);
        $targetItems = $compareRun->snapshotB?->items
            ->keyBy(fn ($item): string => strtoupper($item->method).' '.strtolower($item->path))
            ?? collect();
        $assertionEvaluations = $targetItems
            ->filter(fn ($item): bool => $item->endpoint !== null)
            ->mapWithKeys(fn ($item): array => [
                strtoupper($item->method).' '.strtolower($item->path) => $assertions->evaluate($item->endpoint, null, $item),
            ])
            ->all();
        $regressionEvaluation = $regressions->evaluateCompare($compareRun);

        return view('snapshots.compare-show', compact(
            'project',
            'compareRun',
            'assertionEvaluations',
            'regressionEvaluation'
        ));
    }

    private function ensureScanBelongsToProject(Project $project, ScanRun $scanRun): void
    {
        abort_unless($scanRun->project_id === $project->id, 404);
    }

    private function ensureSnapshotBelongsToProject(Project $project, Snapshot $snapshot): void
    {
        abort_unless($snapshot->project_id === $project->id, 404);
    }

    private function ensureCompareBelongsToProject(Project $project, CompareRun $compareRun): void
    {
        abort_unless($compareRun->project_id === $project->id, 404);
    }
}
