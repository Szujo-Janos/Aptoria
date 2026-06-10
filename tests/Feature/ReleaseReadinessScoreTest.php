<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Environment;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\TestCase as ApiTestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Models\User;
use App\Services\ReleaseReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseReadinessScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_readiness_exposes_weighted_score_breakdown(): void
    {
        $admin = User::query()->create([
            'name' => 'Readiness Admin',
            'email' => 'readiness@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Scored API',
            'slug' => 'scored-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $environment = Environment::query()->create([
            'project_id' => $project->id,
            'name' => 'Staging',
            'base_url' => 'https://staging.example.test',
            'is_production' => false,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'environment_id' => $environment->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/{id}',
            'name' => 'Get user',
            'auth_required' => false,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_LOW,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $scanRun = ScanRun::query()->create([
            'project_id' => $project->id,
            'environment_id' => $environment->id,
            'created_by' => $admin->id,
            'status' => ScanRun::STATUS_COMPLETED,
            'mode' => 'safe',
            'total_endpoints' => 1,
            'scanned_count' => 1,
            'success_count' => 1,
            'warning_count' => 0,
            'error_count' => 0,
        ]);

        $scanResult = ScanResult::query()->create([
            'scan_run_id' => $scanRun->id,
            'endpoint_id' => $endpoint->id,
            'method' => Endpoint::METHOD_GET,
            'url' => 'https://staging.example.test/users/1',
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
            'response_time_ms' => 90,
            'content_type' => 'application/json',
            'risk_level' => Endpoint::RISK_LOW,
        ]);

        $suite = TestSuite::query()->create([
            'project_id' => $project->id,
            'name' => 'Release regression',
            'status' => TestSuite::STATUS_ACTIVE,
        ]);

        $case = ApiTestCase::query()->create([
            'project_id' => $project->id,
            'test_suite_id' => $suite->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'GET user works',
            'steps' => 'Run safe probe.',
            'expected_result' => 'HTTP 200.',
            'type' => ApiTestCase::TYPE_HYBRID,
            'priority' => ApiTestCase::PRIORITY_HIGH,
            'status' => ApiTestCase::STATUS_READY,
            'last_run_status' => ApiTestCase::RUN_PASS,
            'last_run_at' => now(),
        ]);

        TestCaseResult::query()->create([
            'test_case_id' => $case->id,
            'project_id' => $project->id,
            'scan_run_id' => $scanRun->id,
            'scan_result_id' => $scanResult->id,
            'status' => TestCaseResult::STATUS_PASS,
            'actual_result' => 'Passed.',
            'executed_at' => now(),
        ]);

        $summary = app(ReleaseReadinessService::class)->summarize($project->fresh());

        $this->assertSame(100, $summary['score_component_total']);
        $this->assertCount(10, $summary['score_components']);
        $this->assertContains('endpoint_coverage', array_column($summary['score_components'], 'key'));
        $this->assertContains('security', array_column($summary['score_components'], 'key'));
        $this->assertGreaterThanOrEqual(0, $summary['score']);
        $this->assertLessThanOrEqual(100, $summary['score']);

        $this->actingAs($admin)
            ->get(route('projects.release-readiness.show', $project))
            ->assertOk()
            ->assertSee(__('messages.release_readiness.score_breakdown_title'))
            ->assertSee(__('messages.release_readiness.score_components.endpoint_coverage'))
            ->assertSee(__('messages.release_readiness.score_components.security'));
    }


    public function test_release_readiness_counts_finding_lifecycle_states(): void
    {
        $admin = User::query()->create([
            'name' => 'Lifecycle Readiness Admin',
            'email' => 'readiness-lifecycle@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Lifecycle Score API',
            'slug' => 'lifecycle-score-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        foreach ([
            Finding::STATUS_REOPENED => Finding::SEVERITY_CRITICAL,
            Finding::STATUS_FIXED => Finding::SEVERITY_HIGH,
            Finding::STATUS_FALSE_POSITIVE => Finding::SEVERITY_HIGH,
            Finding::STATUS_ACCEPTED_RISK => Finding::SEVERITY_MEDIUM,
        ] as $status => $severity) {
            Finding::query()->create([
                'project_id' => $project->id,
                'title' => 'Lifecycle '.$status,
                'source' => Finding::SOURCE_MANUAL,
                'severity' => $severity,
                'status' => $status,
            ]);
        }

        $summary = app(ReleaseReadinessService::class)->summarize($project->fresh());

        $this->assertSame(1, $summary['finding_counts']['reopened']);
        $this->assertSame(1, $summary['finding_counts']['fixed']);
        $this->assertSame(1, $summary['finding_counts']['false_positive']);
        $this->assertSame(1, $summary['finding_counts']['accepted_risk']);
        $this->assertSame(1, $summary['finding_counts']['critical_open']);
        $this->assertSame(1, $summary['finding_counts']['open']);

        $findingsComponent = collect($summary['score_components'])->firstWhere('key', 'findings');
        $this->assertNotNull($findingsComponent);
        $this->assertContains(__('messages.findings.statuses.reopened').': 1', $findingsComponent['checks']);
        $this->assertContains(__('messages.findings.statuses.accepted_risk').': 1', $findingsComponent['checks']);
        $this->assertContains(__('messages.findings.statuses.false_positive').': 1', $findingsComponent['checks']);
        $this->assertContains(__('messages.findings.statuses.fixed').': 1', $findingsComponent['checks']);
    }

}
