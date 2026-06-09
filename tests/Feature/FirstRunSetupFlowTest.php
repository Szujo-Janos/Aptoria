<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FirstRunSetupFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_successful_login_redirects_to_profile_once(): void
    {
        $user = User::query()->create([
            'name' => 'Aptoria Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'role' => 'admin',
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('profile.show'));

        $this->assertNotNull($user->fresh()?->first_login_at);
        $this->assertNotNull($user->fresh()?->last_login_at);

        $this->post(route('logout'));

        $this->post(route('login.attempt'), [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_setup_cannot_be_locked_without_an_admin_user(): void
    {
        $this->post(route('setup.finish'), ['confirm' => '1'])
            ->assertSessionHasErrors('setup');
    }
}
