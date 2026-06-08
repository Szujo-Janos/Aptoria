<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SetupAccessService
{
    public function isLocalRequest(Request $request): bool
    {
        $ip = (string) $request->ip();
        $host = strtolower((string) $request->getHost());

        return in_array($ip, ['127.0.0.1', '::1'], true)
            || in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)
            || str_ends_with($host, '.localhost');
    }

    public function tokenPath(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'setup-token.txt');
    }

    public function configuredToken(bool $createIfMissing = false): ?string
    {
        $envToken = trim((string) env('APTORIA_SETUP_TOKEN', ''));
        if ($envToken !== '') {
            return $this->isUsableToken($envToken) ? $envToken : null;
        }

        $path = $this->tokenPath();
        if (is_file($path)) {
            $token = trim((string) file_get_contents($path));
            return $this->isUsableToken($token) ? $token : null;
        }

        if (! $createIfMissing) {
            return null;
        }

        $directory = dirname($path);
        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $token = Str::random(max(48, (int) config('aptoria.setup_token_min_length', 32)));
        @file_put_contents($path, $token.PHP_EOL);

        if (! is_file($path)) {
            return null;
        }

        $storedToken = trim((string) file_get_contents($path));

        return $this->isUsableToken($storedToken) ? $storedToken : null;
    }

    public function authorizeRequest(Request $request): bool
    {
        if (app()->environment('testing') || $this->isLocalRequest($request)) {
            return true;
        }

        $sessionToken = (string) $request->session()->get('aptoria_setup_token', '');
        if ($sessionToken !== '' && $this->isValidToken($sessionToken, false)) {
            return true;
        }

        $token = (string) ($request->query('setup_token') ?: $request->input('setup_token', ''));
        if ($token !== '' && $this->isValidToken($token, true)) {
            $request->session()->put('aptoria_setup_token', $token);
            return true;
        }

        return false;
    }

    public function isValidToken(string $submittedToken, bool $createIfMissing = true): bool
    {
        $configuredToken = $this->configuredToken($createIfMissing);
        if (! is_string($configuredToken) || $configuredToken === '') {
            return false;
        }

        return hash_equals($configuredToken, $submittedToken);
    }

    public function isUsableToken(?string $token): bool
    {
        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        $minimumLength = max(16, (int) config('aptoria.setup_token_min_length', 32));
        if (strlen($token) < $minimumLength) {
            return false;
        }

        $placeholders = array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            (array) config('aptoria.setup_token_placeholder_values', [])
        );

        return ! in_array(strtolower($token), $placeholders, true);
    }
}
