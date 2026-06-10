<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_changes_are_recorded_in_audit_log_and_visible_on_timeline(): void
    {
        $user = User::factory()->create();

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Audit Demo',
            'slug' => 'audit-demo',
            'base_url' => 'https://api.audit-demo.test',
            'is_active' => true,
        ]);

        $project->update(['description' => 'Updated for audit evidence.']);

        $this->assertDatabaseHas('audit_logs', [
            'project_id' => $project->id,
            'event_type' => AuditLog::EVENT_MODEL,
            'action' => AuditLog::ACTION_CREATED,
            'auditable_type' => Project::class,
            'auditable_id' => $project->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'project_id' => $project->id,
            'event_type' => AuditLog::EVENT_MODEL,
            'action' => AuditLog::ACTION_UPDATED,
            'auditable_type' => Project::class,
            'auditable_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->get(route('audit-log.index'))
            ->assertOk()
            ->assertSee(__('messages.audit_log.heading'))
            ->assertSee('Audit Demo')
            ->assertSee(__('messages.nav.audit_log'))
            ->assertDontSee('messages.audit_log');
    }

    public function test_project_audit_log_filters_and_json_export_are_available(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Filtered Audit Project',
            'slug' => 'filtered-audit-project',
            'base_url' => 'https://api.filtered-audit.test',
            'is_active' => true,
        ]);

        $otherProject = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Other Audit Project',
            'slug' => 'other-audit-project',
            'base_url' => 'https://api.other-audit.test',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('projects.audit-log.index', [$project, 'action' => 'created']))
            ->assertOk()
            ->assertSee('Filtered Audit Project')
            ->assertDontSee('Other Audit Project')
            ->assertSee(route('projects.audit-log.json', $project), false);

        $this->actingAs($user)
            ->getJson(route('projects.audit-log.json', [$project, 'action' => 'created']))
            ->assertOk()
            ->assertJsonPath('version', config('aptoria.version'))
            ->assertJsonFragment(['name' => 'Filtered Audit Project'])
            ->assertJsonMissing(['name' => 'Other Audit Project']);
    }

    public function test_login_and_logout_are_explicit_audit_events(): void
    {
        $user = User::factory()->create([
            'email' => 'audit-login@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'audit-login@example.com',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => AuditLog::EVENT_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
        ]);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => AuditLog::EVENT_AUTH,
            'action' => AuditLog::ACTION_LOGOUT,
        ]);
    }
}
