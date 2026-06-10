<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Project;
use App\Models\TestSuite;
use App\Services\AssertionEvaluationService;
use App\Services\SafeProbeService;
use App\Services\TestSuites\RegressionSuiteBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegressionSuiteBuilderController extends Controller
{
    public function create(Project $project): View
    {
        $endpoints = $project->endpoints()
            ->with(['environment', 'authProfile', 'assertionRules'])
            ->orderByRaw("CASE method WHEN 'GET' THEN 0 WHEN 'HEAD' THEN 1 WHEN 'POST' THEN 2 WHEN 'PUT' THEN 3 WHEN 'PATCH' THEN 4 WHEN 'DELETE' THEN 5 ELSE 6 END")
            ->orderBy('path')
            ->get();

        return view('test_suites.builder', [
            'project' => $project,
            'endpoints' => $endpoints,
            'defaultName' => __('messages.regression_builder.default_suite_name', ['date' => now()->format('Y-m-d')]),
        ]);
    }

    public function store(Request $request, Project $project, RegressionSuiteBuilderService $builder): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'endpoint_ids' => ['required', 'array', 'min:1'],
            'endpoint_ids.*' => ['integer'],
            'priority' => ['required', Rule::in(\App\Models\TestCase::PRIORITIES)],
            'expected_status' => ['nullable', 'integer', 'min:100', 'max:599'],
            'include_status_assertions' => ['nullable', 'boolean'],
            'include_json_path_assertions' => ['nullable', 'boolean'],
            'required_json_paths' => ['nullable', 'string', 'max:4000'],
        ]);

        $suite = $builder->build($project, $validated);

        return redirect()
            ->route('projects.test-suites.show', [$project, $suite])
            ->with('success', __('messages.regression_builder.created', [
                'cases' => $suite->testCases()->count(),
            ]));
    }

    public function run(
        Request $request,
        Project $project,
        TestSuite $testSuite,
        RegressionSuiteBuilderService $builder,
        SafeProbeService $safeProbe,
        AssertionEvaluationService $assertions
    ): RedirectResponse {
        abort_unless($testSuite->project_id === $project->id, 404);

        $summary = $builder->runSuite($project, $testSuite, $request->user(), $safeProbe, $assertions);

        return redirect()
            ->route('projects.test-suites.show', [$project, $testSuite])
            ->with('success', __('messages.regression_builder.run_completed', [
                'total' => $summary['total'],
                'pass' => $summary['pass'],
                'fail' => $summary['fail'],
                'blocked' => $summary['blocked'],
                'skipped' => $summary['skipped'],
            ]));
    }
}
