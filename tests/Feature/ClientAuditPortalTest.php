<?php

namespace Tests\Feature;

use App\Models\ClientPortalAccess;
use App\Models\ClientPortalAcknowledgement;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Models\ReportVersion;
use App\Models\RiskAcceptance;
use App\Models\ScanRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAuditPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_public_client_portal_and_it_exposes_only_approved_project_evidence(): void
    {
        [$admin, $project, $approvedReport, $draftReport, $decision] = $this->fixture('client-portal@example.com');
        [$otherAdmin, $otherProject, $otherReport] = $this->otherFixture();

        $this->actingAs($admin)
            ->get(route('projects.client-portal.index', $project))
            ->assertOk()
            ->assertSee(__('messages.client_portal.title'))
            ->assertSee(__('messages.client_portal.create_access'));

        $response = $this->actingAs($admin)
            ->post(route('projects.client-portal.store', $project), [
                'label' => 'Acme release handoff',
                'contact_name' => 'Acme Reviewer',
                'contact_email' => 'reviewer@example.test',
                'role' => ClientPortalAccess::ROLE_CLIENT_APPROVER,
                ClientPortalAccess::PERMISSION_REPORTS => '1',
                ClientPortalAccess::PERMISSION_RELEASE_DECISIONS => '1',
                ClientPortalAccess::PERMISSION_ACCEPTED_RISKS => '1',
                ClientPortalAccess::PERMISSION_FINDINGS => '1',
                ClientPortalAccess::PERMISSION_EVIDENCE_PACKAGE => '1',
                ClientPortalAccess::PERMISSION_APPROVE_REPORTS => '1',
                ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE => '1',
                ClientPortalAccess::PERMISSION_APPROVE_RISKS => '1',
            ]);

        $access = ClientPortalAccess::query()->firstOrFail();
        $response->assertRedirect(route('projects.client-portal.index', $project));
        $this->assertSame($project->id, $access->project_id);
        $this->assertTrue($access->allows(ClientPortalAccess::PERMISSION_APPROVE_REPORTS));

        $this->get(route('client-portal.show', $access))
            ->assertOk()
            ->assertSee(__('messages.client_portal.public_title'))
            ->assertSee('Approved executive handoff')
            ->assertSee('RC client release')
            ->assertSee('Accepted risk needs client sign-off')
            ->assertDontSee('Draft internal report');

        $this->get(route('client-portal.reports.markdown', [$access, $approvedReport]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('Approved client evidence', false);

        $this->get(route('client-portal.reports.markdown', [$access, $draftReport]))
            ->assertNotFound();

        $this->get(route('client-portal.reports.markdown', [$access, $otherReport]))
            ->assertNotFound();

        $this->post(route('client-portal.acknowledgements.store', $access), [
            'acknowledgement_type' => ClientPortalAcknowledgement::TYPE_REPORT_APPROVAL,
            'report_version_id' => $approvedReport->id,
            'actor_name' => 'Acme Reviewer',
            'actor_email' => 'reviewer@example.test',
        ])->assertRedirect(route('client-portal.show', $access));

        $this->assertDatabaseHas('client_portal_acknowledgements', [
            'project_id' => $project->id,
            'client_portal_access_id' => $access->id,
            'report_version_id' => $approvedReport->id,
            'acknowledgement_type' => ClientPortalAcknowledgement::TYPE_REPORT_APPROVAL,
        ]);

        $this->get(route('client-portal.release-decisions.json', [$access, $decision]))
            ->assertOk()
            ->assertJsonPath('decision_status', ReleaseDecision::STATUS_CONDITIONAL_GO);

        $this->actingAs($admin)
            ->patch(route('projects.client-portal.revoke', [$project, $access]))
            ->assertRedirect(route('projects.client-portal.index', $project));

        $access->refresh();
        $this->assertSame(ClientPortalAccess::STATUS_REVOKED, $access->status);
        $this->get(route('client-portal.show', $access))->assertNotFound();
    }

    /** @return array{0: User, 1: Project, 2: ReportVersion, 3: ReportVersion, 4: ReleaseDecision} */
    private function fixture(string $email): array
    {
        $admin = User::query()->create([
            'name' => 'Client Portal Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Client Portal API',
            'slug' => str('client-portal-api-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/orders/42',
            'name' => 'Order detail',
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
            'total_endpoints' => 1,
            'scanned_count' => 1,
            'success_count' => 1,
        ]);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Accepted risk needs client sign-off',
            'source' => Finding::SOURCE_SCAN,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_OPEN,
        ]);

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'accepted_by_user_id' => $admin->id,
            'accepted_until' => now()->addMonth(),
            'reason' => 'Business accepts the known release risk for RC scope.',
            'status' => RiskAcceptance::STATUS_ACTIVE,
        ]);

        $approvedReport = ReportVersion::query()->create([
            'project_id' => $project->id,
            'generated_by_user_id' => $admin->id,
            'approved_by_user_id' => $admin->id,
            'title' => 'Approved executive handoff',
            'report_type' => ReportVersion::TYPE_EXECUTIVE,
            'status' => ReportVersion::STATUS_APPROVED,
            'content_checksum' => hash('sha256', 'Approved client evidence'),
            'markdown_content' => '# Approved client evidence',
            'approved_at' => now(),
        ]);

        $draftReport = ReportVersion::query()->create([
            'project_id' => $project->id,
            'generated_by_user_id' => $admin->id,
            'title' => 'Draft internal report',
            'report_type' => ReportVersion::TYPE_TECHNICAL,
            'status' => ReportVersion::STATUS_DRAFT,
            'content_checksum' => hash('sha256', 'Draft internal report'),
            'markdown_content' => '# Draft internal report',
        ]);

        $decision = ReleaseDecision::query()->create([
            'project_id' => $project->id,
            'decision_owner_user_id' => $admin->id,
            'release_name' => 'RC client release',
            'target_environment' => 'Production',
            'decision_status' => ReleaseDecision::STATUS_CONDITIONAL_GO,
            'decision_notes' => 'Conditional release with accepted risk acknowledgement.',
            'release_score' => 82,
            'package_checksum' => hash('sha256', 'client-portal-decision'),
        ]);

        return [$admin, $project, $approvedReport, $draftReport, $decision];
    }

    /** @return array{0: User, 1: Project, 2: ReportVersion} */
    private function otherFixture(): array
    {
        $admin = User::query()->create([
            'name' => 'Other Admin',
            'email' => 'other-client-portal@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Other Project',
            'slug' => str('other-project-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://other.example.test',
            'is_active' => true,
        ]);

        $report = ReportVersion::query()->create([
            'project_id' => $project->id,
            'generated_by_user_id' => $admin->id,
            'approved_by_user_id' => $admin->id,
            'title' => 'Other approved report',
            'report_type' => ReportVersion::TYPE_EXECUTIVE,
            'status' => ReportVersion::STATUS_APPROVED,
            'content_checksum' => hash('sha256', 'Other report'),
            'markdown_content' => '# Other report',
            'approved_at' => now(),
        ]);

        return [$admin, $project, $report];
    }
}
