<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationMenuCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_sidebar_uses_task_based_navigation_groups(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Release &amp; reports', false)
            ->assertSee('Operations')
            ->assertSee('Audit &amp; admin', false)
            ->assertSee('Help &amp; workflow', false)
            ->assertSee('Monitor Alerts')
            ->assertSee('Demo Project');
    }

    public function test_project_sidebar_groups_current_project_modules_consistently(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Current project')
            ->assertSee('API inventory')
            ->assertSee('Quality workflow')
            ->assertSee('Risk &amp; evidence', false)
            ->assertSee('Release &amp; reports', false)
            ->assertSee('Automation &amp; audit', false)
            ->assertSee('Project Settings')
            ->assertSee('Monitors');
    }

    public function test_profile_dropdown_contains_only_account_level_actions(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $html = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aptoria-clean-profile-menu', $html);

        $menuStart = strpos($html, 'aptoria-clean-profile-menu');
        $menuEnd = strpos($html, '</ul>', $menuStart);
        $profileMenu = substr($html, $menuStart, $menuEnd - $menuStart);

        $this->assertStringContainsString('My Profile', $profileMenu);
        $this->assertStringContainsString('Default Report Identity', $profileMenu);
        $this->assertStringContainsString('Settings', $profileMenu);
        $this->assertStringContainsString('Help', $profileMenu);
        $this->assertStringContainsString('Sign out', $profileMenu);

        $this->assertStringNotContainsString('Release Readiness', $profileMenu);
        $this->assertStringNotContainsString('Calendar', $profileMenu);
        $this->assertStringNotContainsString('Demo Project', $profileMenu);
        $this->assertStringNotContainsString('Audit Log', $profileMenu);
        $this->assertStringNotContainsString('System Health', $profileMenu);
        $this->assertStringNotContainsString('Monitors', $profileMenu);
        $this->assertSame(1, substr_count($profileMenu, 'class="divider"'));
    }
}
