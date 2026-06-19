<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_create_user_with_temporary_password(): void
    {
        app(SetupStateService::class)->markInstalled();

        $admin = User::factory()->create(['role' => 'admin', 'password_change_required' => false]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'QA Reviewer',
                'email' => 'reviewer@example.test',
                'role' => 'user',
                'locale' => 'en',
                'timezone' => 'Europe/Budapest',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('temporary_password');

        $this->assertDatabaseHas('users', [
            'email' => 'reviewer@example.test',
            'role' => 'user',
            'password_change_required' => true,
        ]);
    }

    public function test_non_admin_cannot_open_user_management(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['role' => 'user', 'password_change_required' => false]);

        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }
}
