<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindingRequest;
use App\Models\ContractValidationResult;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\TestCase;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FindingController extends Controller
{
    public function index(Project $project, Request $request, SettingService $settings): View
    {
        $status = (string) $request->query('status', 'open');
        $severity = (string) $request->query('severity', '');
        $source = (string) $request->query('source', '');

        $findings = $project->findings()
            ->with(['endpoint', 'testCase', 'scanRun', 'scanResult', 'contractValidationResult', 'evidence'])
            ->when($status === 'open', fn ($query) => $query->whereIn('status', Finding::OPEN_STATUSES))
            ->when($status !== '' && $status !== 'all' && $status !== 'open', fn ($query) => $query->where('status', $status))
            ->when($severity !== '', fn ($query) => $query->where('severity', $severity))
            ->when($source !== '', fn ($query) => $query->where('source', $source))
            ->latest('detected_at')
            ->paginate($settings->integer('app.items_per_page', 25))
            ->withQueryString();

        $summary = [
            'total' => $project->findings()->count(),
            'open' => $project->findings()->whereIn('status', Finding::OPEN_STATUSES)->count(),
            'critical' => $project->findings()->whereIn('status', Finding::OPEN_STATUSES)->where('severity', Finding::SEVERITY_CRITICAL)->count(),
            'high' => $project->findings()->whereIn('status', Finding::OPEN_STATUSES)->where('severity', Finding::SEVERITY_HIGH)->count(),
        ];

        return view('findings.index', compact('project', 'findings', 'summary', 'status', 'severity', 'source'));
    }

    public function create(Project $project, Request $request): View
    {
        $finding = new Finding($this->prefill($project, $request));
        $this->loadFormData($project);

        return view('findings.create', compact('project', 'finding'));
    }

    public function store(FindingRequest $request, Project $project): RedirectResponse
    {
        $finding = $project->findings()->create($this->payload($request, $project));

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.created'));
    }

    public function show(Project $project, Finding $finding): View
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        $finding->load(['endpoint', 'testCase.testSuite', 'scanRun', 'scanResult.scanRun', 'contractValidationResult.run', 'evidence']);

        return view('findings.show', compact('project', 'finding'));
    }

    public function edit(Project $project, Finding $finding): View
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        $this->loadFormData($project);

        return view('findings.edit', compact('project', 'finding'));
    }

    public function update(FindingRequest $request, Project $project, Finding $finding): RedirectResponse
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        $finding->update($this->payload($request, $project));

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.updated'));
    }

    public function destroy(Project $project, Finding $finding): RedirectResponse
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        $finding->delete();

        return redirect()
            ->route('projects.findings.index', $project)
            ->with('success', __('messages.findings.deleted'));
    }

    /** @return array<string, mixed> */
    private function payload(FindingRequest $request, Project $project): array
    {
        $data = $request->validated();

        foreach (['endpoint_id', 'test_case_id', 'scan_run_id', 'scan_result_id', 'contract_validation_result_id'] as $key) {
            $data[$key] = $data[$key] ? (int) $data[$key] : null;
        }

        $data['endpoint_id'] = $this->belongsToProject(Endpoint::class, $project, $data['endpoint_id']) ? $data['endpoint_id'] : null;
        $data['test_case_id'] = $this->belongsToProject(TestCase::class, $project, $data['test_case_id']) ? $data['test_case_id'] : null;
        $data['scan_run_id'] = $this->belongsToProject(ScanRun::class, $project, $data['scan_run_id']) ? $data['scan_run_id'] : null;
        $data['scan_result_id'] = $this->belongsToProject(ScanResult::class, $project, $data['scan_result_id']) ? $data['scan_result_id'] : null;
        $data['contract_validation_result_id'] = $this->belongsToProject(ContractValidationResult::class, $project, $data['contract_validation_result_id']) ? $data['contract_validation_result_id'] : null;
        $data['detected_at'] = $request->route('finding')?->detected_at ?? now();

        return $data;
    }

    /** @return array<string, mixed> */
    private function prefill(Project $project, Request $request): array
    {
        $data = [
            'source' => $request->query('source', Finding::SOURCE_MANUAL),
            'severity' => $request->query('severity', Finding::SEVERITY_MEDIUM),
            'status' => Finding::STATUS_OPEN,
            'endpoint_id' => $request->query('endpoint_id'),
            'test_case_id' => $request->query('test_case_id'),
            'scan_result_id' => $request->query('scan_result_id'),
            'contract_validation_result_id' => $request->query('contract_validation_result_id'),
        ];

        if ($data['endpoint_id'] && $endpoint = $project->endpoints()->find($data['endpoint_id'])) {
            $data['title'] = $endpoint->method.' '.$endpoint->path.' — '.__('messages.findings.title_placeholder');
            $data['actual_result'] = $endpoint->latestScanResult?->error_message ?: null;
        }

        if ($data['scan_result_id'] && $scanResult = $project->scanResults()->find($data['scan_result_id'])) {
            $data['scan_run_id'] = $scanResult->scan_run_id;
            $data['endpoint_id'] = $scanResult->endpoint_id ?: $data['endpoint_id'];
            $data['source'] = Finding::SOURCE_SCAN;
            $data['actual_result'] = trim(($scanResult->status_code ? 'HTTP '.$scanResult->status_code."\n" : '').($scanResult->error_message ?: '').($scanResult->body_preview ? "\n\n".$scanResult->body_preview : ''));
        }

        if ($data['contract_validation_result_id'] && $contractResult = $project->contractValidationResults()->find($data['contract_validation_result_id'])) {
            $data['endpoint_id'] = $contractResult->endpoint_id ?: $data['endpoint_id'];
            $data['scan_result_id'] = $contractResult->scan_result_id ?: $data['scan_result_id'];
            $data['source'] = Finding::SOURCE_CONTRACT;
            $data['severity'] = in_array($contractResult->severity, Finding::SEVERITIES, true) ? $contractResult->severity : Finding::SEVERITY_HIGH;
            $data['title'] = $contractResult->check_type_label.' — '.($contractResult->method ? $contractResult->method.' ' : '').$contractResult->path;
            $data['description'] = $contractResult->message;
            $data['expected_result'] = $contractResult->expected;
            $data['actual_result'] = $contractResult->actual;
        }

        return $data;
    }

    private function loadFormData(Project $project): void
    {
        $project->load([
            'endpoints' => fn ($query) => $query->orderBy('method')->orderBy('path'),
            'testCases' => fn ($query) => $query->with('testSuite')->orderBy('title'),
            'scanRuns' => fn ($query) => $query->latest()->limit(30),
            'scanRuns.results',
            'contractValidationResults' => fn ($query) => $query->latest()->limit(50),
        ]);
    }

    private function belongsToProject(string $modelClass, Project $project, ?int $id): bool
    {
        if (! $id) {
            return true;
        }

        if ($modelClass === ScanResult::class) {
            return ScanResult::query()
                ->whereKey($id)
                ->whereHas('scanRun', fn ($query) => $query->where('project_id', $project->id))
                ->exists();
        }

        return $modelClass::query()->whereKey($id)->where('project_id', $project->id)->exists();
    }

    private function ensureFindingBelongsToProject(Project $project, Finding $finding): void
    {
        abort_unless($finding->project_id === $project->id, 404);
    }
}
