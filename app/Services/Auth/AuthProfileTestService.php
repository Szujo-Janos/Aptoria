<?php

namespace App\Services\Auth;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\Project;
use App\Services\Endpoints\PathParameterResolver;
use App\Services\Security\NetworkTargetGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AuthProfileTestService
{
    public function __construct(
        private readonly AuthProfileRuntimeService $authRuntime,
        private readonly NetworkTargetGuard $networkGuard,
        private readonly PathParameterResolver $pathResolver,
    ) {
    }

    /**
     * @param  array{test_target:string,test_method?:string|null,test_url?:string|null,test_endpoint_id?:int|string|null}  $payload
     * @return array<string, mixed>
     */
    public function run(Project $project, AuthProfile $profile, array $payload): array
    {
        $target = $this->resolveTarget($project, $payload);
        $method = $target['method'];
        $url = $target['url'];

        $baseResult = [
            'profile_name' => $profile->name,
            'profile_type' => $profile->type_label,
            'auth_summary' => $this->authRuntime->maskedSummary($profile),
            'auth_applied' => $this->authRuntime->shouldApply($profile),
            'method' => $method,
            'url' => $this->authRuntime->maskValue($url),
            'target_label' => $target['label'],
            'status' => null,
            'duration_ms' => null,
            'content_type' => null,
            'response_preview' => null,
            'response_headers' => [],
            'ok' => false,
            'style' => 'danger',
            'status_label' => __('messages.auth_profiles.test_status_failed'),
            'message' => __('messages.auth_profiles.test_failed_generic'),
        ];

        if (! in_array($method, ['GET', 'HEAD'], true)) {
            return array_merge($baseResult, [
                'message' => __('messages.auth_profiles.test_unsafe_method'),
            ]);
        }

        if ($this->networkGuard->isBlocked($url, false, false)) {
            return array_merge($baseResult, [
                'message' => __('messages.auth_profiles.test_private_blocked'),
            ]);
        }

        if (! $this->authRuntime->isComplete($profile)) {
            return array_merge($baseResult, [
                'style' => 'warning',
                'status_label' => __('messages.auth_profiles.test_status_incomplete'),
                'message' => __('messages.auth_profiles.test_incomplete'),
            ]);
        }

        try {
            $pendingRequest = Http::timeout(10)
                ->connectTimeout(5)
                ->acceptJson()
                ->withUserAgent('Aptoria/'.config('aptoria.version').' Auth Profile Test')
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 3,
                        'on_redirect' => function ($request, $response, $uri): void {
                            $this->networkGuard->assertAllowed((string) $uri, false, false);
                        },
                    ],
                    'verify' => true,
                    'http_errors' => false,
                ]);

            $pendingRequest = $this->authRuntime->applyToRequest($pendingRequest, $profile);

            $started = microtime(true);
            $response = $pendingRequest->send($method, $url);
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            $status = $response->status();
            $passed = $status >= 200 && $status < 400;
            $unauthorized = in_array($status, [401, 403], true);

            return array_merge($baseResult, [
                'ok' => $passed,
                'style' => $passed ? 'success' : ($unauthorized ? 'danger' : 'warning'),
                'status_label' => $passed
                    ? __('messages.auth_profiles.test_status_passed')
                    : ($unauthorized ? __('messages.auth_profiles.test_status_failed') : __('messages.auth_profiles.test_status_review')),
                'message' => $passed
                    ? __('messages.auth_profiles.test_success', ['status' => $status, 'time' => $durationMs])
                    : __('messages.auth_profiles.test_http_failure', ['status' => $status, 'time' => $durationMs]),
                'status' => $status,
                'duration_ms' => $durationMs,
                'content_type' => $response->header('Content-Type') ?: __('messages.common.not_available'),
                'response_preview' => $this->previewBody($method, $response->body()),
                'response_headers' => $this->previewHeaders($response->headers()),
            ]);
        } catch (ConnectionException $exception) {
            return array_merge($baseResult, [
                'message' => __('messages.auth_profiles.test_connection_failed', [
                    'message' => $this->authRuntime->maskValue($exception->getMessage()),
                ]),
            ]);
        } catch (Throwable $exception) {
            return array_merge($baseResult, [
                'message' => __('messages.auth_profiles.test_failed', [
                    'message' => $this->authRuntime->maskValue($exception->getMessage()),
                ]),
            ]);
        }
    }

    /**
     * @param  array{test_target:string,test_method?:string|null,test_url?:string|null,test_endpoint_id?:int|string|null}  $payload
     * @return array{method:string,url:string,label:string}
     */
    private function resolveTarget(Project $project, array $payload): array
    {
        if (($payload['test_target'] ?? 'custom') === 'endpoint' && filled($payload['test_endpoint_id'] ?? null)) {
            $endpoint = $project->endpoints()->whereKey((int) $payload['test_endpoint_id'])->first();
            if ($endpoint instanceof Endpoint) {
                return [
                    'method' => strtoupper((string) $endpoint->method),
                    'url' => $this->pathResolver->buildUrl($project, $endpoint),
                    'label' => $endpoint->method.' '.$endpoint->path,
                ];
            }
        }

        return [
            'method' => strtoupper((string) ($payload['test_method'] ?? 'GET')),
            'url' => (string) ($payload['test_url'] ?? $project->base_url),
            'label' => __('messages.auth_profiles.custom_test_target'),
        ];
    }

    private function previewBody(string $method, string $body): string
    {
        if ($method === 'HEAD') {
            return __('messages.auth_profiles.test_head_no_body');
        }

        $body = trim($this->authRuntime->maskValue($body));
        if ($body === '') {
            return __('messages.auth_profiles.test_empty_body');
        }

        return Str::limit($body, 2000, '…');
    }

    /**
     * @param  array<string, array<int, string>|string>  $headers
     * @return array<string, string>
     */
    private function previewHeaders(array $headers): array
    {
        $masked = $this->authRuntime->maskHeaders($headers);
        $preview = [];

        foreach ($masked as $name => $values) {
            $values = is_array($values) ? $values : [$values];
            $preview[(string) $name] = Str::limit(implode(', ', array_map('strval', $values)), 180, '…');
        }

        ksort($preview);

        return array_slice($preview, 0, 8, true);
    }
}
