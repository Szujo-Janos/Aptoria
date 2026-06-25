<?php

namespace App\Services;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Environment;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\TestCase;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LiveDemoApiSandboxService
{
    public const DEMO_PROJECT_SLUG = 'aptoria-live-demo-api';
    public const DEMO_USER_EMAIL = 'demo@aptoria.dev';
    public const DEMO_USER_PASSWORD = 'aptoria-demo-2026';

    public function __construct(
        private readonly ProjectAccessService $projectAccess,
        private readonly EvidenceRepositoryService $evidenceRepository,
        private readonly NativeTestEvidenceService $nativeTests,
    ) {
    }

    public function build(User $owner): array
    {
        return DB::transaction(function () use ($owner): array {
            $deletedProjects = $this->deleteExistingDemoProject();
            $baseUrl = rtrim((string) config('aptoria.demo.api_base_url'), '/');

            $project = Project::create([
                'user_id' => $owner->id,
                'name' => 'Aptoria Sandbox API',
                'slug' => self::DEMO_PROJECT_SLUG,
                'description' => 'Sandbox JSON API workspace wired to Aptoria so visitors can run safe scans, import OpenAPI/Postman/CSV artifacts, create evidence, review findings and generate release gates without touching live data.',
                'base_url' => $baseUrl,
                'environment_label' => 'sandbox-api',
                'status' => 'active',
                'workspace_type' => Project::WORKSPACE_TYPE_SANDBOX,
                'qa_owner' => $owner->name,
                'release_goal' => 'Demonstrate the Aptoria evidence-first API QA workflow against a safe synthetic JSON API.',
                'is_active' => true,
            ]);

            $this->projectAccess->ensureOwnerMembership($project);
            $demoUser = $this->ensureDemoViewer();
            $this->attachDemoViewer($project, $demoUser, $owner);

            $environment = $this->createEnvironment($project, $baseUrl);
            [$publicAuth, $bearerAuth] = $this->createAuthProfiles($project);
            $endpoints = $this->createEndpoints($project, $environment, $publicAuth, $bearerAuth);
            $this->createAssertions($project, $endpoints);
            $this->createRepositoryEvidence($project, $owner, $endpoints);
            $this->createNativeTests($project, $owner, $endpoints);
            $this->createDemoFinding($project, $endpoints['leaky_token']);
            $this->createProjectSettings($project, $environment, $bearerAuth);

            return [
                'project' => $project->fresh(),
                'demo_user' => $demoUser,
                'deleted_projects' => $deletedProjects,
                'summary' => [
                    'base_url' => $baseUrl,
                    'endpoints' => $project->endpoints()->count(),
                    'assertions' => $project->assertionRules()->count(),
                    'evidence' => $project->evidence()->count(),
                    'test_cases' => $project->testCases()->count(),
                    'findings' => $project->findings()->count(),
                ],
            ];
        });
    }

    private function deleteExistingDemoProject(): int
    {
        $ids = Project::query()->where('slug', self::DEMO_PROJECT_SLUG)->pluck('id')->all();

        if ($ids === []) {
            return 0;
        }

        foreach ([
            'release_gate_events', 'release_gate_items', 'release_gates',
            'test_runs', 'test_cases', 'test_suites',
            'evidence_lifecycle_events', 'finding_evidence', 'findings',
            'endpoint_assertion_rules', 'scan_results', 'scan_runs',
            'endpoints', 'auth_profiles', 'environments', 'project_settings',
            'project_memberships', 'audit_logs', 'external_import_items', 'external_import_runs',
            'report_versions', 'release_readiness_runs', 'calendar_events',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'project_id')) {
                DB::table($table)->whereIn('project_id', $ids)->delete();
            }
        }

        Project::query()->whereIn('id', $ids)->delete();

        return count($ids);
    }

    public function demoUserEmail(): string
    {
        return (string) config('aptoria.demo.demo_user_email', self::DEMO_USER_EMAIL);
    }

    public function demoUserPassword(): string
    {
        return (string) config('aptoria.demo.demo_user_password', self::DEMO_USER_PASSWORD);
    }

    private function ensureDemoViewer(): User
    {
        return User::updateOrCreate(
            ['email' => $this->demoUserEmail()],
            [
                'name' => 'Aptoria Demo Viewer',
                'password' => Hash::make($this->demoUserPassword()),
                'role' => 'user',
                'locale' => 'en',
                'timezone' => 'Europe/Budapest',
                'password_change_required' => false,
            ]
        );
    }

    private function attachDemoViewer(Project $project, User $demoUser, User $owner): void
    {
        if (! Schema::hasTable('project_memberships')) {
            return;
        }

        ProjectMembership::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $demoUser->id],
            [
                'role' => ProjectMembership::ROLE_READ_ONLY_VIEWER,
                'status' => ProjectMembership::STATUS_ACTIVE,
                'invited_by_user_id' => $owner->id,
                'added_at' => now(),
            ]
        );
    }

    private function createEnvironment(Project $project, string $baseUrl): Environment
    {
        return Environment::create([
            'project_id' => $project->id,
            'name' => 'Sandbox API',
            'base_url' => $baseUrl,
            'environment_type' => 'staging',
            'is_production' => false,
            'is_default' => true,
            'notes' => 'Live synthetic JSON API used by demo. Safe scan should remain limited to this target in public demo mode.',
        ]);
    }

    /** @return array{0:AuthProfile,1:AuthProfile} */
    private function createAuthProfiles(Project $project): array
    {
        $public = AuthProfile::create([
            'project_id' => $project->id,
            'name' => 'Public demo endpoints',
            'type' => 'none',
            'is_default' => false,
            'notes' => 'No-auth profile for public demo API endpoints.',
        ]);

        $bearer = AuthProfile::create([
            'project_id' => $project->id,
            'name' => 'Demo Bearer Token',
            'type' => 'bearer',
            'encrypted_token' => (string) config('aptoria.demo.auth_token'),
            'is_default' => true,
            'notes' => 'Bearer token used by /demo-api/security/private-account.',
        ]);

        return [$public, $bearer];
    }

    /** @return array<string,Endpoint> */
    private function createEndpoints(Project $project, Environment $environment, AuthProfile $publicAuth, AuthProfile $bearerAuth): array
    {
        $rows = [
            'health' => ['GET', '/health', 'Health check', 'Healthy JSON endpoint used for safe scan smoke tests.', false, 200, 'application/json', 'low', $publicAuth],
            'users' => ['GET', '/users', 'List demo users', 'Synthetic customer/user list.', false, 200, 'application/json', 'public', $publicAuth],
            'user_detail' => ['GET', '/users/1', 'Get demo user', 'Single user detail response without unresolved path parameter.', false, 200, 'application/json', 'low', $publicAuth],
            'orders' => ['GET', '/orders', 'List demo orders', 'Synthetic order list for JSON evidence capture.', false, 200, 'application/json', 'low', $publicAuth],
            'products' => ['GET', '/products', 'List demo products', 'Product catalogue style JSON response.', false, 200, 'application/json', 'low', $publicAuth],
            'summary' => ['GET', '/reports/summary', 'Release report summary', 'Release-like JSON signal for evidence/report demos.', false, 200, 'application/json', 'review', $publicAuth],
            'public_profile' => ['GET', '/security/public-profile', 'Public profile boundary', 'Public metadata endpoint used to discuss auth boundaries.', false, 200, 'application/json', 'public', $publicAuth],
            'private_account' => ['GET', '/security/private-account', 'Private account boundary', 'Protected endpoint requiring the demo bearer token.', true, 200, 'application/json', 'review', $bearerAuth],
            'leaky_token' => ['GET', '/security/leaky-token-example', 'Leaky token example', 'Intentional sensitive-data response used to create review findings.', false, 200, 'application/json', 'high', $publicAuth],
            'server_error' => ['GET', '/errors/server-error', 'Intentional 500 error', 'Intentional HTTP 500 used to demonstrate blocker detection.', false, 200, 'application/json', 'high', $publicAuth],
            'slow_response' => ['GET', '/errors/slow-response', 'Intentional slow response', 'Intentional slow response used for max response time assertions.', false, 200, 'application/json', 'review', $publicAuth],
            'scenario_templates' => ['GET', '/scenarios', 'Guided scenario templates', 'JSON list of guided demo scenarios for visitor onboarding.', false, 200, 'application/json', 'low', $publicAuth],
            'scenario_security' => ['GET', '/scenarios/security-leak-review', 'Security leak scenario template', 'Single guided scenario showing sensitive-data review flow.', false, 200, 'application/json', 'review', $publicAuth],
            'scenario_release_evidence' => ['GET', '/scenarios/release-gate-decision/evidence.json', 'Release gate scenario evidence', 'Scenario run-sheet evidence JSON for release decision demonstrations.', false, 200, 'application/json', 'review', $publicAuth],
            'create_order' => ['POST', '/orders', 'Create demo order', 'Destructive-looking endpoint intentionally excluded from safe scan.', true, 201, 'application/json', 'review', $bearerAuth, true],
        ];

        $endpoints = [];
        foreach ($rows as $key => $row) {
            [$method, $path, $name, $description, $authRequired, $status, $contentType, $risk, $authProfile] = $row;
            $excluded = (bool) ($row[9] ?? false);
            $endpoints[$key] = Endpoint::create([
                'project_id' => $project->id,
                'environment_id' => $environment->id,
                'auth_profile_id' => $authProfile->id,
                'method' => $method,
                'path' => $path,
                'name' => $name,
                'description' => $description,
                'tags' => 'live-demo,sandbox,'.Str::slug($key),
                'auth_required' => $authRequired,
                'expected_status' => $status,
                'expected_content_type' => $contentType,
                'risk_level' => $risk,
                'is_active' => true,
                'excluded_from_scan' => $excluded,
                'notes' => $excluded ? 'Excluded because it is not a safe GET/HEAD endpoint.' : 'Sandbox endpoint safe for scan demonstrations.',
            ]);
        }

        return $endpoints;
    }

    /** @param array<string,Endpoint> $endpoints */
    private function createAssertions(Project $project, array $endpoints): void
    {
        foreach ($endpoints as $endpoint) {
            if (! in_array($endpoint->method, ['GET', 'HEAD'], true)) {
                continue;
            }

            EndpointAssertionRule::create([
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'name' => 'Expected status for '.$endpoint->name,
                'rule_key' => 'status_code',
                'operator' => 'equals',
                'expected_value' => (string) $endpoint->expected_status,
                'severity' => in_array($endpoint->risk_level, ['high', 'critical'], true) ? 'blocker' : 'warning',
                'enabled' => true,
                'description' => 'Demo assertion generated from the sandbox API sandbox endpoint definition.',
            ]);
        }

        EndpointAssertionRule::create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoints['slow_response']->id,
            'name' => 'Slow response should stay under 1000ms',
            'rule_key' => 'max_response_time_ms',
            'operator' => 'lte',
            'expected_value' => '1000',
            'severity' => 'warning',
            'enabled' => true,
            'description' => 'Intentional demo warning: /errors/slow-response sleeps for more than one second.',
        ]);
    }

    /** @param array<string,Endpoint> $endpoints */
    private function createRepositoryEvidence(Project $project, User $user, array $endpoints): void
    {
        $payload = $this->evidenceRepository->prepareForCreate([
            'endpoint_id' => $endpoints['summary']->id,
            'type' => 'json_response',
            'title' => 'Demo release summary JSON response',
            'source_label' => 'Sandbox API',
            'content' => json_encode([
                'endpoint' => 'GET /reports/summary',
                'purpose' => 'Demonstrates JSON evidence captured from a live API endpoint.',
                'readiness_score' => 82,
                'decision' => 'conditional_go',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'url' => rtrim((string) config('aptoria.demo.api_base_url'), '/').'/reports/summary',
            'captured_at' => now(),
            'metadata_json' => ['source' => 'live_demo_seed'],
        ], $project, $user);

        $evidence = FindingEvidence::create($payload);
        $this->evidenceRepository->recordCreated($evidence, $user);
        $this->evidenceRepository->verify($evidence, $user, 'Verified baseline evidence for the public demo sandbox.');

        $scenarioPayload = $this->evidenceRepository->prepareForCreate([
            'endpoint_id' => $endpoints['scenario_release_evidence']->id,
            'type' => 'json_response',
            'title' => 'Guided release gate scenario run sheet',
            'source_label' => 'Sandbox Scenario Templates',
            'content' => json_encode([
                'scenario' => 'release-gate-decision',
                'purpose' => 'Shows how a visitor can walk from QA Cockpit signals to a release gate decision package.',
                'recommended_endpoints' => ['/reports/summary', '/errors/server-error', '/errors/slow-response'],
                'expected_result' => 'Conditional release decision with explicit blockers, warnings and evidence context.',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'url' => rtrim((string) config('aptoria.demo.api_base_url'), '/').'/scenarios/release-gate-decision/evidence.json',
            'captured_at' => now(),
            'metadata_json' => ['source' => 'live_demo_scenario_seed', 'scenario' => 'release-gate-decision'],
        ], $project, $user);

        $scenarioEvidence = FindingEvidence::create($scenarioPayload);
        $this->evidenceRepository->recordCreated($scenarioEvidence, $user);
        $this->evidenceRepository->verify($scenarioEvidence, $user, 'Verified guided scenario evidence for the public demo walkthrough.');
    }

    /** @param array<string,Endpoint> $endpoints */
    private function createNativeTests(Project $project, User $user, array $endpoints): void
    {
        $suite = TestSuite::create([
            'project_id' => $project->id,
            'created_by_user_id' => $user->id,
            'name' => 'Sandbox API Smoke Suite',
            'description' => 'Native Aptoria tests that exercise the sandbox API sandbox.',
            'status' => 'active',
            'priority' => 'high',
            'owner_name' => $user->name,
            'metadata_json' => ['source' => 'live_demo_seed'],
        ]);

        $healthCase = TestCase::create([
            'project_id' => $project->id,
            'test_suite_id' => $suite->id,
            'endpoint_id' => $endpoints['health']->id,
            'created_by_user_id' => $user->id,
            'title' => 'Health endpoint returns OK JSON',
            'description' => 'Confirms the sandbox API is reachable.',
            'steps' => "1. Send GET /health\n2. Confirm status 200\n3. Confirm JSON status equals ok",
            'expected_result' => 'HTTP 200 with JSON status=ok.',
            'type' => 'manual',
            'priority' => 'high',
            'status' => 'active',
            'source' => 'native',
            'tags' => 'live-demo,smoke',
        ]);

        $serverErrorCase = TestCase::create([
            'project_id' => $project->id,
            'test_suite_id' => $suite->id,
            'endpoint_id' => $endpoints['server_error']->id,
            'created_by_user_id' => $user->id,
            'title' => 'Server error endpoint creates a blocker signal',
            'description' => 'Intentional failing test to show finding and evidence creation.',
            'steps' => "1. Send GET /errors/server-error\n2. Observe HTTP 500\n3. Record finding and evidence",
            'expected_result' => 'HTTP 200 for release readiness. Demo intentionally returns 500.',
            'type' => 'manual',
            'priority' => 'high',
            'status' => 'active',
            'source' => 'native',
            'tags' => 'live-demo,blocker',
        ]);

        $this->nativeTests->recordRun($project, $healthCase, [
            'status' => 'pass',
            'duration_ms' => 44,
            'environment_label' => 'Sandbox API',
            'actual_result' => 'HTTP 200 with {"status":"ok"}.',
            'evidence_summary' => 'Smoke test proof generated from the sandbox API seed.',
        ], $user);

        $this->nativeTests->recordRun($project, $serverErrorCase, [
            'status' => 'fail',
            'duration_ms' => 180,
            'environment_label' => 'Sandbox API',
            'actual_result' => 'HTTP 500 returned by intentional demo endpoint.',
            'failure_summary' => 'Intentional server error demonstrates blocker detection.',
            'evidence_summary' => 'Failed native test linked to generated finding and repository evidence.',
            'create_finding' => true,
            'finding_title' => 'Intentional demo API 500 blocker',
            'finding_severity' => 'high',
            'finding_priority' => 'high',
        ], $user);
    }

    private function createDemoFinding(Project $project, Endpoint $endpoint): void
    {
        Finding::create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Debug token exposed in public JSON response',
            'source' => 'demo_api',
            'severity' => 'high',
            'status' => 'confirmed',
            'priority' => 'high',
            'summary' => 'The sandbox endpoint intentionally exposes debug_token and api_key fields to show how Aptoria tracks sensitive response evidence.',
            'reproduction_steps' => 'Send GET /security/leaky-token-example and inspect the JSON response body.',
            'expected_result' => 'Public responses should not expose token-like debug fields.',
            'actual_result' => 'The response includes debug_token and api_key fields for demo purposes.',
            'recommendation' => 'Remove sensitive debug fields from public API responses and add an assertion or sensitive-data review step.',
            'evidence_required' => true,
            'retest_required' => true,
            'metadata_json' => ['source' => 'live_demo_seed'],
        ]);
    }

    private function createProjectSettings(Project $project, Environment $environment, AuthProfile $authProfile): void
    {
        foreach ([
            'scan.enabled' => '1',
            'scan.default_environment_id' => (string) $environment->id,
            'scan.default_auth_profile_id' => (string) $authProfile->id,
            'scan.allow_private_networks' => '0',
            'scan.require_confirmation' => '0',
            'scan.store_response_body_preview' => '1',
            'project.notes' => 'Sandbox API workspace generated by v0.0.61 with live/sandbox separation and guided scenario templates.',
        ] as $key => $value) {
            DB::table('project_settings')->updateOrInsert(
                ['project_id' => $project->id, 'key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
