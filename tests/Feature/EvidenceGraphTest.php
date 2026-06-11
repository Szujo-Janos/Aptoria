<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Models\RiskAcceptance;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Models\User;
use App\Services\Evidence\EvidenceGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceGraphTest extends TestCase
{
    use RefreshDatabase;

    public function test_evidence_graph_summarizes_endpoint_finding_and_release_links(): void
    {
        [$admin, $project, $endpoint, $finding] = $this->graphFixture('evidence-graph-summary@example.com');

        $graph = app(EvidenceGraphService::class)->summarize($project->fresh());

        $this->assertSame(1, $graph['summary']['endpoints']);
        $this->assertSame(1, $graph['summary']['findings']);
        $this->assertSame(2, $graph['summary']['finding_evidence']);
        $this->assertSame(1, $graph['summary']['release_decisions']);
        $this->assertSame(0, $graph['endpoint_maps']->first()['missing_links']->count());
        $this->assertTrue($graph['finding_chains']->first()['has_retest_evidence']);

        $this->actingAs($admin)
            ->get(route('projects.evidence-graph.index', $project))
            ->assertOk()
            ->assertSee(__('messages.evidence_graph.title'))
            ->assertSee('GET /orders/{id}')
            ->assertSee('Order response exposed internal status');

        $this->actingAs($admin)
            ->get(route('projects.evidence-graph.endpoint', [$project, $endpoint]))
            ->assertOk()
            ->assertSee(__('messages.evidence_graph.endpoint_map_title'))
            ->assertSee(__('messages.evidence_graph.nodes.scan_results'));

        $this->actingAs($admin)
            ->get(route('projects.evidence-graph.finding', [$project, $finding]))
            ->assertOk()
            ->assertSee(__('messages.evidence_graph.finding_chain_title'))
            ->assertSee(__('messages.findings.evidence_types.retest'));
    }

    public function test_release_evidence_graph_and_report_builder_include_evidence_graph_summary(): void
    {
        [$admin, $project] = $this->graphFixture('evidence-graph-report@example.com');

        $this->actingAs($admin)
            ->get(route('projects.evidence-graph.release', $project))
            ->assertOk()
            ->assertSee(__('messages.evidence_graph.release_graph'))
            ->assertSee(__('messages.evidence_graph.nodes.release_decision'));

        $this->actingAs($admin)
            ->post(route('projects.reports.builder.markdown', $project), [
                'title' => 'Evidence Graph QA Report',
                'audience' => 'release',
                'decision' => 'conditional',
                'sections' => ['evidence_graph'],
                'finding_limit' => 25,
                'endpoint_limit' => 25,
                'test_case_limit' => 25,
                'contract_result_limit' => 25,
                'include_evidence_details' => '1',
            ])
            ->assertOk()
            ->assertSee('Evidence Graph Summary', false)
            ->assertSee('Endpoint Evidence Map', false)
            ->assertSee('Finding Evidence Chain', false);
    }

    /** @return array{0: User, 1: Project, 2: Endpoint, 3: Finding} */
    private function graphFixture(string $email): array
    {
        $admin = User::query()->create([
            'name' => 'Evidence Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Evidence Graph API',
            'slug' => str('evidence-graph-api-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/orders/{id}',
            'name' => 'Show order',
            'auth_required' => true,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        EndpointAssertionRule::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
            'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
            'expected_value' => '200',
            'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            'enabled' => true,
        ]);

        $scanRun = ScanRun::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'status' => ScanRun::STATUS_COMPLETED,
            'mode' => 'safe',
            'total_endpoints' => 1,
            'scanned_count' => 1,
            'success_count' => 1,
            'warning_count' => 0,
            'error_count' => 0,
        ]);

        $scanResult = ScanResult::query()->create([
            'scan_run_id' => $scanRun->id,
            'endpoint_id' => $endpoint->id,
            'method' => Endpoint::METHOD_GET,
            'url' => 'https://api.example.test/orders/123',
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
            'response_time_ms' => 120,
            'content_type' => 'application/json',
            'response_size' => 240,
            'body_preview' => '{"id":123}',
            'risk_level' => Endpoint::RISK_HIGH,
        ]);

        Snapshot::query()->create([
            'project_id' => $project->id,
            'scan_run_id' => $scanRun->id,
            'created_by' => $admin->id,
            'name' => 'RC evidence snapshot',
            'snapshot_hash' => sha1('rc-evidence'),
            'endpoint_count' => 1,
        ]);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'scan_run_id' => $scanRun->id,
            'scan_result_id' => $scanResult->id,
            'title' => 'Order response exposed internal status',
            'source' => Finding::SOURCE_SCAN,
            'severity' => Finding::SEVERITY_HIGH,
            'priority' => Finding::PRIORITY_HIGH,
            'status' => Finding::STATUS_FIXED,
            'verification_status' => Finding::VERIFICATION_PENDING,
            'retest_required' => true,
        ]);

        FindingEvidence::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'type' => FindingEvidence::TYPE_JSON_RESPONSE,
            'source_label' => 'Safe scan',
            'content' => 'Original response included internal status.',
            'captured_by_user_id' => $admin->id,
            'captured_at' => now(),
        ]);

        FindingEvidence::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'type' => FindingEvidence::TYPE_RETEST,
            'source_label' => 'Retest',
            'content' => 'Retest evidence confirms the field is removed.',
            'captured_by_user_id' => $admin->id,
            'captured_at' => now(),
        ]);

        RiskAcceptance::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'accepted_by_user_id' => $admin->id,
            'accepted_until' => now()->addDays(14),
            'reason' => 'Temporary release limitation accepted for controlled RC testing.',
            'release_scope' => 'RC testing',
            'expiry_action' => RiskAcceptance::EXPIRY_ACTION_RENEW_OR_CLOSE,
            'status' => RiskAcceptance::STATUS_ACTIVE,
        ]);

        ReleaseDecision::query()->create([
            'project_id' => $project->id,
            'decision_owner_user_id' => $admin->id,
            'release_name' => 'RC evidence release',
            'target_environment' => 'Staging',
            'decision_status' => ReleaseDecision::STATUS_CONDITIONAL_GO,
            'decision_notes' => 'Proceed with evidence chain attached.',
            'release_score' => 82,
            'readiness_status' => 'warning',
            'blocker_count' => 0,
            'warning_count' => 1,
            'accepted_risk_count' => 1,
            'blind_spot_count' => 0,
            'decision_package_json' => ['package_version' => '1.1.24'],
            'package_checksum' => sha1('decision-package'),
        ]);

        return [$admin, $project, $endpoint, $finding];
    }
}
