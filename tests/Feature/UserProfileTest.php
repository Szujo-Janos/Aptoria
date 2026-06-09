<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_requires_authentication(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_center(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('Account information')
            ->assertSee('Activity summary')
            ->assertSee('Aptoria v'.config('aptoria.version'));
    }

    public function test_profile_details_can_be_updated(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('profile.update'), [
                'name' => 'Updated Aptoria Admin',
                'email' => 'updated-admin@example.com',
                'locale' => 'hu',
                'timezone' => 'Europe/Budapest',
            ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHas('locale', 'hu');

        $admin->refresh();

        $this->assertSame('Updated Aptoria Admin', $admin->name);
        $this->assertSame('updated-admin@example.com', $admin->email);
        $this->assertSame('hu', $admin->locale);
        $this->assertSame('Europe/Budapest', $admin->timezone);
    }

    public function test_password_can_be_changed_from_profile_center(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $admin->forceFill(['password' => Hash::make('old-password')])->save();

        $this->actingAs($admin)
            ->put(route('profile.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('profile.show'));

        $this->assertTrue(Hash::check('new-secure-password', (string) $admin->refresh()->password));
    }
}
