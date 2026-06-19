<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_portal_rejects_non_approved_report_versions(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $report = $project->reportVersions()->create([
            'generated_by_user_id' => $user->id,
            'type' => 'release_decision',
            'status' => 'draft',
            'title' => 'Draft delivery candidate',
            'content_markdown' => '# Draft',
            'content_html' => '<h1>Draft</h1>',
            'data_json' => ['draft' => true],
            'checksum' => hash('sha256', 'draft'),
            'generated_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('projects.client-portal.index', $project))
            ->post(route('projects.client-portal.store', $project), [
                'name' => 'Draft portal link',
                'role' => 'client_viewer',
                'report_version_id' => $report->id,
                'permissions' => ['reports'],
            ])
            ->assertRedirect(route('projects.client-portal.index', $project))
            ->assertSessionHasErrors('report_version_id');
    }

    public function test_approved_report_can_create_delivery_link_from_report_detail(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $report = $project->reportVersions()->create([
            'generated_by_user_id' => $user->id,
            'approved_by_user_id' => $user->id,
            'type' => 'release_decision',
            'status' => 'approved',
            'title' => 'Approved release decision report',
            'content_markdown' => '# Approved',
            'content_html' => '<h1>Approved</h1>',
            'data_json' => ['approved' => true],
            'checksum' => hash('sha256', 'approved'),
            'generated_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('projects.reports.delivery-link', [$project, $report]), [
                'name' => 'Client handoff',
                'acknowledge_required' => '1',
                'confirm_delivery' => '1',
            ])
            ->assertRedirect(route('projects.reports.show', [$project, $report]));

        $this->assertDatabaseHas('client_portal_accesses', [
            'project_id' => $project->id,
            'report_version_id' => $report->id,
            'name' => 'Client handoff',
            'role' => 'client_approver',
        ]);

        $this->assertSame(1, $report->fresh()->client_delivery_count);
    }
}
