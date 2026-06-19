<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\Project;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindingRetestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_finding_can_be_marked_ready_for_retest(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $finding = Finding::factory()->create([
            'project_id' => $project->id,
            'retest_required' => true,
            'retest_status' => 'required',
        ]);

        $this->actingAs($user)
            ->post(route('projects.findings.ready-for-retest', [$project, $finding]), [
                'retest_note' => 'Fix is deployed to staging.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $this->assertDatabaseHas('findings', [
            'id' => $finding->id,
            'status' => 'ready_for_retest',
            'retest_required' => true,
            'retest_status' => 'ready_for_retest',
        ]);
    }

    public function test_passed_retest_verifies_finding_and_attaches_evidence(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $finding = Finding::factory()->create([
            'project_id' => $project->id,
            'status' => 'ready_for_retest',
            'retest_required' => true,
            'retest_status' => 'ready_for_retest',
        ]);

        $this->actingAs($user)
            ->post(route('projects.findings.record-retest', [$project, $finding]), [
                'result' => 'passed',
                'retest_note' => 'Endpoint passed after the fix.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $this->assertDatabaseHas('findings', [
            'id' => $finding->id,
            'status' => 'verified',
            'retest_required' => false,
            'retest_status' => 'passed',
            'retested_by_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('finding_evidence', [
            'finding_id' => $finding->id,
            'project_id' => $project->id,
            'type' => 'retest',
        ]);
    }

    public function test_failed_retest_keeps_finding_in_retest_failed_state(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $finding = Finding::factory()->create([
            'project_id' => $project->id,
            'status' => 'ready_for_retest',
            'retest_required' => true,
            'retest_status' => 'ready_for_retest',
        ]);

        $this->actingAs($user)
            ->post(route('projects.findings.record-retest', [$project, $finding]), [
                'result' => 'failed',
                'retest_note' => 'Endpoint still returns the wrong response.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $this->assertDatabaseHas('findings', [
            'id' => $finding->id,
            'status' => 'retest_failed',
            'retest_required' => true,
            'retest_status' => 'failed',
            'retested_by_user_id' => $user->id,
        ]);
    }
}
