<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use App\Models\TestCase as ApiTestCase;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaCoverageMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_qa_coverage_matrix_renders_endpoint_gaps(): void
    {
        $user = User::query()->create([
            'name' => 'QA Admin',
            'email' => 'qa-coverage@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Coverage API',
            'slug' => 'coverage-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/health',
            'name' => 'Health',
            'auth_required' => false,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_LOW,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $this->actingAs($user)
            ->get(route('projects.qa-coverage.index', $project))
            ->assertOk()
            ->assertSee('QA Coverage Matrix')
            ->assertSee('/health')
            ->assertSee('Missing test cases')
            ->assertSee('Missing assertions');

        $suite = $project->testSuites()->create([
            'name' => 'Smoke',
            'status' => TestSuite::STATUS_ACTIVE,
        ]);
        $project->testCases()->create([
            'test_suite_id' => $suite->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Health endpoint responds',
            'steps' => 'Run the health endpoint.',
            'expected_result' => 'HTTP 200.',
            'type' => ApiTestCase::TYPE_MANUAL,
            'priority' => ApiTestCase::PRIORITY_HIGH,
            'status' => ApiTestCase::STATUS_READY,
            'last_run_status' => ApiTestCase::RUN_PASS,
        ]);
        EndpointAssertionRule::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
            'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
            'expected_value' => '200',
            'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            'enabled' => true,
        ]);

        $this->actingAs($user)
            ->get(route('projects.qa-coverage.index', ['project' => $project, 'gap' => 'missing_test_cases']))
            ->assertOk()
            ->assertSee('No endpoints match the current coverage filters.')
            ->assertDontSee('<code>GET /health</code>', false);
    }
}
