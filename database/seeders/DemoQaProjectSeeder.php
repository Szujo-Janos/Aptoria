<?php

namespace Database\Seeders;

use App\Models\AuthProfile;
use App\Models\Project;
use App\Models\User;
use App\Services\Settings\ProjectSettingService;
use App\Services\Audit\AuditLogService;
use App\Services\Calendar\CalendarActivityLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DemoQaProjectSeeder extends Seeder
{
    public const PROJECT_SLUG = 'northstar-commerce-demo-review';

    public function run(): void
    {
        AuditLogService::withoutRecording(fn () => CalendarActivityLogger::withoutRecording(fn () => $this->seedDemo()));
    }

    private function seedDemo(): void
    {
        if (! Schema::hasTable('projects')) {
            throw new RuntimeException('Run migrations before importing the comprehensive demo project.');
        }

        $admin = User::query()->firstOrCreate(
            ['email' => config('aptoria.default_admin.email', 'admin@example.com')],
            [
                'name' => 'Aptoria Admin',
                'password' => Hash::make(config('aptoria.default_admin.password', 'change-me-now')),
                'role' => 'admin',
            ]
        );

        DB::transaction(function () use ($admin): void {
            Project::query()->where('slug', self::PROJECT_SLUG)->delete();

            $project = Project::query()->create([
                'user_id' => $admin->id,
                'name' => 'Northstar Commerce API - Full QA Demo',
                'slug' => self::PROJECT_SLUG,
                'description' => 'Comprehensive simulated QA review showing inventory, safe scans, assertions, regression evidence, contract validation, findings, tests, monitoring and a blocked release gate.',
                'base_url' => 'https://api.demo.northstar.example',
                'is_active' => true,
            ]);

            $baselineTime = now()->subDays(7);
            $reviewTime = now()->subDay();
            $timestamps = fn ($at = null): array => [
                'created_at' => $at ?: now(),
                'updated_at' => $at ?: now(),
            ];
            $json = fn (array $value): string => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $noAuth = AuthProfile::query()->create([
                'project_id' => $project->id,
                'name' => 'Public endpoints',
                'type' => AuthProfile::TYPE_NONE,
                'notes' => 'Demo no-auth profile for documented public endpoints.',
                'is_default' => false,
            ]);
            $bearer = AuthProfile::query()->create([
                'project_id' => $project->id,
                'name' => 'Staging QA token',
                'type' => AuthProfile::TYPE_BEARER,
                'encrypted_token' => 'demo-token-not-valid-outside-this-simulation',
                'notes' => 'Synthetic credential used only to demonstrate authenticated scan metadata.',
                'is_default' => true,
            ]);
            $customHeader = AuthProfile::query()->create([
                'project_id' => $project->id,
                'name' => 'Internal review header',
                'type' => AuthProfile::TYPE_CUSTOM_HEADER,
                'header_name' => 'X-Demo-Review-Key',
                'encrypted_header_value' => 'demo-review-key-not-real',
                'notes' => 'Synthetic custom header profile for internal endpoint review.',
                'is_default' => false,
            ]);

            $stagingId = DB::table('environments')->insertGetId([
                'project_id' => $project->id,
                'name' => 'staging',
                'base_url' => 'https://staging-api.demo.northstar.example',
                'environment_type' => \App\Models\Environment::TYPE_STAGING,
                'auth_profile_id' => $bearer->id,
                'is_production' => false,
                ...$timestamps(),
            ]);
            $productionId = DB::table('environments')->insertGetId([
                'project_id' => $project->id,
                'name' => 'production',
                'base_url' => 'https://api.demo.northstar.example',
                'environment_type' => \App\Models\Environment::TYPE_PRODUCTION,
                'auth_profile_id' => $bearer->id,
                'is_production' => true,
                ...$timestamps(),
            ]);

            app(ProjectSettingService::class)->seedDefaults($project);
            foreach ([
                ['scan.default_environment_id', (string) $stagingId, 'integer', 'scan'],
                ['scan.default_auth_profile_id', (string) $bearer->id, 'integer', 'scan'],
                ['scan.timeout_seconds', '12', 'integer', 'scan'],
                ['risk.slow_response_ms', '1200', 'integer', 'risk'],
            ] as [$key, $value, $type, $group]) {
                DB::table('project_settings')->updateOrInsert(
                    ['project_id' => $project->id, 'key' => $key],
                    ['value' => $value, 'type' => $type, 'group' => $group, 'updated_at' => now(), 'created_at' => now()]
                );
            }

            $endpointDefinitions = [
                'health' => ['GET', '/health', 'Service health', false, 200, 'application/json', 'low', false, $noAuth->id, 'Public health response contains no business data.'],
                'products' => ['GET', '/v1/products', 'Product catalogue', false, 200, 'application/json', 'public', false, $noAuth->id, 'Documented public catalogue endpoint.'],
                'user' => ['GET', '/v1/users/{id}', 'Customer profile detail', true, 200, 'application/json', 'high', false, $bearer->id, 'Customer data endpoint requires authentication and authorization review.'],
                'audit' => ['GET', '/internal/admin/audit-log', 'Administrative audit log', true, 200, 'application/json', 'critical', false, $customHeader->id, 'Internal administrative path with sensitive audit evidence.'],
                'status' => ['HEAD', '/status', 'Availability status headers', false, 200, 'text/plain', 'low', false, $noAuth->id, 'Safe HEAD endpoint used for availability monitoring.'],
                'create_order' => ['POST', '/v1/orders', 'Create order', true, 201, 'application/json', 'high', false, $bearer->id, 'State-changing method is intentionally excluded from automatic safe execution.'],
                'delete_user' => ['DELETE', '/v1/users/{id}', 'Delete customer account', true, 204, 'application/json', 'critical', false, $bearer->id, 'Destructive operation must only be reviewed manually in a controlled environment.'],
                'debug' => ['GET', '/debug/config', 'Debug configuration', true, 404, 'application/json', 'critical', true, $customHeader->id, 'Debug path is explicitly excluded from automatic scanning.'],
            ];

            $endpointIds = [];
            foreach ($endpointDefinitions as $key => [$method, $path, $name, $authRequired, $expectedStatus, $expectedType, $risk, $excluded, $authProfileId, $reason]) {
                $endpointIds[$key] = DB::table('endpoints')->insertGetId([
                    'project_id' => $project->id,
                    'environment_id' => $stagingId,
                    'auth_profile_id' => $authProfileId,
                    'method' => $method,
                    'path' => $path,
                    'name' => $name,
                    'description' => 'Synthetic endpoint used by the comprehensive Aptoria installation demo.',
                    'tags' => 'demo, qa-review, '.strtolower($method),
                    'auth_required' => $authRequired,
                    'expected_status' => $expectedStatus,
                    'expected_content_type' => $expectedType,
                    'risk_level' => $risk,
                    'risk_reason' => $reason,
                    'qa_notes' => $excluded ? 'Excluded by QA until debug exposure is reviewed.' : 'Review stored demo evidence before changing status.',
                    'is_active' => true,
                    'excluded_from_scan' => $excluded,
                    ...$timestamps(),
                ]);
            }

            foreach (['user', 'delete_user'] as $key) {
                DB::table('endpoint_path_parameters')->insert([
                    'project_id' => $project->id,
                    'endpoint_id' => $endpointIds[$key],
                    'parameter_name' => 'id',
                    'test_value' => '42',
                    'description' => 'Synthetic customer identifier for safe URL resolution demonstrations.',
                    'enabled' => true,
                    ...$timestamps(),
                ]);
            }

            $assertions = [
                [null, 'https_required', 'equals', null, '1', 'fail'],
                [null, 'forbidden_header', 'not_exists', null, 'x-powered-by', 'warning'],
                [$endpointIds['health'], 'status_code', 'equals', null, '200', 'fail'],
                [$endpointIds['health'], 'max_response_time_ms', 'less_than_or_equal', null, '500', 'warning'],
                [$endpointIds['products'], 'json_path_type', 'equals', 'data', 'array', 'fail'],
                [$endpointIds['user'], 'required_header', 'exists', null, 'cache-control', 'warning'],
                [$endpointIds['audit'], 'max_response_time_ms', 'less_than_or_equal', null, '1000', 'warning'],
            ];
            foreach ($assertions as [$endpointId, $rule, $operator, $target, $expected, $severity]) {
                DB::table('endpoint_assertion_rules')->insert([
                    'project_id' => $project->id,
                    'endpoint_id' => $endpointId,
                    'rule_key' => $rule,
                    'operator' => $operator,
                    'target_path' => $target,
                    'expected_value' => $expected,
                    'severity' => $severity,
                    'enabled' => true,
                    ...$timestamps(),
                ]);
            }

            $baselineScanId = DB::table('scan_runs')->insertGetId([
                'project_id' => $project->id,
                'environment_id' => $stagingId,
                'created_by' => $admin->id,
                'status' => 'completed',
                'mode' => 'safe',
                'started_at' => $baselineTime,
                'finished_at' => $baselineTime->copy()->addSeconds(4),
                'duration_ms' => 4180,
                'total_endpoints' => 8,
                'scanned_count' => 5,
                'skipped_count' => 3,
                'success_count' => 5,
                'warning_count' => 0,
                'error_count' => 0,
                'summary_json' => $json(['scenario' => 'clean baseline', 'safe_methods_only' => true]),
                ...$timestamps($baselineTime),
            ]);
            $reviewScanId = DB::table('scan_runs')->insertGetId([
                'project_id' => $project->id,
                'environment_id' => $stagingId,
                'created_by' => $admin->id,
                'status' => 'completed',
                'mode' => 'safe',
                'started_at' => $reviewTime,
                'finished_at' => $reviewTime->copy()->addSeconds(9),
                'duration_ms' => 9280,
                'total_endpoints' => 8,
                'scanned_count' => 5,
                'skipped_count' => 3,
                'success_count' => 2,
                'warning_count' => 2,
                'error_count' => 1,
                'summary_json' => $json(['scenario' => 'realistic regression review', 'safe_methods_only' => true, 'headline' => 'Release blocked by profile exposure and upstream 5xx']),
                ...$timestamps($reviewTime),
            ]);

            $resultIds = [];
            $insertResult = function (int $scanId, string $key, array $data, $at) use (&$resultIds, $endpointIds, $endpointDefinitions, $stagingId, $bearer, $noAuth, $customHeader, $timestamps, $json): int {
                [$method, $path] = $endpointDefinitions[$key];
                $authProfile = match ($key) {
                    'health', 'products', 'status' => $noAuth,
                    'audit', 'debug' => $customHeader,
                    default => $bearer,
                };
                $id = DB::table('scan_results')->insertGetId([
                    'scan_run_id' => $scanId,
                    'endpoint_id' => $endpointIds[$key],
                    'auth_profile_id' => $authProfile->id,
                    'auth_applied' => $authProfile->type !== AuthProfile::TYPE_NONE,
                    'auth_summary' => $authProfile->type === AuthProfile::TYPE_NONE ? 'No authentication' : ucfirst(str_replace('_', ' ', $authProfile->type)).' applied (secret masked)',
                    'method' => $method,
                    'url' => 'https://staging-api.demo.northstar.example'.str_replace('{id}', '42', $path),
                    'status' => $data['status'],
                    'status_code' => $data['code'] ?? null,
                    'response_time_ms' => $data['time'] ?? null,
                    'content_type' => $data['type'] ?? null,
                    'response_size' => $data['size'] ?? null,
                    'headers_json' => $json($data['headers'] ?? []),
                    'body_preview' => $data['body'] ?? null,
                    'error_message' => $data['error'] ?? null,
                    'risk_level' => $data['risk'] ?? null,
                    'risk_reason' => $data['reason'] ?? null,
                    'expected_status_matched' => $data['status_match'] ?? null,
                    'expected_content_type_matched' => $data['type_match'] ?? null,
                    ...$timestamps($at),
                ]);
                $resultIds[$scanId][$key] = $id;

                return $id;
            };

            foreach ([
                'health' => ['status' => 'completed', 'code' => 200, 'time' => 82, 'type' => 'application/json', 'size' => 54, 'body' => '{"status":"ok","version":"2026.06"}', 'risk' => 'low', 'reason' => 'Baseline matched.', 'status_match' => true, 'type_match' => true],
                'products' => ['status' => 'completed', 'code' => 200, 'time' => 240, 'type' => 'application/json', 'size' => 1840, 'body' => '{"data":[{"id":"P-100","name":"Demo keyboard"}]}', 'risk' => 'public', 'reason' => 'Documented public response.', 'status_match' => true, 'type_match' => true],
                'user' => ['status' => 'completed', 'code' => 200, 'time' => 310, 'type' => 'application/json', 'size' => 620, 'body' => '{"id":42,"display_name":"Demo Customer","email":"masked@example.test"}', 'risk' => 'high', 'reason' => 'Authenticated customer data response.', 'status_match' => true, 'type_match' => true],
                'audit' => ['status' => 'completed', 'code' => 200, 'time' => 690, 'type' => 'application/json', 'size' => 940, 'body' => '{"events":[{"action":"demo.login.review","actor":"qa-demo"}]}', 'risk' => 'critical', 'reason' => 'Internal admin response requires strict review.', 'status_match' => true, 'type_match' => true],
                'status' => ['status' => 'completed', 'code' => 200, 'time' => 48, 'type' => 'text/plain', 'size' => 0, 'body' => null, 'risk' => 'low', 'reason' => 'HEAD baseline matched.', 'status_match' => true, 'type_match' => true],
                'create_order' => ['status' => 'skipped', 'risk' => 'high', 'reason' => 'POST excluded from safe scan.', 'error' => 'Safe scan never automatically executes POST requests.'],
                'delete_user' => ['status' => 'skipped', 'risk' => 'critical', 'reason' => 'DELETE excluded from safe scan.', 'error' => 'Safe scan never automatically executes DELETE requests.'],
                'debug' => ['status' => 'skipped', 'risk' => 'critical', 'reason' => 'Endpoint explicitly excluded from scan.', 'error' => 'Excluded by endpoint configuration.'],
            ] as $key => $data) {
                $insertResult($baselineScanId, $key, $data, $baselineTime);
            }

            foreach ([
                'health' => ['status' => 'completed', 'code' => 200, 'time' => 96, 'type' => 'application/json', 'size' => 61, 'body' => '{"status":"degraded","dependency":"catalog"}', 'risk' => 'review', 'reason' => 'Successful response reports degraded state.', 'status_match' => true, 'type_match' => true],
                'products' => ['status' => 'failed', 'code' => 503, 'time' => 1840, 'type' => 'application/json', 'size' => 142, 'body' => '{"error":"catalog service temporarily unavailable","trace_id":"demo-trace-7842"}', 'risk' => 'high', 'reason' => 'Unexpected 5xx and slow response.', 'status_match' => false, 'type_match' => true, 'error' => 'Upstream returned HTTP 503.'],
                'user' => ['status' => 'completed', 'code' => 200, 'time' => 460, 'type' => 'text/html', 'size' => 1280, 'body' => '<html><body>Demo customer profile fallback</body></html>', 'risk' => 'critical', 'reason' => 'Sensitive profile endpoint returned unexpected content type.', 'status_match' => true, 'type_match' => false],
                'audit' => ['status' => 'completed', 'code' => 200, 'time' => 2380, 'type' => 'application/json', 'size' => 3200, 'body' => '{"events":[{"action":"demo.export","actor":"qa-demo"}],"warning":"slow simulated response"}', 'risk' => 'critical', 'reason' => 'Admin endpoint exceeded response-time threshold.', 'status_match' => true, 'type_match' => true],
                'status' => ['status' => 'completed', 'code' => 200, 'time' => 52, 'type' => 'text/plain', 'size' => 0, 'body' => null, 'risk' => 'low', 'reason' => 'HEAD check matched.', 'status_match' => true, 'type_match' => true],
                'create_order' => ['status' => 'skipped', 'risk' => 'high', 'reason' => 'POST excluded from safe scan.', 'error' => 'Manual or dedicated test environment review required.'],
                'delete_user' => ['status' => 'skipped', 'risk' => 'critical', 'reason' => 'DELETE excluded from safe scan.', 'error' => 'Manual destructive-operation review required.'],
                'debug' => ['status' => 'skipped', 'risk' => 'critical', 'reason' => 'Endpoint explicitly excluded from scan.', 'error' => 'Excluded until debug exposure is remediated.'],
            ] as $key => $data) {
                $insertResult($reviewScanId, $key, $data, $reviewTime);
            }

            $baselineSnapshotId = $this->createSnapshot($project->id, $stagingId, $baselineScanId, $admin->id, 'Baseline - clean safe scan', $baselineTime, $endpointIds, $endpointDefinitions, $resultIds[$baselineScanId], $json, $timestamps);
            $reviewSnapshotId = $this->createSnapshot($project->id, $stagingId, $reviewScanId, $admin->id, 'Validation - regression detected', $reviewTime, $endpointIds, $endpointDefinitions, $resultIds[$reviewScanId], $json, $timestamps);

            $compareId = DB::table('compare_runs')->insertGetId([
                'project_id' => $project->id,
                'snapshot_a_id' => $baselineSnapshotId,
                'snapshot_b_id' => $reviewSnapshotId,
                'created_by' => $admin->id,
                'summary_json' => $json(['changed' => 4, 'critical' => 1, 'high' => 2, 'review' => 1, 'recommendation' => 'Block release and investigate regression evidence.']),
                ...$timestamps($reviewTime),
            ]);
            foreach ([
                ['changed', 'GET', '/v1/products', 'status_code', '200', '503', 'critical'],
                ['changed', 'GET', '/v1/products', 'response_time_ms', '240 ms', '1840 ms', 'high'],
                ['changed', 'GET', '/v1/users/{id}', 'content_type', 'application/json', 'text/html', 'high'],
                ['changed', 'GET', '/internal/admin/audit-log', 'response_time_ms', '690 ms', '2380 ms', 'review'],
            ] as [$type, $method, $path, $field, $old, $new, $severity]) {
                DB::table('compare_items')->insert(['compare_run_id' => $compareId, 'change_type' => $type, 'method' => $method, 'path' => $path, 'field_changed' => $field, 'old_value' => $old, 'new_value' => $new, 'severity' => $severity, ...$timestamps($reviewTime)]);
            }

            DB::table('api_monitors')->insert([
                'project_id' => $project->id,
                'environment_id' => $stagingId,
                'baseline_snapshot_id' => $baselineSnapshotId,
                'created_by' => $admin->id,
                'name' => 'Nightly staging release watch',
                'frequency' => 'daily',
                'is_enabled' => true,
                'auto_snapshot' => true,
                'auto_compare' => true,
                'notify_dashboard' => true,
                'last_run_at' => $reviewTime,
                'next_run_at' => now()->addDay(),
                'last_scan_run_id' => $reviewScanId,
                'last_snapshot_id' => $reviewSnapshotId,
                'last_compare_run_id' => $compareId,
                'last_status' => 'regression_detected',
                'last_message' => 'Demo monitor detected HTTP 503, content-type drift and a slow admin endpoint.',
                'summary_json' => $json(['regressions' => 4, 'action' => 'review before release']),
                ...$timestamps($reviewTime),
            ]);

            $smokeSuiteId = DB::table('test_suites')->insertGetId(['project_id' => $project->id, 'name' => 'Release smoke and security review', 'description' => 'Representative manual, automated and hybrid QA cases.', 'status' => 'active', ...$timestamps()]);
            $testCases = [
                'health' => ['Health endpoint reports ready state', $endpointIds['health'], 'automated', 'high', 'active', 'pass', 'GET /health through the safe scan engine.', 'HTTP 200 and status=ok.', 'HTTP 200 returned but dependency is degraded.'],
                'products' => ['Catalogue stays available under release load', $endpointIds['products'], 'hybrid', 'critical', 'active', 'fail', 'Run safe GET scan and inspect stored response.', 'HTTP 200 JSON response under 1200 ms.', 'HTTP 503 after 1840 ms.'],
                'user' => ['Customer profile requires auth and returns JSON', $endpointIds['user'], 'manual', 'critical', 'active', 'fail', 'Use synthetic staging token and request customer 42.', 'Authorized JSON response with minimized fields.', 'HTTP 200 returned as text/html.'],
                'delete_user' => ['Account deletion authorization review', $endpointIds['delete_user'], 'manual', 'critical', 'ready', 'blocked', 'Review DELETE contract and authorization controls without executing it.', 'Owner-only authorization documented and regression-tested.', 'Blocked pending security-owner review.'],
            ];
            $testCaseIds = [];
            foreach ($testCases as $key => [$title, $endpointId, $type, $priority, $status, $runStatus, $steps, $expected, $actual]) {
                $testCaseIds[$key] = DB::table('test_cases')->insertGetId([
                    'project_id' => $project->id,
                    'test_suite_id' => $smokeSuiteId,
                    'endpoint_id' => $endpointId,
                    'title' => $title,
                    'description' => 'Synthetic release QA scenario created by the comprehensive demo importer.',
                    'preconditions' => 'Use only the included synthetic demo evidence and credentials.',
                    'steps' => $steps,
                    'expected_result' => $expected,
                    'actual_result' => $actual,
                    'type' => $type,
                    'priority' => $priority,
                    'status' => $status,
                    'last_run_status' => $runStatus,
                    'last_run_at' => $reviewTime,
                    ...$timestamps($reviewTime),
                ]);
                DB::table('test_case_results')->insert([
                    'test_case_id' => $testCaseIds[$key],
                    'project_id' => $project->id,
                    'scan_run_id' => $reviewScanId,
                    'scan_result_id' => $resultIds[$reviewScanId][$key] ?? null,
                    'status' => $runStatus === 'blocked' ? 'blocked' : $runStatus,
                    'actual_result' => $actual,
                    'notes' => 'Stored demo execution evidence. No external request was executed during import.',
                    'executed_at' => $reviewTime,
                    ...$timestamps($reviewTime),
                ]);
            }

            $contractRunId = DB::table('contract_validation_runs')->insertGetId([
                'project_id' => $project->id,
                'scan_run_id' => $reviewScanId,
                'source_name' => 'northstar-commerce-demo-openapi.yaml',
                'contract_hash' => hash('sha256', 'northstar-demo-contract-1.0'),
                'status' => 'completed',
                'total_checks' => 5,
                'passed_count' => 2,
                'warning_count' => 1,
                'failed_count' => 2,
                'breaking_count' => 1,
                'missing_endpoint_count' => 0,
                'undocumented_endpoint_count' => 1,
                'schema_checked_count' => 2,
                'started_at' => $reviewTime,
                'finished_at' => $reviewTime->copy()->addSeconds(2),
                'summary_json' => $json(['decision' => 'blocked', 'reason' => 'Profile content type drift and undocumented debug endpoint.']),
                ...$timestamps($reviewTime),
            ]);
            $contractResults = [];
            foreach ([
                'health' => ['operation_documented', 'low', 'pass', 'Health endpoint is documented.', 'GET /health documented', 'GET /health present'],
                'products' => ['status_code', 'critical', 'fail', 'Catalogue returned an undocumented 503 response.', '200', '503'],
                'user' => ['content_type', 'high', 'fail', 'Customer profile content type differs from contract.', 'application/json', 'text/html'],
                'audit' => ['scan_evidence', 'medium', 'warning', 'Administrative endpoint response exceeded the QA threshold.', '<= 1000 ms', '2380 ms'],
                'debug' => ['operation_implemented', 'medium', 'warning', 'Debug endpoint exists in inventory but is intentionally excluded and undocumented.', 'No exposed debug endpoint', 'GET /debug/config'],
            ] as $key => [$check, $severity, $status, $message, $expected, $actual]) {
                $contractResults[$key] = DB::table('contract_validation_results')->insertGetId([
                    'contract_validation_run_id' => $contractRunId,
                    'project_id' => $project->id,
                    'endpoint_id' => $endpointIds[$key],
                    'scan_result_id' => $resultIds[$reviewScanId][$key],
                    'method' => $endpointDefinitions[$key][0],
                    'path' => $endpointDefinitions[$key][1],
                    'check_type' => $check,
                    'severity' => $severity,
                    'status' => $status,
                    'message' => $message,
                    'expected' => $expected,
                    'actual' => $actual,
                    'evidence_json' => $json(['source' => 'stored demo scan result', 'simulated' => true]),
                    ...$timestamps($reviewTime),
                ]);
            }

            $findings = [
                'catalogue' => [$endpointIds['products'], $testCaseIds['products'], $resultIds[$reviewScanId]['products'], $contractResults['products'], 'Catalogue unavailable during release validation', 'scan', 'critical', 'open', 'Expected HTTP 200; stored safe scan received HTTP 503 after 1840 ms.', 'Verify upstream health, timeout policy and add a regression test before release.'],
                'profile' => [$endpointIds['user'], $testCaseIds['user'], $resultIds[$reviewScanId]['user'], $contractResults['user'], 'Customer profile returned unexpected HTML', 'contract', 'high', 'confirmed', 'Sensitive customer endpoint returned text/html instead of the documented JSON contract.', 'Verify authentication, error handling, data minimization and response content negotiation.'],
                'audit' => [$endpointIds['audit'], null, $resultIds[$reviewScanId]['audit'], $contractResults['audit'], 'Administrative audit log response is slow', 'assertion', 'medium', 'in_progress', 'Stored response time exceeded the configured 1000 ms QA threshold.', 'Profile the query and retain a performance regression check.'],
                'delete' => [$endpointIds['delete_user'], $testCaseIds['delete_user'], $resultIds[$reviewScanId]['delete_user'], null, 'Account deletion security review is blocked', 'test_case', 'high', 'open', 'Destructive endpoint was correctly skipped by safe scan, but manual authorization evidence is missing.', 'Document endpoint ownership and verify owner-only authorization in an isolated test environment.'],
            ];
            $findingIds = [];
            foreach ($findings as $key => [$endpointId, $testCaseId, $scanResultId, $contractResultId, $title, $source, $severity, $status, $description, $recommendation]) {
                $findingIds[$key] = DB::table('findings')->insertGetId([
                    'project_id' => $project->id,
                    'endpoint_id' => $endpointId,
                    'test_case_id' => $testCaseId,
                    'scan_run_id' => $reviewScanId,
                    'scan_result_id' => $scanResultId,
                    'contract_validation_result_id' => $contractResultId,
                    'title' => $title,
                    'description' => $description,
                    'source' => $source,
                    'severity' => $severity,
                    'status' => $status,
                    'reproduction_steps' => "1. Open the stored demo scan.\n2. Inspect the linked endpoint result.\n3. Compare it with the baseline snapshot.",
                    'expected_result' => 'Release validation matches documented status, content type, security and performance expectations.',
                    'actual_result' => $description,
                    'recommendation' => $recommendation,
                    'detected_at' => $reviewTime,
                    ...$timestamps($reviewTime),
                ]);
                DB::table('finding_evidence')->insert([
                    'finding_id' => $findingIds[$key],
                    'project_id' => $project->id,
                    'type' => 'http',
                    'source_label' => 'Stored safe scan evidence',
                    'content' => 'Synthetic evidence linked to the imported comprehensive demo scan. No live target was contacted.',
                    'url' => 'https://staging-api.demo.northstar.example'.str_replace('{id}', '42', $endpointDefinitions[array_search($endpointId, $endpointIds, true)][1]),
                    'metadata_json' => $json(['scan_run_id' => $reviewScanId, 'scan_result_id' => $scanResultId, 'simulated' => true]),
                    ...$timestamps($reviewTime),
                ]);
            }

            $gateId = DB::table('qa_release_gates')->insertGetId([
                'project_id' => $project->id,
                'release_name' => 'Northstar Commerce 2026.06 demo release',
                'target_environment' => 'production',
                'gate_profile' => 'strict',
                'automated_status' => 'blocked',
                'final_decision' => 'blocked',
                'score' => 54,
                'grade' => 'D',
                'endpoint_count' => 8,
                'endpoint_coverage_percent' => 100,
                'qa_coverage_percent' => 75,
                'test_execution_percent' => 100,
                'test_pass_rate' => 25,
                'blocker_count' => 2,
                'warning_count' => 3,
                'evidence_count' => 12,
                'reviewed_by' => 'Demo QA Lead',
                'reviewed_at' => $reviewTime,
                'decision_notes' => 'Demo release is blocked until the catalogue 503 and profile content-type regression are resolved.',
                'summary_json' => $json(['simulated' => true, 'open_findings' => 4, 'regressions' => 4, 'safe_scan_only' => true]),
                ...$timestamps($reviewTime),
            ]);
            foreach ([
                ['blocker', $endpointIds['products'], 'critical', 'catalogue_503', 'Catalogue release blocker', 'HTTP 503 detected in the latest safe scan.', 'Resolve upstream failure and rerun release validation.'],
                ['blocker', $endpointIds['user'], 'high', 'profile_content_type', 'Customer profile contract blocker', 'Documented JSON endpoint returned HTML.', 'Fix content negotiation and add a regression test.'],
                ['warning', $endpointIds['audit'], 'medium', 'audit_slow', 'Administrative audit endpoint is slow', 'Latest stored response exceeded the QA threshold.', 'Review query performance before production promotion.'],
                ['evidence', $endpointIds['status'], 'info', 'safe_scan_policy', 'Safe scan policy preserved', 'POST and DELETE operations were stored as skipped, not executed.', 'Keep destructive-method review manual and isolated.'],
                ['recommendation', $endpointIds['delete_user'], 'high', 'authorization_review', 'Complete destructive-operation authorization review', 'Manual evidence is still missing.', 'Verify authentication, authorization, ownership and regression coverage.'],
            ] as [$type, $endpointId, $severity, $rule, $title, $message, $recommendation]) {
                DB::table('qa_release_gate_items')->insert([
                    'qa_release_gate_id' => $gateId,
                    'project_id' => $project->id,
                    'endpoint_id' => $endpointId,
                    'item_type' => $type,
                    'source' => 'comprehensive_demo_import',
                    'severity' => $severity,
                    'rule_key' => $rule,
                    'title' => $title,
                    'message' => $message,
                    'recommendation' => $recommendation,
                    'metadata_json' => $json(['simulated' => true]),
                    ...$timestamps($reviewTime),
                ]);
            }
        });
    }

    private function createSnapshot(int $projectId, int $environmentId, int $scanRunId, int $userId, string $name, $at, array $endpointIds, array $endpointDefinitions, array $resultIds, callable $json, callable $timestamps): int
    {
        $snapshotId = DB::table('snapshots')->insertGetId([
            'project_id' => $projectId,
            'environment_id' => $environmentId,
            'scan_run_id' => $scanRunId,
            'created_by' => $userId,
            'name' => $name,
            'description' => 'Synthetic snapshot generated by the comprehensive installation demo importer.',
            'snapshot_hash' => hash('sha256', $name.'-'.$scanRunId),
            'endpoint_count' => count($endpointIds),
            'summary_json' => $json(['simulated' => true, 'scan_run_id' => $scanRunId]),
            ...$timestamps($at),
        ]);

        foreach ($endpointIds as $key => $endpointId) {
            [$method, $path, , $authRequired, $expectedStatus, $expectedType, $risk] = $endpointDefinitions[$key];
            $result = DB::table('scan_results')->where('id', $resultIds[$key])->first();
            DB::table('snapshot_items')->insert([
                'snapshot_id' => $snapshotId,
                'endpoint_id' => $endpointId,
                'method' => $method,
                'path' => $path,
                'auth_required' => $authRequired,
                'risk_level' => $result?->risk_level ?: $risk,
                'status_code' => $result?->status_code,
                'content_type' => $result?->content_type,
                'response_time_ms' => $result?->response_time_ms,
                'expected_status' => $expectedStatus,
                'expected_content_type' => $expectedType,
                'source_hash' => hash('sha256', $scanRunId.'-'.$method.'-'.$path),
                'metadata_json' => $json(['simulated' => true, 'scan_result_id' => $resultIds[$key]]),
                ...$timestamps($at),
            ]);
        }

        return $snapshotId;
    }
}
