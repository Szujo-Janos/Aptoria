<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TestCase as ApiTestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestSuitesAndCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_test_suite_test_case_and_record_result(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('projects.test-suites.store', $project), [
                'name' => 'Smoke API Coverage',
                'description' => 'Critical release checks for public API endpoints.',
                'status' => TestSuite::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $suite = TestSuite::query()->where('project_id', $project->id)->where('name', 'Smoke API Coverage')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('projects.test-cases.store', $project), [
                'test_suite_id' => $suite->id,
                'endpoint_id' => $endpoint->id,
                'title' => 'GET endpoint returns expected response',
                'description' => 'Manual smoke coverage for the endpoint.',
                'preconditions' => 'Demo project exists.',
                'steps' => "1. Open endpoint\n2. Run request\n3. Review response",
                'expected_result' => 'The endpoint returns a successful response.',
                'actual_result' => null,
                'type' => ApiTestCase::TYPE_MANUAL,
                'priority' => ApiTestCase::PRIORITY_HIGH,
                'status' => ApiTestCase::STATUS_READY,
            ])
            ->assertRedirect();

        $case = ApiTestCase::query()->where('project_id', $project->id)->where('title', 'GET endpoint returns expected response')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('projects.test-cases.results.store', [$project, $case]), [
                'status' => TestCaseResult::STATUS_PASS,
                'actual_result' => 'HTTP 200 with expected JSON body.',
                'notes' => 'Manual QA passed.',
            ])
            ->assertRedirect(route('projects.test-cases.show', [$project, $case]));

        $case->refresh();
        $this->assertSame(ApiTestCase::RUN_PASS, $case->last_run_status);
        $this->assertSame('HTTP 200 with expected JSON body.', $case->actual_result);
        $this->assertSame(1, $case->results()->count());
    }

    public function test_project_endpoint_and_report_pages_render_test_case_context(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->firstOrFail();

        $suite = $project->testSuites()->create([
            'name' => 'Regression Suite',
            'status' => TestSuite::STATUS_ACTIVE,
        ]);

        $case = $project->testCases()->create([
            'test_suite_id' => $suite->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Endpoint stays compatible',
            'steps' => 'Run the linked endpoint request.',
            'expected_result' => 'Response remains compatible.',
            'type' => ApiTestCase::TYPE_HYBRID,
            'priority' => ApiTestCase::PRIORITY_CRITICAL,
            'status' => ApiTestCase::STATUS_ACTIVE,
            'last_run_status' => ApiTestCase::RUN_FAIL,
        ]);

        $case->results()->create([
            'project_id' => $project->id,
            'status' => TestCaseResult::STATUS_FAIL,
            'actual_result' => 'Missing expected field.',
            'executed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Test Suites')
            ->assertSee('Test cases total');

        $this->actingAs($admin)
            ->get(route('projects.endpoints.show', [$project, $endpoint]))
            ->assertOk()
            ->assertSee('Linked Test Cases')
            ->assertSee('Endpoint stays compatible');

        $this->actingAs($admin)
            ->get(route('projects.reports.full-project.markdown', $project))
            ->assertOk()
            ->assertSee('## Test Suites', false)
            ->assertSee('## Failed / Blocked Test Cases', false)
            ->assertSee('Endpoint stays compatible');
    }
}
