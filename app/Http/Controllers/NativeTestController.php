<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\TestRun;
use App\Models\TestSuite;
use App\Services\AuditLogger;
use App\Services\NativeTestEvidenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NativeTestController extends Controller
{
    public function index(Project $project): View
    {
        $suites = $project->testSuites()
            ->withCount(['cases', 'runs'])
            ->latest()
            ->get();

        $runs = $project->testRuns()->get();
        $totalRuns = $runs->count();
        $passRuns = $runs->where('status', 'pass')->count();

        return view('native_tests.index', [
            'project' => $project,
            'suites' => $suites,
            'endpoints' => $project->endpoints()->orderBy('method')->orderBy('path')->get(),
            'metrics' => [
                'suites' => $suites->count(),
                'cases' => $project->testCases()->count(),
                'runs' => $totalRuns,
                'pass_rate' => $totalRuns > 0 ? round(($passRuns / $totalRuns) * 100) : 0,
                'failed' => $runs->where('status', 'fail')->count(),
            ],
        ]);
    }

    public function createSuite(Project $project): View
    {
        return view('native_tests.create-suite', ['project' => $project]);
    }

    public function storeSuite(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(TestSuite::STATUSES)],
            'priority' => ['required', Rule::in(TestSuite::PRIORITIES)],
            'owner_name' => ['nullable', 'string', 'max:180'],
        ]);

        $suite = $project->testSuites()->create($data + ['created_by_user_id' => $request->user()?->id]);

        $auditLogger->record('created', __('messages.audit_messages.test_suite_created'), $project, [
            'test_suite_id' => $suite->id,
            'name' => $suite->name,
        ], 'test', 'info');

        return redirect()->route('projects.tests.suites.show', [$project, $suite])->with('status', __('messages.native_tests.suite_created'));
    }

    public function showSuite(Project $project, TestSuite $testSuite): View
    {
        $this->ensureSuiteBelongsToProject($project, $testSuite);

        $testSuite->load(['createdBy']);
        $cases = $testSuite->cases()->with(['endpoint', 'latestRun'])->withCount('runs')->latest()->get();
        $runs = $testSuite->runs()->latest('executed_at')->with(['testCase', 'evidence', 'finding'])->limit(20)->get();

        return view('native_tests.show-suite', [
            'project' => $project,
            'suite' => $testSuite,
            'cases' => $cases,
            'runs' => $runs,
            'endpoints' => $project->endpoints()->orderBy('method')->orderBy('path')->get(),
        ]);
    }

    public function createCase(Project $project, TestSuite $testSuite): View
    {
        $this->ensureSuiteBelongsToProject($project, $testSuite);

        return view('native_tests.create-case', [
            'project' => $project,
            'suite' => $testSuite,
            'endpoints' => $project->endpoints()->orderBy('method')->orderBy('path')->get(),
        ]);
    }

    public function storeCase(Request $request, Project $project, TestSuite $testSuite, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureSuiteBelongsToProject($project, $testSuite);
        $data = $this->validateCase($request, $project);

        $case = $testSuite->cases()->create($data + [
            'project_id' => $project->id,
            'created_by_user_id' => $request->user()?->id,
            'source' => 'native',
        ]);

        $auditLogger->record('created', __('messages.audit_messages.test_case_created'), $project, [
            'test_suite_id' => $testSuite->id,
            'test_case_id' => $case->id,
            'title' => $case->title,
        ], 'test', 'info');

        return redirect()->route('projects.tests.cases.show', [$project, $case])->with('status', __('messages.native_tests.case_created'));
    }

    public function showCase(Project $project, TestCase $testCase): View
    {
        $this->ensureCaseBelongsToProject($project, $testCase);
        $testCase->load(['suite', 'endpoint', 'createdBy', 'evidence']);

        return view('native_tests.show-case', [
            'project' => $project,
            'case' => $testCase,
            'runs' => $testCase->runs()->with(['executedBy', 'evidence', 'finding'])->latest('executed_at')->get(),
        ]);
    }

    public function createRun(Project $project, TestCase $testCase): View
    {
        $this->ensureCaseBelongsToProject($project, $testCase);
        $testCase->load(['suite', 'endpoint']);

        return view('native_tests.create-run', [
            'project' => $project,
            'case' => $testCase,
        ]);
    }

    public function storeRun(Request $request, Project $project, TestCase $testCase, NativeTestEvidenceService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureCaseBelongsToProject($project, $testCase);

        $data = $request->validate([
            'status' => ['required', Rule::in(TestRun::STATUSES)],
            'executed_at' => ['nullable', 'date'],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
            'environment_label' => ['nullable', 'string', 'max:180'],
            'actual_result' => ['nullable', 'string', 'max:8000'],
            'failure_summary' => ['nullable', 'string', 'max:5000'],
            'evidence_summary' => ['nullable', 'string', 'max:5000'],
            'create_finding' => ['nullable', 'boolean'],
            'finding_title' => ['nullable', 'string', 'max:180'],
            'finding_severity' => ['nullable', Rule::in(Finding::SEVERITIES)],
            'finding_priority' => ['nullable', Rule::in(Finding::PRIORITIES)],
        ]);

        if (($data['status'] ?? null) !== 'fail') {
            $data['create_finding'] = false;
        }

        $result = $service->recordRun($project, $testCase->load('suite'), $data, $request->user());

        $auditLogger->record('created', __('messages.audit_messages.test_run_recorded'), $project, [
            'test_case_id' => $testCase->id,
            'test_run_id' => $result['run']->id,
            'finding_evidence_id' => $result['evidence']->id,
            'status' => $result['run']->status,
        ], 'test', $result['run']->status === 'fail' ? 'warning' : 'info');

        return redirect()->route('projects.tests.cases.show', [$project, $testCase])->with('status', __('messages.native_tests.run_recorded'));
    }

    private function validateCase(Request $request, Project $project): array
    {
        $data = $request->validate([
            'endpoint_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'preconditions' => ['nullable', 'string', 'max:8000'],
            'steps' => ['nullable', 'string', 'max:12000'],
            'expected_result' => ['nullable', 'string', 'max:8000'],
            'type' => ['required', Rule::in(TestCase::TYPES)],
            'priority' => ['required', Rule::in(TestCase::PRIORITIES)],
            'status' => ['required', Rule::in(TestCase::STATUSES)],
            'tags' => ['nullable', 'string', 'max:500'],
            'external_reference' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($data['endpoint_id'])) {
            abort_unless($project->endpoints()->whereKey($data['endpoint_id'])->exists(), 404);
        }

        return $data;
    }

    private function ensureSuiteBelongsToProject(Project $project, TestSuite $suite): void
    {
        abort_unless((int) $suite->project_id === (int) $project->id, 404);
    }

    private function ensureCaseBelongsToProject(Project $project, TestCase $case): void
    {
        abort_unless((int) $case->project_id === (int) $project->id, 404);
    }
}
