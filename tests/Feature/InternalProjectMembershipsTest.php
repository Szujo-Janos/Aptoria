<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ReleaseDecision;
use App\Models\ReportVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalProjectMembershipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_member_only_sees_assigned_projects(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['role' => 'user']);
        $assigned = $this->project($owner, 'Assigned API');
        $other = $this->project($owner, 'Other API');

        $assigned->memberships()->create([
            'user_id' => $member->id,
            'role' => ProjectMembership::ROLE_QA_ENGINEER,
            'joined_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Assigned API')
            ->assertDontSee('Other API');
    }

    public function test_project_admin_can_add_member_by_existing_email(): void
    {
        $owner = User::factory()->create();
        $target = User::factory()->create(['role' => 'user']);
        $project = $this->project($owner, 'Membership API');

        $project->memberships()->create([
            'user_id' => $owner->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'joined_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('projects.members.store', $project), [
                'email' => $target->email,
                'role' => ProjectMembership::ROLE_REVIEWER,
                'notes' => 'Reviewer for release evidence.',
            ])
            ->assertRedirect(route('projects.members.index', $project));

        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $project->id,
            'user_id' => $target->id,
            'role' => ProjectMembership::ROLE_REVIEWER,
        ]);
    }



    public function test_members_page_shows_available_internal_users_for_direct_project_add(): void
    {
        $owner = User::factory()->create();
        $available = User::factory()->create([
            'role' => 'user',
            'name' => 'Available QA User',
            'email' => 'available.qa@example.com',
        ]);
        $member = User::factory()->create([
            'role' => 'user',
            'name' => 'Already Member',
            'email' => 'member.qa@example.com',
        ]);
        $project = $this->project($owner, 'Membership Directory API');

        $project->memberships()->create([
            'user_id' => $owner->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'joined_at' => now(),
        ]);
        $project->memberships()->create([
            'user_id' => $member->id,
            'role' => ProjectMembership::ROLE_QA_ENGINEER,
            'joined_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('projects.members.index', $project))
            ->assertOk()
            ->assertSee('Available QA User')
            ->assertSee('available.qa@example.com')
            ->assertSee(__('messages.project_members.existing_user.add_to_project'));
    }

    public function test_project_admin_can_create_missing_user_while_adding_member(): void
    {
        $owner = User::factory()->create();
        $project = $this->project($owner, 'Created Member API');

        $project->memberships()->create([
            'user_id' => $owner->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'joined_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('projects.members.store', $project), [
                'email' => 'new.qa@example.com',
                'create_user' => '1',
                'new_user_name' => 'New QA User',
                'new_user_password' => 'ChangeMe123!',
                'new_user_password_confirmation' => 'ChangeMe123!',
                'role' => ProjectMembership::ROLE_QA_ENGINEER,
                'notes' => 'Created directly from project membership screen.',
            ])
            ->assertRedirect(route('projects.members.index', $project));

        $created = User::query()->where('email', 'new.qa@example.com')->first();

        $this->assertNotNull($created);
        $this->assertSame('user', $created->role);
        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $project->id,
            'user_id' => $created->id,
            'role' => ProjectMembership::ROLE_QA_ENGINEER,
        ]);
    }

    public function test_missing_user_requires_create_user_option(): void
    {
        $owner = User::factory()->create();
        $project = $this->project($owner, 'Missing Member API');

        $project->memberships()->create([
            'user_id' => $owner->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'joined_at' => now(),
        ]);

        $this->actingAs($owner)
            ->from(route('projects.members.index', $project))
            ->post(route('projects.members.store', $project), [
                'email' => 'missing.qa@example.com',
                'role' => ProjectMembership::ROLE_QA_ENGINEER,
            ])
            ->assertRedirect(route('projects.members.index', $project))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', [
            'email' => 'missing.qa@example.com',
        ]);
    }

    public function test_read_only_member_cannot_create_finding(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create(['role' => 'user']);
        $project = $this->project($owner, 'Read Only API');

        $project->memberships()->create([
            'user_id' => $viewer->id,
            'role' => ProjectMembership::ROLE_READ_ONLY_VIEWER,
            'joined_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->post(route('projects.findings.store', $project), $this->findingPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('findings', [
            'project_id' => $project->id,
            'title' => 'Critical auth bypass',
        ]);
    }

    public function test_release_approver_can_finalize_release_decision_but_qa_engineer_cannot_approve_report(): void
    {
        $owner = User::factory()->create();
        $approver = User::factory()->create(['role' => 'user']);
        $qa = User::factory()->create(['role' => 'user']);
        $project = $this->project($owner, 'Release API');

        $project->memberships()->create([
            'user_id' => $approver->id,
            'role' => ProjectMembership::ROLE_RELEASE_APPROVER,
            'joined_at' => now(),
        ]);
        $project->memberships()->create([
            'user_id' => $qa->id,
            'role' => ProjectMembership::ROLE_QA_ENGINEER,
            'joined_at' => now(),
        ]);

        $this->actingAs($approver)
            ->post(route('projects.release-decisions.store', $project), [
                'release_name' => 'v1.0.0',
                'target_environment' => 'staging',
                'decision_status' => ReleaseDecision::STATUS_GO,
                'decision_notes' => 'Approved by release owner.',
            ])
            ->assertRedirect();

        $report = $project->reportVersions()->create([
            'generated_by_user_id' => $owner->id,
            'title' => 'Release report',
            'report_type' => ReportVersion::TYPE_TECHNICAL,
            'markdown_content' => '# Release report',
        ]);

        $this->actingAs($qa)
            ->patch(route('projects.report-versions.approve', [$project, $report]))
            ->assertForbidden();

        $this->assertDatabaseHas('release_decisions', [
            'project_id' => $project->id,
            'decision_status' => ReleaseDecision::STATUS_GO,
        ]);
        $this->assertDatabaseMissing('report_versions', [
            'id' => $report->id,
            'status' => ReportVersion::STATUS_APPROVED,
        ]);
    }

    private function project(User $owner, string $name): Project
    {
        return Project::query()->create([
            'user_id' => $owner->id,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'base_url' => 'https://example.test',
            'is_active' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function findingPayload(): array
    {
        return [
            'title' => 'Critical auth bypass',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_CRITICAL,
            'priority' => Finding::PRIORITY_HIGH,
            'status' => Finding::STATUS_OPEN,
            'verification_status' => Finding::VERIFICATION_PENDING,
            'description' => 'Read-only users must not be able to create findings.',
        ];
    }
}
