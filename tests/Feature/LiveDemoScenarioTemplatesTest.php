<?php

namespace Tests\Feature;

use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveDemoScenarioTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_scenarios_endpoint_lists_guided_templates(): void
    {
        app(SetupStateService::class)->markInstalled();

        $this->getJson(route('demo-api.scenarios.index'))
            ->assertOk()
            ->assertJsonPath('meta.count', 4)
            ->assertJsonFragment(['slug' => 'security-leak-review'])
            ->assertJsonFragment(['slug' => 'release-gate-decision']);
    }

    public function test_demo_scenario_evidence_endpoint_returns_run_sheet(): void
    {
        app(SetupStateService::class)->markInstalled();

        $this->getJson(route('demo-api.scenarios.evidence', 'release-gate-decision'))
            ->assertOk()
            ->assertJsonPath('scenario.slug', 'release-gate-decision')
            ->assertJsonPath('evidence_type', 'demo_scenario_run_sheet')
            ->assertJsonFragment(['source' => 'aptoria-live-demo-scenario-template']);
    }

    public function test_public_demo_guide_renders_selected_scenario(): void
    {
        app(SetupStateService::class)->markInstalled();

        $this->get('/demo-guide?scenario=artifact-import-trace')
            ->assertOk()
            ->assertSee('Artifact import trace')
            ->assertSee('Scenario Templates JSON');
    }
}
