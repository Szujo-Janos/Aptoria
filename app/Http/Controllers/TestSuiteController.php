<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestSuiteRequest;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\TestSuite;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TestSuiteController extends Controller
{
    public function index(Project $project, SettingService $settings): View
    {
        $suites = $project->testSuites()
            ->withCount('testCases')
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('test_suites.index', compact('project', 'suites'));
    }

    public function create(Project $project): View
    {
        return view('test_suites.create', [
            'project' => $project,
            'testSuite' => new TestSuite([
                'project_id' => $project->id,
                'status' => TestSuite::STATUS_ACTIVE,
            ]),
        ]);
    }

    public function store(TestSuiteRequest $request, Project $project): RedirectResponse
    {
        $testSuite = $project->testSuites()->create($request->validated());

        return redirect()
            ->route('projects.test-suites.show', [$project, $testSuite])
            ->with('success', __('messages.test_suites.created'));
    }

    public function show(Project $project, TestSuite $testSuite): View
    {
        $this->ensureSuiteBelongsToProject($project, $testSuite);

        $testSuite->load([
            'testCases' => fn ($query) => $query->with(['endpoint', 'latestResult'])->orderBy('execution_order')->orderBy('id'),
        ]);

        $summary = $this->summary($testSuite->testCases);

        return view('test_suites.show', compact('project', 'testSuite', 'summary'));
    }

    public function edit(Project $project, TestSuite $testSuite): View
    {
        $this->ensureSuiteBelongsToProject($project, $testSuite);

        return view('test_suites.edit', compact('project', 'testSuite'));
    }

    public function update(TestSuiteRequest $request, Project $project, TestSuite $testSuite): RedirectResponse
    {
        $this->ensureSuiteBelongsToProject($project, $testSuite);
        $testSuite->update($request->validated());

        return redirect()
            ->route('projects.test-suites.show', [$project, $testSuite])
            ->with('success', __('messages.test_suites.updated'));
    }

    public function destroy(Project $project, TestSuite $testSuite): RedirectResponse
    {
        $this->ensureSuiteBelongsToProject($project, $testSuite);
        $testSuite->delete();

        return redirect()
            ->route('projects.test-suites.index', $project)
            ->with('success', __('messages.test_suites.deleted'));
    }

    /** @param Collection<int, TestCase> $testCases */
    private function summary(Collection $testCases): array
    {
        $statuses = [
            TestCase::RUN_PASS => 0,
            TestCase::RUN_FAIL => 0,
            TestCase::RUN_BLOCKED => 0,
            TestCase::RUN_SKIPPED => 0,
            TestCase::RUN_NOT_RUN => 0,
        ];

        foreach ($testCases as $testCase) {
            $status = $testCase->last_run_status ?: TestCase::RUN_NOT_RUN;
            $statuses[$status] = ($statuses[$status] ?? 0) + 1;
        }

        return [
            'total' => $testCases->count(),
            'statuses' => $statuses,
        ];
    }

    private function ensureSuiteBelongsToProject(Project $project, TestSuite $testSuite): void
    {
        abort_unless($testSuite->project_id === $project->id, 404);
    }
}
