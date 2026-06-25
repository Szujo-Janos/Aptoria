<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LiveDemoApiSandboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDemoGuidedWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_demo_guide_lists_live_api_and_credentials(): void
    {
        $this->get('/demo-guide')
            ->assertOk()
            ->assertSee('Try Aptoria against a real JSON API')
            ->assertSee('demo@aptoria.dev')
            ->assertSee('/demo-api/health')
            ->assertSee('/demo-api/artifacts/openapi.json');
    }

    public function test_project_demo_guide_links_into_workspace_workflow(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'Safe-Root-2026!',
            'role' => 'admin',
        ]);

        $result = app(LiveDemoApiSandboxService::class)->build($admin);

        $this->actingAs($admin)
            ->get(route('projects.demo-guide.show', $result['project']))
            ->assertOk()
            ->assertSee('Suggested walkthrough')
            ->assertSee('Safe Scan')
            ->assertSee('QA Cockpit')
            ->assertSee('Release Gates');
    }
}
