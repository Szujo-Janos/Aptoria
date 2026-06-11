<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindingRequest;
use App\Models\ContractValidationResult;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\QaReleaseGate;
use App\Models\User;
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
        $verification = (string) $request->query('verification', '');
        $owner = (string) $request->query('owner', '');
        $due = (string) $request->query('due', '');

        $findings = $project->findings()
            ->with(['endpoint', 'testCase', 'scanRun', 'scanResult', 'contractValidationResult', 'evidence.capturedBy', 'lifecycleChangedBy', 'owner', 'verifiedBy', 'linkedReleaseGate'])
            ->when($status === 'open', fn ($query) => $query->whereIn('status', Finding::OPEN_STATUSES))
            ->when($status !== '' && $status !== 'all' && $status !== 'open', fn ($query) => $query->where('status', $status))
            ->when($severity !== '', fn ($query) => $query->where('severity', $severity))
            ->when($source !== '', fn ($query) => $query->where('source', $source))
            ->when($verification !== '', fn ($query) => $query->where('verification_status', $verification))
            ->when($owner !== '', fn ($query) => $owner === 'unassigned' ? $query->whereNull('owner_user_id') : $query->where('owner_user_id', (int) $owner))
            ->when($due === 'overdue', fn ($query) => $query->whereNotNull('due_date')->where('due_date', '<', now())->whereNotIn('verification_status', [Finding::VERIFICATION_VERIFIED, Finding::VERIFICATION_NOT_REQUIRED]))
            ->latest('detected_at')
            ->paginate($settings->integer('app.items_per_page', 25))
            ->withQueryString();

        $statusCounts = $project->findings()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'total' => $project->findings()->count(),
            'open' => $project->findings()->whereIn('status', Finding::OPEN_STATUSES)->count(),
            'critical' => $project->findings()->whereIn('status', Finding::OPEN_STATUSES)->where('severity', Finding::SEVERITY_CRITICAL)->count(),
            'high' => $project->findings()->whereIn('status', Finding::OPEN_STATUSES)->where('severity', Finding::SEVERITY_HIGH)->count(),
            'fixed' => (int) ($statusCounts[Finding::STATUS_FIXED] ?? 0),
            'accepted_risk' => (int) ($statusCounts[Finding::STATUS_ACCEPTED_RISK] ?? 0),
            'false_positive' => (int) ($statusCounts[Finding::STATUS_FALSE_POSITIVE] ?? 0),
            'reopened' => (int) ($statusCounts[Finding::STATUS_REOPENED] ?? 0),
            'ready_for_retest' => (int) ($statusCounts[Finding::STATUS_READY_FOR_RETEST] ?? 0),
            'retest_failed' => (int) ($statusCounts[Finding::STATUS_RETEST_FAILED] ?? 0),
            'verified' => (int) ($statusCounts[Finding::STATUS_VERIFIED] ?? 0),
            'overdue' => $project->findings()->whereNotNull('due_date')->where('due_date', '<', now())->whereNotIn('verification_status', [Finding::VERIFICATION_VERIFIED, Finding::VERIFICATION_NOT_REQUIRED])->count(),
            'status_counts' => collect(Finding::LIFECYCLE_STATUSES)->mapWithKeys(fn (string $rowStatus): array => [$rowStatus => (int) ($statusCounts[$rowStatus] ?? 0)])->all(),
        ];
        $owners = User::query()->orderBy('name')->get();

        return view('findings.index', compact('project', 'findings', 'summary', 'status', 'severity', 'source', 'verification', 'owner', 'due', 'owners'));
    }

    public function create(Project $project, Request $request): View
    {
        $finding = new Finding($this->prefill($project, $request));
        $formOptions = $this->loadFormData($project);

        return view('findings.create', array_merge(compact('project', 'finding'), $formOptions));
    }

    public function store(FindingRequest $request, Project $project): RedirectResponse
    {
        $finding = $project->findings()->create($this->payload($request, $project));
        $finding->forceFill([
            'lifecycle_changed_at' => now(),
            'lifecycle_changed_by_user_id' => $request->user()?->id,
        ])->save();
        $this->recordLifecycleEvent($request, $project, $finding, null, $finding->status, __('messages.findings.lifecycle.created_note'));

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.created'));
    }

    public function show(Project $project, Finding $finding): View
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        $finding->load(['endpoint', 'testCase.testSuite', 'scanRun', 'scanResult.scanRun', 'contractValidationResult.run', 'evidence.capturedBy', 'lifecycleEvents.user', 'lifecycleChangedBy', 'owner', 'verifiedBy', 'linkedReleaseGate', 'comments.user', 'riskAcceptances.acceptedBy', 'riskAcceptances.renewedFrom']);

        return view('findings.show', compact('project', 'finding'));
    }

    public function edit(Project $project, Finding $finding): View
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        $formOptions = $this->loadFormData($project);

        return view('findings.edit', array_merge(compact('project', 'finding'), $formOptions));
    }

    public function update(FindingRequest $request, Project $project, Finding $finding): RedirectResponse
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        $fromStatus = $finding->status;
        $finding->update($this->payload($request, $project));

        if ($fromStatus !== $finding->status) {
            $finding->forceFill([
                'lifecycle_changed_at' => now(),
                'lifecycle_changed_by_user_id' => $request->user()?->id,
            ])->save();
            $this->recordLifecycleEvent($request, $project, $finding, $fromStatus, $finding->status, $finding->lifecycle_note);
        }

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.updated'));
    }

    public function transition(Request $request, Project $project, Finding $finding): RedirectResponse
    {
        $this->ensureFindingBelongsToProject($project, $finding);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', Finding::LIFECYCLE_STATUSES)],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);

        $fromStatus = $finding->status;
        $toStatus = (string) $data['status'];
        $allowed = array_keys($finding->availableLifecycleTransitions());

        if ($toStatus !== $fromStatus && ! in_array($toStatus, $allowed, true)) {
            return back()->withErrors(['status' => __('messages.findings.lifecycle.invalid_transition')]);
        }

        $transitionPayload = [
            'status' => $toStatus,
            'lifecycle_note' => $data['note'] ?? null,
            'lifecycle_changed_at' => now(),
            'lifecycle_changed_by_user_id' => $request->user()?->id,
            'reopened_count' => $toStatus === Finding::STATUS_REOPENED && $fromStatus !== Finding::STATUS_REOPENED
                ? ((int) $finding->reopened_count) + 1
                : (int) $finding->reopened_count,
        ];

        if ($toStatus === Finding::STATUS_VERIFIED) {
            $transitionPayload['verified_by_user_id'] = $request->user()?->id;
            $transitionPayload['verified_at'] = now();
            $transitionPayload['last_retest_at'] = now();
        } elseif ($toStatus === Finding::STATUS_RETEST_FAILED) {
            $transitionPayload['last_retest_at'] = now();
        }

        $finding->forceFill($transitionPayload)->save();

        $this->recordLifecycleEvent($request, $project, $finding, $fromStatus, $toStatus, $data['note'] ?? null);

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.lifecycle.updated'));
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

        foreach (['owner_user_id', 'endpoint_id', 'test_case_id', 'linked_release_gate_id', 'scan_run_id', 'scan_result_id', 'contract_validation_result_id', 'verified_by_user_id'] as $key) {
            $data[$key] = ($data[$key] ?? null) ? (int) $data[$key] : null;
        }

        $data['owner_user_id'] = $this->belongsToUser($data['owner_user_id']) ? $data['owner_user_id'] : null;
        $data['verified_by_user_id'] = $this->belongsToUser($data['verified_by_user_id']) ? $data['verified_by_user_id'] : null;
        $data['endpoint_id'] = $this->belongsToProject(Endpoint::class, $project, $data['endpoint_id']) ? $data['endpoint_id'] : null;
        $data['test_case_id'] = $this->belongsToProject(TestCase::class, $project, $data['test_case_id']) ? $data['test_case_id'] : null;
        $data['linked_release_gate_id'] = $this->belongsToProject(QaReleaseGate::class, $project, $data['linked_release_gate_id']) ? $data['linked_release_gate_id'] : null;
        $data['scan_run_id'] = $this->belongsToProject(ScanRun::class, $project, $data['scan_run_id']) ? $data['scan_run_id'] : null;
        $data['scan_result_id'] = $this->belongsToProject(ScanResult::class, $project, $data['scan_result_id']) ? $data['scan_result_id'] : null;
        $data['contract_validation_result_id'] = $this->belongsToProject(ContractValidationResult::class, $project, $data['contract_validation_result_id']) ? $data['contract_validation_result_id'] : null;
        $data['retest_required'] = $request->boolean('retest_required');
        $data['fix_evidence_required'] = $request->boolean('fix_evidence_required');
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
            'priority' => Finding::PRIORITY_MEDIUM,
            'verification_status' => Finding::VERIFICATION_PENDING,
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

    /** @return array<string, mixed> */
    private function loadFormData(Project $project): array
    {
        $project->load([
            'endpoints' => fn ($query) => $query->orderBy('method')->orderBy('path'),
            'testCases' => fn ($query) => $query->with('testSuite')->orderBy('title'),
            'scanRuns' => fn ($query) => $query->latest()->limit(30),
            'scanRuns.results',
            'contractValidationResults' => fn ($query) => $query->latest()->limit(50),
        ]);

        return [
            'users' => User::query()->orderBy('name')->get(),
            'releaseGates' => $project->qaReleaseGates()->latest()->limit(50)->get(),
        ];
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

    private function belongsToUser(?int $id): bool
    {
        return ! $id || User::query()->whereKey($id)->exists();
    }

    private function recordLifecycleEvent(Request $request, Project $project, Finding $finding, ?string $fromStatus, string $toStatus, ?string $note = null): void
    {
        $finding->lifecycleEvents()->create([
            'project_id' => $project->id,
            'user_id' => $request->user()?->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'changed_at' => now(),
        ]);
    }

    private function ensureFindingBelongsToProject(Project $project, Finding $finding): void
    {
        abort_unless($finding->project_id === $project->id, 404);
    }
}
