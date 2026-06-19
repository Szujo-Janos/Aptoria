<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperHelpPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_how_it_works_page_explains_full_release_workflow(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);

        $this->actingAs($user)
            ->get(route('help.how_it_works'))
            ->assertOk()
            ->assertSee(__('messages.help.how.title'))
            ->assertSee(__('messages.help.how.workflow_title'))
            ->assertSee(__('messages.help.how.domain_title'))
            ->assertSee(__('messages.help.how.safety_title'))
            ->assertSee('OpenAPI contract sample')
            ->assertSee('Release evidence summary shape');
    }

    public function test_developer_help_page_contains_samples_architecture_and_troubleshooting(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);

        $this->actingAs($user)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee(__('messages.help.developer.title'))
            ->assertSee(__('messages.help.developer.architecture_title'))
            ->assertSee(__('messages.help.developer.modules_title'))
            ->assertSee(__('messages.help.developer.extension_title'))
            ->assertSee('aptoria-0.0.34.zip')
            ->assertSee('Endpoint import CSV shape')
            ->assertSee('Security checklist for developers');
    }

    public function test_sidebar_links_to_both_help_pages(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('help.how_it_works'), false)
            ->assertSee(route('help.index'), false)
            ->assertSee(__('messages.nav.how_it_works'))
            ->assertSee(__('messages.nav.help'));
    }
    public function test_developer_help_pages_use_registered_semantic_icons(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);

        $this->actingAs($user)
            ->get(route('help.how_it_works'))
            ->assertOk()
            ->assertSee('data-lucide="sitemap"', false)
            ->assertSee('data-lucide="git-fork"', false)
            ->assertSee('data-lucide="shield-chevron"', false)
            ->assertDontSee('data-lucide="target"', false)
            ->assertDontSee('data-lucide="map"', false)
            ->assertDontSee('data-lucide="git-branch"', false);

        $this->actingAs($user)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('data-lucide="book-open"', false)
            ->assertSee('data-lucide="help-circle"', false)
            ->assertSee('data-lucide="play-circle"', false)
            ->assertSee('data-lucide="file-code-2"', false)
            ->assertDontSee('data-lucide="rocket"', false)
            ->assertDontSee('data-lucide="terminal"', false)
            ->assertDontSee('data-lucide="boxes"', false)
            ->assertDontSee('data-lucide="wrench"', false)
            ->assertDontSee('data-lucide="stethoscope"', false);
    }

}
