<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\TestCase;
use App\Models\TestRun;
use App\Models\TestSuite;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

class NativeTestEvidenceModelTest extends BaseTestCase
{
    use RefreshDatabase;

    public function test_native_test_run_creates_repository_evidence(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $user->id,
            'added_at' => now(),
        ]);

        $suite = TestSuite::create(['project_id' => $project->id, 'name' => 'Release smoke', 'status' => 'active', 'priority' => 'normal']);
        $case = TestCase::create([
            'project_id' => $project->id,
            'test_suite_id' => $suite->id,
            'title' => 'Customer invoices are tenant-scoped',
            'type' => 'manual',
            'priority' => 'high',
            'status' => 'active',
            'expected_result' => 'Only customer invoices are returned.',
        ]);

        $this->actingAs($user)
            ->post(route('projects.tests.runs.store', [$project, $case]), [
                'status' => 'pass',
                'actual_result' => 'Only owned invoices were returned.',
                'evidence_summary' => 'Manual run passed on staging.',
            ])
            ->assertRedirect(route('projects.tests.cases.show', [$project, $case]));

        $run = TestRun::query()->firstOrFail();
        $this->assertSame('pass', $run->status);
        $this->assertNotNull($run->finding_evidence_id);

        $this->assertDatabaseHas('finding_evidence', [
            'id' => $run->finding_evidence_id,
            'project_id' => $project->id,
            'test_case_id' => $case->id,
            'test_run_id' => $run->id,
            'type' => 'test_result',
            'repository_status' => FindingEvidence::STATUS_ACTIVE,
            'integrity_status' => FindingEvidence::INTEGRITY_CURRENT,
        ]);
    }

    public function test_failed_native_run_can_create_linked_finding_and_evidence(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectMembership::ROLE_QA_ENGINEER,
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $user->id,
            'added_at' => now(),
        ]);

        $suite = TestSuite::create(['project_id' => $project->id, 'name' => 'Security review', 'status' => 'active', 'priority' => 'high']);
        $case = TestCase::create([
            'project_id' => $project->id,
            'test_suite_id' => $suite->id,
            'title' => 'Admin endpoint rejects normal user',
            'type' => 'manual',
            'priority' => 'urgent',
            'status' => 'active',
            'expected_result' => '403 Forbidden',
        ]);

        $this->actingAs($user)
            ->post(route('projects.tests.runs.store', [$project, $case]), [
                'status' => 'fail',
                'actual_result' => 'Endpoint returned 200.',
                'failure_summary' => 'Normal user can access admin endpoint.',
                'create_finding' => '1',
                'finding_severity' => 'high',
                'finding_priority' => 'urgent',
            ])
            ->assertRedirect(route('projects.tests.cases.show', [$project, $case]));

        $this->assertDatabaseHas('findings', [
            'project_id' => $project->id,
            'source' => 'test_case',
            'severity' => 'high',
            'priority' => 'urgent',
            'status' => 'confirmed',
        ]);

        $finding = Finding::query()->firstOrFail();
        $run = TestRun::query()->firstOrFail();

        $this->assertSame($finding->id, $run->finding_id);
        $this->assertDatabaseHas('finding_evidence', [
            'finding_id' => $finding->id,
            'test_case_id' => $case->id,
            'test_run_id' => $run->id,
            'type' => 'test_result',
        ]);
    }
}
