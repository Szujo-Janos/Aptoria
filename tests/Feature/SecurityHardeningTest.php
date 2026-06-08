<?php

namespace Tests\Feature;

use App\Services\Endpoints\EndpointImportService;
use App\Services\Security\NetworkTargetGuard;
use App\Services\Security\SensitiveValueMasker;
use App\Services\Security\SetupAccessService;
use App\Services\Settings\SettingService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_network_target_guard_blocks_localhost_and_private_targets(): void
    {
        $guard = new NetworkTargetGuard();

        $this->assertTrue($guard->isBlocked('http://localhost/openapi.json'));
        $this->assertTrue($guard->isBlocked('http://127.0.0.1/openapi.json'));
        $this->assertTrue($guard->isBlocked('http://10.0.0.10/openapi.json'));
        $this->assertTrue($guard->isBlocked('http://192.168.1.10/openapi.json'));
        $this->assertTrue($guard->isBlocked('ftp://example.com/openapi.json'));
        $this->assertTrue($guard->isBlocked('https://user:pass@example.com/openapi.json'));
        $this->assertFalse($guard->isBlocked('https://8.8.8.8/openapi.json'));
    }

    public function test_remote_openapi_import_rejects_private_targets_before_fetching(): void
    {
        $service = app(EndpointImportService::class);

        $this->expectException(ValidationException::class);

        $service->fetchRemotePayload('http://127.0.0.1/openapi.json');
    }


    public function test_security_headers_are_applied_to_sensitive_pages(): void
    {
        $response = $this->get(route('setup.index'));

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'same-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');

        $cacheControl = (string) $response->headers->get('Cache-Control');

        foreach (['no-store', 'no-cache', 'must-revalidate', 'max-age=0'] as $directive) {
            $this->assertStringContainsString($directive, $cacheControl);
        }
    }

    public function test_setup_tokens_must_be_strong_and_non_placeholder(): void
    {
        config()->set('aptoria.setup_token_min_length', 32);
        config()->set('aptoria.setup_token_placeholder_values', ['change-this-long-random-setup-token']);

        $service = app(SetupAccessService::class);

        $this->assertFalse($service->isUsableToken('short-token'));
        $this->assertFalse($service->isUsableToken('change-this-long-random-setup-token'));
        $this->assertTrue($service->isUsableToken(str_repeat('a', 40)));
    }

    public function test_sensitive_value_masker_masks_export_tokens_and_credentials(): void
    {
        $settings = Mockery::mock(SettingService::class);
        $settings->shouldReceive('boolean')
            ->with('security.hide_tokens_in_exports', true)
            ->andReturn(true);

        $masker = new SensitiveValueMasker($settings);

        $masked = $masker->maskForExport('Authorization: Bearer abc.def.ghi token=secret123 password=myPassword');

        $this->assertStringContainsString('Authorization: Bearer ********', $masked);
        $this->assertStringContainsString('Bearer ********', $masked);
        $this->assertStringNotContainsString('Authorization: ********', $masked);
        $this->assertStringContainsString('token=********', $masked);
        $this->assertStringContainsString('password=********', $masked);
        $this->assertStringNotContainsString('secret123', $masked);
        $this->assertStringNotContainsString('myPassword', $masked);
    }
}
