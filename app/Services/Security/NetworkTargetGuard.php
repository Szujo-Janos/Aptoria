<?php

namespace App\Services\Security;

use InvalidArgumentException;

class NetworkTargetGuard
{
    /** @var array<int, string> */
    private const BLOCKED_HOSTS = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '::1',
    ];

    /** @var array<int, string> */
    private const ALWAYS_BLOCKED_HOSTS = [
        '169.254.169.254',
        'metadata.google.internal',
        'metadata',
        'instance-data',
        '169.254.170.2',
    ];

    /** @var array<int, string> */
    private const BLOCKED_HOST_SUFFIXES = [
        '.localhost',
        '.local',
        '.internal',
    ];

    public function isValidHttpUrl(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        return in_array($scheme, ['http', 'https'], true) && $host !== '';
    }

    public function isBlocked(string $url, bool $allowPrivateNetworks = false, bool $allowLocalhost = false): bool
    {
        if (! $this->isValidHttpUrl($url)) {
            return true;
        }

        $parts = parse_url($url);
        if ((string) ($parts['user'] ?? '') !== '' || (string) ($parts['pass'] ?? '') !== '') {
            return true;
        }

        $host = $this->normalizeHost((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return true;
        }

        if ($this->isAlwaysBlockedHost($host)) {
            return true;
        }

        if ($this->isLocalHostName($host)) {
            return ! $allowLocalhost;
        }

        if ($this->isPrivateOrReservedIp($host)) {
            return ! $allowPrivateNetworks;
        }

        if ($allowPrivateNetworks) {
            return false;
        }

        foreach ($this->resolveHost($host) as $ip) {
            if ($this->isPrivateOrReservedIp($ip)) {
                return true;
            }
        }

        return false;
    }

    public function assertAllowed(string $url, bool $allowPrivateNetworks = false, bool $allowLocalhost = false): void
    {
        if ($this->isBlocked($url, $allowPrivateNetworks, $allowLocalhost)) {
            throw new InvalidArgumentException('Blocked unsafe network target.');
        }
    }

    public function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        $location = trim($location);
        if ($location === '') {
            return $baseUrl;
        }

        if (preg_match('/^https?:\/\//i', $location)) {
            return $location;
        }

        $base = parse_url($baseUrl);
        $scheme = (string) ($base['scheme'] ?? '');
        $host = (string) ($base['host'] ?? '');
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        if ($scheme === '' || $host === '') {
            return $location;
        }

        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }

        if (str_starts_with($location, '/')) {
            return $scheme.'://'.$host.$port.$location;
        }

        $path = (string) ($base['path'] ?? '/');
        $directory = rtrim(str_contains($path, '/') ? substr($path, 0, (int) strrpos($path, '/') + 1) : '/', '/');

        return $scheme.'://'.$host.$port.$directory.'/'.$location;
    }

    /** @return array<int, string> */
    private function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = @gethostbynamel($host) ?: [];
        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];
            foreach ($records as $record) {
                foreach (['ip', 'ipv6'] as $key) {
                    if (isset($record[$key]) && is_string($record[$key])) {
                        $ips[] = $record[$key];
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($ips, fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP) !== false)));
    }

    private function normalizeHost(string $host): string
    {
        return strtolower(trim($host, '[] '));
    }

    private function isAlwaysBlockedHost(string $host): bool
    {
        if (in_array($host, self::ALWAYS_BLOCKED_HOSTS, true)) {
            return true;
        }

        // Block decimal-only IPv4 integer tricks such as 2130706433 (127.0.0.1).
        return preg_match('/^[0-9]{8,}$/', $host) === 1;
    }

    private function isLocalHostName(string $host): bool
    {
        if (in_array($host, self::BLOCKED_HOSTS, true)) {
            return true;
        }

        foreach (self::BLOCKED_HOST_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function isPrivateOrReservedIp(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
