<?php

namespace App\Services\Security;

use App\Models\Endpoint;
use App\Models\Finding;

class BrokenAuthComparisonService
{
    /**
     * @param array<string, mixed> $auth
     * @param array<string, mixed> $noAuth
     * @return array<string, mixed>
     */
    public function evaluate(Endpoint $endpoint, array $auth, array $noAuth): array
    {
        $authStatus = $this->nullableInt($auth['status_code'] ?? null);
        $noAuthStatus = $this->nullableInt($noAuth['status_code'] ?? null);
        $authBody = (string) ($auth['body'] ?? '');
        $noAuthBody = (string) ($noAuth['body'] ?? '');
        $noAuthSensitive = (bool) (($noAuth['sensitive_data']['detected'] ?? false));
        $sameBody = $this->sameBodyFingerprint($authBody, $noAuthBody);
        $unauthorizedDenied = in_array($noAuthStatus, [401, 403], true);
        $noAuthSuccessful = $noAuthStatus !== null && $noAuthStatus >= 200 && $noAuthStatus < 400;

        $reason = 'no_auth_denied';
        $detected = false;
        $severity = Finding::SEVERITY_LOW;

        if (! $endpoint->auth_required) {
            $reason = 'endpoint_not_marked_auth_required';
        } elseif ($noAuthSuccessful && $noAuthSensitive) {
            $reason = 'no_auth_sensitive_success';
            $detected = true;
            $severity = Finding::SEVERITY_CRITICAL;
        } elseif ($noAuthSuccessful && $sameBody) {
            $reason = 'no_auth_same_success_response';
            $detected = true;
            $severity = Finding::SEVERITY_CRITICAL;
        } elseif ($noAuthSuccessful) {
            $reason = 'no_auth_success';
            $detected = true;
            $severity = Finding::SEVERITY_HIGH;
        } elseif ($noAuthSensitive) {
            $reason = 'no_auth_sensitive_error_response';
            $detected = true;
            $severity = Finding::SEVERITY_HIGH;
        } elseif ($unauthorizedDenied) {
            $reason = 'no_auth_denied';
        } elseif ($noAuthStatus === null) {
            $reason = 'no_auth_request_failed';
        } else {
            $reason = 'no_auth_not_successful';
        }

        return [
            'checked' => true,
            'detected' => $detected,
            'reason' => $reason,
            'summary' => __('messages.broken_auth.reasons.'.$reason),
            'severity' => $severity,
            'auth_status_code' => $authStatus,
            'no_auth_status_code' => $noAuthStatus,
            'auth_response_time_ms' => $this->nullableInt($auth['response_time_ms'] ?? null),
            'no_auth_response_time_ms' => $this->nullableInt($noAuth['response_time_ms'] ?? null),
            'auth_content_type' => $auth['content_type'] ?? null,
            'no_auth_content_type' => $noAuth['content_type'] ?? null,
            'same_body_fingerprint' => $sameBody,
            'no_auth_sensitive_data_detected' => $noAuthSensitive,
            'no_auth_sensitive_data_count' => (int) ($noAuth['sensitive_data']['count'] ?? 0),
            'no_auth_sensitive_data_summary' => $noAuth['sensitive_data']['summary'] ?? null,
            'no_auth_body_preview' => $noAuth['body_preview'] ?? null,
            'auth_body_preview' => $auth['body_preview'] ?? null,
        ];
    }

    private function sameBodyFingerprint(string $left, string $right): bool
    {
        $left = $this->normalizeBody($left);
        $right = $this->normalizeBody($right);

        return $left !== '' && $right !== '' && hash('sha256', $left) === hash('sha256', $right);
    }

    private function normalizeBody(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return (string) json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        }

        return preg_replace('/\s+/', ' ', $body) ?: $body;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
