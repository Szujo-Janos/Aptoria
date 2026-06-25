<?php

namespace App\Services;

class NetworkTargetGuard
{
    public function inspect(string $url, bool $allowPrivateNetworks = false): array
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? null;
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return $this->blocked('invalid_scheme', __('messages.safe_scan.guard_invalid_scheme'));
        }

        if (! is_string($host) || trim($host) === '') {
            return $this->blocked('missing_host', __('messages.safe_scan.guard_missing_host'));
        }

        $host = trim($host, '[]');

        if ($this->demoModeRestrictsTargets()) {
            $allowedTarget = $this->isAllowedDemoTarget($host);
            if (! $allowedTarget) {
                return $this->blocked('demo_target_not_allowed', __('messages.demo_mode.target_not_allowed'));
            }

            return ['allowed' => true, 'reason' => null, 'code' => null, 'host' => $host];
        }

        if ($allowPrivateNetworks) {
            return ['allowed' => true, 'reason' => null, 'code' => null, 'host' => $host];
        }

        if ($this->isLocalHost($host)) {
            return $this->blocked('localhost_blocked', __('messages.safe_scan.guard_localhost'));
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP) && ! $this->isPublicIp($ip)) {
            return $this->blocked('private_network_blocked', __('messages.safe_scan.guard_private_network'));
        }

        return ['allowed' => true, 'reason' => null, 'code' => null, 'host' => $host];
    }

    private function blocked(string $code, string $reason): array
    {
        return ['allowed' => false, 'reason' => $reason, 'code' => $code, 'host' => null];
    }

    private function demoModeRestrictsTargets(): bool
    {
        return (bool) config('aptoria.demo.mode', false) && count((array) config('aptoria.demo.allowed_targets', [])) > 0;
    }

    private function isAllowedDemoTarget(string $host): bool
    {
        $host = strtolower(trim($host));

        foreach ((array) config('aptoria.demo.allowed_targets', []) as $allowed) {
            $allowed = strtolower(trim((string) $allowed));
            if ($allowed === '') {
                continue;
            }

            $allowedHost = parse_url(str_contains($allowed, '://') ? $allowed : 'https://'.$allowed, PHP_URL_HOST) ?: $allowed;
            $allowedHost = strtolower(trim((string) $allowedHost, '[]'));

            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                return true;
            }
        }

        return false;
    }

    private function isLocalHost(string $host): bool
    {
        $host = strtolower($host);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.localhost');
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
