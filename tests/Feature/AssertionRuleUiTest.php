<?php

namespace Tests\Feature;

use App\Models\EndpointAssertionRule;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssertionRuleUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_default_rule_can_be_created_and_displayed(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('projects.assertion-rules.store', $project), [
                'project_id' => $project->id,
                'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
                'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
                'expected_value' => '200',
                'severity' => EndpointAssertionRule::SEVERITY_FAIL,
                'enabled' => '1',
            ])
            ->assertRedirect(route('projects.settings.edit', $project))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(route('projects.settings.edit', $project))
            ->assertOk()
            ->assertSee('Default Assertion Rules')
            ->assertSee('Expected status code');
    }

    public function test_endpoint_detail_and_scan_detail_show_assertion_status(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('path', '/todos/1')->firstOrFail();
        $project->assertionRules()->create([
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
            'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
            'expected_value' => '200',
            'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            'enabled' => true,
        ]);
        $scanRun = $project->scanRuns()->create(['status' => 'completed', 'mode' => 'safe']);
        $scanRun->results()->create([
            'endpoint_id' => $endpoint->id,
            'method' => 'GET',
            'url' => $endpoint->full_url,
            'status' => 'completed',
            'status_code' => 200,
            'content_type' => 'application/json',
        ]);

        $this->actingAs($admin)
            ->get(route('projects.endpoints.show', [$project, $endpoint]))
            ->assertOk()
            ->assertSee('Assertions')
            ->assertSee('PASS');

        $this->actingAs($admin)
            ->get(route('projects.scans.show', [$project, $scanRun]))
            ->assertOk()
            ->assertSee('Assertion status')
            ->assertSee('PASS');
    }

    public function test_response_body_assertion_rule_can_be_created_from_ui(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('path', '/todos/1')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('projects.assertion-rules.store', $project), [
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'rule_key' => EndpointAssertionRule::RULE_JSON_PATH_TYPE,
                'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
                'target_path' => 'data.items',
                'expected_value' => 'array',
                'severity' => EndpointAssertionRule::SEVERITY_FAIL,
                'enabled' => '1',
            ])
            ->assertRedirect(route('projects.endpoints.show', [$project, $endpoint]))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(route('projects.endpoints.show', [$project, $endpoint]))
            ->assertOk()
            ->assertSee('JSON path type')
            ->assertSee('data.items');
    }

}
