<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\Project;
use App\Models\RiskAcceptance;
use App\Models\User;
use App\Services\ReleaseReadinessService;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskAcceptanceExpiryRenewalTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiring_accepted_risk_creates_readiness_warning(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $finding = Finding::factory()->create([
            'project_id' => $project->id,
            'severity' => 'high',
            'status' => 'triaged',
        ]);

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'accepted_by_user_id' => $user->id,
            'status' => 'active',
            'accepted_at' => now(),
            'accepted_until' => now()->addDays(3)->toDateString(),
            'reason' => 'Known limitation accepted for this release candidate.',
            'business_justification' => 'The release can continue because mitigation is documented.',
            'release_scope' => '0.0.29 QA gate',
        ]);

        $evaluation = app(ReleaseReadinessService::class)->evaluate($project);
        $risk = $evaluation['risk_acceptance'];
        $renewalCheck = collect($evaluation['checks'])->firstWhere('key', 'accepted_risk_renewal_window');

        $this->assertSame(1, $risk['active']);
        $this->assertSame(1, $risk['expiring_soon']);
        $this->assertSame(0, $risk['expired']);
        $this->assertSame('warning', $renewalCheck['level']);
    }

    public function test_expired_accepted_risk_blocks_readiness(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $finding = Finding::factory()->create([
            'project_id' => $project->id,
            'severity' => 'critical',
            'status' => 'confirmed',
        ]);

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'accepted_by_user_id' => $user->id,
            'status' => 'active',
            'accepted_at' => now()->subDays(10),
            'accepted_until' => now()->subDay()->toDateString(),
            'reason' => 'Temporary risk acceptance expired.',
            'business_justification' => 'The business acceptance window has ended.',
        ]);

        $evaluation = app(ReleaseReadinessService::class)->evaluate($project);
        $risk = $evaluation['risk_acceptance'];
        $expiryCheck = collect($evaluation['checks'])->firstWhere('key', 'accepted_risk_expiry');

        $this->assertSame(0, $risk['active']);
        $this->assertSame(1, $risk['expired']);
        $this->assertSame('blocker', $expiryCheck['level']);
    }

    public function test_risk_acceptance_can_be_renewed_from_finding_detail(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $finding = Finding::factory()->create([
            'project_id' => $project->id,
            'severity' => 'high',
            'status' => 'triaged',
        ]);
        $previous = RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'accepted_by_user_id' => $user->id,
            'status' => 'active',
            'accepted_at' => now()->subMonth(),
            'accepted_until' => now()->subDay()->toDateString(),
            'reason' => 'Initial acceptance expired.',
            'business_justification' => 'Initial release exception.',
        ]);

        $this->actingAs($user)
            ->post(route('projects.findings.risk-acceptance.renew', [$project, $finding]), [
                'accepted_until' => now()->addMonth()->toDateString(),
                'reason' => 'Renewed for the next release candidate.',
                'business_justification' => 'The mitigation is still valid and tracked.',
                'mitigation_note' => 'Manual monitoring remains active.',
                'release_scope' => '0.0.29 renewal window',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $this->assertDatabaseHas('risk_acceptances', [
            'id' => $previous->id,
            'status' => 'renewed',
        ]);

        $this->assertDatabaseHas('risk_acceptances', [
            'finding_id' => $finding->id,
            'status' => 'active',
            'renewed_from_id' => $previous->id,
            'release_scope' => '0.0.29 renewal window',
        ]);
    }
}
