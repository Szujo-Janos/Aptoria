<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use JsonException;
use Throwable;

class LicenseGuardService
{
    public function __construct(
        private readonly LicenseFingerprintService $fingerprints,
        private readonly OnlineLicenseAuthorityService $onlineAuthority,
    ) {
    }

    public function isEnforced(): bool
    {
        return (bool) config('aptoria.license.required', false);
    }

    public function licenseMode(): string
    {
        return $this->onlineAuthority->mode();
    }

    public function licenseFilePath(): string
    {
        return $this->absolutePath((string) config('aptoria.license.file_path', storage_path('app/aptoria-license.json')));
    }

    public function publicKeyPath(): string
    {
        return $this->absolutePath((string) config('aptoria.license.public_key_path', storage_path('app/license-public.pem')));
    }

    public function publicKeyConfigured(): bool
    {
        return $this->publicKey() !== null;
    }

    public function status(): array
    {
        $fingerprints = $this->fingerprints->current();
        $enforced = $this->isEnforced();
        $path = $this->licenseFilePath();
        $exists = File::exists($path);

        if (! $exists) {
            return $this->statusPayload('missing', ! $enforced, $enforced ? 'danger' : 'warning', null, $fingerprints, [
                'enforced' => $enforced,
                'file_path' => $path,
                'public_key_path' => $this->publicKeyPath(),
                'public_key_configured' => $this->publicKeyConfigured(),
                'message' => $enforced ? 'License file is missing.' : 'License guard is installed but not enforced. Add a license before enabling portable enforcement.',
            ]);
        }

        try {
            $document = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->statusPayload('malformed', false, 'danger', null, $fingerprints, [
                'enforced' => $enforced,
                'file_path' => $path,
                'public_key_path' => $this->publicKeyPath(),
                'public_key_configured' => $this->publicKeyConfigured(),
                'message' => 'License file is not valid JSON.',
            ]);
        }

        return $this->evaluateDocument($document, $path);
    }

    public function evaluateDocument(array $document, ?string $filePath = null): array
    {
        $fingerprints = $this->fingerprints->current();
        $enforced = $this->isEnforced();
        $path = $filePath ? $this->absolutePath($filePath) : $this->licenseFilePath();
        $base = [
            'enforced' => $enforced,
            'file_path' => $path,
            'public_key_path' => $this->publicKeyPath(),
            'public_key_configured' => $this->publicKeyConfigured(),
        ];

        $payload = $document['payload'] ?? null;
        $signature = $document['signature'] ?? null;

        if (! is_array($payload) || ! is_string($signature) || trim($signature) === '') {
            return $this->statusPayload('malformed', false, 'danger', is_array($payload) ? $payload : null, $fingerprints, $base + [
                'message' => 'License file must contain a payload object and base64 signature.',
            ]);
        }

        foreach (['license_id', 'product', 'edition', 'issued_at', 'expires_at'] as $requiredKey) {
            if (! array_key_exists($requiredKey, $payload) || trim((string) $payload[$requiredKey]) === '') {
                return $this->statusPayload('missing_required_field', false, 'danger', $payload, $fingerprints, $base + [
                    'message' => 'License payload is missing required field: '.$requiredKey.'.',
                ]);
            }
        }

        if (($payload['product'] ?? null) !== 'aptoria') {
            return $this->statusPayload('product_mismatch', false, 'danger', $payload, $fingerprints, $base + [
                'message' => 'License product does not match Aptoria.',
            ]);
        }

        $publicKey = $this->publicKey();
        if ($publicKey === null) {
            return $this->statusPayload('missing_public_key', false, 'danger', $payload, $fingerprints, $base + [
                'message' => 'License public key is missing.',
            ]);
        }

        if (! $this->verifySignature($payload, $signature, $publicKey)) {
            return $this->statusPayload('bad_signature', false, 'danger', $payload, $fingerprints, $base + [
                'message' => 'License signature could not be verified.',
            ]);
        }

        $expiry = $payload['expires_at'] ?? null;
        if (is_string($expiry) && $expiry !== '') {
            try {
                if (CarbonImmutable::parse($expiry)->isPast()) {
                    return $this->statusPayload('expired', false, 'danger', $payload, $fingerprints, $base + [
                        'message' => 'License has expired.',
                    ]);
                }
            } catch (Throwable) {
                return $this->statusPayload('invalid_expiry', false, 'danger', $payload, $fingerprints, $base + [
                    'message' => 'License expiry date is invalid.',
                ]);
            }
        }

        if (! $this->fingerprintAllowed($payload, $fingerprints)) {
            return $this->statusPayload('fingerprint_mismatch', false, 'danger', $payload, $fingerprints, $base + [
                'message' => 'License is not bound to this machine or portable drive.',
            ]);
        }

        return $this->statusPayload('valid', true, $enforced ? 'success' : 'info', $payload, $fingerprints, $base + [
            'message' => $enforced ? 'License is valid and enforced.' : 'License is valid. Enforcement is currently disabled.',
        ]);
    }

    public function allowsRuntime(): bool
    {
        if (! $this->isEnforced()) {
            return true;
        }

        $status = $this->status();
        if (! (bool) ($status['valid'] ?? false)) {
            return false;
        }

        if ($this->onlineAuthority->enabled()) {
            return $this->onlineAuthority->allowsRuntime($status);
        }

        return true;
    }

    public function canonicalPayload(array $payload): string
    {
        $normalized = $this->sortRecursive($payload);

        return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) ?: '{}';
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

    private function publicKey(): ?string
    {
        $inline = trim((string) config('aptoria.license.public_key', ''));
        if ($inline !== '') {
            return str_replace('\\n', "\n", $inline);
        }

        $path = $this->publicKeyPath();
        if ($path !== '' && File::exists($path)) {
            return (string) File::get($path);
        }

        return null;
    }

    private function fingerprintAllowed(array $payload, array $fingerprints): bool
    {
        $binding = $payload['fingerprint_binding'] ?? ['mode' => 'none'];
        if (! is_array($binding)) {
            return true;
        }

        $mode = (string) ($binding['mode'] ?? 'none');
        if ($mode === 'none' || $mode === '') {
            return true;
        }

        $allowed = $binding['fingerprints'] ?? [];
        if (! is_array($allowed) || $allowed === []) {
            return false;
        }

        $allowed = array_map([$this, 'normalizeFingerprint'], $allowed);
        $machine = $this->normalizeFingerprint((string) ($fingerprints['machine']['value'] ?? ''));
        $usb = $this->normalizeFingerprint((string) ($fingerprints['usb']['value'] ?? ''));

        return match ($mode) {
            'machine' => in_array($machine, $allowed, true),
            'usb' => in_array($usb, $allowed, true),
            'machine_or_usb' => in_array($machine, $allowed, true) || in_array($usb, $allowed, true),
            default => false,
        };
    }

    private function normalizeFingerprint(string $fingerprint): string
    {
        $fingerprint = trim(strtolower($fingerprint));

        return str_starts_with($fingerprint, 'sha256:') ? $fingerprint : 'sha256:'.$fingerprint;
    }

    private function statusPayload(string $state, bool $valid, string $tone, ?array $payload, array $fingerprints, array $extra): array
    {
        $expiresAt = $payload['expires_at'] ?? null;
        $daysRemaining = null;
        if (is_string($expiresAt) && $expiresAt !== '') {
            try {
                $daysRemaining = CarbonImmutable::now()->diffInDays(CarbonImmutable::parse($expiresAt), false);
            } catch (Throwable) {
                $daysRemaining = null;
            }
        }

        $status = array_merge($extra, [
            'license_mode' => $this->licenseMode(),
            'state' => $state,
            'valid' => $valid,
            'tone' => $tone,
            'label' => $this->labelFor($state),
            'payload' => $payload,
            'fingerprints' => $fingerprints,
            'license_id' => $payload['license_id'] ?? null,
            'subject' => $payload['subject'] ?? null,
            'issued_to' => $payload['issued_to'] ?? null,
            'edition' => $payload['edition'] ?? null,
            'product' => $payload['product'] ?? null,
            'issued_at' => $payload['issued_at'] ?? null,
            'expires_at' => $expiresAt,
            'days_remaining' => $daysRemaining,
            'features' => $payload['features'] ?? [],
            'binding_mode' => $payload['fingerprint_binding']['mode'] ?? 'none',
            'matched_binding' => $this->matchedBinding($payload, $fingerprints),
        ]);

        if ($this->onlineAuthority->enabled()) {
            $status['online_authority'] = $this->onlineAuthority->statusFor($status);
        }

        return $status;
    }

    private function matchedBinding(?array $payload, array $fingerprints): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        $binding = $payload['fingerprint_binding'] ?? ['mode' => 'none'];
        if (! is_array($binding)) {
            return null;
        }

        $mode = (string) ($binding['mode'] ?? 'none');
        if ($mode === 'none' || $mode === '') {
            return 'none';
        }

        $allowed = $binding['fingerprints'] ?? [];
        if (! is_array($allowed) || $allowed === []) {
            return null;
        }

        $allowed = array_map([$this, 'normalizeFingerprint'], $allowed);
        $machine = $this->normalizeFingerprint((string) ($fingerprints['machine']['value'] ?? ''));
        $usb = $this->normalizeFingerprint((string) ($fingerprints['usb']['value'] ?? ''));

        if (($mode === 'machine' || $mode === 'machine_or_usb') && in_array($machine, $allowed, true)) {
            return 'machine';
        }

        if (($mode === 'usb' || $mode === 'machine_or_usb') && in_array($usb, $allowed, true)) {
            return 'usb';
        }

        return null;
    }

    private function labelFor(string $state): string
    {
        return match ($state) {
            'valid' => 'Valid',
            'missing' => 'Missing',
            'malformed' => 'Malformed',
            'missing_required_field' => 'Missing field',
            'product_mismatch' => 'Product mismatch',
            'missing_public_key' => 'Missing public key',
            'bad_signature' => 'Bad signature',
            'expired' => 'Expired',
            'invalid_expiry' => 'Invalid expiry',
            'fingerprint_mismatch' => 'Fingerprint mismatch',
            default => ucfirst(str_replace('_', ' ', $state)),
        };
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

    private function absolutePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return storage_path('app/aptoria-license.json');
        }

        if (preg_match('/^[A-Z]:[\\\\\/]/i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}
