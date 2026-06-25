<?php

namespace Tests\Feature;

use App\Models\ClientPortalAccess;
use App\Models\Project;
use App\Models\ReleaseGate;
use App\Models\ReportVersion;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalDecisionHandoffTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portal_can_show_and_download_approved_release_gate_decision_package(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Handoff QA Project']);
        $gate = ReleaseGate::create([
            'project_id' => $project->id,
            'created_by_user_id' => $user->id,
            'finalized_by_user_id' => $user->id,
            'title' => 'Release 1.0 gate',
            'status' => 'approved',
            'automated_decision' => 'pass',
            'final_decision' => 'go',
            'score' => 94,
            'grade' => 'A',
            'blocker_count' => 0,
            'warning_count' => 1,
            'verified_evidence_count' => 3,
            'test_run_count' => 5,
            'decision_note' => 'Approved for client handoff.',
            'finalized_at' => now(),
        ]);

        $report = ReportVersion::create([
            'project_id' => $project->id,
            'generated_by_user_id' => $user->id,
            'approved_by_user_id' => $user->id,
            'release_gate_id' => $gate->id,
            'type' => 'release_decision',
            'status' => 'approved',
            'title' => 'Release Gate Decision Package',
            'content_markdown' => '# Release Gate Decision Package',
            'content_html' => '<h1>Release Gate Decision Package</h1>',
            'data_json' => [
                'source' => ['type' => 'release_gate_decision_package'],
                'release_gate' => [
                    'id' => $gate->id,
                    'title' => $gate->title,
                    'final_decision' => 'go',
                    'final_decision_label' => 'Go',
                    'score' => 94,
                    'grade' => 'A',
                    'blocker_count' => 0,
                    'warning_count' => 1,
                    'decision_note' => 'Approved for client handoff.',
                ],
                'metrics' => [
                    'score' => 94,
                    'grade' => 'A',
                    'blockers' => 0,
                    'warnings' => 1,
                    'verified_evidence' => 3,
                    'test_runs' => 5,
                ],
                'gate_items' => [],
            ],
            'checksum' => hash('sha256', 'handoff-package'),
            'generated_at' => now(),
            'approved_at' => now(),
        ]);

        $access = ClientPortalAccess::create([
            'project_id' => $project->id,
            'report_version_id' => $report->id,
            'created_by_user_id' => $user->id,
            'name' => 'Client decision package handoff',
            'role' => 'client_approver',
            'permissions_json' => ['decision_package'],
            'is_active' => true,
            'acknowledge_required' => true,
            'acknowledgement_status' => 'pending',
        ]);

        $this->get(route('client-portal.show', $access->token))
            ->assertOk()
            ->assertSee('Decision packages')
            ->assertSee('Release 1.0 gate')
            ->assertSee('94/100');

        $zip = $this->get(route('client-portal.reports.download', [$access->token, $report, 'zip']));
        $zip->assertOk();
        $this->assertStringStartsWith('PK', $zip->baseResponse->getContent());
        $this->assertSame('application/zip', $zip->headers->get('Content-Type'));
    }
}
