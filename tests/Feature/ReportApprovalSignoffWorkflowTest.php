<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportApprovalSignoffWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_approval_stores_review_notes_and_signoff_context(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create([
            'password_change_required' => false,
            'report_role_title' => 'QA Lead',
        ]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $report = $project->reportVersions()->create([
            'generated_by_user_id' => $user->id,
            'type' => 'release_decision',
            'status' => 'draft',
            'title' => 'Release candidate report',
            'content_markdown' => '# Release candidate',
            'content_html' => '<h1>Release candidate</h1>',
            'data_json' => ['candidate' => true],
            'checksum' => hash('sha256', 'candidate'),
            'generated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('projects.reports.status', [$project, $report]), [
                'status' => 'approved',
                'review_note' => 'Readiness, findings and evidence reviewed.',
                'approval_note' => 'Approved for client handoff.',
                'approval_signoff_name' => 'János Szujó',
                'approval_signoff_role' => 'QA Lead',
                'approval_signoff_statement' => 'I approve this fixed report version as release evidence.',
                'confirm_status' => '1',
            ])
            ->assertRedirect(route('projects.reports.show', [$project, $report]));

        $this->assertDatabaseHas('report_versions', [
            'id' => $report->id,
            'status' => 'approved',
            'review_note' => 'Readiness, findings and evidence reviewed.',
            'approval_note' => 'Approved for client handoff.',
            'approval_signoff_name' => 'János Szujó',
            'approval_signoff_role' => 'QA Lead',
            'approval_signoff_statement' => 'I approve this fixed report version as release evidence.',
        ]);

        $fresh = $report->fresh();
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertNotNull($fresh->approved_at);
        $this->assertNotNull($fresh->approval_signed_at);
        $this->assertSame($user->id, $fresh->reviewed_by_user_id);
        $this->assertSame($user->id, $fresh->approved_by_user_id);
        $this->assertSame('János Szujó', $fresh->approval_context_json['signoff_name']);
        $this->assertSame('release_decision', $fresh->approval_context_json['source_type']);

        $this->assertDatabaseHas('audit_logs', [
            'project_id' => $project->id,
            'action' => 'report_status_updated',
            'event_type' => 'report',
        ]);
    }

    public function test_report_approval_requires_signoff_name_and_statement(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $report = $project->reportVersions()->create([
            'generated_by_user_id' => $user->id,
            'type' => 'technical_summary',
            'status' => 'draft',
            'title' => 'Unsigned report',
            'content_markdown' => '# Unsigned',
            'content_html' => '<h1>Unsigned</h1>',
            'checksum' => hash('sha256', 'unsigned'),
            'generated_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('projects.reports.show', [$project, $report]))
            ->post(route('projects.reports.status', [$project, $report]), [
                'status' => 'approved',
                'confirm_status' => '1',
            ])
            ->assertRedirect(route('projects.reports.show', [$project, $report]))
            ->assertSessionHasErrors(['approval_signoff_name', 'approval_signoff_statement']);

        $this->assertSame('draft', $report->fresh()->status);
    }
}
