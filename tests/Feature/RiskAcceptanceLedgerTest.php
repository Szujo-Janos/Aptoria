<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\RiskAcceptance;
use App\Models\User;
use App\Services\ReleaseReadinessService;
use App\Services\Risk\RiskAcceptanceLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskAcceptanceLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_risk_creates_auditable_ledger_record_and_syncs_finding(): void
    {
        [$admin, $project, $finding] = $this->riskFixture('risk-ledger-create@example.com');

        $this->actingAs($admin)
            ->post(route('projects.findings.risk-acceptances.store', [$project, $finding]), [
                'accepted_until' => now()->addDays(30)->format('Y-m-d'),
                'reason' => 'OAuth callback limitation accepted for RC-2 while upstream provider rollout is pending.',
                'business_justification' => 'The affected client is still on the legacy identity provider.',
                'mitigation_note' => 'Limit scope to staging release and monitor failed token refreshes.',
                'evidence_requirement' => 'Attach retest evidence before production rollout.',
                'release_scope' => 'RC-2 staging',
                'expiry_action' => RiskAcceptance::EXPIRY_ACTION_RENEW_OR_CLOSE,
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $acceptance = RiskAcceptance::query()->firstOrFail();

        $this->assertSame($project->id, $acceptance->project_id);
        $this->assertSame($finding->id, $acceptance->finding_id);
        $this->assertSame($admin->id, $acceptance->accepted_by_user_id);
        $this->assertSame(RiskAcceptance::STATUS_ACTIVE, $acceptance->status);
        $this->assertSame(RiskAcceptance::EXPIRY_ACTION_RENEW_OR_CLOSE, $acceptance->expiry_action);
        $this->assertFalse($acceptance->is_expired);

        $finding->refresh();
        $this->assertSame(Finding::STATUS_ACCEPTED_RISK, $finding->status);
        $this->assertNotNull($finding->accepted_risk_expires_at);
        $this->assertStringContainsString('OAuth callback limitation', (string) $finding->accepted_risk_note);
    }

    public function test_risk_ledger_page_filters_missing_expired_and_high_risk_acceptances(): void
    {
        [$admin, $project, $finding] = $this->riskFixture('risk-ledger-page@example.com');

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'accepted_by_user_id' => $admin->id,
            'accepted_until' => null,
            'reason' => 'Accepted without expiry for a temporary customer exception.',
            'release_scope' => 'Customer UAT',
            'expiry_action' => RiskAcceptance::EXPIRY_ACTION_REVIEW,
            'status' => RiskAcceptance::STATUS_ACTIVE,
        ]);

        $expiredFinding = Finding::query()->create([
            'project_id' => $project->id,
            'title' => 'Expired accepted rate-limit bypass',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_CRITICAL,
            'status' => Finding::STATUS_ACCEPTED_RISK,
        ]);

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $expiredFinding->id,
            'accepted_by_user_id' => $admin->id,
            'accepted_until' => now()->subDay(),
            'reason' => 'Temporary bypass expired.',
            'release_scope' => 'RC-1',
            'expiry_action' => RiskAcceptance::EXPIRY_ACTION_BLOCK_RELEASE,
            'status' => RiskAcceptance::STATUS_ACTIVE,
        ]);

        $summary = app(RiskAcceptanceLedgerService::class)->summarize($project->fresh());
        $this->assertSame(2, $summary['summary']['active']);
        $this->assertSame(1, $summary['summary']['without_expiry']);
        $this->assertSame(1, $summary['summary']['expired']);
        $this->assertSame(2, $summary['summary']['active_high_or_critical']);

        $this->actingAs($admin)
            ->get(route('projects.risk-acceptances.index', [$project, 'expiry' => 'missing']))
            ->assertOk()
            ->assertSee(__('messages.risk_acceptances.title'))
            ->assertSee('Accepted without expiry')
            ->assertDontSee('Temporary bypass expired');

        $this->actingAs($admin)
            ->get(route('projects.risk-acceptances.index', [$project, 'expiry' => 'expired']))
            ->assertOk()
            ->assertSee('Temporary bypass expired')
            ->assertSee(__('messages.risk_acceptances.statuses.expired'));
    }

    public function test_release_readiness_and_full_report_include_risk_acceptance_summary(): void
    {
        [$admin, $project, $finding] = $this->riskFixture('risk-ledger-readiness@example.com');

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'accepted_by_user_id' => $admin->id,
            'accepted_until' => now()->subDay(),
            'reason' => 'Expired production exception must block the release decision.',
            'release_scope' => 'Production RC',
            'expiry_action' => RiskAcceptance::EXPIRY_ACTION_BLOCK_RELEASE,
            'status' => RiskAcceptance::STATUS_ACTIVE,
        ]);

        $summary = app(ReleaseReadinessService::class)->summarize($project->fresh());

        $this->assertSame(1, $summary['risk_acceptances']['summary']['expired']);
        $this->assertTrue(collect($summary['blocking_issues'])->contains(__('messages.release_readiness.issues.expired_risk_acceptances', ['count' => 1])));

        $this->actingAs($admin)
            ->get(route('projects.release-readiness.show', $project))
            ->assertOk()
            ->assertSee(__('messages.risk_acceptances.report_summary_title'))
            ->assertSee(__('messages.risk_acceptances.metrics.expired'));

        $this->actingAs($admin)
            ->post(route('projects.reports.builder.markdown', $project), [
                'title' => 'Risk Ledger QA Report',
                'audience' => 'release',
                'decision' => 'conditional',
                'sections' => ['executive_summary', 'release_readiness', 'findings_evidence'],
                'finding_limit' => 25,
                'endpoint_limit' => 25,
                'test_case_limit' => 25,
                'contract_result_limit' => 25,
                'include_evidence_details' => '1',
            ])
            ->assertOk()
            ->assertSee('Risk Acceptance Ledger Summary', false)
            ->assertSee('Expired production exception must block the release decision.', false);
    }

    /** @return array{0: User, 1: Project, 2: Finding} */
    private function riskFixture(string $email): array
    {
        $admin = User::query()->create([
            'name' => 'Risk Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Risk Ledger API',
            'slug' => str('risk-ledger-api-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/oauth/callback',
            'name' => 'OAuth callback',
            'auth_required' => true,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'OAuth callback accepted limitation',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_HIGH,
            'priority' => Finding::PRIORITY_HIGH,
            'status' => Finding::STATUS_CONFIRMED,
            'verification_status' => Finding::VERIFICATION_PENDING,
        ]);

        return [$admin, $project, $finding];
    }
}
