<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SetupAccessService
{
    public function requiresToken(Request $request): bool
    {
        return ! $this->isLocalRequest($request);
    }

    public function isLocalRequest(Request $request): bool
    {
        $host = strtolower((string) $request->getHost());
        $ip = (string) $request->ip();

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || in_array($ip, ['127.0.0.1', '::1'], true)
            || str_ends_with($host, '.localhost');
    }

    public function tokenPath(): string
    {
        return config('aptoria.setup_token_path', storage_path('app/setup-token.txt'));
    }

    public function configuredToken(bool $createIfMissing = false): ?string
    {
        $envToken = trim((string) env('APTORIA_SETUP_TOKEN', ''));
        if ($envToken !== '') {
            return $envToken;
        }

        if (File::exists($this->tokenPath())) {
            $token = trim((string) File::get($this->tokenPath()));
            return $token !== '' ? $token : null;
        }

        if (! $createIfMissing) {
            return null;
        }

        File::ensureDirectoryExists(dirname($this->tokenPath()));
        $token = Str::random(48);
        File::put($this->tokenPath(), $token.PHP_EOL);

        return $token;
    }

    public function ensureTokenExists(): string
    {
        return $this->configuredToken(true) ?? '';
    }

    public function authorizeRequest(Request $request): bool
    {
        if (! $this->requiresToken($request)) {
            return true;
        }

        $expected = $this->configuredToken(true);
        if ($expected === null || $expected === '') {
            return false;
        }

        $sessionToken = (string) $request->session()->get('aptoria_setup_token', '');
        if ($sessionToken !== '' && hash_equals($expected, $sessionToken)) {
            return true;
        }

        $provided = trim((string) ($request->query('setup_token') ?: $request->input('setup_token') ?: $request->header('X-Aptoria-Setup-Token', '')));
        if ($provided !== '' && hash_equals($expected, $provided)) {
            $request->session()->put('aptoria_setup_token', $expected);
            return true;
        }

        return false;
    }

    public function hasValidAccess(Request $request): bool
    {
        return $this->authorizeRequest($request);
    }

    /** @return array<string,mixed> */
    public function accessContext(Request $request): array
    {
        $requiresToken = $this->requiresToken($request);
        $token = $requiresToken ? $this->configuredToken(true) : null;

        return [
            'is_local' => ! $requiresToken,
            'requires_token' => $requiresToken,
            'has_valid_access' => $this->authorizeRequest($request),
            'token_path' => $this->tokenPath(),
            'token_hint' => $token ? substr($token, 0, 6).'…'.substr($token, -6) : null,
        ];
    }
}
