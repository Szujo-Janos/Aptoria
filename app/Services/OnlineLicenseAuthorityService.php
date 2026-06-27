<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class OnlineLicenseAuthorityService
{
    public function __construct(
        private readonly LicenseFingerprintService $fingerprints,
    ) {
    }

    public function enabled(): bool
    {
        return in_array($this->mode(), ['online_authority', 'hybrid'], true);
    }

    public function mode(): string
    {
        return trim((string) config('aptoria.license.mode', 'local_package')) ?: 'local_package';
    }

    public function statusFor(array $localStatus): array
    {
        $authorityUrl = $this->authorityUrl($localStatus);
        $base = [
            'enabled' => $this->enabled(),
            'mode' => $this->mode(),
            'authority_url' => $authorityUrl,
            'lease_endpoint_url' => $this->leaseEndpointUrl($localStatus),
            'lease_cache_path' => $this->leaseCachePath(),
            'authority_public_key_path' => $this->authorityPublicKeyPath(),
            'authority_public_key_configured' => $this->authorityPublicKeyConfigured(),
            'offline_grace_hours' => $this->offlineGraceHours(),
            'last_checked_at' => null,
            'valid_until' => null,
            'grace_until' => null,
            'lease_id' => null,
            'message' => 'Online license authority is disabled.',
        ];

        if (! $this->enabled()) {
            return array_merge($base, [
                'state' => 'disabled',
                'valid' => true,
                'tone' => 'secondary',
                'label' => 'Local package',
            ]);
        }

        if (! (bool) ($localStatus['valid'] ?? false)) {
            return array_merge($base, [
                'state' => 'waiting_for_local_license',
                'valid' => false,
                'tone' => 'warning',
                'label' => 'Waiting for local license',
                'message' => 'Install a valid local license before requesting an online runtime lease.',
            ]);
        }

        $document = $this->cachedLeaseDocument();
        if ($document === null) {
            return array_merge($base, [
                'state' => 'missing_lease',
                'valid' => false,
                'tone' => 'warning',
                'label' => 'No runtime lease',
                'message' => 'No cached runtime lease is available yet.',
            ]);
        }

        return array_merge($base, $this->evaluateLeaseDocument($document, $localStatus));
    }

    public function allowsRuntime(array $localStatus): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (! (bool) ($localStatus['valid'] ?? false)) {
            return false;
        }

        $status = $this->statusFor($localStatus);
        if ((bool) ($status['valid'] ?? false)) {
            return true;
        }

        $refreshed = $this->refreshLease($localStatus);

        return (bool) ($refreshed['valid'] ?? false);
    }

    public function refreshLease(array $localStatus): array
    {
        if (! $this->enabled()) {
            return $this->statusFor($localStatus);
        }

        if (! (bool) ($localStatus['valid'] ?? false)) {
            return [
                'state' => 'waiting_for_local_license',
                'valid' => false,
                'tone' => 'warning',
                'label' => 'Waiting for local license',
                'message' => 'A valid local license is required before online verification.',
            ];
        }

        $url = $this->leaseEndpointUrl($localStatus);
        if ($url === null) {
            return [
                'state' => 'authority_not_configured',
                'valid' => false,
                'tone' => 'warning',
                'label' => 'Authority not configured',
                'message' => 'APTORIA_LICENSE_AUTHORITY_URL is not configured.',
            ];
        }

        $requestPayload = $this->requestPayload($localStatus);

        try {
            $response = Http::timeout($this->timeoutSeconds())
                ->acceptJson()
                ->asJson()
                ->post($url, $requestPayload);
        } catch (Throwable $exception) {
            return [
                'state' => 'authority_unreachable',
                'valid' => false,
                'tone' => 'warning',
                'label' => 'Authority unreachable',
                'message' => 'Could not reach the online license authority: '.$exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'state' => 'authority_rejected',
                'valid' => false,
                'tone' => 'danger',
                'label' => 'Authority rejected',
                'message' => 'Online license authority rejected the request with HTTP '.$response->status().'.',
            ];
        }

        $document = $response->json();

        if (! is_array($document)) {
            return [
                'state' => 'malformed_authority_response',
                'valid' => false,
                'tone' => 'danger',
                'label' => 'Malformed response',
                'message' => 'Online license authority did not return a valid JSON object.',
            ];
        }

        $evaluated = $this->evaluateLeaseDocument($document, $localStatus);
        if (! (bool) ($evaluated['valid'] ?? false)) {
            return $evaluated;
        }

        $path = $this->leaseCachePath();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $this->evaluateLeaseDocument($document, $localStatus);
    }

    public function clearCachedLease(): void
    {
        $path = $this->leaseCachePath();
        if ($path !== '' && File::exists($path)) {
            File::delete($path);
        }
    }

    public function authorityPublicKeyConfigured(): bool
    {
        return $this->authorityPublicKey() !== null;
    }

    public function requestPayload(array $localStatus): array
    {
        $fingerprints = $this->fingerprints->current();

        return [
            'request_format' => 'aptoria-runtime-lease-request-v1',
            'product' => 'aptoria',
            'app_version' => (string) config('aptoria.version'),
            'generated_at' => CarbonImmutable::now()->toIso8601String(),
            'install_id' => $this->installId(),
            'license' => [
                'license_id' => $localStatus['license_id'] ?? null,
                'edition' => $localStatus['edition'] ?? null,
                'subject' => $localStatus['subject'] ?? $localStatus['issued_to'] ?? null,
                'expires_at' => $localStatus['expires_at'] ?? null,
                'binding_mode' => $localStatus['binding_mode'] ?? null,
            ],
            'runtime' => [
                'os_family' => PHP_OS_FAMILY,
                'php_version' => PHP_VERSION,
                'php_sapi' => PHP_SAPI,
                'app_url' => (string) config('app.url'),
                'base_path_hash' => 'sha256:'.hash('sha256', $this->normalizePath(base_path())),
                'manifest_hash' => $this->runtimeManifestHash(),
            ],
            'fingerprints' => [
                'machine' => $fingerprints['machine']['value'] ?? null,
                'usb' => $fingerprints['usb']['value'] ?? null,
            ],
        ];
    }

    private function evaluateLeaseDocument(array $document, array $localStatus): array
    {
        $payload = $document['payload'] ?? null;
        $signature = $document['signature'] ?? null;

        if (! is_array($payload) || ! is_string($signature) || trim($signature) === '') {
            return [
                'state' => 'malformed_lease',
                'valid' => false,
                'tone' => 'danger',
                'label' => 'Malformed lease',
                'message' => 'Runtime lease must contain a payload object and base64 signature.',
            ];
        }

        $publicKey = $this->authorityPublicKey();
        if ($publicKey === null) {
            return [
                'state' => 'missing_authority_public_key',
                'valid' => false,
                'tone' => 'danger',
                'label' => 'Missing authority key',
                'message' => 'Online authority public key is missing.',
            ];
        }

        if (! $this->verifySignature($payload, $signature, $publicKey)) {
            return [
                'state' => 'bad_lease_signature',
                'valid' => false,
                'tone' => 'danger',
                'label' => 'Bad lease signature',
                'message' => 'Runtime lease signature could not be verified.',
            ];
        }

        $licenseId = $payload['license_id'] ?? null;
        if ($licenseId !== ($localStatus['license_id'] ?? null)) {
            return [
                'state' => 'lease_license_mismatch',
                'valid' => false,
                'tone' => 'danger',
                'label' => 'License mismatch',
                'message' => 'Runtime lease belongs to another license.',
            ];
        }

        $status = (string) ($payload['status'] ?? 'invalid');
        if ($status !== 'valid') {
            return [
                'state' => $status === 'revoked' ? 'revoked' : 'authority_'.$status,
                'valid' => false,
                'tone' => 'danger',
                'label' => ucfirst(str_replace('_', ' ', $status)),
                'lease_id' => $payload['lease_id'] ?? null,
                'valid_until' => $payload['valid_until'] ?? null,
                'message' => 'Online license authority returned status: '.$status.((string) ($payload['reason'] ?? '') !== '' ? ' — '.$payload['reason'] : '').'.',
            ];
        }

        $validUntil = $payload['valid_until'] ?? null;
        if (! is_string($validUntil) || trim($validUntil) === '') {
            return [
                'state' => 'lease_missing_expiry',
                'valid' => false,
                'tone' => 'danger',
                'label' => 'Lease missing expiry',
                'message' => 'Runtime lease does not contain valid_until.',
            ];
        }

        try {
            $validUntilDate = CarbonImmutable::parse($validUntil);
        } catch (Throwable) {
            return [
                'state' => 'lease_invalid_expiry',
                'valid' => false,
                'tone' => 'danger',
                'label' => 'Invalid lease expiry',
                'message' => 'Runtime lease expiry date is invalid.',
            ];
        }

        $now = CarbonImmutable::now();
        $graceUntil = $validUntilDate->addHours($this->offlineGraceHours());

        if ($validUntilDate->isFuture()) {
            return [
                'state' => 'online_verified',
                'valid' => true,
                'tone' => 'success',
                'label' => 'Online verified',
                'lease_id' => $payload['lease_id'] ?? null,
                'last_checked_at' => $payload['issued_at'] ?? null,
                'valid_until' => $validUntilDate->toIso8601String(),
                'grace_until' => $graceUntil->toIso8601String(),
                'message' => 'Runtime lease is valid.',
            ];
        }

        if ($this->offlineGraceHours() > 0 && $now->lessThanOrEqualTo($graceUntil)) {
            return [
                'state' => 'offline_grace',
                'valid' => true,
                'tone' => 'warning',
                'label' => 'Offline grace',
                'lease_id' => $payload['lease_id'] ?? null,
                'last_checked_at' => $payload['issued_at'] ?? null,
                'valid_until' => $validUntilDate->toIso8601String(),
                'grace_until' => $graceUntil->toIso8601String(),
                'message' => 'Runtime lease expired, but offline grace is still active.',
            ];
        }

        return [
            'state' => 'lease_expired',
            'valid' => false,
            'tone' => 'danger',
            'label' => 'Lease expired',
            'lease_id' => $payload['lease_id'] ?? null,
            'last_checked_at' => $payload['issued_at'] ?? null,
            'valid_until' => $validUntilDate->toIso8601String(),
            'grace_until' => $graceUntil->toIso8601String(),
            'message' => 'Runtime lease has expired and offline grace is no longer active.',
        ];
    }

    private function cachedLeaseDocument(): ?array
    {
        $path = $this->leaseCachePath();
        if (! File::exists($path)) {
            return null;
        }

        try {
            $document = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($document) ? $document : null;
    }

    private function verifySignature(array $payload, string $signature, string $publicKey): bool
    {
        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }

        $result = @openssl_verify($this->canonicalPayload($payload), $decoded, $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    private function canonicalPayload(array $payload): string
    {
        $normalized = $this->sortRecursive($payload);

        return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) ?: '{}';
    }

    private function sortRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        ksort($value);

        return $value;
    }

    private function authorityPublicKey(): ?string
    {
        $inline = trim((string) config('aptoria.license.authority.public_key', ''));
        if ($inline !== '') {
            return str_replace('\n', "\n", $inline);
        }

        $path = $this->authorityPublicKeyPath();
        if ($path !== '' && File::exists($path)) {
            return (string) File::get($path);
        }

        return null;
    }

    private function installId(): string
    {
        $path = storage_path('app/license-install-id');
        if (File::exists($path)) {
            $existing = trim((string) File::get($path));
            if ($existing !== '') {
                return $existing;
            }
        }

        File::ensureDirectoryExists(dirname($path));
        $id = 'aptoria_install_'.Str::uuid()->toString();
        File::put($path, $id);

        return $id;
    }

    private function runtimeManifestHash(): string
    {
        $files = [
            'app/Services/LicenseGuardService.php',
            'app/Services/OnlineLicenseAuthorityService.php',
            'app/Http/Middleware/EnsureLicenseIsValid.php',
            'routes/web.php',
            'bootstrap/app.php',
            'VERSION',
        ];

        $hashes = [];
        foreach ($files as $file) {
            $path = base_path($file);
            $hashes[$file] = File::exists($path) ? hash_file('sha256', $path) : null;
        }

        ksort($hashes);

        return 'sha256:'.hash('sha256', json_encode($hashes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}');
    }


    private function leaseEndpointUrl(?array $localStatus = null): ?string
    {
        $base = $this->authorityUrl($localStatus);
        if ($base === '') {
            return null;
        }

        $payloadEndpoint = trim((string) data_get($localStatus ?? [], 'payload.authority.lease_endpoint', ''));
        $endpoint = $payloadEndpoint !== ''
            ? $payloadEndpoint
            : trim((string) config('aptoria.license.authority.lease_endpoint', '/api/license/runtime-lease'));

        return rtrim($base, '/').'/'.ltrim($endpoint, '/');
    }

    private function authorityUrl(?array $localStatus = null): string
    {
        $payloadUrl = rtrim(trim((string) data_get($localStatus ?? [], 'payload.authority.url', '')), '/');
        if ($payloadUrl !== '') {
            return $payloadUrl;
        }

        return rtrim(trim((string) config('aptoria.license.authority.url', '')), '/');
    }

    private function leaseCachePath(): string
    {
        return $this->absolutePath((string) config('aptoria.license.authority.lease_cache_path', storage_path('app/license-runtime-lease.json')));
    }

    private function authorityPublicKeyPath(): string
    {
        return $this->absolutePath((string) config('aptoria.license.authority.public_key_path', storage_path('app/license-authority-public.pem')));
    }

    private function timeoutSeconds(): int
    {
        return max(2, (int) config('aptoria.license.authority.timeout_seconds', 8));
    }

    private function offlineGraceHours(): int
    {
        return max(0, (int) config('aptoria.license.authority.offline_grace_hours', 72));
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', realpath($path) ?: $path);
    }

    private function absolutePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return storage_path('app/license-runtime-lease.json');
        }

        if (preg_match('/^[A-Z]:[\\\\\/]/i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}
