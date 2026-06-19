<?php

namespace App\Http\Controllers;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\EndpointTestBatch;
use App\Models\Environment;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\AuthProfileTesterService;
use App\Services\EndpointAssertionEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EndpointController extends Controller
{
    public function index(Project $project): View
    {
        $endpoints = $project->endpoints()
            ->with(['environment', 'authProfile', 'latestTestRun'])
            ->latest()
            ->get();

        return view('endpoints.index', [
            'project' => $project,
            'endpoints' => $endpoints,
            'environments' => $project->environments()->orderByDesc('is_default')->orderBy('name')->get(),
            'authProfiles' => $project->authProfiles()->orderByDesc('is_default')->orderBy('name')->get(),
            'metrics' => $this->metrics($endpoints),
            'endpointTestResult' => session('endpoint_quick_test_result'),
            'endpointBatchResult' => session('endpoint_batch_test_result'),
            'endpointTestRuns' => $project->endpointTestRuns()
                ->with(['endpoint', 'environment', 'authProfile', 'batch'])
                ->latest('checked_at')
                ->latest()
                ->take(8)
                ->get(),
            'endpointTestBatches' => $project->endpointTestBatches()
                ->withCount('testRuns')
                ->latest('completed_at')
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }

    public function store(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $this->validated($request, $project);
        $endpoint = $project->endpoints()->create($data);

        $auditLogger->record('created', __('messages.audit_messages.endpoint_created'), $project, [
            'endpoint_id' => $endpoint->id,
            'method' => $endpoint->method,
            'path' => $endpoint->path,
            'risk_level' => $endpoint->risk_level,
        ], 'endpoint');

        return redirect()->route('projects.endpoints.index', $project)->with('status', __('messages.endpoints.created'));
    }

    public function update(Request $request, Project $project, Endpoint $endpoint, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $endpoint);
        $before = $endpoint->only(['method', 'path', 'name', 'risk_level', 'auth_required', 'excluded_from_scan', 'is_active']);
        $data = $this->validated($request, $project, $endpoint);

        $endpoint->update($data);

        $auditLogger->record('updated', __('messages.audit_messages.endpoint_updated'), $project, [
            'endpoint_id' => $endpoint->id,
            'before' => $before,
            'after' => $endpoint->only(['method', 'path', 'name', 'risk_level', 'auth_required', 'excluded_from_scan', 'is_active']),
        ], 'endpoint');

        return redirect()->route('projects.endpoints.index', $project)->with('status', __('messages.endpoints.updated'));
    }


    public function test(Project $project, Endpoint $endpoint, AuthProfileTesterService $testerService, EndpointAssertionEvaluationService $assertionService, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $endpoint);

        $endpoint->load(['environment', 'authProfile']);
        $result = $this->buildEndpointQuickTestResult($project, $endpoint, $testerService);
        $result = $this->applyAssertionSummary($project, $endpoint, $result, $assertionService);
        [$testRun, $result] = $this->persistEndpointQuickTestResult($project, $endpoint, $result);

        $auditLogger->record('endpoint_quick_test_completed', __('messages.audit_messages.endpoint_quick_test_completed'), $project, [
            'endpoint_id' => $endpoint->id,
            'method' => $endpoint->method,
            'path' => $endpoint->path,
            'url' => $result['url'] ?? null,
            'state' => $result['state'] ?? 'unknown',
            'status_code' => $result['status_code'] ?? null,
            'response_time_ms' => $result['response_time_ms'] ?? null,
            'test_run_id' => $testRun->id,
        ], 'endpoint');

        return redirect()
            ->route('projects.endpoints.index', $project)
            ->with('status', __('messages.endpoints.quick_test_completed'))
            ->with('endpoint_quick_test_result', $result);
    }

    public function testAll(Project $project, AuthProfileTesterService $testerService, EndpointAssertionEvaluationService $assertionService, AuditLogger $auditLogger): RedirectResponse
    {
        $startedAt = now();
        $endpoints = $project->endpoints()
            ->with(['environment', 'authProfile'])
            ->where('is_active', true)
            ->where('excluded_from_scan', false)
            ->whereIn('method', ['GET', 'HEAD'])
            ->orderBy('id')
            ->get();

        $batch = $project->endpointTestBatches()->create([
            'state' => 'skipped',
            'tone' => 'secondary',
            'total' => $endpoints->count(),
            'started_at' => $startedAt,
        ]);

        $summary = [
            'batch_id' => $batch->id,
            'total' => $endpoints->count(),
            'passed' => 0,
            'warning' => 0,
            'failed' => 0,
            'skipped' => 0,
            'recent_runs' => [],
        ];

        foreach ($endpoints as $endpoint) {
            $result = $this->buildEndpointQuickTestResult($project, $endpoint, $testerService);
            $result = $this->applyAssertionSummary($project, $endpoint, $result, $assertionService);
            [$testRun, $result] = $this->persistEndpointQuickTestResult($project, $endpoint, $result, $batch);
            $state = (string) ($result['state'] ?? 'skipped');

            if (array_key_exists($state, $summary)) {
                $summary[$state]++;
            }

            $summary['recent_runs'][] = [
                'id' => $testRun->id,
                'endpoint_name' => $result['endpoint_name'] ?? __('messages.endpoints.unnamed'),
                'endpoint_path' => $result['endpoint_path'] ?? $endpoint->path,
                'endpoint_method' => $result['endpoint_method'] ?? $endpoint->method,
                'state' => $state,
                'tone' => $result['tone'] ?? 'secondary',
                'status_code' => $result['status_code'] ?? null,
                'checked_at' => $testRun->checked_at?->toDateTimeString(),
            ];
        }

        $summary['recent_runs'] = array_slice(array_reverse($summary['recent_runs']), 0, 6);
        $summary['state'] = $this->batchState($summary);
        $summary['tone'] = $this->batchTone($summary['state']);
        $summary['message'] = $summary['total'] > 0
            ? __('messages.endpoints.batch_test_completed')
            : __('messages.endpoints.batch_test_empty');
        $summary['completed_at'] = now()->toDateTimeString();

        $batch->update([
            'state' => $summary['state'],
            'tone' => $summary['tone'],
            'total' => $summary['total'],
            'passed' => $summary['passed'],
            'warning' => $summary['warning'],
            'failed' => $summary['failed'],
            'skipped' => $summary['skipped'],
            'summary_json' => $summary,
            'completed_at' => now(),
        ]);

        $auditLogger->record('endpoint_batch_quick_test_completed', __('messages.audit_messages.endpoint_batch_quick_test_completed'), $project, [
            'endpoint_test_batch_id' => $batch->id,
            'total' => $summary['total'],
            'passed' => $summary['passed'],
            'warning' => $summary['warning'],
            'failed' => $summary['failed'],
            'skipped' => $summary['skipped'],
        ], 'endpoint');

        return redirect()
            ->route('projects.endpoints.index', $project)
            ->with('status', $summary['message'])
            ->with('endpoint_batch_test_result', $summary);
    }

    public function destroy(Project $project, Endpoint $endpoint, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $endpoint);

        $auditLogger->record('deleted', __('messages.audit_messages.endpoint_deleted'), $project, [
            'endpoint_id' => $endpoint->id,
            'method' => $endpoint->method,
            'path' => $endpoint->path,
        ], 'endpoint', 'warning');

        $endpoint->delete();

        return redirect()->route('projects.endpoints.index', $project)->with('status', __('messages.endpoints.deleted'));
    }


    private function buildEndpointQuickTestResult(Project $project, Endpoint $endpoint, AuthProfileTesterService $testerService): array
    {
        if (! in_array($endpoint->method, ['GET', 'HEAD'], true)) {
            $result = $this->quickTestResult($endpoint, 'skipped', __('messages.endpoints.quick_test_skip_method'));
        } elseif ($endpoint->auth_required && ! $endpoint->authProfile) {
            $result = $this->quickTestResult($endpoint, 'skipped', __('messages.endpoints.quick_test_skip_auth_missing'));
        } else {
            $result = $testerService->test(
                $project,
                $endpoint->environment,
                $endpoint->authProfile,
                $endpoint->method,
                $endpoint->path,
                $endpoint->expected_status,
            );
        }

        $result['endpoint_id'] = $endpoint->id;
        $result['endpoint_name'] = $endpoint->name ?: __('messages.endpoints.unnamed');
        $result['endpoint_path'] = $endpoint->path;
        $result['endpoint_method'] = $endpoint->method;
        $result['expected_content_type'] = $endpoint->expected_content_type;

        if ($endpoint->expected_content_type && ! empty($result['content_type'])) {
            $result['content_type_matched'] = str_contains(
                strtolower((string) $result['content_type']),
                strtolower((string) $endpoint->expected_content_type),
            );

            if ($result['content_type_matched'] === false && ($result['state'] ?? null) === 'passed') {
                $result['state'] = 'warning';
                $result['tone'] = 'warning';
                $result['message'] = __('messages.endpoints.quick_test_content_type_mismatch');
            }
        } else {
            $result['content_type_matched'] = null;
        }

        return $result;
    }

    private function applyAssertionSummary(Project $project, Endpoint $endpoint, array $result, EndpointAssertionEvaluationService $assertionService): array
    {
        $summary = $assertionService->evaluate($project, $endpoint, $result);
        $result['assertion_summary'] = $summary;

        if (($summary['failed'] ?? 0) > 0) {
            $result['state'] = ($summary['has_blocker_failure'] ?? false) ? 'failed' : (($result['state'] ?? 'skipped') === 'passed' ? 'warning' : ($result['state'] ?? 'warning'));
            $result['tone'] = ($result['state'] ?? 'warning') === 'failed' ? 'danger' : 'warning';
            $result['message'] = __('messages.assertions.quick_test_assertion_failures', ['count' => $summary['failed']]);
        }

        return $result;
    }

    private function persistEndpointQuickTestResult(Project $project, Endpoint $endpoint, array $result, ?EndpointTestBatch $batch = null): array
    {
        $testRun = $endpoint->testRuns()->create([
            'project_id' => $project->id,
            'endpoint_test_batch_id' => $batch?->id,
            'environment_id' => $result['environment_id'] ?? $endpoint->environment_id,
            'auth_profile_id' => $result['auth_profile_id'] ?? $endpoint->auth_profile_id,
            'method' => $result['method'] ?? $endpoint->method,
            'url' => $result['url'] ?? $endpoint->path,
            'state' => $result['state'] ?? 'skipped',
            'tone' => $result['tone'] ?? 'secondary',
            'message' => $result['message'] ?? null,
            'expected_status' => $result['expected_status'] ?? $endpoint->expected_status,
            'status_code' => $result['status_code'] ?? null,
            'status_matched' => $result['status_matched'] ?? null,
            'expected_content_type' => $endpoint->expected_content_type,
            'content_type' => $result['content_type'] ?? null,
            'content_type_matched' => $result['content_type_matched'] ?? null,
            'assertion_total' => $result['assertion_summary']['total'] ?? 0,
            'assertion_passed' => $result['assertion_summary']['passed'] ?? 0,
            'assertion_failed' => $result['assertion_summary']['failed'] ?? 0,
            'assertion_summary_json' => $result['assertion_summary'] ?? null,
            'response_time_ms' => $result['response_time_ms'] ?? null,
            'response_size' => $result['response_size'] ?? null,
            'body_preview' => $result['body_preview'] ?? null,
            'checked_at' => now(),
        ]);

        $result['test_run_id'] = $testRun->id;
        $result['checked_at'] = $testRun->checked_at?->toDateTimeString();

        return [$testRun, $result];
    }

    private function quickTestResult(Endpoint $endpoint, string $state, string $message): array
    {
        $tone = match ($state) {
            'passed' => 'success',
            'warning' => 'warning',
            'failed' => 'danger',
            default => 'secondary',
        };

        return [
            'state' => $state,
            'tone' => $tone,
            'message' => $message,
            'method' => $endpoint->method,
            'url' => $endpoint->path,
            'expected_status' => $endpoint->expected_status,
            'status_code' => null,
            'status_matched' => null,
            'response_time_ms' => null,
            'content_type' => null,
            'response_size' => null,
            'body_preview' => '',
            'auth_profile_id' => $endpoint->authProfile?->id,
            'auth_profile_name' => $endpoint->authProfile?->name ?: __('messages.auth_profiles.no_auth_preview'),
            'environment_id' => $endpoint->environment?->id,
            'environment_name' => $endpoint->environment?->name ?: __('messages.auth_profiles.project_base_url'),
            'checked_at' => now()->toDateTimeString(),
        ];
    }


    private function batchState(array $summary): string
    {
        if (($summary['failed'] ?? 0) > 0) {
            return 'failed';
        }

        if ((($summary['warning'] ?? 0) + ($summary['skipped'] ?? 0)) > 0) {
            return 'warning';
        }

        return ($summary['total'] ?? 0) > 0 ? 'passed' : 'skipped';
    }

    private function batchTone(string $state): string
    {
        return match ($state) {
            'passed' => 'success',
            'warning' => 'warning',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    private function validated(Request $request, Project $project, ?Endpoint $endpoint = null): array
    {
        $request->merge([
            'method' => strtoupper((string) $request->input('method', 'GET')),
            'path' => $this->normalizePath((string) $request->input('path', '/')),
        ]);

        $data = $request->validate([
            'method' => ['required', Rule::in(Endpoint::METHODS)],
            'path' => [
                'required',
                'string',
                'max:600',
                Rule::unique('endpoints')->where(fn ($query) => $query->where('project_id', $project->id)->where('method', $request->input('method')))->ignore($endpoint?->id),
            ],
            'name' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'tags' => ['nullable', 'string', 'max:255'],
            'environment_id' => ['nullable', 'integer'],
            'auth_profile_id' => ['nullable', 'integer'],
            'expected_status' => ['nullable', 'integer', 'between:100,599'],
            'expected_content_type' => ['nullable', 'string', 'max:120'],
            'risk_level' => ['required', Rule::in(Endpoint::RISK_LEVELS)],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $data['auth_required'] = $request->boolean('auth_required');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['excluded_from_scan'] = $request->boolean('excluded_from_scan');
        $data['environment_id'] = $this->validProjectEnvironmentId($project, $data['environment_id'] ?? null);
        $data['auth_profile_id'] = $this->validProjectAuthProfileId($project, $data['auth_profile_id'] ?? null);

        if ($data['auth_required'] && ! $data['auth_profile_id']) {
            $data['auth_profile_id'] = $project->defaultAuthProfile()?->id;
        }

        return $data;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parsed = parse_url($path);
            $path = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?'.$parsed['query'] : '');
        }

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }

    private function validProjectEnvironmentId(Project $project, mixed $environmentId): ?int
    {
        if (! $environmentId) {
            return $project->defaultEnvironment()?->id;
        }

        return Environment::query()
            ->where('project_id', $project->id)
            ->where('id', $environmentId)
            ->exists() ? (int) $environmentId : null;
    }

    private function validProjectAuthProfileId(Project $project, mixed $authProfileId): ?int
    {
        if (! $authProfileId) {
            return null;
        }

        return AuthProfile::query()
            ->where('project_id', $project->id)
            ->where('id', $authProfileId)
            ->exists() ? (int) $authProfileId : null;
    }

    private function ensureBelongsToProject(Project $project, Endpoint $endpoint): void
    {
        abort_unless((int) $endpoint->project_id === (int) $project->id, 404);
    }

    private function metrics($endpoints): array
    {
        return [
            'total' => $endpoints->count(),
            'safe' => $endpoints->filter(fn (Endpoint $endpoint) => in_array($endpoint->method, ['GET', 'HEAD'], true) && $endpoint->is_active && ! $endpoint->excluded_from_scan)->count(),
            'auth_required' => $endpoints->where('auth_required', true)->count(),
            'review' => $endpoints->whereIn('risk_level', ['review', 'high', 'critical'])->count(),
            'tested' => $endpoints->filter(fn (Endpoint $endpoint) => $endpoint->latestTestRun !== null)->count(),
            'latest_failed' => $endpoints->filter(fn (Endpoint $endpoint) => in_array($endpoint->latestTestRun?->state, ['failed'], true))->count(),
            'latest_warning' => $endpoints->filter(fn (Endpoint $endpoint) => in_array($endpoint->latestTestRun?->state, ['warning', 'skipped'], true))->count(),
        ];
    }
}
