<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Models\RiskAcceptance;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\ReleaseDecisions\ReleaseDecisionRoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseDecisionRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_decision_room_saves_a_decision_package_with_evidence_snapshot(): void
    {
        [$admin, $project] = $this->decisionFixture('release-decision-room@example.com');

        $summary = app(ReleaseDecisionRoomService::class)->summarize($project->fresh());
        $this->assertArrayHasKey('current_package', $summary);
        $this->assertArrayHasKey('evidence_ids', $summary['current_package']);
        $this->assertNotEmpty($summary['package_checksum']);

        $this->actingAs($admin)
            ->get(route('projects.release-decisions.index', $project))
            ->assertOk()
            ->assertSee(__('messages.release_decisions.title'))
            ->assertSee(__('messages.release_decisions.evidence_chain'));

        $response = $this->actingAs($admin)
            ->post(route('projects.release-decisions.store', $project), [
                'release_name' => 'RC-1 customer release',
                'target_environment' => 'Staging',
                'decision_status' => ReleaseDecision::STATUS_CONDITIONAL_GO,
                'decision_notes' => 'Proceed only after the accepted risk is reviewed with the customer.',
            ]);

        $decision = ReleaseDecision::query()->firstOrFail();
        $response->assertRedirect(route('projects.release-decisions.show', [$project, $decision]));

        $this->assertSame($project->id, $decision->project_id);
        $this->assertSame($admin->id, $decision->decision_owner_user_id);
        $this->assertSame(ReleaseDecision::STATUS_CONDITIONAL_GO, $decision->decision_status);
        $this->assertNotNull($decision->decided_at);
        $this->assertNotEmpty($decision->package_checksum);
        $this->assertIsArray($decision->decision_package_json);
        $this->assertArrayHasKey('finding_state_snapshot', $decision->decision_package_json);
        $this->assertArrayHasKey('accepted_risk_ledger', $decision->decision_package_json);
    }

    public function test_release_decision_package_exports_and_report_integration_are_available(): void
    {
        [$admin, $project] = $this->decisionFixture('release-decision-export@example.com');

        $decision = app(ReleaseDecisionRoomService::class)->createDecision($project->fresh(), [
            'release_name' => 'RC-2 release decision',
            'target_environment' => 'Production',
            'decision_status' => ReleaseDecision::STATUS_BLOCKED,
            'decision_notes' => 'No-Go until blocker evidence is cleared.',
        ], $admin);

        $this->actingAs($admin)
            ->get(route('projects.release-decisions.show', [$project, $decision]))
            ->assertOk()
            ->assertSee(__('messages.release_decisions.package_title'))
            ->assertSee('No-Go until blocker evidence is cleared.');

        $this->actingAs($admin)
            ->get(route('projects.release-decisions.markdown', [$project, $decision]))
            ->assertOk()
            ->assertSee('Aptoria Release Decision Package', false)
            ->assertSee('Release Decision Summary', false);

        $this->actingAs($admin)
            ->get(route('projects.release-decisions.json', [$project, $decision]))
            ->assertOk()
            ->assertJsonPath('decision_status', ReleaseDecision::STATUS_BLOCKED)
            ->assertJsonPath('decision_package.package_version', config('aptoria.version'));

        $this->actingAs($admin)
            ->get(route('projects.release-readiness.show', $project))
            ->assertOk()
            ->assertSee(__('messages.release_decisions.latest_decision'))
            ->assertSee(__('messages.release_decisions.statuses.blocked'));

        $this->actingAs($admin)
            ->post(route('projects.reports.builder.markdown', $project), [
                'title' => 'Decision Room QA Report',
                'audience' => 'release',
                'decision' => 'blocked',
                'sections' => ['executive_summary', 'release_readiness'],
                'finding_limit' => 25,
                'endpoint_limit' => 25,
                'test_case_limit' => 25,
                'contract_result_limit' => 25,
            ])
            ->assertOk()
            ->assertSee('Release Decision', false)
            ->assertSee('No-Go until blocker evidence is cleared.', false);
    }

    /** @return array{0: User, 1: Project} */
    private function decisionFixture(string $email): array
    {
        $admin = User::query()->create([
            'name' => 'Decision Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Decision Room API',
            'slug' => str('decision-room-api-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/billing/invoices',
            'name' => 'List invoices',
            'auth_required' => true,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        ScanRun::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'status' => ScanRun::STATUS_COMPLETED,
            'mode' => 'safe',
            'total_endpoints' => 1,
            'scanned_count' => 0,
            'success_count' => 0,
            'warning_count' => 1,
            'error_count' => 0,
        ]);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Invoice export accepted limitation',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_HIGH,
            'priority' => Finding::PRIORITY_HIGH,
            'status' => Finding::STATUS_ACCEPTED_RISK,
            'verification_status' => Finding::VERIFICATION_PENDING,
        ]);

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'accepted_by_user_id' => $admin->id,
            'accepted_until' => now()->addDays(7),
            'reason' => 'Customer accepts temporary invoice export limitation for RC testing.',
            'release_scope' => 'RC testing',
            'expiry_action' => RiskAcceptance::EXPIRY_ACTION_RENEW_OR_CLOSE,
            'status' => RiskAcceptance::STATUS_ACTIVE,
        ]);

        return [$admin, $project];
    }
}
