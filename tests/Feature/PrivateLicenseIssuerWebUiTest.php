<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PrivateLicenseIssuerWebUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_license_issuer_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('program-settings.license-issuer'));
        $this->assertTrue(Route::has('program-settings.license-issuer.keypair'));
        $this->assertTrue(Route::has('program-settings.license-issuer.issue'));
        $this->assertTrue(Route::has('program-settings.license-issuer.verify'));
    }

    public function test_admin_can_open_private_license_issuer_ui(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password_change_required' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('program-settings.license-issuer'))
            ->assertOk()
            ->assertSee('Private License Issuer')
            ->assertSee('tools/license-issuer');
    }
}
