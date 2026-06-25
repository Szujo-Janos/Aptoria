<?php

namespace Tests\Feature;

use App\Services\LicenseFingerprintService;
use App\Services\LicenseGuardService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PortableLicenseGuardFoundationTest extends TestCase
{
    public function test_signed_license_bound_to_current_machine_fingerprint_is_valid(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->assertNotFalse($key);

        openssl_pkey_export($key, $privateKey);
        $details = openssl_pkey_get_details($key);
        $publicKey = $details['key'];

        $fingerprint = app(LicenseFingerprintService::class)->compact()['machine'];
        $payload = [
            'license_id' => 'APT-TEST-001',
            'product' => 'aptoria',
            'edition' => 'portable',
            'subject' => 'Automated test',
            'issued_at' => now()->subDay()->toIso8601String(),
            'expires_at' => now()->addDay()->toIso8601String(),
            'features' => ['portable_usb'],
            'fingerprint_binding' => [
                'mode' => 'machine',
                'fingerprints' => [$fingerprint],
            ],
        ];

        $canonical = app(LicenseGuardService::class)->canonicalPayload($payload);
        openssl_sign($canonical, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $path = storage_path('app/test-aptoria-license.json');
        File::put($path, json_encode([
            'payload' => $payload,
            'signature' => base64_encode($signature),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        config([
            'aptoria.license.required' => true,
            'aptoria.license.file_path' => $path,
            'aptoria.license.public_key' => $publicKey,
            'aptoria.license.public_key_path' => null,
        ]);

        $status = app(LicenseGuardService::class)->status();

        $this->assertTrue($status['valid']);
        $this->assertSame('valid', $status['state']);
        $this->assertSame('APT-TEST-001', $status['license_id']);

        File::delete($path);
    }

    public function test_enforced_missing_license_blocks_runtime_status(): void
    {
        config([
            'aptoria.license.required' => true,
            'aptoria.license.file_path' => storage_path('app/missing-aptoria-license.json'),
        ]);

        $status = app(LicenseGuardService::class)->status();

        $this->assertFalse($status['valid']);
        $this->assertSame('missing', $status['state']);
        $this->assertFalse(app(LicenseGuardService::class)->allowsRuntime());
    }
}
