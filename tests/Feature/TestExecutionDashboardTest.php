<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TestCase as ApiTestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestExecutionDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_execution_dashboard_renders_summary_and_queue(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $suite = $project->testSuites()->create([
            'name' => 'Release Smoke',
            'status' => TestSuite::STATUS_ACTIVE,
        ]);

        $project->testCases()->create([
            'test_suite_id' => $suite->id,
            'title' => 'Critical health endpoint passes',
            'steps' => 'Run the health endpoint check.',
            'expected_result' => 'HTTP 200 with healthy body.',
            'type' => ApiTestCase::TYPE_MANUAL,
            'priority' => ApiTestCase::PRIORITY_CRITICAL,
            'status' => ApiTestCase::STATUS_READY,
            'last_run_status' => ApiTestCase::RUN_NOT_RUN,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.test-execution.index', $project))
            ->assertOk()
            ->assertSee('Test Execution Dashboard')
            ->assertSee('Execution coverage')
            ->assertSee('Suite execution matrix')
            ->assertSee('Critical health endpoint passes');
    }

    public function test_quick_result_records_execution_from_dashboard(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $suite = $project->testSuites()->create([
            'name' => 'Dashboard Suite',
            'status' => TestSuite::STATUS_ACTIVE,
        ]);

        $case = $project->testCases()->create([
            'test_suite_id' => $suite->id,
            'title' => 'Dashboard quick mark case',
            'steps' => 'Run manually.',
            'expected_result' => 'Passes.',
            'type' => ApiTestCase::TYPE_MANUAL,
            'priority' => ApiTestCase::PRIORITY_HIGH,
            'status' => ApiTestCase::STATUS_READY,
            'last_run_status' => ApiTestCase::RUN_NOT_RUN,
        ]);

        $this->actingAs($admin)
            ->post(route('projects.test-execution.results.store', [$project, $case]), [
                'status' => TestCaseResult::STATUS_PASS,
                'notes' => 'Quick dashboard action.',
            ])
            ->assertRedirect(route('projects.test-execution.index', $project));

        $case->refresh();
        $this->assertSame(ApiTestCase::RUN_PASS, $case->last_run_status);
        $this->assertSame(1, $case->results()->count());
    }
}
