<?php

namespace App\Services;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\Environment;
use App\Models\Project;
use App\Models\ProjectSetting;
use App\Models\ScanRun;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SafeProbeService
{
    public function __construct(
        private readonly NetworkTargetGuard $targetGuard,
        private readonly SensitiveValueMasker $masker,
    ) {}

    public function run(Project $project, ?Environment $selectedEnvironment = null, ?AuthProfile $selectedAuthProfile = null): ScanRun
    {
        $started = microtime(true);
        $run = $project->scanRuns()->create([
            'environment_id' => $selectedEnvironment?->id,
            'auth_profile_id' => $selectedAuthProfile?->id,
            'profile' => 'safe',
            'status' => 'running',
            'started_at' => now(),
            'summary_json' => ['total' => 0, 'passed' => 0, 'warning' => 0, 'failed' => 0, 'skipped' => 0],
        ]);

        try {
            $endpoints = $project->endpoints()
                ->with(['environment', 'authProfile'])
                ->where('is_active', true)
                ->where('excluded_from_scan', false)
                ->latest()
                ->get();

            foreach ($endpoints as $endpoint) {
                $this->probeEndpoint($run, $project, $endpoint, $selectedEnvironment, $selectedAuthProfile);
            }

            $summary = $this->summarize($run);
            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'summary_json' => $summary,
            ]);
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'error_message' => $exception->getMessage(),
                'summary_json' => $this->summarize($run),
            ]);
        }

        return $run->fresh(['results.endpoint', 'environment', 'authProfile']);
    }

    private function probeEndpoint(ScanRun $run, Project $project, Endpoint $endpoint, ?Environment $selectedEnvironment, ?AuthProfile $selectedAuthProfile): void
    {
        $environment = $endpoint->environment ?: $selectedEnvironment ?: $project->defaultEnvironment();
        $authProfile = $endpoint->authProfile ?: $selectedAuthProfile ?: $project->defaultAuthProfile();
        $url = $this->buildUrl($endpoint, $environment, $project);

        if (! in_array($endpoint->method, ['GET', 'HEAD'], true)) {
            $this->storeSkipped($run, $project, $endpoint, $environment, $authProfile, $url, __('messages.safe_scan.skip_method'));
            return;
        }

        if (! $url) {
            $this->storeSkipped($run, $project, $endpoint, $environment, $authProfile, null, __('messages.safe_scan.skip_url'));
            return;
        }

        if (preg_match('/\{[^}]+\}/', $url)) {
            $this->storeSkipped($run, $project, $endpoint, $environment, $authProfile, $url, __('messages.safe_scan.skip_path_parameter'));
            return;
        }

        if ($endpoint->auth_required && ! $authProfile) {
            $this->storeSkipped($run, $project, $endpoint, $environment, null, $url, __('messages.safe_scan.skip_auth_missing'));
            return;
        }

        if ($authProfile && $authProfile->secret_needs_rotation) {
            $this->storeSkipped($run, $project, $endpoint, $environment, $authProfile, $url, __('messages.safe_scan.skip_auth_rotation'));
            return;
        }

        $guard = $this->targetGuard->inspect($url, $this->allowPrivateNetworks($project));
        if (! $guard['allowed']) {
            $this->storeSkipped($run, $project, $endpoint, $environment, $authProfile, $url, (string) $guard['reason']);
            return;
        }

        $headers = $authProfile ? $authProfile->runtimeHeaders() : [];
        if ($endpoint->auth_required && $authProfile && $headers === []) {
            $this->storeSkipped($run, $project, $endpoint, $environment, $authProfile, $url, __('messages.safe_scan.skip_auth_missing'));
            return;
        }

        $started = microtime(true);

        try {
            $request = Http::timeout($this->timeoutSeconds($project))
                ->connectTimeout(min($this->timeoutSeconds($project), 5))
                ->withoutRedirecting()
                ->withHeaders(array_merge([
                    'Accept' => 'application/json, text/plain, */*',
                    'User-Agent' => 'Aptoria-SafeProbe/0.0.7',
                ], $headers));

            $response = $endpoint->method === 'HEAD'
                ? $request->head($url)
                : $request->get($url);

            $duration = (int) round((microtime(true) - $started) * 1000);
            $body = $endpoint->method === 'HEAD' ? '' : $this->masker->mask(Str::limit($response->body(), 3000, '…'));
            $contentType = $response->header('Content-Type');
            $statusMatched = $endpoint->expected_status ? $response->status() === (int) $endpoint->expected_status : null;
            $contentTypeMatched = $endpoint->expected_content_type
                ? Str::contains(strtolower((string) $contentType), strtolower($endpoint->expected_content_type))
                : null;
            $risk = $this->riskFor($response->status(), $statusMatched, $contentTypeMatched);

            $run->results()->create([
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'environment_id' => $environment?->id,
                'auth_profile_id' => $authProfile?->id,
                'method' => $endpoint->method,
                'url' => $url,
                'status' => $risk['status'],
                'status_code' => $response->status(),
                'response_time_ms' => $duration,
                'content_type' => $contentType,
                'response_size' => strlen($response->body()),
                'headers_json' => $this->safeHeaders($response->headers()),
                'body_preview' => $body,
                'expected_status_matched' => $statusMatched,
                'expected_content_type_matched' => $contentTypeMatched,
                'risk_level' => $risk['level'],
                'risk_reason' => $risk['reason'],
            ]);
        } catch (ConnectionException $exception) {
            $this->storeFailed($run, $project, $endpoint, $environment, $authProfile, $url, __('messages.safe_scan.connection_failed').': '.$exception->getMessage());
        } catch (Throwable $exception) {
            $this->storeFailed($run, $project, $endpoint, $environment, $authProfile, $url, $exception->getMessage());
        }
    }

    private function buildUrl(Endpoint $endpoint, ?Environment $environment, Project $project): ?string
    {
        $baseUrl = trim((string) ($environment?->base_url ?: $project->base_url));
        $path = trim((string) $endpoint->path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if ($baseUrl === '') {
            return null;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function storeSkipped(ScanRun $run, Project $project, Endpoint $endpoint, ?Environment $environment, ?AuthProfile $authProfile, ?string $url, string $reason): void
    {
        $run->results()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'environment_id' => $environment?->id,
            'auth_profile_id' => $authProfile?->id,
            'method' => $endpoint->method,
            'url' => $url ?: $endpoint->path,
            'status' => 'skipped',
            'risk_level' => 'low',
            'risk_reason' => $reason,
            'error_message' => $reason,
        ]);
    }

    private function storeFailed(ScanRun $run, Project $project, Endpoint $endpoint, ?Environment $environment, ?AuthProfile $authProfile, string $url, string $reason): void
    {
        $run->results()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'environment_id' => $environment?->id,
            'auth_profile_id' => $authProfile?->id,
            'method' => $endpoint->method,
            'url' => $url,
            'status' => 'failed',
            'risk_level' => 'review',
            'risk_reason' => $reason,
            'error_message' => $reason,
        ]);
    }

    private function riskFor(int $statusCode, ?bool $statusMatched, ?bool $contentTypeMatched): array
    {
        if ($statusCode >= 500) {
            return ['status' => 'failed', 'level' => 'high', 'reason' => __('messages.safe_scan.risk_server_error')];
        }

        if ($statusCode >= 400) {
            return ['status' => 'warning', 'level' => 'review', 'reason' => __('messages.safe_scan.risk_client_error')];
        }

        if ($statusMatched === false || $contentTypeMatched === false) {
            return ['status' => 'warning', 'level' => 'review', 'reason' => __('messages.safe_scan.risk_expectation_mismatch')];
        }

        return ['status' => 'passed', 'level' => 'low', 'reason' => __('messages.safe_scan.risk_ok')];
    }

    private function safeHeaders(array $headers): array
    {
        $masked = [];
        foreach ($headers as $key => $value) {
            $lower = strtolower((string) $key);
            if (str_contains($lower, 'authorization') || str_contains($lower, 'cookie') || str_contains($lower, 'token') || str_contains($lower, 'key')) {
                $masked[$key] = ['••••••'];
                continue;
            }
            $masked[$key] = array_map(fn ($item) => $this->masker->mask((string) $item), (array) $value);
        }

        return $masked;
    }

    private function summarize(ScanRun $run): array
    {
        $counts = $run->results()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return [
            'total' => array_sum($counts),
            'passed' => (int) ($counts['passed'] ?? 0),
            'warning' => (int) ($counts['warning'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
            'skipped' => (int) ($counts['skipped'] ?? 0),
        ];
    }

    private function allowPrivateNetworks(Project $project): bool
    {
        return ProjectSetting::get($project, 'scan.allow_private_networks', '0') === '1';
    }

    private function timeoutSeconds(Project $project): int
    {
        $value = (int) ProjectSetting::get($project, 'scan.timeout_seconds', '10');

        return max(2, min($value, 30));
    }
}
