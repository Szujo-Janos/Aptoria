<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestCaseRequest;
use App\Http\Requests\TestCaseResultRequest;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\TestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TestCaseController extends Controller
{
    public function index(Project $project, SettingService $settings): View
    {
        $suiteId = request()->integer('suite_id') ?: null;
        $status = trim((string) request('status', ''));

        $testCases = $project->testCases()
            ->with(['testSuite', 'endpoint', 'latestResult'])
            ->when($suiteId, fn ($query) => $query->where('test_suite_id', $suiteId))
            ->when($status !== '', fn ($query) => $query->where('last_run_status', $status))
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25))
            ->withQueryString();

        $suites = $project->testSuites()->orderBy('name')->get();

        return view('test_cases.index', compact('project', 'testCases', 'suites', 'suiteId', 'status'));
    }

    public function create(Project $project): View
    {
        $project->load(['testSuites' => fn ($query) => $query->orderBy('name'), 'endpoints' => fn ($query) => $query->orderBy('method')->orderBy('path')]);

        return view('test_cases.create', [
            'project' => $project,
            'testCase' => new TestCase([
                'project_id' => $project->id,
                'test_suite_id' => request()->integer('test_suite_id') ?: null,
                'endpoint_id' => request()->integer('endpoint_id') ?: null,
                'type' => TestCase::TYPE_MANUAL,
                'priority' => TestCase::PRIORITY_MEDIUM,
                'status' => TestCase::STATUS_DRAFT,
                'last_run_status' => TestCase::RUN_NOT_RUN,
            ]),
        ]);
    }

    public function store(TestCaseRequest $request, Project $project): RedirectResponse
    {
        $payload = $this->payload($request);
        $testCase = $project->testCases()->create($payload);

        return redirect()
            ->route('projects.test-cases.show', [$project, $testCase])
            ->with('success', __('messages.test_cases.created'));
    }

    public function show(Project $project, TestCase $testCase): View
    {
        $this->ensureTestCaseBelongsToProject($project, $testCase);
        $testCase->load(['testSuite', 'endpoint.latestScanResult.scanRun', 'latestResult.scanResult.scanRun', 'results.scanResult.endpoint', 'results.scanRun', 'findings.evidence']);

        $scanResults = $this->availableScanResults($project, $testCase);

        return view('test_cases.show', compact('project', 'testCase', 'scanResults'));
    }

    public function edit(Project $project, TestCase $testCase): View
    {
        $this->ensureTestCaseBelongsToProject($project, $testCase);
        $project->load(['testSuites' => fn ($query) => $query->orderBy('name'), 'endpoints' => fn ($query) => $query->orderBy('method')->orderBy('path')]);
        $testCase->load(['testSuite', 'endpoint']);

        return view('test_cases.edit', compact('project', 'testCase'));
    }

    public function update(TestCaseRequest $request, Project $project, TestCase $testCase): RedirectResponse
    {
        $this->ensureTestCaseBelongsToProject($project, $testCase);
        $testCase->update($this->payload($request));

        return redirect()
            ->route('projects.test-cases.show', [$project, $testCase])
            ->with('success', __('messages.test_cases.updated'));
    }

    public function destroy(Project $project, TestCase $testCase): RedirectResponse
    {
        $this->ensureTestCaseBelongsToProject($project, $testCase);
        $suite = $testCase->testSuite;
        $testCase->delete();

        return redirect()
            ->route('projects.test-suites.show', [$project, $suite])
            ->with('success', __('messages.test_cases.deleted'));
    }

    public function markResult(TestCaseResultRequest $request, Project $project, TestCase $testCase): RedirectResponse
    {
        $this->ensureTestCaseBelongsToProject($project, $testCase);
        $validated = $request->validated();

        $scanResult = null;
        if (! empty($validated['scan_result_id'])) {
            $scanResult = ScanResult::query()
                ->with('scanRun')
                ->whereKey((int) $validated['scan_result_id'])
                ->whereHas('scanRun', fn ($query) => $query->where('project_id', $project->id))
                ->firstOrFail();
        }

        $result = $testCase->results()->create([
            'project_id' => $project->id,
            'scan_run_id' => $scanResult?->scan_run_id,
            'scan_result_id' => $scanResult?->id,
            'status' => $validated['status'],
            'actual_result' => trim((string) ($validated['actual_result'] ?? '')) ?: null,
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            'executed_at' => now(),
        ]);

        $testCase->update([
            'last_run_status' => $result->status,
            'last_run_at' => $result->executed_at,
            'actual_result' => $result->actual_result ?: $testCase->actual_result,
        ]);

        return redirect()
            ->route('projects.test-cases.show', [$project, $testCase])
            ->with('success', __('messages.test_cases.result_recorded'));
    }

    private function payload(TestCaseRequest $request): array
    {
        $validated = $request->validated();

        return [
            'test_suite_id' => $validated['test_suite_id'],
            'endpoint_id' => $validated['endpoint_id'] ?? null,
            'title' => $validated['title'],
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'preconditions' => trim((string) ($validated['preconditions'] ?? '')) ?: null,
            'steps' => $validated['steps'],
            'expected_result' => $validated['expected_result'],
            'actual_result' => trim((string) ($validated['actual_result'] ?? '')) ?: null,
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'status' => $validated['status'],
        ];
    }

    private function availableScanResults(Project $project, TestCase $testCase)
    {
        return ScanResult::query()
            ->with(['endpoint', 'scanRun'])
            ->whereHas('scanRun', fn ($query) => $query->where('project_id', $project->id))
            ->when($testCase->endpoint_id, fn ($query) => $query->where('endpoint_id', $testCase->endpoint_id))
            ->latest()
            ->limit(50)
            ->get();
    }

    private function ensureTestCaseBelongsToProject(Project $project, TestCase $testCase): void
    {
        abort_unless($testCase->project_id === $project->id, 404);
    }
}
