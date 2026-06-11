<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ReportVersion;
use App\Models\RiskAcceptance;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\Cockpit\QaCockpitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaCockpitTest extends TestCase
{
    use RefreshDatabase;

    public function test_qa_cockpit_summarizes_daily_release_work_queues(): void
    {
        [$admin, $project] = $this->fixture('qa-cockpit@example.com');

        $cockpit = app(QaCockpitService::class)->summarize($project->fresh());

        $this->assertSame(1, $cockpit['metrics']['open_blockers']);
        $this->assertSame(1, $cockpit['metrics']['fixes_waiting_for_retest']);
        $this->assertSame(1, $cockpit['metrics']['accepted_risks_expiring']);
        $this->assertSame(1, $cockpit['metrics']['stale_scans']);
        $this->assertSame(1, $cockpit['metrics']['stale_reports']);
        $this->assertGreaterThanOrEqual(1, $cockpit['metrics']['endpoints_without_evidence']);
        $this->assertSame(1, $cockpit['metrics']['release_candidates_needing_decision']);
        $this->assertTrue($cockpit['queues']['priority']->isNotEmpty());

        $this->actingAs($admin)
            ->get(route('projects.qa-cockpit.index', $project))
            ->assertOk()
            ->assertSee(__('messages.qa_cockpit.title'))
            ->assertSee(__('messages.qa_cockpit.priority_queue'))
            ->assertSee('Critical payment auth bypass')
            ->assertSee('Fixed order schema mismatch');
    }

    /** @return array{0: User, 1: Project} */
    private function fixture(string $email): array
    {
        $admin = User::query()->create([
            'name' => 'QA Cockpit Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'QA Cockpit API',
            'slug' => str('qa-cockpit-api-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/orders/{id}',
            'name' => 'Order detail',
            'auth_required' => true,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_POST,
            'path' => '/payments',
            'name' => 'Create payment',
            'auth_required' => true,
            'expected_status' => 201,
            'risk_level' => Endpoint::RISK_CRITICAL,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        ScanRun::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'status' => ScanRun::STATUS_COMPLETED,
            'mode' => 'safe',
            'started_at' => now()->subDays(20),
            'finished_at' => now()->subDays(20),
            'total_endpoints' => 1,
            'scanned_count' => 1,
            'success_count' => 1,
        ]);

        Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Critical payment auth bypass',
            'source' => Finding::SOURCE_SCAN,
            'severity' => Finding::SEVERITY_CRITICAL,
            'priority' => Finding::PRIORITY_CRITICAL,
            'status' => Finding::STATUS_OPEN,
            'due_date' => now()->subDay(),
        ]);

        Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Fixed order schema mismatch',
            'source' => Finding::SOURCE_CONTRACT,
            'severity' => Finding::SEVERITY_HIGH,
            'priority' => Finding::PRIORITY_HIGH,
            'status' => Finding::STATUS_READY_FOR_RETEST,
            'verification_status' => Finding::VERIFICATION_READY_FOR_RETEST,
            'retest_required' => true,
            'due_date' => now()->addDay(),
        ]);

        $riskFinding = Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Accepted risk expires soon',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_ACCEPTED_RISK,
        ]);

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $riskFinding->id,
            'accepted_by_user_id' => $admin->id,
            'accepted_until' => now()->addDays(3),
            'reason' => 'Temporary accepted risk for RC scope.',
            'status' => RiskAcceptance::STATUS_ACTIVE,
        ]);

        ReportVersion::query()->create([
            'project_id' => $project->id,
            'generated_by_user_id' => $admin->id,
            'approved_by_user_id' => $admin->id,
            'title' => 'Old approved report',
            'report_type' => ReportVersion::TYPE_TECHNICAL,
            'status' => ReportVersion::STATUS_APPROVED,
            'markdown_content' => '# Old approved report',
            'content_checksum' => hash('sha256', 'Old approved report'),
            'generated_at' => now()->subDays(30),
            'approved_at' => now()->subDays(30),
        ]);

        return [$admin, $project];
    }
}
