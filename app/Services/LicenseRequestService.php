<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LicenseRequestService
{
    public function __construct(private readonly LicenseFingerprintService $fingerprints, private readonly LicenseGuardService $licenses)
    {
    }

    public function build(?User $user = null): array
    {
        $fingerprints = $this->fingerprints->current();

        return [
            'request_format' => 'aptoria-license-request-v1',
            'product' => 'aptoria',
            'version' => (string) config('aptoria.version'),
            'edition_request' => $this->defaultEdition(),
            'generated_at' => Carbon::now()->toIso8601String(),
            'runtime' => [
                'os_family' => PHP_OS_FAMILY,
                'php_version' => PHP_VERSION,
                'php_sapi' => PHP_SAPI,
                'app_url' => (string) config('app.url'),
                'base_path_hash' => 'sha256:'.hash('sha256', $this->normalizePath(base_path())),
            ],
            'license' => [
                'enforcement_required' => $this->licenses->isEnforced(),
                'license_mode' => $this->licenses->licenseMode(),
                'license_file_path' => $this->licenses->licenseFilePath(),
                'public_key_configured' => $this->licenses->publicKeyConfigured(),
                'public_key_path' => $this->licenses->publicKeyPath(),
                'online_authority_url' => (string) config('aptoria.license.authority.url', ''),
                'online_runtime_lease_cache' => (string) config('aptoria.license.authority.lease_cache_path', ''),
            ],
            'fingerprints' => [
                'machine' => $fingerprints['machine']['value'] ?? null,
                'usb' => $fingerprints['usb']['value'] ?? null,
            ],
            'preferred_binding_modes' => [
                'machine_or_usb',
                'machine',
                'usb',
            ],
            'requested_features' => [
                'portable_usb',
                'evidence_repository',
                'import_adapter',
                'native_test_evidence',
                'release_gate',
                'client_portal',
            ],
            'request_id' => 'lrq_'.Str::lower(Str::random(24)),
            'requested_by' => $user ? [
                'user_id' => $user->getKey(),
                'role' => $user->role ?? null,
            ] : null,
            'privacy_note' => 'This request contains hashed machine/portable fingerprints and runtime metadata only. It does not include raw host identifiers.',
        ];
    }

    public function filename(): string
    {
        return 'aptoria-license-request-'.now()->format('Ymd-His').'.json';
    }

    public function toJson(?User $user = null): string
    {
        return json_encode($this->build($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    private function defaultEdition(): string
    {
        return $this->licenses->isEnforced() ? 'portable' : 'server';
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', realpath($path) ?: $path);
    }
}
