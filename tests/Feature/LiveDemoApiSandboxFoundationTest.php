<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LiveDemoApiSandboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveDemoApiSandboxFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_api_health_endpoint_returns_json(): void
    {
        $this->getJson('/demo-api/health')
            ->assertOk()
            ->assertJsonPath('service', 'Aptoria Live Demo API');
    }

    public function test_private_demo_endpoint_requires_bearer_token(): void
    {
        $this->getJson('/demo-api/security/private-account')->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer aptoria-demo-token')
            ->getJson('/demo-api/security/private-account')
            ->assertOk()
            ->assertJsonPath('account.id', 'demo-private-account');
    }

    public function test_live_demo_project_seed_creates_endpoint_inventory(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'Safe-Root-2026!',
            'role' => 'admin',
        ]);

        $result = app(LiveDemoApiSandboxService::class)->build($admin);

        $this->assertSame('aptoria-live-demo-api', $result['project']->slug);
        $this->assertGreaterThanOrEqual(10, $result['summary']['endpoints']);
        $this->assertGreaterThanOrEqual(1, $result['summary']['evidence']);
        $this->assertGreaterThanOrEqual(2, $result['summary']['test_cases']);
    }
}
