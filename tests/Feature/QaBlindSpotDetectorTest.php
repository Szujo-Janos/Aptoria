<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\QaReleaseGate;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\BlindSpots\QaBlindSpotDetectorService;
use App\Services\ReleaseReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaBlindSpotDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_without_scan_and_assertion_is_detected_and_page_renders(): void
    {
        [$admin, $project] = $this->projectFixture('blind-spot-page@example.com');
        $endpoint = $this->endpoint($project, Endpoint::RISK_HIGH, true);

        $summary = app(QaBlindSpotDetectorService::class)->summarize($project->fresh());

        $this->assertSame(1, $summary['summary']['untested_endpoints']);
        $this->assertSame(1, $summary['summary']['missing_assertions']);
        $this->assertGreaterThanOrEqual(1, $summary['summary']['release_blockers']);

        $this->actingAs($admin)
            ->get(route('projects.blind-spots.index', $project))
            ->assertOk()
            ->assertSee(__('messages.blind_spots.title'))
            ->assertSee($endpoint->path)
            ->assertSee(__('messages.blind_spots.types.endpoint_without_scan'));
    }

    public function test_assertion_and_no_auth_comparison_clear_endpoint_blind_spots(): void
    {
        [, $project] = $this->projectFixture('blind-spot-clear@example.com');
        $endpoint = $this->endpoint($project, Endpoint::RISK_HIGH, true);
        $scanRun = $this->scanRun($project);

        ScanResult::query()->create([
            'scan_run_id' => $scanRun->id,
            'endpoint_id' => $endpoint->id,
            'method' => $endpoint->method,
            'url' => 'https://api.example.test/users/1',
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
            'response_time_ms' => 80,
            'content_type' => 'application/json',
            'risk_level' => Endpoint::RISK_LOW,
            'broken_auth_summary_json' => ['summary' => 'No-auth comparison completed.'],
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

        QaReleaseGate::query()->create([
            'project_id' => $project->id,
            'release_name' => 'RC-1',
            'target_environment' => 'Staging',
            'gate_profile' => QaReleaseGate::PROFILE_STANDARD,
            'automated_status' => QaReleaseGate::STATUS_PASS,
            'final_decision' => QaReleaseGate::DECISION_PENDING,
            'score' => 90,
            'grade' => 'A',
            'endpoint_count' => 1,
            'endpoint_coverage_percent' => 100,
            'qa_coverage_percent' => 100,
            'test_execution_percent' => 0,
            'test_pass_rate' => 0,
            'blocker_count' => 0,
            'warning_count' => 0,
            'evidence_count' => 1,
        ]);

        $summary = app(QaBlindSpotDetectorService::class)->summarize($project->fresh());

        $this->assertSame(0, $summary['summary']['untested_endpoints']);
        $this->assertSame(0, $summary['summary']['missing_assertions']);
        $this->assertSame(0, $summary['summary']['missing_auth_comparisons']);
    }

    public function test_fixed_finding_requires_retest_evidence(): void
    {
        [, $project] = $this->projectFixture('blind-spot-retest@example.com');
        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'title' => 'Sensitive response field fixed',
            'source' => Finding::SOURCE_SCAN,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_FIXED,
        ]);

        $summary = app(QaBlindSpotDetectorService::class)->summarize($project->fresh());
        $this->assertSame(1, $summary['summary']['unverified_fixes']);

        FindingEvidence::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'type' => FindingEvidence::TYPE_RETEST,
            'source_label' => 'Retest probe',
            'content' => 'Retest passed after fix.',
            'captured_at' => now(),
        ]);

        $summary = app(QaBlindSpotDetectorService::class)->summarize($project->fresh());
        $this->assertSame(0, $summary['summary']['unverified_fixes']);
    }

    public function test_accepted_risk_requires_expiry_and_flags_expired_risks(): void
    {
        [, $project] = $this->projectFixture('blind-spot-risk@example.com');
        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'title' => 'Temporary accepted auth limitation',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_ACCEPTED_RISK,
        ]);

        $summary = app(QaBlindSpotDetectorService::class)->summarize($project->fresh());
        $this->assertSame(1, $summary['summary']['risk_without_expiry']);

        $finding->forceFill(['accepted_risk_expires_at' => now()->subDay()])->save();
        $summary = app(QaBlindSpotDetectorService::class)->summarize($project->fresh());

        $this->assertSame(1, $summary['summary']['expired_accepted_risks']);
        $this->assertSame(1, $summary['summary']['release_blockers']);
    }

    public function test_release_readiness_and_full_reports_include_blind_spot_summary(): void
    {
        [$admin, $project] = $this->projectFixture('blind-spot-readiness@example.com');
        $this->endpoint($project, Endpoint::RISK_CRITICAL, true);

        $summary = app(ReleaseReadinessService::class)->summarize($project->fresh());

        $this->assertGreaterThan(0, $summary['blind_spots']['summary']['total']);
        $this->assertGreaterThan(0, $summary['blind_spots']['summary']['release_blockers']);
        $this->assertTrue(collect($summary['score_components'])->firstWhere('key', 'report_signoff') !== null);

        $this->actingAs($admin)
            ->get(route('projects.release-readiness.show', $project))
            ->assertOk()
            ->assertSee(__('messages.blind_spots.report_summary_title'))
            ->assertSee(__('messages.blind_spots.open'));

        $this->actingAs($admin)
            ->post(route('projects.reports.builder.markdown', $project), [
                'title' => 'Executive Blind Spot Report',
                'audience' => 'management',
                'decision' => 'conditional',
                'sections' => ['executive_summary', 'release_readiness', 'blind_spots', 'appendix'],
                'endpoint_limit' => 25,
                'test_case_limit' => 25,
                'finding_limit' => 25,
                'contract_result_limit' => 25,
            ])
            ->assertOk()
            ->assertSee('## Blind Spot Summary', false)
            ->assertSee('Endpoint without scan', false);
    }

    /** @return array{0: User, 1: Project} */
    private function projectFixture(string $email): array
    {
        $admin = User::query()->create([
            'name' => 'QA Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Blind Spot API',
            'slug' => str('blind-spot-api-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        return [$admin, $project];
    }

    private function endpoint(Project $project, string $riskLevel = Endpoint::RISK_HIGH, bool $authRequired = false): Endpoint
    {
        return Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/{id}',
            'name' => 'Show user',
            'auth_required' => $authRequired,
            'expected_status' => 200,
            'risk_level' => $riskLevel,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);
    }

    private function scanRun(Project $project): ScanRun
    {
        return ScanRun::query()->create([
            'project_id' => $project->id,
            'created_by' => $project->user_id,
            'status' => ScanRun::STATUS_COMPLETED,
            'mode' => 'safe',
            'total_endpoints' => 1,
            'scanned_count' => 1,
            'success_count' => 1,
            'warning_count' => 0,
            'error_count' => 0,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }
}
