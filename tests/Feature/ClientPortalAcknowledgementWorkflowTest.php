<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ReportDeliveryService;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalAcknowledgementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_client_portal_acknowledgement_creates_history_record_and_updates_delivery_summary(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $report = $project->reportVersions()->create([
            'generated_by_user_id' => $user->id,
            'approved_by_user_id' => $user->id,
            'type' => 'release_decision',
            'status' => 'approved',
            'title' => 'Approved client handoff report',
            'content_markdown' => '# Approved',
            'content_html' => '<h1>Approved</h1>',
            'data_json' => ['approved' => true],
            'checksum' => hash('sha256', 'client-handoff'),
            'generated_at' => now(),
            'approved_at' => now(),
        ]);

        $access = app(ReportDeliveryService::class)->createDeliveryLink($project, $report, $user, [
            'name' => 'Client release acknowledgement',
            'acknowledge_required' => true,
        ]);

        $this->post(route('client-portal.acknowledge', $access->token), [
            'acknowledged_by_name' => 'Jane Reviewer',
            'acknowledged_by_email' => 'jane@example.com',
            'decision_status' => 'approved',
            'comment' => 'Approved with the documented evidence package.',
            'acknowledge_terms' => '1',
        ])->assertRedirect(route('client-portal.show', $access->token));

        $this->assertDatabaseHas('client_portal_acknowledgements', [
            'project_id' => $project->id,
            'client_portal_access_id' => $access->id,
            'report_version_id' => $report->id,
            'decision_status' => 'approved',
            'acknowledged_by_name' => 'Jane Reviewer',
            'acknowledged_by_email' => 'jane@example.com',
        ]);

        $freshAccess = $access->fresh();
        $this->assertSame('acknowledged', $freshAccess->acknowledgement_status);
        $this->assertSame('approved', $freshAccess->acknowledgement_decision);
        $this->assertNotNull($freshAccess->latest_acknowledgement_id);

        $summary = $report->fresh()->client_delivery_summary_json;
        $this->assertSame('approved', $summary['last_acknowledgement']['decision_status']);
        $this->assertSame(1, $summary['acknowledgement_count']);
    }
}
