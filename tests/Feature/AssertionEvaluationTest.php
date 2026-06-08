<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use App\Models\ScanResult;
use App\Services\AssertionEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssertionEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_code_rule_can_pass_and_fail(): void
    {
        $this->seed();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('path', '/todos/1')->firstOrFail();
        $project->assertionRules()->create([
            'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
            'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
            'expected_value' => '200',
            'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            'enabled' => true,
        ]);

        $passing = app(AssertionEvaluationService::class)->evaluate($endpoint, new ScanResult([
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
        ]));
        $failing = app(AssertionEvaluationService::class)->evaluate($endpoint, new ScanResult([
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 500,
        ]));

        $this->assertSame(AssertionEvaluationService::STATUS_PASS, $passing['status']);
        $this->assertSame(AssertionEvaluationService::STATUS_FAIL, $failing['status']);
        $this->assertCount(1, $failing['failed_rules']);
    }

    public function test_warning_rule_produces_warning_status(): void
    {
        $this->seed();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('path', '/todos/1')->firstOrFail();
        $project->assertionRules()->create([
            'rule_key' => EndpointAssertionRule::RULE_MAX_RESPONSE_TIME_MS,
            'operator' => EndpointAssertionRule::OPERATOR_LESS_THAN_OR_EQUAL,
            'expected_value' => '100',
            'severity' => EndpointAssertionRule::SEVERITY_WARNING,
            'enabled' => true,
        ]);

        $analysis = app(AssertionEvaluationService::class)->evaluate($endpoint, new ScanResult([
            'status' => ScanResult::STATUS_COMPLETED,
            'response_time_ms' => 250,
        ]));

        $this->assertSame(AssertionEvaluationService::STATUS_WARNING, $analysis['status']);
        $this->assertCount(1, $analysis['warning_rules']);
    }

    public function test_endpoint_rule_overrides_project_default_with_the_same_key(): void
    {
        $this->seed();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('path', '/todos/1')->firstOrFail();
        $project->assertionRules()->create([
            'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
            'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
            'expected_value' => '200',
            'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            'enabled' => true,
        ]);
        $endpointRule = $project->assertionRules()->create([
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
            'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
            'expected_value' => '201',
            'severity' => EndpointAssertionRule::SEVERITY_WARNING,
            'enabled' => true,
        ]);

        $analysis = app(AssertionEvaluationService::class)->evaluate($endpoint, new ScanResult([
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
        ]));

        $this->assertSame(AssertionEvaluationService::STATUS_WARNING, $analysis['status']);
        $this->assertCount(1, $analysis['results']);
        $this->assertSame($endpointRule->id, $analysis['results'][0]['rule_id']);
    }

    public function test_endpoint_without_rules_is_not_configured(): void
    {
        $this->seed();
        $endpoint = Project::query()
            ->where('slug', 'demo-public-api')
            ->firstOrFail()
            ->endpoints()
            ->firstOrFail();

        $analysis = app(AssertionEvaluationService::class)->evaluate($endpoint);

        $this->assertSame(AssertionEvaluationService::STATUS_NOT_CONFIGURED, $analysis['status']);
        $this->assertSame([], $analysis['results']);
    }

    public function test_response_body_json_path_assertions_can_pass_and_fail(): void
    {
        $this->seed();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('path', '/todos/1')->firstOrFail();
        $project->assertionRules()->create([
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_JSON_PATH_VALUE,
            'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
            'target_path' => 'data.status',
            'expected_value' => 'active',
            'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            'enabled' => true,
        ]);
        $project->assertionRules()->create([
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_JSON_PATH_COUNT,
            'operator' => EndpointAssertionRule::OPERATOR_GREATER_THAN_OR_EQUAL,
            'target_path' => '$.data.items',
            'expected_value' => '2',
            'severity' => EndpointAssertionRule::SEVERITY_WARNING,
            'enabled' => true,
        ]);

        $passing = app(AssertionEvaluationService::class)->evaluate($endpoint, new ScanResult([
            'status' => ScanResult::STATUS_COMPLETED,
            'body_preview' => '{"data":{"status":"active","items":[{"id":1},{"id":2}]}}',
        ]));
        $failing = app(AssertionEvaluationService::class)->evaluate($endpoint, new ScanResult([
            'status' => ScanResult::STATUS_COMPLETED,
            'body_preview' => '{"data":{"status":"inactive","items":[{"id":1}]}}',
        ]));

        $this->assertSame(AssertionEvaluationService::STATUS_PASS, $passing['status']);
        $this->assertSame(AssertionEvaluationService::STATUS_FAIL, $failing['status']);
        $this->assertCount(1, $failing['failed_rules']);
        $this->assertCount(1, $failing['warning_rules']);
    }

}
