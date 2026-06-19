<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMembershipAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_membership_is_created_when_project_is_created(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['role' => 'admin', 'password_change_required' => false]);

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'Membership API QA',
            'description' => 'Project access regression fixture.',
            'base_url' => 'https://api.example.test',
            'environment_label' => 'staging',
            'status' => 'active',
            'qa_owner' => 'QA Owner',
            'release_goal' => 'Validate access foundation.',
        ]);

        $response->assertRedirect();

        $project = Project::query()->where('name', 'Membership API QA')->firstOrFail();

        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'status' => ProjectMembership::STATUS_ACTIVE,
        ]);
    }

    public function test_non_member_cannot_open_project_workspace(): void
    {
        app(SetupStateService::class)->markInstalled();

        $owner = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $stranger = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($stranger)
            ->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_project_member_can_view_but_read_only_member_cannot_edit(): void
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
            ->get(route('projects.show', $project))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('projects.edit', $project))
            ->assertForbidden();
    }

    public function test_project_admin_can_add_existing_user_to_project(): void
    {
        app(SetupStateService::class)->markInstalled();

        $owner = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $member = User::factory()->create(['role' => 'user', 'password_change_required' => false, 'email' => 'qa@example.test']);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $owner->id,
            'added_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('projects.members.store', $project), [
                'email' => 'qa@example.test',
                'role' => ProjectMembership::ROLE_QA_ENGINEER,
            ])
            ->assertRedirect(route('projects.members.index', $project));

        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => ProjectMembership::ROLE_QA_ENGINEER,
            'status' => ProjectMembership::STATUS_ACTIVE,
        ]);
    }

    public function test_project_admin_can_create_user_and_add_to_project(): void
    {
        app(SetupStateService::class)->markInstalled();

        $owner = User::factory()->create(['role' => 'user', 'password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        ProjectMembership::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => ProjectMembership::ROLE_PROJECT_ADMIN,
            'status' => ProjectMembership::STATUS_ACTIVE,
            'invited_by_user_id' => $owner->id,
            'added_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('projects.members.create-user', $project), [
                'name' => 'New QA Member',
                'email' => 'new-qa@example.test',
                'role' => ProjectMembership::ROLE_QA_ENGINEER,
                'locale' => 'en',
                'timezone' => 'Europe/Budapest',
            ])
            ->assertRedirect(route('projects.members.index', $project))
            ->assertSessionHas('temporary_password');

        $user = User::query()->where('email', 'new-qa@example.test')->firstOrFail();

        $this->assertSame('user', $user->role);
        $this->assertTrue((bool) $user->password_change_required);
        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectMembership::ROLE_QA_ENGINEER,
            'status' => ProjectMembership::STATUS_ACTIVE,
        ]);
    }

}
