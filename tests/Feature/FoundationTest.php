<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_page_is_available_before_install_lock(): void
    {
        File::delete(app(SetupStateService::class)->lockPath());

        $this->get('/setup')->assertOk();
    }

    public function test_uninstalled_application_redirects_to_setup(): void
    {
        File::delete(app(SetupStateService::class)->lockPath());

        $this->get('/dashboard')->assertRedirect('/setup');
    }

    public function test_setup_is_closed_after_install_lock(): void
    {
        app(SetupStateService::class)->markInstalled();

        $this->get('/setup')->assertRedirect('/login');
    }

    public function test_password_change_required_user_is_sent_to_profile(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create([
            'password_change_required' => true,
        ]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/profile');
    }
}
