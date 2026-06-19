<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Services\EvidenceRepositoryService;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceRepositoryFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_evidence_repository_records_checksum_and_lifecycle_event(): void
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

        $this->actingAs($user)->post(route('projects.evidence.store', $project), [
            'type' => 'http',
            'title' => 'Repository checksum proof',
            'source_label' => 'Manual QA retest',
            'content' => 'HTTP 200 response verified after retest.',
        ])->assertRedirect();

        $evidence = FindingEvidence::query()->where('title', 'Repository checksum proof')->firstOrFail();

        $this->assertSame(FindingEvidence::STATUS_ACTIVE, $evidence->repository_status);
        $this->assertSame(FindingEvidence::INTEGRITY_CURRENT, $evidence->integrity_status);
        $this->assertSame(FindingEvidence::CHECKSUM_ALGORITHM, $evidence->checksum_algorithm);
        $this->assertNotEmpty($evidence->sha256);

        $this->assertDatabaseHas('evidence_lifecycle_events', [
            'finding_evidence_id' => $evidence->id,
            'action' => 'created',
        ]);
    }

    public function test_reviewer_can_verify_evidence_without_full_manage_permission(): void
    {
        app(SetupStateService::class)->markInstalled();

        $owner = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $reviewer = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $owner->id]);
        $evidence = FindingEvidence::factory()->create([
            'project_id' => $project->id,
            'captured_by_user_id' => $owner->id,
            'repository_status' => FindingEvidence::STATUS_ACTIVE,
            'integrity_status' => FindingEvidence::INTEGRITY_CURRENT,
            'sha256' => null,
        ]);

        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $reviewer->id,
            'role' => ProjectMembership::ROLE_REVIEWER,
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $owner->id,
            'added_at' => now(),
        ]);

        app(EvidenceRepositoryService::class)->syncIntegrityState($evidence);

        $this->actingAs($reviewer)
            ->post(route('projects.evidence.verify', [$project, $evidence]), [
                'repository_notes' => 'Reviewed for release evidence pack.',
            ])
            ->assertRedirect(route('projects.evidence.show', [$project, $evidence]));

        $this->assertDatabaseHas('finding_evidence', [
            'id' => $evidence->id,
            'repository_status' => FindingEvidence::STATUS_VERIFIED,
            'reviewed_by_user_id' => $reviewer->id,
        ]);

        $this->assertDatabaseHas('evidence_lifecycle_events', [
            'finding_evidence_id' => $evidence->id,
            'action' => 'verified',
        ]);
    }

    public function test_delete_action_archives_evidence_instead_of_hard_deleting_it(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $evidence = FindingEvidence::factory()->create([
            'project_id' => $project->id,
            'captured_by_user_id' => $user->id,
            'repository_status' => FindingEvidence::STATUS_ACTIVE,
        ]);

        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $user->id,
            'added_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('projects.evidence.destroy', [$project, $evidence]))
            ->assertRedirect(route('projects.evidence.index', $project));

        $this->assertDatabaseHas('finding_evidence', [
            'id' => $evidence->id,
            'repository_status' => FindingEvidence::STATUS_ARCHIVED,
        ]);
    }
    public function test_evidence_create_screen_uses_scrollable_page_form_with_required_help_text(): void
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

        $this->actingAs($user)
            ->get(route('projects.evidence.create', $project))
            ->assertOk()
            ->assertSee('aptoria-evidence-intake-form', false)
            ->assertSee('aptoria-form-section', false)
            ->assertSee('name="title"', false)
            ->assertSee('name="content"', false)
            ->assertSee(__('messages.evidence.title_help'))
            ->assertSee(__('messages.evidence.content_help'))
            ->assertDontSee('evidenceCreateModal');
    }

    public function test_finding_detail_links_to_evidence_create_page_instead_of_modal(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $finding = Finding::factory()->create(['project_id' => $project->id]);

        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $user->id,
            'added_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('projects.findings.show', [$project, $finding]))
            ->assertOk()
            ->assertSee(route('projects.evidence.create', ['project' => $project, 'finding_id' => $finding->id, 'endpoint_id' => $finding->endpoint_id, 'scan_result_id' => $finding->scan_result_id]), false)
            ->assertDontSee('evidenceCreateModal');
    }

}
