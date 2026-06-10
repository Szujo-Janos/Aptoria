<?php

namespace App\Services\Security;

use App\Services\Settings\SettingService;

class SensitiveValueMasker
{
    private const MASK = '********';

    /** @var array<int, string> */
    private const SENSITIVE_KEYS = [
        'authorization',
        'access_token',
        'refresh_token',
        'client_secret',
        'api_key',
        'apikey',
        'token',
        'secret',
        'password',
        'passwd',
        'pwd',
        'cookie',
        'session',
        'x-api-key',
        'set-cookie',
        'csrf',
        'xsrf',
        'private_key',
        'client_key',
        'id_token',
    ];

    public function __construct(private readonly SettingService $settings)
    {
    }

    public function maskForUi(?string $value): string
    {
        if (! $this->settings->boolean('security.hide_tokens_in_ui', true)) {
            return (string) $value;
        }

        return $this->mask((string) $value);
    }

    public function maskForExport(?string $value): string
    {
        if (! $this->settings->boolean('security.hide_tokens_in_exports', true)) {
            return (string) $value;
        }

        return $this->mask((string) $value);
    }

    public function maskForStorage(?string $value): string
    {
        if (! $this->settings->boolean('scan.mask_secrets', true)) {
            return (string) $value;
        }

        return $this->mask((string) $value);
    }

    public function mask(?string $value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return $value;
        }

        $patterns = [
            // Preserve well-known Authorization schemes instead of collapsing them to "Authorization: ********".
            '/((?:Authorization|authorization)\s*[:=]\s*)(Bearer|Basic)\s+[^\s,;"}\]]+/i',
            '/("authorization"\s*:\s*")(Bearer|Basic)\s+([^"\n]+)(")/i',
            '/(Bearer\s+)[A-Za-z0-9\-._~+\/]+=*/i',
            '/(Basic\s+)[A-Za-z0-9+\/]+=*/i',
            '/\beyJ[A-Za-z0-9_\-]{8,}\.[A-Za-z0-9_\-]{8,}\.[A-Za-z0-9_\-]{8,}\b/',
            '/("?(?:token|api_key|apikey|secret|password|access_token|refresh_token|client_secret|x-api-key|id_token|private_key|client_key|csrf|xsrf)"?\s*[:=]\s*")([^"\n]{4,})(")/i',
            '/([?&](?:token|api_key|apikey|secret|password|access_token|refresh_token|client_secret|x-api-key|id_token|private_key|client_key|csrf|xsrf)=)([^&#\s]+)/i',
            '/((?:token|api_key|apikey|secret|password|access_token|refresh_token|client_secret|x-api-key|id_token|private_key|client_key|csrf|xsrf)\s*[:=]\s*)([^\s,;]{4,})/i',
            '/((?:Authorization|authorization)\s*[:=]\s*+)(?!(?:Bearer|Basic)\s+\*{8}(?:\s|$))([^\r\n,;"}\]]{4,})/i',
            '/("authorization"\s*:\s*")(?!(?:Bearer|Basic)\s+\*{8}")([^"\n]{4,})(")/i',
            '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i',
            '/(?<!\d)(?:\+\d{1,3}[\s.\-]?)?(?:\(?\d{2,4}\)?[\s.\-]?){2,4}\d{2,4}(?!\d)/',
        ];

        $replacements = [
            '$1$2 '.self::MASK,
            '$1$2 '.self::MASK.'$4',
            '$1'.self::MASK,
            '$1'.self::MASK,
            self::MASK,
            '$1'.self::MASK.'$3',
            '$1'.self::MASK,
            '$1'.self::MASK,
            '$1'.self::MASK,
            '$1'.self::MASK.'$3',
            '[email]',
            '[phone]',
        ];

        return preg_replace($patterns, $replacements, $value) ?? $value;
    }

    /** @param array<string, array<int, string>|string> $headers */
    public function maskHeaders(array $headers): array
    {
        $masked = [];

        foreach ($headers as $name => $values) {
            $values = is_array($values) ? $values : [$values];
            if ($this->isSensitiveKey((string) $name)) {
                $masked[$name] = [self::MASK];
                continue;
            }

            $masked[$name] = array_map(fn (mixed $value): string => $this->mask((string) $value), $values);
        }

        return $masked;
    }

    /** @param array<mixed> $data */
    public function maskArray(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $masked[$key] = self::MASK;
                continue;
            }

            $masked[$key] = is_array($value) ? $this->maskArray($value) : $this->mask((string) $value);
        }

        return $masked;
    }

    public function maskedCredential(?string $value): string
    {
        if (! $this->settings->boolean('security.mask_auth_secrets', true)) {
            return (string) $value;
        }

        return filled($value) ? self::MASK : '';
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['_', '-'], '', $key));
        $configuredKeys = array_merge(
            self::SENSITIVE_KEYS,
            $this->settings->csv('security.custom_sensitive_headers'),
            $this->settings->csv('security.custom_sensitive_json_fields')
        );

        if (! $this->settings->boolean('security.mask_authorization_header', true)) {
            $configuredKeys = array_values(array_filter($configuredKeys, fn (string $item): bool => ! in_array(strtolower($item), ['authorization'], true)));
        }
        if (! $this->settings->boolean('security.mask_cookie_header', true)) {
            $configuredKeys = array_values(array_filter($configuredKeys, fn (string $item): bool => ! in_array(strtolower($item), ['cookie'], true)));
        }
        if (! $this->settings->boolean('security.mask_set_cookie_header', true)) {
            $configuredKeys = array_values(array_filter($configuredKeys, fn (string $item): bool => ! in_array(strtolower($item), ['set-cookie'], true)));
        }

        foreach ($configuredKeys as $sensitiveKey) {
            $needle = str_replace(['_', '-'], '', strtolower((string) $sensitiveKey));
            if ($needle !== '' && str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
