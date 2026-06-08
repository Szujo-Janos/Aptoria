<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\SafeProbeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RiskDetailViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_detail_displays_upgraded_risk_explanation(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('path', '/users/1')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('projects.endpoints.show', [$project, $endpoint]))
            ->assertOk()
            ->assertSee('Risk analysis')
            ->assertSee('Manual risk level')
            ->assertSee('Calculated risk level')
            ->assertSee('Final risk level')
            ->assertSee('Public sensitive-looking endpoint')
            ->assertSee('Why this matters')
            ->assertSee('Suggested QA action')
            ->assertSee('Suggested developer review action');
    }

    public function test_scan_details_displays_risk_summary(): void
    {
        $this->seed();
        config()->set('aptoria.private_network_scan_default', true);

        Http::fake([
            '*' => Http::response(['ok' => true], 200, ['Content-Type' => 'application/json']),
        ]);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $scanRun = app(SafeProbeService::class)->runProject($project, null, $admin);

        $this->actingAs($admin)
            ->get(route('projects.scans.show', [$project, $scanRun]))
            ->assertOk()
            ->assertSee('Risk summary')
            ->assertSee('Top risky endpoints')
            ->assertSee('Final risk level')
            ->assertSee('Risk score');
    }
}
