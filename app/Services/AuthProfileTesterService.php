<?php

namespace App\Services;

use App\Models\AuthProfile;
use App\Models\Environment;
use App\Models\Project;
use App\Models\ProjectSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AuthProfileTesterService
{
    public function __construct(
        private readonly NetworkTargetGuard $targetGuard,
        private readonly SensitiveValueMasker $masker,
    ) {}

    public function test(Project $project, ?Environment $environment, ?AuthProfile $authProfile, string $method, string $path, ?int $expectedStatus = null): array
    {
        $method = strtoupper($method);
        $url = $this->buildUrl($project, $environment, $path);

        if (! in_array($method, ['GET', 'HEAD'], true)) {
            return $this->result('skipped', __('messages.auth_profiles.test_skip_method'), $method, $url, $expectedStatus);
        }

        if (! $url) {
            return $this->result('skipped', __('messages.auth_profiles.test_skip_url'), $method, null, $expectedStatus);
        }

        if (preg_match('/\{[^}]+\}/', $url)) {
            return $this->result('skipped', __('messages.auth_profiles.test_skip_path_parameter'), $method, $url, $expectedStatus);
        }

        if ($authProfile && (int) $authProfile->project_id !== (int) $project->id) {
            return $this->result('skipped', __('messages.auth_profiles.test_skip_foreign_auth'), $method, $url, $expectedStatus);
        }

        if ($environment && (int) $environment->project_id !== (int) $project->id) {
            return $this->result('skipped', __('messages.auth_profiles.test_skip_foreign_environment'), $method, $url, $expectedStatus);
        }

        if ($authProfile && $authProfile->secret_needs_rotation) {
            return $this->result('skipped', __('messages.auth_profiles.test_skip_rotation'), $method, $url, $expectedStatus, $authProfile, $environment);
        }

        $headers = $authProfile ? $authProfile->runtimeHeaders() : [];
        if ($authProfile && $authProfile->type !== 'none' && $headers === []) {
            return $this->result('skipped', __('messages.auth_profiles.test_skip_missing_secret'), $method, $url, $expectedStatus, $authProfile, $environment);
        }

        $guard = $this->targetGuard->inspect($url, $this->allowPrivateNetworks($project));
        if (! $guard['allowed']) {
            return $this->result('skipped', (string) $guard['reason'], $method, $url, $expectedStatus, $authProfile, $environment);
        }

        $started = microtime(true);

        try {
            $request = Http::timeout($this->timeoutSeconds($project))
                ->connectTimeout(min($this->timeoutSeconds($project), 5))
                ->withoutRedirecting()
                ->withHeaders(array_merge([
                    'Accept' => 'application/json, text/plain, */*',
                    'User-Agent' => 'Aptoria-AuthTester/0.0.18',
                ], $headers));

            $response = $method === 'HEAD' ? $request->head($url) : $request->get($url);
            $duration = (int) round((microtime(true) - $started) * 1000);
            $actualStatus = $response->status();
            $statusMatched = $expectedStatus ? $actualStatus === $expectedStatus : null;
            $state = $this->stateFor($actualStatus, $statusMatched);

            return [
                'state' => $state['state'],
                'tone' => $state['tone'],
                'message' => $state['message'],
                'method' => $method,
                'url' => $url,
                'expected_status' => $expectedStatus,
                'status_code' => $actualStatus,
                'status_matched' => $statusMatched,
                'response_time_ms' => $duration,
                'content_type' => $response->header('Content-Type'),
                'response_size' => strlen($response->body()),
                'body_preview' => $method === 'HEAD' ? '' : $this->masker->mask(Str::limit($response->body(), 1500, '…')),
                'auth_profile_id' => $authProfile?->id,
                'auth_profile_name' => $authProfile?->name ?: __('messages.auth_profiles.no_auth_preview'),
                'environment_id' => $environment?->id,
                'environment_name' => $environment?->name ?: __('messages.auth_profiles.project_base_url'),
                'checked_at' => now()->toDateTimeString(),
            ];
        } catch (ConnectionException $exception) {
            return $this->result('failed', __('messages.auth_profiles.test_connection_failed').': '.$exception->getMessage(), $method, $url, $expectedStatus, $authProfile, $environment);
        } catch (Throwable $exception) {
            return $this->result('failed', $exception->getMessage(), $method, $url, $expectedStatus, $authProfile, $environment);
        }
    }

    private function buildUrl(Project $project, ?Environment $environment, string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $baseUrl = trim((string) ($environment?->base_url ?: $project->base_url));
        if ($baseUrl === '') {
            return null;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function result(string $state, string $message, string $method, ?string $url, ?int $expectedStatus, ?AuthProfile $authProfile = null, ?Environment $environment = null): array
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
            'method' => $method,
            'url' => $url,
            'expected_status' => $expectedStatus,
            'status_code' => null,
            'status_matched' => null,
            'response_time_ms' => null,
            'content_type' => null,
            'response_size' => null,
            'body_preview' => '',
            'auth_profile_id' => $authProfile?->id,
            'auth_profile_name' => $authProfile?->name ?: __('messages.auth_profiles.no_auth_preview'),
            'environment_id' => $environment?->id,
            'environment_name' => $environment?->name ?: __('messages.auth_profiles.project_base_url'),
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    private function stateFor(int $statusCode, ?bool $statusMatched): array
    {
        if ($statusMatched === false) {
            return [
                'state' => 'warning',
                'tone' => 'warning',
                'message' => __('messages.auth_profiles.test_status_mismatch'),
            ];
        }

        if ($statusCode >= 200 && $statusCode < 400) {
            return [
                'state' => 'passed',
                'tone' => 'success',
                'message' => __('messages.auth_profiles.test_passed'),
            ];
        }

        if ($statusCode === 401 || $statusCode === 403) {
            return [
                'state' => 'failed',
                'tone' => 'danger',
                'message' => __('messages.auth_profiles.test_auth_failed'),
            ];
        }

        if ($statusCode >= 400) {
            return [
                'state' => 'warning',
                'tone' => 'warning',
                'message' => __('messages.auth_profiles.test_http_warning'),
            ];
        }

        return [
            'state' => 'warning',
            'tone' => 'warning',
            'message' => __('messages.auth_profiles.test_review'),
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
