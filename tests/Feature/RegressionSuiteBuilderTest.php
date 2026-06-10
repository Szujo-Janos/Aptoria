<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use App\Models\TestCase as ApiTestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegressionSuiteBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_regression_suite_builder_creates_cases_and_assertions_from_endpoints(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoints = $project->endpoints()->limit(2)->pluck('id')->all();

        $this->actingAs($admin)
            ->get(route('projects.test-suites.builder', $project))
            ->assertOk()
            ->assertSee(__('messages.regression_builder.title'))
            ->assertSee(__('messages.regression_builder.endpoint_selection'));

        $this->actingAs($admin)
            ->post(route('projects.test-suites.builder.store', $project), [
                'name' => 'Generated Regression Suite',
                'description' => 'Generated from endpoint inventory.',
                'endpoint_ids' => $endpoints,
                'priority' => ApiTestCase::PRIORITY_CRITICAL,
                'expected_status' => 200,
                'include_status_assertions' => '1',
                'include_json_path_assertions' => '1',
                'required_json_paths' => "$.id\n$.name",
            ])
            ->assertRedirect();

        $suite = TestSuite::query()->where('name', 'Generated Regression Suite')->firstOrFail();

        $this->assertSame(count($endpoints), $suite->testCases()->count());
        $this->assertDatabaseHas('test_cases', [
            'project_id' => $project->id,
            'test_suite_id' => $suite->id,
            'priority' => ApiTestCase::PRIORITY_CRITICAL,
            'status' => ApiTestCase::STATUS_READY,
            'execution_order' => 1,
        ]);
        $this->assertGreaterThanOrEqual(count($endpoints), EndpointAssertionRule::query()->where('project_id', $project->id)->where('rule_key', EndpointAssertionRule::RULE_STATUS_CODE)->count());
        $this->assertGreaterThanOrEqual(2, EndpointAssertionRule::query()->where('project_id', $project->id)->where('rule_key', EndpointAssertionRule::RULE_JSON_PATH_VALUE)->count());
    }

    public function test_regression_suite_can_run_auto_probe_cases_and_record_results(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('method', Endpoint::METHOD_GET)->firstOrFail();
        $endpoint->update(['expected_status' => 200]);

        Http::fake([
            '*' => Http::response(['id' => 1, 'name' => 'Jane'], 200, ['Content-Type' => 'application/json']),
        ]);

        $suite = $project->testSuites()->create([
            'name' => 'Runnable Regression Suite',
            'status' => TestSuite::STATUS_ACTIVE,
        ]);

        $case = $project->testCases()->create([
            'test_suite_id' => $suite->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Generated runnable case',
            'steps' => 'Run safe probe.',
            'expected_result' => 'HTTP 200.',
            'type' => ApiTestCase::TYPE_HYBRID,
            'priority' => ApiTestCase::PRIORITY_HIGH,
            'status' => ApiTestCase::STATUS_READY,
            'execution_order' => 1,
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

        $this->actingAs($admin)
            ->post(route('projects.test-suites.run', [$project, $suite]))
            ->assertRedirect(route('projects.test-suites.show', [$project, $suite]));

        $case->refresh();
        $this->assertSame(ApiTestCase::RUN_PASS, $case->last_run_status);
        $this->assertSame(1, TestCaseResult::query()->where('test_case_id', $case->id)->count());
    }

    public function test_regression_suite_run_forms_use_loader_animation(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();

        $suite = $project->testSuites()->create([
            'name' => 'Animated Regression Suite',
            'status' => TestSuite::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.test-suites.show', [$project, $suite]))
            ->assertOk()
            ->assertSee('aptoria-suite-run-modal', false)
            ->assertSee('data-aptoria-suite-run-form="true"', false)
            ->assertSee(__('messages.regression_builder.modal_title'));

        $this->actingAs($admin)
            ->get(route('projects.test-execution.index', $project))
            ->assertOk()
            ->assertSee('data-aptoria-suite-run-form="true"', false)
            ->assertSee(__('messages.regression_builder.running_label'));

        $script = file_get_contents(public_path('assets/aptoria/js/app.js'));
        $this->assertStringContainsString("form.getAttribute('data-aptoria-confirm') === 'true'", $script);
        $this->assertStringContainsString('stopImmediatePropagation', $script);
        $this->assertStringNotContainsString("getAttribute('data-aptoria-confirm=\"true\"')", $script);
    }

}
