<?php

namespace App\Services;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Environment;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\Auth\AuthProfileRuntimeService;
use App\Services\Risk\RiskAnalyzer;
use App\Services\Endpoints\PathParameterResolver;
use App\Services\Security\BrokenAuthComparisonService;
use App\Services\Security\NetworkTargetGuard;
use App\Services\Security\SensitiveDataDetector;
use App\Services\Schema\SchemaDriftDetector;
use App\Services\Settings\ProjectSettingService;
use App\Services\Settings\SettingService;
use App\Services\Settings\SettingsRuntimeService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SafeProbeService
{
    /** @var array<int, string> */
    private array $allowedMethods = [Endpoint::METHOD_GET, Endpoint::METHOD_HEAD];

    /** @var array<string, mixed> */
    private array $activeProfile = [];

    private float $scanStartedAt = 0.0;
    private int $failedEndpointCount = 0;
    private int $criticalFindingCount = 0;

    public function __construct(
        private readonly RiskAnalyzer $riskAnalyzer,
        private readonly AuthProfileRuntimeService $authRuntime,
        private readonly PathParameterResolver $pathParameters,
        private readonly SettingService $settings,
        private readonly SettingsRuntimeService $runtime,
        private readonly ProjectSettingService $projectSettings,
        private readonly NetworkTargetGuard $networkGuard,
        private readonly SensitiveDataDetector $sensitiveDetector,
        private readonly BrokenAuthComparisonService $brokenAuthComparison,
        private readonly SchemaDriftDetector $schemaDriftDetector,
    ) {
    }

    public function runProject(Project $project, ?Environment $environment, ?User $user, string $scanProfile = ''): ScanRun
    {
        $profileKey = $scanProfile !== '' ? $scanProfile : $this->runtime->defaultScanProfile();
        $this->activateScanProfile($profileKey);

        $runningScans = ScanRun::query()->where('status', ScanRun::STATUS_RUNNING)->count();
        if ($runningScans >= $this->runtime->maxConcurrentScans()) {
            return ScanRun::query()->create([
                'project_id' => $project->id,
                'environment_id' => $environment?->id,
                'created_by' => $user?->id,
                'status' => ScanRun::STATUS_FAILED,
                'mode' => (string) ($this->activeProfile['mode'] ?? 'safe'),
                'started_at' => now(),
                'finished_at' => now(),
                'error_message' => 'Scan skipped by configured concurrent scan limit.',
            ])->refresh();
        }

        $scanRun = ScanRun::query()->create([
            'project_id' => $project->id,
            'environment_id' => $environment?->id,
            'created_by' => $user?->id,
            'status' => ScanRun::STATUS_RUNNING,
            'mode' => (string) ($this->activeProfile['mode'] ?? 'safe'),
            'started_at' => now(),
        ]);

        try {
            if (! $this->projectSettings->boolean($project, 'scan.enabled', true)) {
                $scanRun->update([
                    'status' => ScanRun::STATUS_FAILED,
                    'finished_at' => now(),
                    'error_message' => __('messages.project_settings.scan_disabled_message'),
                ]);

                return $scanRun->refresh();
            }

            $maxEndpoints = max(1, $this->projectSettings->integer($project, 'scan.max_endpoints_per_scan', (int) ($this->activeProfile['max_endpoints'] ?? $this->settings->integer('scan.max_endpoints_per_scan', 100))));

            $endpoints = $project->endpoints()
                ->with(['environment.authProfile', 'authProfile', 'project'])
                ->where('is_active', true)
                ->where('excluded_from_scan', false)
                ->orderBy('method')
                ->orderBy('path')
                ->limit($maxEndpoints)
                ->get();

            $this->probeEndpoints($scanRun, $project, $environment, $endpoints);
            $this->completeScanRun($scanRun);
        } catch (Throwable $exception) {
            $scanRun->update([
                'status' => ScanRun::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }

        return $scanRun->refresh();
    }

    public function runEndpoint(Project $project, Endpoint $endpoint, ?User $user): ScanRun
    {
        $endpoint->load(['environment.authProfile', 'authProfile', 'project']);

        $scanRun = ScanRun::query()->create([
            'project_id' => $project->id,
            'environment_id' => $endpoint->environment_id,
            'created_by' => $user?->id,
            'status' => ScanRun::STATUS_RUNNING,
            'mode' => 'safe-single',
            'started_at' => now(),
        ]);

        try {
            $this->probeEndpoints($scanRun, $project, $endpoint->environment, new EloquentCollection([$endpoint]));
            $this->completeScanRun($scanRun);
        } catch (Throwable $exception) {
            $scanRun->update([
                'status' => ScanRun::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }

        return $scanRun->refresh();
    }

    /**
     * @param EloquentCollection<int, Endpoint> $endpoints
     */
    private function probeEndpoints(ScanRun $scanRun, Project $project, ?Environment $forcedEnvironment, EloquentCollection $endpoints): void
    {
        $this->scanStartedAt = microtime(true);
        $this->failedEndpointCount = 0;
        $this->criticalFindingCount = 0;

        foreach ($endpoints as $endpoint) {
            if ($this->shouldStopScan($scanRun)) {
                break;
            }

            $this->probeEndpoint($scanRun, $project, $endpoint, $forcedEnvironment);

            $delayMs = (int) ($this->activeProfile['rate_limit_ms'] ?? $this->settings->integer('scan.rate_limit_ms', $this->settings->integer('scan.delay_between_requests_ms', 250)));
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }
    }

    private function probeEndpoint(ScanRun $scanRun, Project $project, Endpoint $endpoint, ?Environment $forcedEnvironment): void
    {
        $this->allowedMethods = $this->runtime->allowedScanMethods();
        $method = strtoupper($endpoint->method);
        $url = $this->buildUrl($project, $endpoint, $forcedEnvironment);
        $displayUrl = $this->authRuntime->maskValue($url);
        $authProfile = $this->authRuntime->resolveForEndpoint($endpoint, $forcedEnvironment);

        if ($this->settings->boolean('probe.safe_methods_only', true) && ! in_array($method, $this->allowedMethods, true)) {
            $this->recordSkipped($scanRun, $endpoint, $method, $displayUrl, __('messages.scans.skip_destructive_method'));
            return;
        }

        if ($this->settings->boolean('probe.block_destructive_methods', true) && ! in_array($method, $this->allowedMethods, true)) {
            $this->recordSkipped($scanRun, $endpoint, $method, $displayUrl, __('messages.scans.skip_destructive_method'));
            return;
        }

        if (! $endpoint->is_active || $endpoint->excluded_from_scan) {
            $this->recordSkipped($scanRun, $endpoint, $method, $displayUrl, __('messages.scans.skip_inactive_or_excluded'));
            return;
        }

        $unresolvedParameters = $this->pathParameters->unresolvedNames($endpoint);
        if ($unresolvedParameters !== []) {
            $this->recordSkipped($scanRun, $endpoint, $method, $displayUrl, __('messages.scans.skip_unresolved_path_parameters', ['parameters' => implode(', ', $unresolvedParameters)]));
            return;
        }

        if (! $this->networkGuard->isValidHttpUrl($url)) {
            $this->recordSkipped($scanRun, $endpoint, $method, $displayUrl, __('messages.scans.skip_invalid_url'));
            return;
        }

        if ($this->isDangerousUrl($url, $endpoint->path)) {
            $this->recordSkipped($scanRun, $endpoint, $method, $displayUrl, 'Blocked by configured dangerous query/path keyword guard.');
            return;
        }

        $allowPrivateNetwork = $this->settings->boolean('probe.allow_private_network_per_project', true)
            && $this->projectSettings->boolean($project, 'scan.allow_private_networks', false);
        $allowLocalhost = $this->settings->boolean('probe.allow_localhost', false);
        if ($this->settings->boolean('scan.block_private_networks', true) && $this->networkGuard->isBlocked($url, $allowPrivateNetwork, $allowLocalhost)) {
            $this->recordSkipped($scanRun, $endpoint, $method, $displayUrl, __('messages.scans.skip_private_network'));
            return;
        }

        $missingAuthReason = $this->authRuntime->missingReason($endpoint, $authProfile);
        if ($missingAuthReason !== null) {
            $this->recordSkipped($scanRun, $endpoint, $method, $displayUrl, $missingAuthReason, $authProfile);
            return;
        }

        $started = microtime(true);

        try {
            $response = $this->request($endpoint, $authProfile, $method, $url);
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            $contentType = $this->firstHeaderValue($response->header('Content-Type'));
            $body = (string) $response->body();
            $responseSize = strlen($body);
            $maxResponseSize = max(1, $this->settings->integer('scan.max_response_size_kb', 2048)) * 1024;
            $rawHeaders = $response->headers();
            $sensitiveData = $this->sensitiveDetector->inspectResponse($body, $rawHeaders, $contentType);
            $headers = $this->settings->boolean('scan.store_response_headers', true) ? $this->authRuntime->maskHeaders($rawHeaders) : null;
            $bodyPreview = ($this->settings->boolean('scan.store_response_body_preview', true) && $this->projectSettings->boolean($project, 'scan.store_response_body_preview', true)) ? $this->preview($body) : null;
            $authPayload = [
                'status_code' => $response->status(),
                'response_time_ms' => $durationMs,
                'content_type' => $contentType,
                'body' => $body,
                'body_preview' => $bodyPreview,
                'sensitive_data' => $sensitiveData,
            ];
            $brokenAuth = $this->runBrokenAuthComparison($project, $endpoint, $authProfile, $method, $url, $authPayload);
            $schemaDrift = $this->schemaDriftDetector->compareEndpointResponse($endpoint, $bodyPreview, $contentType);

            $this->storeResult($scanRun, $endpoint, [
                'method' => $method,
                'url' => $displayUrl,
                'auth_profile_id' => $authProfile?->id,
                'auth_applied' => $this->authRuntime->shouldApply($authProfile),
                'auth_summary' => $this->authRuntime->safeLabel($authProfile),
                'status' => ScanResult::STATUS_COMPLETED,
                'status_code' => $response->status(),
                'response_time_ms' => $durationMs,
                'content_type' => $contentType,
                'response_size' => $responseSize,
                'headers_json' => $headers,
                'body_preview' => $bodyPreview,
                'response_schema_json' => $schemaDrift['schema'] ?? [],
                'sensitive_data_detected' => $sensitiveData['detected'],
                'sensitive_data_count' => $sensitiveData['count'],
                'sensitive_data_summary_json' => $sensitiveData,
                'broken_auth_detected' => (bool) ($brokenAuth['detected'] ?? false),
                'broken_auth_summary_json' => $brokenAuth,
                'schema_drift_detected' => (bool) ($schemaDrift['detected'] ?? false),
                'schema_drift_count' => (int) ($schemaDrift['count'] ?? 0),
                'schema_drift_summary_json' => $schemaDrift,
                'error_message' => $responseSize > $maxResponseSize ? __('messages.scans.response_size_limit_exceeded', ['size' => round($responseSize / 1024, 2), 'limit' => round($maxResponseSize / 1024, 2)]) : null,
                'expected_status_matched' => $endpoint->expected_status ? $response->status() === (int) $endpoint->expected_status : null,
                'expected_content_type_matched' => $endpoint->expected_content_type ? str_contains(strtolower((string) $contentType), strtolower($endpoint->expected_content_type)) : null,
            ]);
        } catch (ConnectionException $exception) {
            if ($method === Endpoint::METHOD_HEAD && $this->settings->boolean('probe.head_fallback_to_get', false)) {
                $this->probeEndpointWithFallbackGet($scanRun, $project, $endpoint, $url, $displayUrl, $authProfile, $started);
                return;
            }

            $this->recordFailed($scanRun, $endpoint, $method, $displayUrl, $exception->getMessage(), (int) round((microtime(true) - $started) * 1000), $authProfile);
        } catch (Throwable $exception) {
            if ($method === Endpoint::METHOD_HEAD && $this->settings->boolean('probe.head_fallback_to_get', false)) {
                $this->probeEndpointWithFallbackGet($scanRun, $project, $endpoint, $url, $displayUrl, $authProfile, $started);
                return;
            }

            $this->recordFailed($scanRun, $endpoint, $method, $displayUrl, $exception->getMessage(), (int) round((microtime(true) - $started) * 1000), $authProfile);
        }
    }


    private function probeEndpointWithFallbackGet(ScanRun $scanRun, Project $project, Endpoint $endpoint, string $url, string $displayUrl, ?AuthProfile $authProfile, float $started): void
    {
        try {
            $response = $this->request($endpoint, $authProfile, Endpoint::METHOD_GET, $url);
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            $contentType = $this->firstHeaderValue($response->header('Content-Type'));
            $body = (string) $response->body();
            $responseSize = strlen($body);
            $maxResponseSize = max(1, $this->settings->integer('scan.max_response_size_kb', 2048)) * 1024;
            $rawHeaders = $response->headers();
            $sensitiveData = $this->sensitiveDetector->inspectResponse($body, $rawHeaders, $contentType);
            $headers = $this->settings->boolean('scan.store_response_headers', true) ? $this->authRuntime->maskHeaders($rawHeaders) : null;
            $bodyPreview = ($this->settings->boolean('scan.store_response_body_preview', true) && $this->projectSettings->boolean($project, 'scan.store_response_body_preview', true)) ? $this->preview($body) : null;
            $authPayload = [
                'status_code' => $response->status(),
                'response_time_ms' => $durationMs,
                'content_type' => $contentType,
                'body' => $body,
                'body_preview' => $bodyPreview,
                'sensitive_data' => $sensitiveData,
            ];
            $brokenAuth = $this->runBrokenAuthComparison($project, $endpoint, $authProfile, Endpoint::METHOD_GET, $url, $authPayload);
            $schemaDrift = $this->schemaDriftDetector->compareEndpointResponse($endpoint, $bodyPreview, $contentType);

            $this->storeResult($scanRun, $endpoint, [
                'method' => Endpoint::METHOD_GET,
                'url' => $displayUrl,
                'auth_profile_id' => $authProfile?->id,
                'auth_applied' => $this->authRuntime->shouldApply($authProfile),
                'auth_summary' => $this->authRuntime->safeLabel($authProfile),
                'status' => ScanResult::STATUS_COMPLETED,
                'status_code' => $response->status(),
                'response_time_ms' => $durationMs,
                'content_type' => $contentType,
                'response_size' => $responseSize,
                'headers_json' => $headers,
                'body_preview' => $bodyPreview,
                'response_schema_json' => $schemaDrift['schema'] ?? [],
                'sensitive_data_detected' => $sensitiveData['detected'],
                'sensitive_data_count' => $sensitiveData['count'],
                'sensitive_data_summary_json' => $sensitiveData,
                'broken_auth_detected' => (bool) ($brokenAuth['detected'] ?? false),
                'broken_auth_summary_json' => $brokenAuth,
                'schema_drift_detected' => (bool) ($schemaDrift['detected'] ?? false),
                'schema_drift_count' => (int) ($schemaDrift['count'] ?? 0),
                'schema_drift_summary_json' => $schemaDrift,
                'error_message' => $responseSize > $maxResponseSize ? __('messages.scans.response_size_limit_exceeded', ['size' => round($responseSize / 1024, 2), 'limit' => round($maxResponseSize / 1024, 2)]) : 'HEAD failed; retried with GET by Settings policy.',
                'expected_status_matched' => $endpoint->expected_status ? $response->status() === (int) $endpoint->expected_status : null,
                'expected_content_type_matched' => $endpoint->expected_content_type ? str_contains(strtolower((string) $contentType), strtolower($endpoint->expected_content_type)) : null,
            ]);
        } catch (Throwable $exception) {
            $this->recordFailed($scanRun, $endpoint, Endpoint::METHOD_GET, $displayUrl, $exception->getMessage(), (int) round((microtime(true) - $started) * 1000), $authProfile);
        }
    }

    private function request(Endpoint $endpoint, ?AuthProfile $authProfile, string $method, string $url, bool $applyAuth = true): mixed
    {
        $userAgent = str_replace(
            '{version}',
            (string) config('aptoria.version'),
            $this->settings->string('scan.user_agent', 'Aptoria/{version}')
        );
        $followRedirects = $this->settings->boolean('scan.follow_redirects', true);
        $allowPrivateNetwork = $endpoint->project instanceof Project
            ? ($this->settings->boolean('probe.allow_private_network_per_project', true)
                && $this->projectSettings->boolean($endpoint->project, 'scan.allow_private_networks', false))
            : false;
        $allowLocalhost = $this->settings->boolean('probe.allow_localhost', false);
        $redirectOptions = $followRedirects ? [
            'max' => $this->settings->integer('scan.max_redirects', 3),
            'on_redirect' => function ($request, $response, $uri) use ($allowPrivateNetwork, $allowLocalhost): void {
                $this->networkGuard->assertAllowed((string) $uri, $allowPrivateNetwork, $allowLocalhost);
            },
        ] : false;

        $pendingRequest = Http::timeout((int) ($this->activeProfile['timeout'] ?? $this->settings->integer('scan.timeout_seconds', 10)))
            ->connectTimeout($this->settings->integer('scan.connect_timeout_seconds', 5))
            ->acceptJson()
            ->withUserAgent($userAgent)
            ->withOptions([
                'allow_redirects' => $redirectOptions,
                'verify' => $this->settings->boolean('scan.verify_ssl', true),
                'http_errors' => false,
            ]);

        $retryCount = $this->settings->integer('scan.retry_count', 0);
        if ($retryCount > 0) {
            $pendingRequest = $pendingRequest->retry($retryCount, $this->settings->integer('scan.retry_delay_ms', 250), null, false);
        }

        if ($applyAuth) {
            $pendingRequest = $this->authRuntime->applyToRequest($pendingRequest, $authProfile);
        }

        return $pendingRequest->send($method, $url);
    }

    private function completeScanRun(ScanRun $scanRun): void
    {
        $results = $scanRun->results()->get();
        $finishedAt = now();
        $startedAt = $scanRun->started_at ?: $finishedAt;

        $scanRun->update([
            'status' => ScanRun::STATUS_COMPLETED,
            'finished_at' => $finishedAt,
            'duration_ms' => max(0, $startedAt->diffInMilliseconds($finishedAt)),
            'total_endpoints' => $results->count(),
            'scanned_count' => $results->where('status', ScanResult::STATUS_COMPLETED)->count(),
            'skipped_count' => $results->where('status', ScanResult::STATUS_SKIPPED)->count(),
            'success_count' => $results->where('status', ScanResult::STATUS_COMPLETED)->filter(fn (ScanResult $result): bool => $result->status_code !== null && $result->status_code < 400)->count(),
            'warning_count' => $results->where('status', ScanResult::STATUS_COMPLETED)->filter(fn (ScanResult $result): bool => $result->status_code !== null && $result->status_code >= 400 && $result->status_code < 500)->count(),
            'error_count' => $results->where('status', ScanResult::STATUS_FAILED)->count() + $results->where('status_code', '>=', 500)->count(),
            'summary_json' => [
                'safe_methods_only' => $this->settings->boolean('probe.safe_methods_only', true),
                'allowed_methods' => $this->allowedMethods,
                'destructive_methods_blocked' => $this->settings->boolean('probe.block_destructive_methods', true),
                'scan_profile' => (string) ($this->activeProfile['label'] ?? 'Safe'),
                'risk_counts' => collect(Endpoint::RISKS)
                    ->mapWithKeys(fn (string $level): array => [$level => $results->where('risk_level', $level)->count()])
                    ->all(),
                'sensitive_data_results' => $results->where('sensitive_data_detected', true)->count(),
                'broken_auth_results' => $results->where('broken_auth_detected', true)->count(),
                'schema_drift_results' => $results->where('schema_drift_detected', true)->count(),
            ],
        ]);
    }

    private function recordSkipped(ScanRun $scanRun, Endpoint $endpoint, string $method, string $url, string $reason, ?AuthProfile $authProfile = null): void
    {
        $authProfile ??= $this->authRuntime->resolveForEndpoint($endpoint);

        $this->storeResult($scanRun, $endpoint, [
            'method' => $method,
            'url' => $url,
            'auth_profile_id' => $authProfile?->id,
            'auth_applied' => false,
            'auth_summary' => $this->authRuntime->safeLabel($authProfile),
            'status' => ScanResult::STATUS_SKIPPED,
            'error_message' => $reason,
        ]);
    }

    private function recordFailed(ScanRun $scanRun, Endpoint $endpoint, string $method, string $url, string $message, ?int $durationMs, ?AuthProfile $authProfile = null): void
    {
        $authProfile ??= $this->authRuntime->resolveForEndpoint($endpoint);

        $this->failedEndpointCount++;

        $this->storeResult($scanRun, $endpoint, [
            'method' => $method,
            'url' => $url,
            'auth_profile_id' => $authProfile?->id,
            'auth_applied' => $this->authRuntime->shouldApply($authProfile),
            'auth_summary' => $this->authRuntime->safeLabel($authProfile),
            'status' => ScanResult::STATUS_FAILED,
            'response_time_ms' => $durationMs,
            'error_message' => Str::limit($this->authRuntime->maskValue($message), 1000),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function storeResult(ScanRun $scanRun, Endpoint $endpoint, array $attributes): ScanResult
    {
        $result = new ScanResult($attributes);
        $analysis = $this->riskAnalyzer->analyze($endpoint, $result);

        $result->endpoint_id = $endpoint->id;
        $result->risk_level = $analysis['final_level'];
        $result->risk_reason = $analysis['explanation'].' '.$analysis['why_it_matters'];
        $scanRun->results()->save($result);

        if ($analysis['final_level'] === Endpoint::RISK_CRITICAL) {
            $this->criticalFindingCount++;
        }

        if ($result->sensitive_data_detected) {
            $this->recordSensitiveDataFinding($scanRun, $endpoint, $result);
        }

        if ($result->broken_auth_detected) {
            $this->recordBrokenAuthFinding($scanRun, $endpoint, $result);
        }

        if ($result->schema_drift_detected && $this->settings->boolean('security.schema_drift_create_findings', true)) {
            $this->recordSchemaDriftFinding($scanRun, $endpoint, $result);
        }

        return $result;
    }


    /**
     * @param array<string, mixed> $authPayload
     * @return array<string, mixed>|null
     */
    private function runBrokenAuthComparison(Project $project, Endpoint $endpoint, ?AuthProfile $authProfile, string $method, string $url, array $authPayload): ?array
    {
        if (! $this->settings->boolean('security.unauthenticated_access_comparison_enabled', true)) {
            return null;
        }

        if (! $endpoint->auth_required || ! $this->authRuntime->shouldApply($authProfile)) {
            return null;
        }

        if (! in_array(strtoupper($method), [Endpoint::METHOD_GET, Endpoint::METHOD_HEAD], true)) {
            return null;
        }

        try {
            $started = microtime(true);
            $response = $this->request($endpoint, null, $method, $url, false);
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            $contentType = $this->firstHeaderValue($response->header('Content-Type'));
            $body = (string) $response->body();
            $headers = $response->headers();
            $sensitiveData = $this->sensitiveDetector->inspectResponse($body, $headers, $contentType);
            $bodyPreview = $this->settings->boolean('security.unauthenticated_access_store_preview', true) ? $this->preview($body) : null;

            return $this->brokenAuthComparison->evaluate($endpoint, $authPayload, [
                'status_code' => $response->status(),
                'response_time_ms' => $durationMs,
                'content_type' => $contentType,
                'body' => $body,
                'body_preview' => $bodyPreview,
                'sensitive_data' => $sensitiveData,
            ]);
        } catch (Throwable $exception) {
            return [
                'checked' => false,
                'detected' => false,
                'reason' => 'no_auth_request_failed',
                'summary' => __('messages.broken_auth.reasons.no_auth_request_failed'),
                'error' => Str::limit($this->authRuntime->maskValue($exception->getMessage()), 500),
            ];
        }
    }


    private function recordSensitiveDataFinding(ScanRun $scanRun, Endpoint $endpoint, ScanResult $result): void
    {
        $summary = is_array($result->sensitive_data_summary_json) ? $result->sensitive_data_summary_json : [];
        $severity = $this->sensitiveDetector->severityForFinding($summary, (bool) $endpoint->auth_required);
        $matches = is_array($summary['matches'] ?? null) ? $summary['matches'] : [];
        $summaryText = (string) ($summary['summary'] ?? __('messages.sensitive_data.finding_summary_fallback'));

        $finding = Finding::query()->create([
            'project_id' => $endpoint->project_id,
            'endpoint_id' => $endpoint->id,
            'scan_run_id' => $scanRun->id,
            'scan_result_id' => $result->id,
            'title' => __('messages.sensitive_data.finding_title', ['method' => $endpoint->method, 'path' => $endpoint->path]),
            'description' => __('messages.sensitive_data.finding_description', [
                'count' => (int) $result->sensitive_data_count,
                'summary' => $summaryText,
            ]),
            'source' => Finding::SOURCE_SCAN,
            'severity' => $severity,
            'status' => Finding::STATUS_OPEN,
            'reproduction_steps' => __('messages.sensitive_data.finding_reproduction', ['url' => $result->url]),
            'expected_result' => __('messages.sensitive_data.finding_expected'),
            'actual_result' => __('messages.sensitive_data.finding_actual', ['summary' => $summaryText]),
            'recommendation' => __('messages.sensitive_data.finding_recommendation'),
            'detected_at' => now(),
        ]);

        FindingEvidence::query()->create([
            'finding_id' => $finding->id,
            'project_id' => $endpoint->project_id,
            'type' => FindingEvidence::TYPE_HTTP,
            'source_label' => __('messages.sensitive_data.evidence_source'),
            'content' => json_encode([
                'summary' => $summaryText,
                'match_count' => (int) $result->sensitive_data_count,
                'matches' => array_slice($matches, 0, 10),
                'http_status' => $result->status_code,
                'content_type' => $result->content_type,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'url' => $result->url,
            'metadata_json' => [
                'scan_run_id' => $scanRun->id,
                'scan_result_id' => $result->id,
                'sensitive_data_count' => (int) $result->sensitive_data_count,
            ],
        ]);
    }

    private function recordBrokenAuthFinding(ScanRun $scanRun, Endpoint $endpoint, ScanResult $result): void
    {
        $summary = is_array($result->broken_auth_summary_json) ? $result->broken_auth_summary_json : [];
        $summaryText = (string) ($summary['summary'] ?? __('messages.broken_auth.finding_summary_fallback'));
        $severity = in_array(($summary['severity'] ?? null), Finding::SEVERITIES, true)
            ? (string) $summary['severity']
            : Finding::SEVERITY_HIGH;

        $finding = Finding::query()->create([
            'project_id' => $endpoint->project_id,
            'endpoint_id' => $endpoint->id,
            'scan_run_id' => $scanRun->id,
            'scan_result_id' => $result->id,
            'title' => __('messages.broken_auth.finding_title', ['method' => $endpoint->method, 'path' => $endpoint->path]),
            'description' => __('messages.broken_auth.finding_description', [
                'summary' => $summaryText,
                'auth_status' => $summary['auth_status_code'] ?? __('messages.common.not_available'),
                'no_auth_status' => $summary['no_auth_status_code'] ?? __('messages.common.not_available'),
            ]),
            'source' => Finding::SOURCE_SCAN,
            'severity' => $severity,
            'status' => Finding::STATUS_OPEN,
            'reproduction_steps' => __('messages.broken_auth.finding_reproduction', ['url' => $result->url]),
            'expected_result' => __('messages.broken_auth.finding_expected'),
            'actual_result' => __('messages.broken_auth.finding_actual', ['summary' => $summaryText]),
            'recommendation' => __('messages.broken_auth.finding_recommendation'),
            'detected_at' => now(),
        ]);

        FindingEvidence::query()->create([
            'finding_id' => $finding->id,
            'project_id' => $endpoint->project_id,
            'type' => FindingEvidence::TYPE_HTTP,
            'source_label' => __('messages.broken_auth.evidence_source'),
            'content' => json_encode([
                'summary' => $summaryText,
                'auth_status_code' => $summary['auth_status_code'] ?? null,
                'no_auth_status_code' => $summary['no_auth_status_code'] ?? null,
                'same_body_fingerprint' => $summary['same_body_fingerprint'] ?? false,
                'no_auth_sensitive_data_detected' => $summary['no_auth_sensitive_data_detected'] ?? false,
                'no_auth_sensitive_data_count' => $summary['no_auth_sensitive_data_count'] ?? 0,
                'no_auth_body_preview' => $summary['no_auth_body_preview'] ?? null,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'url' => $result->url,
            'metadata_json' => [
                'scan_run_id' => $scanRun->id,
                'scan_result_id' => $result->id,
                'reason' => $summary['reason'] ?? null,
            ],
        ]);
    }



    private function recordSchemaDriftFinding(ScanRun $scanRun, Endpoint $endpoint, ScanResult $result): void
    {
        $summary = is_array($result->schema_drift_summary_json) ? $result->schema_drift_summary_json : [];
        $summaryText = (string) ($summary['summary'] ?? __('messages.schema_drift.finding_summary_fallback'));
        $changes = is_array($summary['changes'] ?? null) ? $summary['changes'] : [];
        $severity = in_array(($summary['highest_severity'] ?? null), Finding::SEVERITIES, true)
            ? (string) $summary['highest_severity']
            : Finding::SEVERITY_HIGH;

        $finding = Finding::query()->create([
            'project_id' => $endpoint->project_id,
            'endpoint_id' => $endpoint->id,
            'scan_run_id' => $scanRun->id,
            'scan_result_id' => $result->id,
            'title' => __('messages.schema_drift.finding_title', ['method' => $endpoint->method, 'path' => $endpoint->path]),
            'description' => __('messages.schema_drift.finding_description', [
                'count' => (int) $result->schema_drift_count,
                'summary' => $summaryText,
            ]),
            'source' => Finding::SOURCE_REGRESSION,
            'severity' => $severity,
            'status' => Finding::STATUS_OPEN,
            'reproduction_steps' => __('messages.schema_drift.finding_reproduction', ['url' => $result->url]),
            'expected_result' => __('messages.schema_drift.finding_expected'),
            'actual_result' => __('messages.schema_drift.finding_actual', ['summary' => $summaryText]),
            'recommendation' => __('messages.schema_drift.finding_recommendation'),
            'detected_at' => now(),
        ]);

        FindingEvidence::query()->create([
            'finding_id' => $finding->id,
            'project_id' => $endpoint->project_id,
            'type' => FindingEvidence::TYPE_HTTP,
            'source_label' => __('messages.schema_drift.evidence_source'),
            'content' => json_encode([
                'summary' => $summaryText,
                'change_count' => (int) $result->schema_drift_count,
                'baseline_scan_result_id' => $summary['baseline_scan_result_id'] ?? null,
                'changes' => array_slice($changes, 0, 25),
                'current_schema' => array_slice(is_array($result->response_schema_json) ? $result->response_schema_json : [], 0, 50, true),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'url' => $result->url,
            'metadata_json' => [
                'scan_run_id' => $scanRun->id,
                'scan_result_id' => $result->id,
                'baseline_scan_result_id' => $summary['baseline_scan_result_id'] ?? null,
            ],
        ]);
    }

    private function activateScanProfile(string $profile): void
    {
        $this->activeProfile = $this->runtime->scanProfile($profile);
    }

    private function shouldStopScan(ScanRun $scanRun): bool
    {
        $runtimeLimit = max(0, $this->settings->integer('scan.run_timeout_seconds', 300));
        if ($runtimeLimit > 0 && $this->scanStartedAt > 0 && (microtime(true) - $this->scanStartedAt) > $runtimeLimit) {
            $scanRun->update(['error_message' => 'Scan stopped by configured total runtime limit.']);
            return true;
        }

        $failedLimit = max(0, $this->settings->integer('scan.stop_after_failed_endpoints', 0));
        if ($failedLimit > 0 && $this->failedEndpointCount >= $failedLimit) {
            $scanRun->update(['error_message' => 'Scan stopped by configured failed endpoint limit.']);
            return true;
        }

        $criticalLimit = max(0, $this->settings->integer('scan.stop_after_critical_findings', 0));
        if ($criticalLimit > 0 && $this->criticalFindingCount >= $criticalLimit) {
            $scanRun->update(['error_message' => 'Scan stopped by configured critical finding limit.']);
            return true;
        }

        return false;
    }

    private function isDangerousUrl(string $url, string $path): bool
    {
        if ($this->settings->boolean('probe.block_destructive_path_keywords', true)) {
            foreach ($this->settings->csv('probe.destructive_path_keywords') as $keyword) {
                if ($keyword !== '' && str_contains(strtolower($path), strtolower($keyword))) {
                    return true;
                }
            }
        }

        if (! $this->settings->boolean('probe.block_dangerous_query_keywords', true)) {
            return false;
        }

        $query = (string) parse_url($url, PHP_URL_QUERY);
        if ($query === '') {
            return false;
        }

        parse_str($query, $params);
        $keys = array_map('strtolower', array_keys($params));
        foreach ($this->settings->csv('probe.dangerous_query_keywords') as $keyword) {
            if ($keyword !== '' && in_array(strtolower($keyword), $keys, true)) {
                return true;
            }
        }

        return false;
    }

    private function buildUrl(Project $project, Endpoint $endpoint, ?Environment $forcedEnvironment): string
    {
        return $this->pathParameters->buildUrl($project, $endpoint, $forcedEnvironment);
    }

    private function preview(string $body): string
    {
        $limit = max(1, $this->settings->integer('scan.max_body_preview_kb', 64)) * 1024;
        $preview = substr($body, 0, $limit);

        return $this->authRuntime->maskForStorage($preview);
    }

    private function firstHeaderValue(mixed $value): ?string
    {
        if (is_array($value)) {
            return $value[0] ?? null;
        }

        return is_string($value) ? $value : null;
    }

}
