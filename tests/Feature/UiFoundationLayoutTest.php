<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFoundationLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_v1132_header_and_panel_actions(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $html = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('aptoria-page-titlebar', $html);
        $this->assertStringContainsString('aptoria-page-actions', $html);
        $this->assertStringContainsString('aptoria-dashboard-root', $html);
        $this->assertStringContainsString('Aptoria Dashboard', $html);
        $this->assertStringContainsString(route('projects.wizard.create'), $html);
        $this->assertStringNotContainsString('aptoria-v2-page-shell', $html);
        $this->assertStringNotContainsString('aptoria-v2-actionbar', $html);
    }

    public function test_project_workspace_uses_v1132_workspace_buttons(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->firstOrFail();

        $html = $this->actingAs($admin)->get(route('projects.show', $project))->assertOk()->getContent();

        $this->assertStringContainsString('aptoria-project-module-section', $html);
        $this->assertStringContainsString('aptoria-workspace-button-section', $html);
        $this->assertStringContainsString('aptoria-workspace-button-grid', $html);
        $this->assertStringContainsString('aptoria-workspace-button', $html);
        $this->assertStringContainsString(route('projects.qa-evidence.index', $project), $html);
        $this->assertStringContainsString(route('projects.release-readiness.show', $project), $html);
        $this->assertStringNotContainsString('aptoria-v2-next-action', $html);
    }
}
