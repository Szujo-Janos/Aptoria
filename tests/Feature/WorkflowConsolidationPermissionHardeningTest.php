<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\EndpointBehaviorLink;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Models\ReleaseWorkflow;
use App\Models\ReportVersion;
use App\Models\RiskAcceptance;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\ReleaseWorkflow\WorkflowConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowConsolidationPermissionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_workflow_consolidates_daily_qa_and_decision_steps(): void
    {
        [$admin, $project] = $this->fixture('release-workflow@example.com');

        $workflow = app(WorkflowConsolidationService::class)->summarize($project->fresh());

        $this->assertSame(15, $workflow['steps']->count());
        $this->assertTrue($workflow['steps']->contains('key', 'project_setup'));
        $this->assertTrue($workflow['steps']->contains('key', 'release_decision'));
        $this->assertTrue($workflow['steps']->contains('key', 'client_acknowledgement'));
        $this->assertGreaterThanOrEqual(1, $workflow['summary']['missing']);

        $this->actingAs($admin)
            ->get(route('projects.release-workflow.index', $project))
            ->assertOk()
            ->assertSee(__('messages.release_workflow.title'))
            ->assertSee(__('messages.release_workflow.steps.project_setup'))
            ->assertSee(__('messages.release_workflow.steps.report_approved'));

        $this->assertDatabaseHas('release_workflows', [
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseHas('release_workflow_steps', [
            'project_id' => $project->id,
            'step_key' => 'release_decision',
        ]);
    }

    public function test_release_workflow_step_can_be_skipped_with_reason_and_reopened(): void
    {
        [$admin, $project] = $this->fixture('release-workflow-skip@example.com');

        $this->actingAs($admin)
            ->get(route('projects.release-workflow.index', $project))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('projects.release-workflow.steps.skip', [$project, 'client_acknowledgement']), [
                'reason' => 'Client acknowledgement is handled in the external customer ticket for this dry run.',
            ])
            ->assertRedirect(route('projects.release-workflow.index', $project));

        $this->assertDatabaseHas('release_workflow_steps', [
            'project_id' => $project->id,
            'step_key' => 'client_acknowledgement',
            'state' => ReleaseWorkflow::STATE_SKIPPED,
        ]);

        $this->actingAs($admin)
            ->patch(route('projects.release-workflow.steps.reopen', [$project, 'client_acknowledgement']))
            ->assertRedirect(route('projects.release-workflow.index', $project));

        $this->assertDatabaseMissing('release_workflow_steps', [
            'project_id' => $project->id,
            'step_key' => 'client_acknowledgement',
            'manual_state' => ReleaseWorkflow::STATE_SKIPPED,
        ]);
    }

    public function test_release_decisions_risks_and_behavior_links_are_audit_logged(): void
    {
        [$admin, $project, $endpoint] = $this->fixture('audit-hardening@example.com', true);
        $this->actingAs($admin);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Audit accepted risk',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_OPEN,
        ]);

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'accepted_by_user_id' => $admin->id,
            'reason' => 'Temporary business acceptance.',
            'status' => RiskAcceptance::STATUS_ACTIVE,
        ]);

        ReleaseDecision::query()->create([
            'project_id' => $project->id,
            'decision_owner_user_id' => $admin->id,
            'release_name' => 'Audit RC',
            'decision_status' => ReleaseDecision::STATUS_NO_GO,
            'release_score' => 40,
            'package_checksum' => hash('sha256', 'audit-rc'),
        ]);

        EndpointBehaviorLink::query()->create([
            'project_id' => $project->id,
            'producer_endpoint_id' => $endpoint->id,
            'consumer_endpoint_id' => $endpoint->id,
            'dependency_type' => EndpointBehaviorLink::TYPE_RESOURCE_FLOW,
            'resource_key' => 'orders',
            'path_parameter' => 'id',
            'confidence' => 70,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'project_id' => $project->id,
            'auditable_type' => RiskAcceptance::class,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'project_id' => $project->id,
            'auditable_type' => ReleaseDecision::class,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'project_id' => $project->id,
            'auditable_type' => EndpointBehaviorLink::class,
        ]);
    }

    /** @return array{0: User, 1: Project, 2?: Endpoint} */
    private function fixture(string $email, bool $withEndpoint = false): array
    {
        $admin = User::query()->create([
            'name' => 'Workflow Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Workflow API',
            'slug' => str('workflow-api-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/orders/{id}',
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
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        ReportVersion::query()->create([
            'project_id' => $project->id,
            'generated_by_user_id' => $admin->id,
            'title' => 'Workflow draft report',
            'report_type' => ReportVersion::TYPE_RELEASE_READINESS,
            'status' => ReportVersion::STATUS_DRAFT,
            'markdown_content' => '# Draft',
            'content_checksum' => hash('sha256', 'Draft'),
        ]);

        return $withEndpoint ? [$admin, $project, $endpoint] : [$admin, $project];
    }
}
