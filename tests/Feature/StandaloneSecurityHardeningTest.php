<?php

namespace Tests\Feature;

use App\Models\ProgramSetting;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StandaloneSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_forced_password_change_blocks_reusing_current_default_password(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('change-me-now'),
            'password_change_required' => true,
        ]);

        $this->actingAs($user)
            ->post('/profile/password', [
                'current_password' => 'change-me-now',
                'password' => 'change-me-now',
                'password_confirmation' => 'change-me-now',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->password_change_required);
    }

    public function test_forced_password_change_accepts_strong_non_default_password_and_clears_flag(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('change-me-now'),
            'password_change_required' => true,
        ]);

        $this->actingAs($user)
            ->post('/profile/password', [
                'current_password' => 'change-me-now',
                'password' => 'Safe-Root-2026!',
                'password_confirmation' => 'Safe-Root-2026!',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->password_change_required);
        $this->assertTrue(Hash::check('Safe-Root-2026!', $user->fresh()->password));
    }

    public function test_web_responses_include_security_headers(): void
    {
        app(SetupStateService::class)->markInstalled();

        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
    }

    public function test_program_settings_persist_session_timeout_value(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);

        $this->actingAs($user)
            ->put('/program-settings', [
                'app_name' => 'Aptoria',
                'default_locale' => 'en',
                'timezone' => 'Europe/Budapest',
                'session_timeout_minutes' => 90,
            ])
            ->assertRedirect('/program-settings');

        $this->assertSame('90', ProgramSetting::get('security.session_timeout_minutes'));
    }
}
