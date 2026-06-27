<?php

namespace App\Services;

use App\Models\AuthProfile;
use App\Models\CalendarEvent;
use App\Models\ClientPortalAccess;
use App\Models\ClientPortalAcknowledgement;
use App\Models\ContractValidationResult;
use App\Models\ContractValidationRun;
use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\EndpointSnapshot;
use App\Models\EndpointSnapshotCompare;
use App\Models\EndpointSnapshotCompareItem;
use App\Models\EndpointSnapshotItem;
use App\Models\EndpointTestBatch;
use App\Models\EndpointTestRun;
use App\Models\ExternalImportRun;
use App\Models\Environment;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\ReleaseDecisionSnapshot;
use App\Models\ReleaseReadinessRun;
use App\Models\ReportVersion;
use App\Models\RiskAcceptance;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\User;
use App\Models\TestSuite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ComprehensiveDemoProjectService
{
    public const DEMO_SLUG = 'aptoria-full-demo-workspace';

    public function build(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $deletedProjects = $this->deleteExistingDemoProjects();

            $project = Project::create([
                'user_id' => $user->id,
                'name' => 'Aptoria Full Demo Workspace',
                'slug' => self::DEMO_SLUG,
                'description' => 'A full synthetic showcase workspace showing every major Aptoria module: inventory, environments, auth, safe scan evidence, assertions, snapshots, regressions, findings, retest, risk acceptance, imports, native tests, release gates, reports, evidence packs, calendar, audit log and client portal handoff.',
                'base_url' => rtrim((string) config('aptoria.demo.api_base_url', 'https://demo.aptoria.dev/demo-api'), '/'),
                'environment_label' => 'staging-demo',
                'status' => 'active',
                'workspace_type' => Project::WORKSPACE_TYPE_SANDBOX,
                'qa_owner' => $user->name,
                'release_goal' => 'Show the full Aptoria evidence-first API QA and release decision workflow in one safe public demo workspace.',
                'is_active' => true,
            ]);

            app(ProjectAccessService::class)->ensureOwnerMembership($project);

            [$staging, $production] = $this->createEnvironments($project);
            [$publicAuth, $bearerAuth, $clientAuth] = $this->createAuthProfiles($project);
            $this->createProjectSettings($project, $staging, $bearerAuth);

            $endpoints = $this->createEndpoints($project, $staging, $bearerAuth, $clientAuth);
            $scanRun = $this->createScanEvidence($project, $staging, $bearerAuth, $endpoints);
            $batch = $this->createEndpointQuickTestEvidence($project, $staging, $bearerAuth, $endpoints);
            $this->createAssertions($project, $endpoints);
            [$baselineSnapshot, $targetSnapshot, $compare, $regressionItem] = $this->createSnapshotsAndCompare($project, $user, $batch, $endpoints);
            [$criticalFinding, $highFinding, $verifiedFinding, $acceptedFinding] = $this->createFindingsEvidenceAndRisk($project, $user, $scanRun, $endpoints, $compare, $regressionItem);
            $contractRun = $this->createContractValidation($project, $user, $endpoints);
            $this->createExternalImportPreview($project, $user, $endpoints);
            $readinessRun = $this->createReleaseReadinessRun($project, $user, $scanRun, $batch, $targetSnapshot, $compare, $contractRun);
            $decision = $this->createReleaseDecision($project, $user, $readinessRun);
            [$report, $portalAccess] = $this->createApprovedReportAndPortal($project, $user, $readinessRun, $decision);
            $this->createCalendarAndAuditTrail($project, $user, $readinessRun, $report, $portalAccess);

            $project->refresh();

            return [
                'project' => $project,
                'deleted_projects' => $deletedProjects,
                'summary' => [
                    'environments' => $project->environments()->count(),
                    'auth_profiles' => $project->authProfiles()->count(),
                    'endpoints' => $project->endpoints()->count(),
                    'scan_runs' => $project->scanRuns()->count(),
                    'quick_tests' => $project->endpointTestRuns()->count(),
                    'assertions' => $project->assertionRules()->count(),
                    'snapshots' => $project->endpointSnapshots()->count(),
                    'snapshot_compares' => $project->endpointSnapshotCompares()->count(),
                    'findings' => $project->findings()->count(),
                    'evidence' => $project->evidence()->count(),
                    'risk_acceptances' => $project->riskAcceptances()->count(),
                    'contract_validations' => $project->contractValidationRuns()->count(),
                    'external_imports' => Schema::hasTable('external_import_runs') ? $project->externalImportRuns()->count() : 0,
                    'readiness_runs' => $project->releaseReadinessRuns()->count(),
                    'release_decisions' => $project->releaseDecisionSnapshots()->count(),
                    'report_versions' => $project->reportVersions()->count(),
                    'client_portal_links' => $project->clientPortalAccesses()->count(),
                    'calendar_events' => $project->calendarEvents()->count(),
                ],
                'highlight_ids' => [
                    'readiness_run_id' => $readinessRun->id,
                    'report_version_id' => $report->id,
                    'client_portal_access_id' => $portalAccess->id,
                    'critical_finding_id' => $criticalFinding->id,
                    'high_finding_id' => $highFinding->id,
                    'verified_finding_id' => $verifiedFinding->id,
                    'accepted_finding_id' => $acceptedFinding->id,
                ],
            ];
        });
    }

    private function deleteExistingDemoProjects(): int
    {
        $projectIds = Project::query()
            ->whereIn('slug', [self::DEMO_SLUG, 'aptoria-full-demo', 'aptoria-guided-demo-sandbox'])
            ->orWhereIn('name', ['Aptoria Full Demo Project', 'Aptoria Guided Demo Sandbox'])
            ->pluck('id');

        if ($projectIds->isEmpty()) {
            return 0;
        }

        $ids = $projectIds->all();

        if (Schema::hasTable('client_portal_accesses') && Schema::hasColumn('client_portal_accesses', 'latest_acknowledgement_id')) {
            DB::table('client_portal_accesses')->whereIn('project_id', $ids)->update(['latest_acknowledgement_id' => null]);
        }

        foreach ([
            'client_portal_acknowledgements',
            'client_portal_accesses',
            'contract_validation_results',
            'contract_validation_runs',
            'external_import_items',
            'external_import_runs',
            'report_versions',
            'release_decision_snapshots',
            'release_readiness_runs',
            'risk_acceptances',
            'finding_evidence',
            'findings',
            'endpoint_snapshot_compare_items',
            'endpoint_snapshot_compares',
            'endpoint_snapshot_items',
            'endpoint_snapshots',
            'endpoint_test_runs',
            'endpoint_test_batches',
            'endpoint_assertion_rules',
            'scan_results',
            'scan_runs',
            'endpoints',
            'auth_profiles',
            'environments',
            'project_settings',
            'calendar_events',
            'audit_logs',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'project_id')) {
                DB::table($table)->whereIn('project_id', $ids)->delete();
            }
        }

        Project::query()->whereIn('id', $ids)->delete();

        return count($ids);
    }

    private function createEnvironments(Project $project): array
    {
        $staging = Environment::create([
            'project_id' => $project->id,
            'name' => 'Demo Staging',
            'base_url' => rtrim((string) config('aptoria.demo.api_base_url', 'https://demo.aptoria.dev/demo-api'), '/'),
            'environment_type' => 'staging',
            'is_production' => false,
            'is_default' => true,
            'notes' => 'Safe synthetic environment used for demo scans, quick tests and report evidence.',
        ]);

        $production = Environment::create([
            'project_id' => $project->id,
            'name' => 'Demo Production',
            'base_url' => rtrim((string) config('aptoria.demo.api_base_url', 'https://demo.aptoria.dev/demo-api'), '/'),
            'environment_type' => 'production',
            'is_production' => true,
            'is_default' => false,
            'notes' => 'Production target placeholder. Demo scans remain represented by stored evidence only.',
        ]);

        return [$staging, $production];
    }

    private function createAuthProfiles(Project $project): array
    {
        $public = AuthProfile::create([
            'project_id' => $project->id,
            'name' => 'Public / no auth',
            'type' => 'none',
            'is_default' => false,
            'notes' => 'Used by public health and catalogue endpoints.',
        ]);

        $bearer = AuthProfile::create([
            'project_id' => $project->id,
            'name' => 'Demo Bearer QA Token',
            'type' => 'bearer',
            'encrypted_token' => 'demo-token-never-use-in-production',
            'is_default' => true,
            'notes' => 'Synthetic token profile for protected demo endpoints. Stored through the encrypted cast.',
        ]);

        $client = AuthProfile::create([
            'project_id' => $project->id,
            'name' => 'Client Header Demo',
            'type' => 'custom_header',
            'header_name' => 'X-Demo-Client',
            'encrypted_header_value' => 'client-demo-key',
            'is_default' => false,
            'notes' => 'Synthetic custom header example for client portal related API paths.',
        ]);

        return [$public, $bearer, $client];
    }

    private function createProjectSettings(Project $project, Environment $environment, AuthProfile $authProfile): void
    {
        foreach ([
            'scan.default_environment_id' => (string) $environment->id,
            'scan.default_auth_profile_id' => (string) $authProfile->id,
            'scan.require_confirmation' => '1',
            'scan.safe_methods_only' => '1',
            'scan.allow_private_networks' => '0',
            'project.notes' => 'Generated by the Aptoria comprehensive demo project builder.',
        ] as $key => $value) {
            $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function createEndpoints(Project $project, Environment $environment, AuthProfile $bearerAuth, AuthProfile $clientAuth): array
    {
        $definitions = [
            'health' => ['GET', '/health', 'Health check', false, null, 'low', 'platform,smoke', 200, 'application/json'],
            'profile' => ['GET', '/security/public-profile', 'Public profile payload', false, null, 'review', 'auth,profile,smoke', 200, 'application/json'],
            'orders' => ['GET', '/orders', 'Order list', false, null, 'high', 'orders,regression,release', 200, 'application/json'],
            'order_detail' => ['GET', '/orders/1001', 'Order detail', false, null, 'review', 'orders,contract', 200, 'application/json'],
            'billing' => ['GET', '/security/leaky-token-example', 'Sensitive token example', false, null, 'critical', 'billing,sensitive,regression', 200, 'application/json'],
            'client_report' => ['GET', '/reports/summary', 'Latest client report package', false, null, 'review', 'client-portal,reports', 200, 'application/json'],
            'feature_flags' => ['GET', '/scenarios', 'Scenario template registry', false, null, 'low', 'release,configuration', 200, 'application/json'],
        ];

        $endpoints = [];

        foreach ($definitions as $key => [$method, $path, $name, $authRequired, $authProfileId, $riskLevel, $tags, $expectedStatus, $expectedContentType]) {
            $endpoints[$key] = Endpoint::create([
                'project_id' => $project->id,
                'environment_id' => $environment->id,
                'auth_profile_id' => $authProfileId,
                'method' => $method,
                'path' => $path,
                'name' => $name,
                'description' => 'Demo endpoint used to show Aptoria evidence-first release QA workflows.',
                'tags' => $tags,
                'auth_required' => $authRequired,
                'expected_status' => $expectedStatus,
                'expected_content_type' => $expectedContentType,
                'risk_level' => $riskLevel,
                'is_active' => true,
                'excluded_from_scan' => false,
                'notes' => 'Generated by comprehensive demo project builder.',
            ]);
        }

        return $endpoints;
    }

    private function createScanEvidence(Project $project, Environment $environment, AuthProfile $authProfile, array $endpoints): ScanRun
    {
        $startedAt = now()->subHours(5);
        $scanRun = ScanRun::create([
            'project_id' => $project->id,
            'environment_id' => $environment->id,
            'auth_profile_id' => $authProfile->id,
            'profile' => 'safe',
            'status' => 'completed',
            'started_at' => $startedAt,
            'completed_at' => $startedAt->copy()->addMinutes(3),
            'duration_ms' => 184000,
            'summary_json' => ['total' => 7, 'passed' => 5, 'warning' => 1, 'failed' => 1, 'skipped' => 0],
        ]);

        $results = [
            'health' => ['passed', 200, 86, 72, 'low', 'Public health response matched baseline.', true, true, '{"ok":true,"version":"1.4.0-rc"}'],
            'profile' => ['passed', 200, 132, 540, 'review', 'Authenticated profile endpoint responded with masked user payload.', true, true, '{"id":42,"role":"qa_reviewer"}'],
            'orders' => ['warning', 200, 780, 6132, 'high', 'Slow response exceeded the release warning threshold.', true, true, '{"data":[{"id":1001,"status":"processing"}]}'],
            'order_detail' => ['passed', 200, 190, 1804, 'review', 'Order detail schema matched expected response contract.', true, true, '{"id":1001,"items":3,"status":"processing"}'],
            'billing' => ['failed', 500, 920, 310, 'critical', 'Server error on sensitive billing endpoint.', false, true, '{"error":"invoice_provider_timeout"}'],
            'client_report' => ['passed', 200, 145, 920, 'review', 'Client report package metadata available.', true, true, '{"latest":"release-decision-demo"}'],
            'feature_flags' => ['passed', 200, 118, 400, 'low', 'Release flags returned expected active/inactive states.', true, true, '{"flags":{"newCheckout":true}}'],
        ];

        foreach ($results as $key => [$status, $code, $time, $size, $riskLevel, $riskReason, $statusMatched, $contentMatched, $body]) {
            $endpoint = $endpoints[$key];
            ScanResult::create([
                'scan_run_id' => $scanRun->id,
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'environment_id' => $environment->id,
                'auth_profile_id' => $endpoint->auth_profile_id,
                'method' => $endpoint->method,
                'url' => rtrim($environment->base_url, '/').$endpoint->path,
                'status' => $status,
                'status_code' => $code,
                'response_time_ms' => $time,
                'content_type' => 'application/json',
                'response_size' => $size,
                'headers_json' => ['content-type' => ['application/json'], 'x-demo-trace' => [Str::lower(Str::random(12))]],
                'body_preview' => $body,
                'expected_status_matched' => $statusMatched,
                'expected_content_type_matched' => $contentMatched,
                'risk_level' => $riskLevel,
                'risk_reason' => $riskReason,
            ]);
        }

        return $scanRun;
    }

    private function createEndpointQuickTestEvidence(Project $project, Environment $environment, AuthProfile $authProfile, array $endpoints): EndpointTestBatch
    {
        $startedAt = now()->subHours(4);
        $batch = EndpointTestBatch::create([
            'project_id' => $project->id,
            'state' => 'failed',
            'tone' => 'danger',
            'total' => 7,
            'passed' => 5,
            'warning' => 1,
            'failed' => 1,
            'skipped' => 0,
            'summary_json' => ['total' => 7, 'passed' => 5, 'warning' => 1, 'failed' => 1, 'assertion_failed' => 2],
            'started_at' => $startedAt,
            'completed_at' => $startedAt->copy()->addMinutes(2),
        ]);

        foreach ($endpoints as $key => $endpoint) {
            $failed = $key === 'billing';
            $warning = $key === 'orders';
            EndpointTestRun::create([
                'project_id' => $project->id,
                'endpoint_test_batch_id' => $batch->id,
                'endpoint_id' => $endpoint->id,
                'environment_id' => $environment->id,
                'auth_profile_id' => $endpoint->auth_profile_id ?: $authProfile->id,
                'method' => $endpoint->method,
                'url' => rtrim($environment->base_url, '/').$endpoint->path,
                'state' => $failed ? 'failed' : ($warning ? 'warning' : 'passed'),
                'tone' => $failed ? 'danger' : ($warning ? 'warning' : 'success'),
                'message' => $failed ? 'HTTP 500 returned during synthetic demo quick test.' : ($warning ? 'Response time exceeded demo threshold.' : 'Quick test passed.'),
                'expected_status' => 200,
                'status_code' => $failed ? 500 : 200,
                'status_matched' => ! $failed,
                'expected_content_type' => 'application/json',
                'content_type' => 'application/json',
                'content_type_matched' => true,
                'assertion_total' => 3,
                'assertion_passed' => $failed ? 1 : ($warning ? 2 : 3),
                'assertion_failed' => $failed ? 2 : ($warning ? 1 : 0),
                'assertion_summary_json' => [
                    'passed' => $failed ? 1 : ($warning ? 2 : 3),
                    'failed' => $failed ? 2 : ($warning ? 1 : 0),
                    'items' => [
                        ['rule' => 'status_code', 'state' => $failed ? 'failed' : 'passed'],
                        ['rule' => 'max_response_time', 'state' => $warning ? 'failed' : 'passed'],
                    ],
                ],
                'response_time_ms' => $failed ? 920 : ($warning ? 780 : 120),
                'response_size' => $failed ? 310 : 900,
                'body_preview' => $failed ? '{"error":"invoice_provider_timeout"}' : '{"ok":true}',
                'checked_at' => $startedAt->copy()->addMinutes(2),
            ]);
        }

        return $batch;
    }

    private function createAssertions(Project $project, array $endpoints): void
    {
        $rules = [
            ['Global HTTP 2xx status', null, 'status_code', 'equals', '200', null, 'blocker', 'Every safe GET endpoint should return HTTP 200 before release.'],
            ['Global JSON content type', null, 'content_type_contains', 'contains', 'application/json', null, 'warning', 'Responses should remain JSON for reportable API endpoints.'],
            ['Order list under 500ms', $endpoints['orders']->id, 'max_response_time', 'less_than_or_equal', '500', null, 'warning', 'Order list performance should stay under the release threshold.'],
            ['Billing must not expose errors', $endpoints['billing']->id, 'body_not_contains', 'not_contains', 'invoice_provider_timeout', null, 'blocker', 'Billing provider errors must be handled before client delivery.'],
            ['Client report has latest key', $endpoints['client_report']->id, 'body_contains', 'contains', 'latest', 'latest', 'info', 'Client handoff endpoint should contain latest report metadata.'],
        ];

        foreach ($rules as [$name, $endpointId, $key, $operator, $expected, $targetPath, $severity, $description]) {
            EndpointAssertionRule::create([
                'project_id' => $project->id,
                'endpoint_id' => $endpointId,
                'name' => $name,
                'rule_key' => $key,
                'operator' => $operator,
                'expected_value' => $expected,
                'target_path' => $targetPath,
                'severity' => $severity,
                'enabled' => true,
                'description' => $description,
            ]);
        }
    }

    private function createSnapshotsAndCompare(Project $project, User $user, EndpointTestBatch $batch, array $endpoints): array
    {
        $baselineAt = now()->subDays(5);
        $targetAt = now()->subHours(3);
        $baseline = EndpointSnapshot::create([
            'project_id' => $project->id,
            'endpoint_test_batch_id' => $batch->id,
            'created_by_user_id' => $user->id,
            'title' => 'Demo baseline snapshot · v1.3 stable',
            'status' => 'captured',
            'tone' => 'success',
            'total' => 6,
            'passed' => 6,
            'warning' => 0,
            'failed' => 0,
            'skipped' => 0,
            'checksum' => hash('sha256', 'aptoria-demo-baseline'),
            'summary_json' => ['release' => 'v1.3 stable', 'source' => 'demo'],
            'notes' => 'Synthetic stable baseline before the v1.4 release candidate.',
            'captured_at' => $baselineAt,
        ]);

        $target = EndpointSnapshot::create([
            'project_id' => $project->id,
            'endpoint_test_batch_id' => $batch->id,
            'created_by_user_id' => $user->id,
            'title' => 'Demo target snapshot · v1.4 RC',
            'status' => 'captured',
            'tone' => 'danger',
            'total' => 7,
            'passed' => 5,
            'warning' => 1,
            'failed' => 1,
            'skipped' => 0,
            'checksum' => hash('sha256', 'aptoria-demo-target'),
            'summary_json' => ['release' => 'v1.4 release candidate', 'source' => 'demo'],
            'notes' => 'Synthetic target snapshot with one regression and one new endpoint.',
            'captured_at' => $targetAt,
        ]);

        $baselineItems = [];
        $targetItems = [];

        foreach ($endpoints as $key => $endpoint) {
            $signature = $endpoint->method.' '.$endpoint->path;
            $targetState = $key === 'billing' ? 'failed' : ($key === 'orders' ? 'warning' : 'passed');
            $targetTone = $key === 'billing' ? 'danger' : ($key === 'orders' ? 'warning' : 'success');
            $targetChecksum = hash('sha256', 'target-'.$signature.'-'.$targetState);

            if ($key !== 'feature_flags') {
                $baselineItems[$key] = EndpointSnapshotItem::create([
                    'endpoint_snapshot_id' => $baseline->id,
                    'project_id' => $project->id,
                    'endpoint_id' => $endpoint->id,
                    'endpoint_signature' => $signature,
                    'endpoint_name' => $endpoint->name,
                    'method' => $endpoint->method,
                    'path' => $endpoint->path,
                    'url' => ''.rtrim((string) config('aptoria.demo.api_base_url', 'https://demo.aptoria.dev/demo-api'), '/').''.$endpoint->path,
                    'state' => 'passed',
                    'tone' => 'success',
                    'status_code' => 200,
                    'content_type' => 'application/json',
                    'response_time_ms' => 130,
                    'response_size' => 600,
                    'assertion_total' => 3,
                    'assertion_failed' => 0,
                    'item_checksum' => hash('sha256', 'baseline-'.$signature.'-passed'),
                    'evidence_json' => ['source' => 'demo baseline'],
                ]);
            }

            $targetItems[$key] = EndpointSnapshotItem::create([
                'endpoint_snapshot_id' => $target->id,
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'endpoint_signature' => $signature,
                'endpoint_name' => $endpoint->name,
                'method' => $endpoint->method,
                'path' => $endpoint->path,
                'url' => ''.rtrim((string) config('aptoria.demo.api_base_url', 'https://demo.aptoria.dev/demo-api'), '/').''.$endpoint->path,
                'state' => $targetState,
                'tone' => $targetTone,
                'status_code' => $key === 'billing' ? 500 : 200,
                'content_type' => 'application/json',
                'response_time_ms' => $key === 'orders' ? 780 : ($key === 'billing' ? 920 : 140),
                'response_size' => $key === 'billing' ? 310 : 800,
                'assertion_total' => 3,
                'assertion_failed' => $key === 'billing' ? 2 : ($key === 'orders' ? 1 : 0),
                'item_checksum' => $targetChecksum,
                'evidence_json' => ['source' => 'demo target', 'state' => $targetState],
            ]);
        }

        $compare = EndpointSnapshotCompare::create([
            'project_id' => $project->id,
            'baseline_snapshot_id' => $baseline->id,
            'target_snapshot_id' => $target->id,
            'compared_by_user_id' => $user->id,
            'status' => 'blocked',
            'tone' => 'danger',
            'total_items' => 7,
            'unchanged_count' => 4,
            'changed_count' => 1,
            'added_count' => 1,
            'removed_count' => 0,
            'regressed_count' => 1,
            'improved_count' => 0,
            'regression_finding_count' => 1,
            'regression_findings_generated_at' => now()->subHours(2),
            'regression_finding_summary_json' => ['generated' => 1, 'critical' => 1],
            'summary_json' => ['headline' => 'One critical billing regression detected in the release candidate.'],
            'notes' => 'Synthetic compare used by the comprehensive demo project.',
            'compared_at' => now()->subHours(2),
        ]);

        foreach ($endpoints as $key => $endpoint) {
            $changeType = match ($key) {
                'billing' => 'regressed',
                'orders' => 'changed',
                'feature_flags' => 'added',
                default => 'unchanged',
            };
            $tone = match ($changeType) {
                'regressed' => 'danger',
                'changed' => 'warning',
                'added' => 'info',
                default => 'success',
            };

            $item = EndpointSnapshotCompareItem::create([
                'endpoint_snapshot_compare_id' => $compare->id,
                'project_id' => $project->id,
                'baseline_item_id' => $baselineItems[$key]->id ?? null,
                'target_item_id' => $targetItems[$key]->id,
                'endpoint_signature' => $endpoint->method.' '.$endpoint->path,
                'method' => $endpoint->method,
                'path' => $endpoint->path,
                'change_type' => $changeType,
                'tone' => $tone,
                'baseline_state' => isset($baselineItems[$key]) ? 'passed' : null,
                'target_state' => $targetItems[$key]->state,
                'baseline_status_code' => isset($baselineItems[$key]) ? 200 : null,
                'target_status_code' => $key === 'billing' ? 500 : 200,
                'baseline_checksum' => $baselineItems[$key]->item_checksum ?? null,
                'target_checksum' => $targetItems[$key]->item_checksum,
                'summary_json' => ['demo' => true, 'change_type' => $changeType],
            ]);

            if ($key === 'billing') {
                $regressionItem = $item;
            }
        }

        return [$baseline, $target, $compare, $regressionItem];
    }

    private function createFindingsEvidenceAndRisk(Project $project, User $user, ScanRun $scanRun, array $endpoints, EndpointSnapshotCompare $compare, EndpointSnapshotCompareItem $regressionItem): array
    {
        $billingResult = $scanRun->results()->where('endpoint_id', $endpoints['billing']->id)->first();
        $ordersResult = $scanRun->results()->where('endpoint_id', $endpoints['orders']->id)->first();

        $critical = Finding::create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoints['billing']->id,
            'scan_run_id' => $scanRun->id,
            'scan_result_id' => $billingResult?->id,
            'endpoint_snapshot_compare_id' => $compare->id,
            'endpoint_snapshot_compare_item_id' => $regressionItem->id,
            'title' => 'Billing invoices endpoint returns HTTP 500',
            'source' => 'regression',
            'severity' => 'critical',
            'status' => 'confirmed',
            'priority' => 'urgent',
            'owner_name' => 'Backend team',
            'due_date' => now()->addDays(2)->toDateString(),
            'summary' => 'The release candidate regressed on a sensitive billing endpoint and now returns HTTP 500.',
            'reproduction_steps' => "1. Use the Demo Bearer QA Token.\n2. Send GET /api/v1/billing/invoices.\n3. Observe HTTP 500.",
            'expected_result' => 'HTTP 200 with masked invoice metadata.',
            'actual_result' => 'HTTP 500 invoice_provider_timeout.',
            'recommendation' => 'Fix provider timeout handling, add fallback response and retest before release approval.',
            'evidence_required' => true,
            'retest_required' => true,
            'retest_status' => 'required',
            'retest_requested_at' => now()->subHours(2),
            'metadata_json' => ['demo' => true, 'release_blocker' => true],
        ]);

        $high = Finding::create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoints['orders']->id,
            'scan_run_id' => $scanRun->id,
            'scan_result_id' => $ordersResult?->id,
            'title' => 'Order list response time exceeds release threshold',
            'source' => 'assertion',
            'severity' => 'high',
            'status' => 'triaged',
            'priority' => 'high',
            'owner_name' => 'API team',
            'due_date' => now()->addDays(5)->toDateString(),
            'summary' => 'The endpoint responds, but response time is above the 500ms release threshold.',
            'expected_result' => 'Response under 500ms.',
            'actual_result' => 'Observed demo response time: 780ms.',
            'recommendation' => 'Review query index and pagination strategy.',
            'evidence_required' => true,
            'retest_required' => true,
            'retest_status' => 'ready_for_retest',
            'retest_requested_at' => now()->subHours(4),
            'ready_for_retest_at' => now()->subHour(),
            'metadata_json' => ['demo' => true],
        ]);

        $verified = Finding::create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoints['profile']->id,
            'title' => 'Profile response leaked verbose role metadata',
            'source' => 'manual',
            'severity' => 'medium',
            'status' => 'verified',
            'priority' => 'normal',
            'owner_name' => 'Identity team',
            'summary' => 'A previous verbose field exposure was fixed and verified by retest.',
            'expected_result' => 'Profile response contains only approved user metadata.',
            'actual_result' => 'Retest response matched the approved schema.',
            'recommendation' => 'Keep the assertion enabled for future release candidates.',
            'evidence_required' => true,
            'retest_required' => true,
            'retest_status' => 'passed',
            'retest_requested_at' => now()->subDays(3),
            'ready_for_retest_at' => now()->subDays(2),
            'retested_at' => now()->subDay(),
            'retested_by_user_id' => $user->id,
            'metadata_json' => ['demo' => true],
        ]);

        $accepted = Finding::create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoints['feature_flags']->id,
            'title' => 'Feature flag endpoint has temporary contract drift',
            'source' => 'contract',
            'severity' => 'high',
            'status' => 'triaged',
            'priority' => 'normal',
            'owner_name' => 'Release manager',
            'due_date' => now()->addDays(14)->toDateString(),
            'summary' => 'The new feature flag endpoint is implemented but still missing from the formal OpenAPI contract.',
            'expected_result' => 'Contract and implementation are aligned before final production rollout.',
            'actual_result' => 'Endpoint exists in inventory and target snapshot, but is not yet listed in the contract.',
            'recommendation' => 'Accept temporarily for demo release candidate, then update OpenAPI before final approval.',
            'evidence_required' => true,
            'retest_required' => false,
            'retest_status' => 'not_required',
            'metadata_json' => ['demo' => true, 'accepted_risk_example' => true],
        ]);

        foreach ([
            [$critical, 'HTTP evidence · billing failure', 'http', $billingResult?->body_preview, 'Safe scan run #'.$scanRun->id],
            [$high, 'Assertion evidence · slow order list', 'request_response', $ordersResult?->body_preview, 'Endpoint quick test batch'],
            [$verified, 'Retest evidence · profile schema clean', 'retest', '{"id":42,"role":"qa_reviewer"}', 'Retest workflow'],
            [$accepted, 'Business risk note · contract drift accepted', 'note', 'Temporary release-scope acceptance. OpenAPI update is planned after stakeholder approval.', 'Risk acceptance ledger'],
        ] as [$finding, $title, $type, $content, $source]) {
            $evidence = FindingEvidence::create([
                'project_id' => $project->id,
                'finding_id' => $finding->id,
                'endpoint_id' => $finding->endpoint_id,
                'scan_result_id' => $finding->scan_result_id,
                'captured_by_user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'source_label' => $source,
                'content' => $content,
                'request_excerpt' => 'GET '.$finding->endpoint?->path,
                'response_excerpt' => $content,
                'captured_at' => now()->subHour(),
                'sha256' => hash('sha256', $title.$finding->id),
                'metadata_json' => ['demo' => true],
            ]);

            if ($type === 'retest') {
                $finding->forceFill(['retest_evidence_id' => $evidence->id])->save();
            }
        }

        RiskAcceptance::create([
            'project_id' => $project->id,
            'finding_id' => $accepted->id,
            'accepted_by_user_id' => $user->id,
            'status' => 'active',
            'accepted_at' => now()->subDay(),
            'accepted_until' => now()->addDays(6)->toDateString(),
            'reason' => 'Demo release candidate can proceed with this documented contract drift as a visible accepted-risk example.',
            'business_justification' => 'The endpoint is read-only, authenticated and covered by scan/test evidence. The formal OpenAPI update is already scheduled.',
            'mitigation_note' => 'Client portal report must call out this accepted risk before handoff.',
            'release_scope' => 'Aptoria demo v1.4 RC only',
            'metadata_json' => ['demo' => true, 'expiry_state' => 'expiring_soon'],
        ]);

        return [$critical, $high, $verified, $accepted];
    }

    private function createContractValidation(Project $project, User $user, array $endpoints): ContractValidationRun
    {
        $contract = [
            'openapi' => '3.0.3',
            'info' => ['title' => 'Aptoria Guided Sandbox API', 'version' => '1.4.0-rc'],
            'paths' => [
                '/health' => ['get' => ['operationId' => 'healthCheck']],
                '/api/v1/me' => ['get' => ['operationId' => 'currentProfile']],
                '/api/v1/orders' => ['get' => ['operationId' => 'listOrders']],
                '/api/v1/orders/1001' => ['get' => ['operationId' => 'showOrder']],
                '/api/v1/billing/invoices' => ['get' => ['operationId' => 'listInvoices']],
                '/api/v1/client/reports/latest' => ['get' => ['operationId' => 'latestClientReport']],
                '/api/v1/reports/archive' => ['get' => ['operationId' => 'legacyReportArchive']],
            ],
        ];

        $run = ContractValidationRun::create([
            'project_id' => $project->id,
            'validated_by_user_id' => $user->id,
            'source_name' => 'Aptoria Guided Sandbox OpenAPI',
            'source_version' => 'v1.4.0-rc',
            'openapi_version' => '3.0.3',
            'status' => 'warning',
            'documented_operations' => 7,
            'inventory_operations' => count($endpoints),
            'matched_operations' => 6,
            'undocumented_inventory_operations' => 1,
            'missing_inventory_operations' => 1,
            'blocker_count' => 0,
            'warning_count' => 2,
            'summary_json' => ['source_warnings' => [], 'demo' => true, 'headline' => 'One inventory endpoint and one legacy contract operation require review.'],
            'contract_json' => json_encode($contract, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'validated_at' => now()->subHours(2),
        ]);

        foreach ($endpoints as $key => $endpoint) {
            ContractValidationResult::create([
                'project_id' => $project->id,
                'contract_validation_run_id' => $run->id,
                'endpoint_id' => $endpoint->id,
                'result_type' => $key === 'feature_flags' ? 'undocumented_endpoint' : 'matched',
                'severity' => $key === 'feature_flags' ? 'warning' : 'info',
                'method' => $endpoint->method,
                'path' => $endpoint->path,
                'operation_id' => $key === 'feature_flags' ? null : Str::camel($endpoint->name),
                'summary' => $key === 'feature_flags' ? 'Inventory endpoint is not yet documented in the OpenAPI contract.' : 'Inventory endpoint matches OpenAPI contract operation.',
                'details_json' => ['demo' => true],
            ]);
        }

        ContractValidationResult::create([
            'project_id' => $project->id,
            'contract_validation_run_id' => $run->id,
            'endpoint_id' => null,
            'result_type' => 'missing_inventory',
            'severity' => 'warning',
            'method' => 'GET',
            'path' => '/api/v1/reports/archive',
            'operation_id' => 'legacyReportArchive',
            'summary' => 'Contract operation is documented but no active inventory endpoint exists.',
            'details_json' => ['demo' => true],
        ]);

        return $run;
    }

    private function createReleaseReadinessRun(Project $project, User $user, ScanRun $scanRun, EndpointTestBatch $batch, EndpointSnapshot $snapshot, EndpointSnapshotCompare $compare, ContractValidationRun $contractRun): ReleaseReadinessRun
    {
        $metrics = [
            'endpoint_count' => $project->endpoints()->count(),
            'safe_endpoint_count' => $project->endpoints()->whereIn('method', ['GET', 'HEAD'])->count(),
            'environment_count' => $project->environments()->count(),
            'auth_profile_count' => $project->authProfiles()->count(),
            'latest_scan_id' => $scanRun->id,
            'latest_scan_failed' => 1,
            'latest_scan_warning' => 1,
            'endpoint_test_batch_count' => 1,
            'latest_endpoint_test_batch_id' => $batch->id,
            'endpoint_snapshot_count' => 2,
            'latest_endpoint_snapshot_id' => $snapshot->id,
            'endpoint_snapshot_compare_count' => 1,
            'latest_endpoint_snapshot_compare_id' => $compare->id,
            'latest_endpoint_snapshot_compare_regressions' => 1,
            'finding_count' => $project->findings()->count(),
            'critical_findings' => 1,
            'high_findings' => 2,
            'evidence_count' => $project->evidence()->count(),
            'accepted_risks' => 1,
            'contract_validation_run_id' => $contractRun->id,
        ];

        $checks = [
            ['key' => 'environment', 'icon' => 'server-cog', 'level' => 'pass', 'label' => 'Default environment exists', 'hint' => 'Demo staging is selected.'],
            ['key' => 'endpoint_inventory', 'icon' => 'list-tree', 'level' => 'pass', 'label' => 'Endpoint inventory exists', 'hint' => 'Seven demo endpoints are available.'],
            ['key' => 'safe_scan', 'icon' => 'radar', 'level' => 'pass', 'label' => 'Safe scan evidence exists', 'hint' => 'One completed safe scan is stored.'],
            ['key' => 'scan_failures', 'icon' => 'circle-alert', 'level' => 'blocker', 'label' => 'No failed scan result', 'hint' => 'Billing endpoint still fails.'],
            ['key' => 'endpoint_batch_evidence', 'icon' => 'layers', 'level' => 'pass', 'label' => 'Batch quick-test evidence exists', 'hint' => 'One synthetic batch run is available.'],
            ['key' => 'endpoint_regression_clean', 'icon' => 'git-compare', 'level' => 'blocker', 'label' => 'No regression drift', 'hint' => 'Billing regression is intentionally present for demo.'],
            ['key' => 'accepted_risk_expiry', 'icon' => 'shield-alert', 'level' => 'warning', 'label' => 'Accepted risks are not expiring soon', 'hint' => 'One accepted risk expires in six days.'],
            ['key' => 'contract_validation_exists', 'icon' => 'file-check-2', 'level' => 'pass', 'label' => 'OpenAPI contract validation exists', 'hint' => 'Contract run is stored.'],
            ['key' => 'contract_validation_review', 'icon' => 'file-warning', 'level' => 'warning', 'label' => 'No contract drift needs review', 'hint' => 'Feature flag endpoint and legacy archive operation require review.'],
        ];

        return ReleaseReadinessRun::create([
            'project_id' => $project->id,
            'generated_by_user_id' => $user->id,
            'status' => 'blocked',
            'score' => 64,
            'grade' => 'C',
            'blocker_count' => 2,
            'warning_count' => 2,
            'check_count' => count($checks),
            'passed_check_count' => 5,
            'metrics_json' => $metrics,
            'checks_json' => $checks,
            'summary_json' => [
                'headline' => 'Demo release is blocked by a billing regression and scan failure.',
                'decision' => 'Do not approve final release until blocker evidence is retested.',
            ],
            'retest_closure_json' => [
                'scope' => 2,
                'closed' => 1,
                'pending' => 1,
                'failed' => 0,
                'missing_evidence' => 0,
                'closure_rate' => 50,
            ],
            'risk_acceptance_json' => [
                'active' => 1,
                'expiring_soon' => 1,
                'expired' => 0,
                'next_expiry' => now()->addDays(6)->toDateString(),
            ],
            'contract_validation_json' => [
                'latest_run_id' => $contractRun->id,
                'status' => 'warning',
                'matched' => 6,
                'warnings' => 2,
                'blockers' => 0,
            ],
            'decision_note' => 'Generated by comprehensive demo builder to show a blocked release package with accepted-risk context.',
            'generated_at' => now()->subHour(),
        ]);
    }

    private function createReleaseDecision(Project $project, User $user, ReleaseReadinessRun $readinessRun): ReleaseDecisionSnapshot
    {
        $markdown = "# Demo Release Decision\n\nDecision: Blocked\n\nThe release candidate has enough evidence for review, but it is blocked by a billing regression. One accepted risk is expiring soon and should be renewed or closed before final client sign-off.";

        return ReleaseDecisionSnapshot::create([
            'project_id' => $project->id,
            'release_readiness_run_id' => $readinessRun->id,
            'decided_by_user_id' => $user->id,
            'decision' => 'blocked',
            'title' => 'Demo v1.4 RC Release Decision',
            'evidence_summary_markdown' => $markdown,
            'evidence_summary_json' => [
                'decision' => 'blocked',
                'safe_scan' => 'completed_with_failure',
                'regression' => 'critical_billing_regression',
                'accepted_risk' => 'one_expiring_soon',
                'contract_validation' => 'warning',
            ],
            'readiness_metrics_json' => $readinessRun->metrics_json,
            'readiness_checks_json' => $readinessRun->checks_json,
            'source_state_json' => [
                'release_readiness_run_id' => $readinessRun->id,
                'frozen_at' => now()->toDateTimeString(),
                'demo' => true,
            ],
            'decision_note' => 'Blocked until billing regression is fixed and retested.',
            'decided_at' => now()->subMinutes(45),
        ]);
    }

    private function createApprovedReportAndPortal(Project $project, User $user, ReleaseReadinessRun $readinessRun, ReleaseDecisionSnapshot $decision): array
    {
        $markdown = "# Aptoria Guided Sandbox Report\n\n## Executive summary\n\nThis synthetic report demonstrates the complete Aptoria evidence chain: endpoint inventory, safe scan, assertions, snapshot regression, findings, retest evidence, risk acceptance, contract validation, release readiness, sign-off and client portal acknowledgement.\n\n## Release decision\n\nBlocked for final production release. Approved only as a demo evidence package.";
        $html = '<h1>Aptoria Guided Sandbox Report</h1><p>This synthetic report demonstrates the complete Aptoria evidence chain.</p><p><strong>Decision:</strong> blocked for final production release; approved as demo evidence package.</p>';

        $report = ReportVersion::create([
            'project_id' => $project->id,
            'generated_by_user_id' => $user->id,
            'reviewed_by_user_id' => $user->id,
            'approved_by_user_id' => $user->id,
            'release_readiness_run_id' => $readinessRun->id,
            'release_decision_snapshot_id' => $decision->id,
            'type' => 'release_decision',
            'status' => 'approved',
            'title' => 'Approved Demo Evidence Package',
            'content_markdown' => $markdown,
            'content_html' => $html,
            'data_json' => [
                'demo' => true,
                'release_readiness_run_id' => $readinessRun->id,
                'release_decision_snapshot_id' => $decision->id,
                'summary' => 'Full demo evidence package generated from Settings.',
            ],
            'checksum' => hash('sha256', $markdown),
            'notes' => 'Synthetic approved report used for client portal demonstration.',
            'review_note' => 'Reviewed as a demo package. Real production approval remains blocked.',
            'approval_note' => 'Approved for demo/client-portal walkthrough only.',
            'approval_signoff_name' => $user->name,
            'approval_signoff_role' => 'Demo QA Approver',
            'approval_signoff_statement' => 'I approve this synthetic evidence package for demonstration purposes.',
            'approval_signed_at' => now()->subMinutes(30),
            'approval_context_json' => ['demo' => true, 'scope' => 'client portal walkthrough'],
            'generated_at' => now()->subMinutes(40),
            'reviewed_at' => now()->subMinutes(35),
            'approved_at' => now()->subMinutes(30),
            'client_delivery_count' => 1,
            'client_download_count' => 1,
            'client_last_delivered_at' => now()->subMinutes(25),
            'client_last_downloaded_at' => now()->subMinutes(15),
            'client_delivery_summary_json' => [
                'latest_acknowledgement' => 'needs_changes',
                'acknowledgement_count' => 1,
                'demo' => true,
            ],
        ]);

        $portal = ClientPortalAccess::create([
            'project_id' => $project->id,
            'report_version_id' => $report->id,
            'created_by_user_id' => $user->id,
            'name' => 'Demo client review link',
            'role' => 'client_approver',
            'permissions_json' => ['reports', 'readiness', 'findings', 'evidence'],
            'is_active' => true,
            'acknowledge_required' => true,
            'acknowledgement_status' => 'acknowledged',
            'acknowledgement_decision' => 'needs_changes',
            'acknowledgement_comment' => 'Demo client requests retest evidence before final sign-off.',
            'expires_at' => now()->addDays(30),
            'last_viewed_at' => now()->subMinutes(20),
            'acknowledged_at' => now()->subMinutes(10),
            'acknowledged_by_name' => 'Demo Client Reviewer',
            'acknowledged_by_email' => 'reviewer@example.test',
        ]);

        $ack = ClientPortalAcknowledgement::create([
            'project_id' => $project->id,
            'client_portal_access_id' => $portal->id,
            'report_version_id' => $report->id,
            'decision_status' => 'needs_changes',
            'acknowledged_by_name' => 'Demo Client Reviewer',
            'acknowledged_by_email' => 'reviewer@example.test',
            'comment' => 'Please attach successful billing retest evidence before final approval.',
            'acknowledge_terms' => true,
            'evidence_summary_json' => ['downloaded' => true, 'decision' => 'needs_changes', 'demo' => true],
            'acknowledged_at' => now()->subMinutes(10),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Aptoria Demo Client Portal',
        ]);

        $portal->forceFill(['latest_acknowledgement_id' => $ack->id])->save();

        return [$report, $portal];
    }


    private function createExternalImportPreview(Project $project, User $user, array $endpoints): ?ExternalImportRun
    {
        if (! Schema::hasTable('external_import_runs') || ! Schema::hasTable('external_import_items')) {
            return null;
        }

        $run = $project->externalImportRuns()->create([
            'created_by_user_id' => $user->id,
            'source_type' => 'newman_json',
            'source_name' => 'Demo Newman + Jira Import Pack',
            'source_version' => 'demo-0.0.35',
            'status' => 'applied',
            'item_count' => 4,
            'endpoint_count' => 1,
            'assertion_count' => 1,
            'finding_count' => 1,
            'evidence_count' => 1,
            'warning_count' => 1,
            'blocker_count' => 1,
            'summary_json' => [
                'item_count' => 4,
                'endpoint_count' => 1,
                'assertion_count' => 1,
                'finding_count' => 1,
                'evidence_count' => 1,
                'blocker_count' => 1,
                'warning_count' => 1,
                'created' => ['endpoints' => 1, 'assertions' => 1, 'findings' => 1, 'evidence' => 1],
                'demo' => true,
            ],
            'raw_excerpt' => '{"run":{"executions":[{"item":{"name":"Demo failing checkout flow"}}]}}',
            'previewed_at' => now()->subHours(4),
            'applied_at' => now()->subHours(3),
        ]);

        $endpoint = $endpoints['checkout'] ?? collect($endpoints)->first();
        $finding = $project->findings()->where('severity', 'high')->latest()->first();

        foreach ([
            ['endpoint', 'update', 'info', 'Demo checkout endpoint from Newman', $endpoint?->method, $endpoint?->path, ['source' => 'newman_json']],
            ['assertion', 'create', 'warning', 'Checkout response remains below 700 ms', $endpoint?->method, $endpoint?->path, ['rule_key' => 'max_response_time', 'expected_value' => '700']],
            ['finding', 'create', 'blocker', 'Newman assertion failed: checkout latency', $endpoint?->method, $endpoint?->path, ['external_key' => 'DEMO-NEWMAN-1']],
            ['evidence', 'create', 'info', 'Newman evidence: checkout latency', $endpoint?->method, $endpoint?->path, ['external_key' => 'DEMO-NEWMAN-1']],
        ] as [$entity, $action, $severity, $title, $method, $path, $payload]) {
            $run->items()->create([
                'project_id' => $project->id,
                'endpoint_id' => $endpoint?->id,
                'finding_id' => $entity === 'finding' || $entity === 'evidence' ? $finding?->id : null,
                'entity_type' => $entity,
                'action' => $action,
                'severity' => $severity,
                'external_key' => $payload['external_key'] ?? null,
                'method' => $method,
                'path' => $path,
                'title' => $title,
                'summary' => 'Demo external QA import item generated for walkthrough coverage.',
                'payload_json' => $payload,
                'status' => 'applied',
                'applied_at' => now()->subHours(3),
            ]);
        }

        return $run;
    }

    private function createCalendarAndAuditTrail(Project $project, User $user, ReleaseReadinessRun $readinessRun, ReportVersion $report, ClientPortalAccess $portalAccess): void
    {
        foreach ([
            ['Release checkpoint · v1.4 RC evidence review', 'release_checkpoint', 'completed', 'high', now()->subHours(3), now()->subHours(2)],
            ['Regression retest · billing invoices', 'regression_retest', 'planned', 'critical', now()->addDay()->setTime(10, 0), now()->addDay()->setTime(11, 0)],
            ['Client follow-up · demo portal feedback', 'alert_follow_up', 'planned', 'normal', now()->addDays(2)->setTime(14, 0), now()->addDays(2)->setTime(14, 30)],
            ['Contract review · feature flag endpoint', 'security_review', 'in_progress', 'high', now()->subHour(), now()->addHour()],
        ] as [$title, $type, $status, $priority, $start, $end]) {
            CalendarEvent::create([
                'project_id' => $project->id,
                'created_by_user_id' => $user->id,
                'title' => $title,
                'description' => 'Generated by the comprehensive demo project builder.',
                'event_type' => $type,
                'status' => $status,
                'priority' => $priority,
                'start_at' => $start,
                'end_at' => $end,
                'location' => 'Aptoria demo workspace',
                'is_all_day' => false,
                'metadata' => ['demo' => true],
            ]);
        }

        foreach ([
            ['created', 'Comprehensive demo project rebuilt from Program Settings.', 'project', $project->id],
            ['evaluated', 'Demo release readiness package generated.', 'release_readiness', $readinessRun->id],
            ['approved', 'Demo report signed off for client portal walkthrough.', 'report', $report->id],
            ['delivered', 'Demo client portal link created and acknowledged.', 'client_portal', $portalAccess->id],
        ] as [$action, $summary, $eventType, $subjectId]) {
            DB::table('audit_logs')->insert([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'event_type' => $eventType,
                'action' => $action,
                'severity' => 'info',
                'subject_type' => null,
                'subject_id' => $subjectId,
                'subject_label' => $project->name,
                'summary' => $summary,
                'metadata' => json_encode(['demo' => true]),
                'ip_address' => request()?->ip(),
                'user_agent' => substr((string) request()?->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        }
    }
}
