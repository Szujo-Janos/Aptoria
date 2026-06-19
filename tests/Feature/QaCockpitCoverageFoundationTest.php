<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\QaCockpitService;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaCockpitCoverageFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_qa_cockpit_summarizes_coverage_and_blind_spots(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['role' => 'admin', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $endpoint = Endpoint::factory()->create(['project_id' => $project->id, 'method' => 'GET']);
        $scanRun = ScanRun::factory()->create(['project_id' => $project->id]);
        ScanResult::factory()->create(['project_id' => $project->id, 'scan_run_id' => $scanRun->id, 'endpoint_id' => $endpoint->id, 'status' => 'passed']);
        FindingEvidence::factory()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'repository_status' => FindingEvidence::STATUS_VERIFIED,
        ]);

        $snapshot = app(QaCockpitService::class)->snapshot($project);

        $this->assertSame(1, $snapshot['metrics']['endpoints']);
        $this->assertSame(100, $snapshot['coverage']['scan']);
        $this->assertSame(100, $snapshot['coverage']['evidence']);
        $this->assertSame(100, $snapshot['coverage']['verified_evidence']);
        $this->assertCount(1, $snapshot['coverage_rows']);
    }

    public function test_project_member_with_read_access_can_open_qa_cockpit(): void
    {
        app(SetupStateService::class)->markInstalled();

        $owner = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $viewer = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $owner->id]);
        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $viewer->id,
            'role' => ProjectMembership::ROLE_READ_ONLY_VIEWER,
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $owner->id,
            'added_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('projects.qa-cockpit.show', $project))
            ->assertOk()
            ->assertSee('QA Cockpit');
    }

    public function test_high_critical_open_findings_are_surface_as_blind_spots(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['role' => 'admin', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        Finding::factory()->create(['project_id' => $project->id, 'severity' => 'critical', 'status' => 'open']);

        $snapshot = app(QaCockpitService::class)->snapshot($project);

        $this->assertSame(1, $snapshot['metrics']['high_critical_open']);
        $this->assertTrue(collect($snapshot['blind_spots'])->contains(fn (array $spot): bool => $spot['category'] === 'findings' && $spot['severity'] === 'blocker'));
    }
}
