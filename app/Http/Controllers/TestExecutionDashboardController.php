<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ScanResult;
use App\Models\TestCase;
use App\Models\TestCaseResult;
use App\Services\Settings\SettingService;
use App\Services\TestExecutionDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TestExecutionDashboardController extends Controller
{
    public function index(Project $project, SettingService $settings, TestExecutionDashboardService $execution): View
    {
        $suiteId = request()->integer('suite_id') ?: null;
        $status = trim((string) request('status', ''));
        $priority = trim((string) request('priority', ''));
        $type = trim((string) request('type', ''));

        $testCases = $project->testCases()
            ->with(['testSuite', 'endpoint', 'latestResult.scanResult.scanRun', 'findings'])
            ->when($suiteId, fn ($query) => $query->where('test_suite_id', $suiteId))
            ->when($status !== '', function ($query) use ($status): void {
                if ($status === TestCase::RUN_NOT_RUN) {
                    $query->where(function ($nested): void {
                        $nested->whereNull('last_run_status')
                            ->orWhere('last_run_status', TestCase::RUN_NOT_RUN);
                    });
                } else {
                    $query->where('last_run_status', $status);
                }
            })
            ->when($priority !== '', fn ($query) => $query->where('priority', $priority))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderByRaw("CASE COALESCE(last_run_status, 'not_run') WHEN 'fail' THEN 0 WHEN 'blocked' THEN 1 WHEN 'not_run' THEN 2 WHEN 'skipped' THEN 3 WHEN 'pass' THEN 4 ELSE 5 END")
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('title')
            ->paginate($settings->integer('app.items_per_page', 25))
            ->withQueryString();

        $suites = $project->testSuites()->orderBy('name')->get();
        $summary = $execution->summarize($project);
        $suiteSummaries = $execution->suiteSummaries($project);
        $recentResults = $project->testCaseResults()
            ->with(['testCase.testSuite', 'testCase.endpoint', 'scanResult.scanRun'])
            ->latest('executed_at')
            ->limit(20)
            ->get();

        return view('test_execution.index', compact(
            'project',
            'testCases',
            'suites',
            'summary',
            'suiteSummaries',
            'recentResults',
            'suiteId',
            'status',
            'priority',
            'type'
        ));
    }

    public function markResult(Request $request, Project $project, TestCase $testCase): RedirectResponse
    {
        abort_unless($testCase->project_id === $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(TestCaseResult::STATUSES)],
            'actual_result' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'scan_result_id' => ['nullable', 'integer'],
        ]);

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
            ->route('projects.test-execution.index', $project)
            ->with('success', __('messages.test_execution.result_recorded'));
    }
}
