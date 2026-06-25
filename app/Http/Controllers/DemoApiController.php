<?php

namespace App\Http\Controllers;

use App\Services\DemoScenarioTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DemoApiController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'Aptoria Sandbox API',
            'version' => '1.0.0',
            'environment' => 'sandbox',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function users(): JsonResponse
    {
        return response()->json([
            'data' => array_values($this->demoUsers()),
            'meta' => [
                'source' => 'aptoria-demo-api',
                'count' => count($this->demoUsers()),
                'pii_policy' => 'synthetic-demo-data',
            ],
        ]);
    }

    public function user(int $id): JsonResponse
    {
        $user = $this->demoUsers()[$id] ?? null;

        if (! $user) {
            return response()->json([
                'error' => 'Not found',
                'code' => 'DEMO_USER_NOT_FOUND',
            ], 404);
        }

        return response()->json(['data' => $user]);
    }

    public function orders(): JsonResponse
    {
        return response()->json([
            'data' => array_values($this->demoOrders()),
            'meta' => [
                'source' => 'aptoria-demo-api',
                'count' => count($this->demoOrders()),
                'currency' => 'EUR',
            ],
        ]);
    }

    public function order(int $id): JsonResponse
    {
        $order = $this->demoOrders()[$id] ?? null;

        if (! $order) {
            return response()->json([
                'error' => 'Not found',
                'code' => 'DEMO_ORDER_NOT_FOUND',
            ], 404);
        }

        return response()->json(['data' => $order]);
    }

    public function products(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['sku' => 'APT-SANDBOX', 'name' => 'Aptoria Sandbox Plan', 'status' => 'active'],
                ['sku' => 'APT-PORTABLE', 'name' => 'Aptoria Portable Runtime', 'status' => 'pilot'],
                ['sku' => 'APT-REPORTS', 'name' => 'Evidence Report Pack', 'status' => 'active'],
            ],
            'meta' => ['source' => 'aptoria-demo-api'],
        ]);
    }



    public function scenarios(DemoScenarioTemplateService $templates): JsonResponse
    {
        $scenarios = $templates->all();

        return response()->json([
            'data' => array_values($scenarios),
            'meta' => [
                'source' => 'aptoria-demo-api',
                'kind' => 'scenario_templates',
                'count' => count($scenarios),
            ],
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function scenario(string $slug, DemoScenarioTemplateService $templates): JsonResponse
    {
        $scenario = $templates->find($slug);

        if (! $scenario) {
            return response()->json([
                'error' => 'Not found',
                'code' => 'DEMO_SCENARIO_NOT_FOUND',
            ], 404);
        }

        return response()->json(['data' => $scenario], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function scenarioEvidence(string $slug, DemoScenarioTemplateService $templates): JsonResponse
    {
        $payload = $templates->evidencePayload($slug);

        if (! $payload) {
            return response()->json([
                'error' => 'Not found',
                'code' => 'DEMO_SCENARIO_NOT_FOUND',
            ], 404);
        }

        return response()->json($payload, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function reportSummary(): JsonResponse
    {
        return response()->json([
            'release' => '2026.06-demo',
            'readiness_score' => 82,
            'decision' => 'conditional_go',
            'blockers' => 1,
            'warnings' => 3,
            'verified_evidence' => 7,
            'open_findings' => [
                'critical' => 0,
                'high' => 1,
                'medium' => 2,
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function publicProfile(): JsonResponse
    {
        return response()->json([
            'profile' => [
                'id' => 'public-demo-profile',
                'display_name' => 'Aptoria Demo Viewer',
                'role' => 'client_viewer',
                'visibility' => 'public',
            ],
            'security_note' => 'This endpoint intentionally represents safe public metadata.',
        ]);
    }

    public function privateAccount(Request $request): JsonResponse
    {
        if (! $this->validBearerToken($request)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Bearer token required.',
                'hint' => 'Use Authorization: Bearer '.config('aptoria.demo.auth_token'),
            ], 401);
        }

        return response()->json([
            'account' => [
                'id' => 'demo-private-account',
                'plan' => 'portable-pilot',
                'license_state' => 'demo-valid',
                'limits' => [
                    'projects' => 3,
                    'evidence_items' => 250,
                ],
            ],
        ]);
    }

    public function leakyTokenExample(): JsonResponse
    {
        return response()->json([
            'user' => 'demo-user',
            'debug_token' => 'demo-token-should-not-be-public',
            'api_key' => 'DEMO-KEY-1234567890',
            'note' => 'This endpoint intentionally simulates exposed sensitive data for Aptoria evidence detection demos.',
        ]);
    }

    public function serverError(): JsonResponse
    {
        return response()->json([
            'error' => 'Internal demo error',
            'code' => 'DEMO_500',
            'message' => 'This endpoint intentionally returns HTTP 500 so Aptoria can detect a blocker.',
        ], 500);
    }

    public function slowResponse(): JsonResponse
    {
        usleep(1250000);

        return response()->json([
            'status' => 'slow',
            'duration_hint_ms' => 1250,
            'message' => 'This endpoint intentionally responds slowly for response-time assertion demos.',
        ]);
    }

    public function openApi(): JsonResponse
    {
        return response()->json($this->openApiDocument(), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function postman(): JsonResponse
    {
        return response()->json($this->postmanCollection(), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function qaCsv(): Response
    {
        $rows = [
            ['type', 'method', 'path', 'title', 'status', 'severity', 'expected_status', 'actual_status', 'notes'],
            ['endpoint', 'GET', '/health', 'Health endpoint is available', 'pass', 'low', '200', '200', 'Baseline live API smoke check'],
            ['test_result', 'GET', '/errors/server-error', 'Server error endpoint should not return 500', 'fail', 'high', '200', '500', 'Intentional demo blocker'],
            ['finding', 'GET', '/security/leaky-token-example', 'Debug token exposed in public JSON', 'open', 'high', '200', '200', 'Sensitive value intentionally exposed for demo'],
            ['evidence', 'GET', '/reports/summary', 'Release summary response captured', 'pass', 'medium', '200', '200', 'JSON response can be attached as evidence'],
            ['evidence', 'GET', '/scenarios/release-gate-decision/evidence.json', 'Guided scenario run sheet is available', 'pass', 'medium', '200', '200', 'Scenario run sheet can be reviewed or imported as evidence'],
        ];

        $csv = implode("\n", array_map(fn (array $row): string => implode(',', array_map([$this, 'csvCell'], $row)), $rows))."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="aptoria-demo-qa-results.csv"',
        ]);
    }

    public function jiraCsv(): Response
    {
        $rows = [
            ['Issue key', 'Summary', 'Issue Type', 'Priority', 'Status', 'Endpoint', 'Evidence'],
            ['APT-DEMO-1', 'Debug token exposed in demo public JSON response', 'Bug', 'High', 'Open', 'GET /security/leaky-token-example', 'Response body contains debug_token and api_key fields'],
            ['APT-DEMO-2', 'Slow report summary response should be reviewed', 'Task', 'Medium', 'In Review', 'GET /errors/slow-response', 'Response exceeds demo performance threshold'],
            ['APT-DEMO-3', 'Release gate can be conditional when high finding is accepted', 'Story', 'Medium', 'Ready for Review', 'Release Gate', 'Decision package should include blocker rationale'],
        ];

        $csv = implode("\n", array_map(fn (array $row): string => implode(',', array_map([$this, 'csvCell'], $row)), $rows))."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="aptoria-demo-jira-issues.csv"',
        ]);
    }

    public function har(): JsonResponse
    {
        $base = rtrim((string) config('aptoria.demo.api_base_url'), '/');

        return response()->json([
            'log' => [
                'version' => '1.2',
                'creator' => ['name' => 'Aptoria Demo HAR Generator', 'version' => config('aptoria.version')],
                'entries' => [
                    $this->harEntry($base.'/health', 200, 114),
                    $this->harEntry($base.'/security/leaky-token-example', 200, 420),
                    $this->harEntry($base.'/errors/server-error', 500, 133),
                    $this->harEntry($base.'/scenarios/release-gate-decision/evidence.json', 200, 640),
                ],
            ],
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function demoUsers(): array
    {
        return [
            1 => ['id' => 1, 'name' => 'Demo Customer', 'email' => 'customer@example.test', 'segment' => 'client'],
            2 => ['id' => 2, 'name' => 'QA Reviewer', 'email' => 'qa.reviewer@example.test', 'segment' => 'internal'],
            3 => ['id' => 3, 'name' => 'Release Approver', 'email' => 'approver@example.test', 'segment' => 'release'],
        ];
    }

    private function demoOrders(): array
    {
        return [
            1001 => ['id' => 1001, 'customer_id' => 1, 'status' => 'paid', 'total' => 129.90],
            1002 => ['id' => 1002, 'customer_id' => 1, 'status' => 'pending_review', 'total' => 59.00],
            1003 => ['id' => 1003, 'customer_id' => 3, 'status' => 'refunded', 'total' => 15.50],
        ];
    }

    private function validBearerToken(Request $request): bool
    {
        $header = (string) $request->header('Authorization');
        $expected = 'Bearer '.config('aptoria.demo.auth_token');

        return hash_equals($expected, $header);
    }

    private function openApiDocument(): array
    {
        $base = rtrim((string) config('aptoria.demo.api_base_url'), '/');

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Aptoria Sandbox API',
                'version' => '1.0.0',
                'description' => 'Synthetic JSON API used to demonstrate Aptoria endpoint inventory, scan evidence, assertions, import adapters, findings and release gates.',
            ],
            'servers' => [['url' => $base]],
            'paths' => [
                '/health' => ['get' => $this->operation('Health check', 200)],
                '/users' => ['get' => $this->operation('List demo users', 200)],
                '/users/{id}' => ['get' => $this->operationWithParameters('Get demo user', 200, [[
                    'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer'],
                ]])],
                '/orders' => ['get' => $this->operation('List demo orders', 200)],
                '/products' => ['get' => $this->operation('List demo products', 200)],
                '/reports/summary' => ['get' => $this->operation('Release report summary', 200)],
                '/security/private-account' => ['get' => $this->operationWithSecurity('Private account example', 200, [['demoBearer' => []]])],
                '/security/leaky-token-example' => ['get' => $this->operation('Intentionally leaky response', 200)],
                '/errors/server-error' => ['get' => $this->operation('Intentional server error', 500)],
                '/errors/slow-response' => ['get' => $this->operation('Intentional slow response', 200)],
                '/scenarios' => ['get' => $this->operation('List guided demo scenario templates', 200)],
                '/scenarios/{slug}' => ['get' => $this->operationWithParameters('Get guided demo scenario template', 200, [[
                    'name' => 'slug', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'],
                ]])],
                '/scenarios/{slug}/evidence.json' => ['get' => $this->operationWithParameters('Get scenario run-sheet evidence JSON', 200, [[
                    'name' => 'slug', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'],
                ]])],
            ],
            'components' => [
                'securitySchemes' => [
                    'demoBearer' => ['type' => 'http', 'scheme' => 'bearer'],
                ],
            ],
        ];
    }

    private function operationWithParameters(string $summary, int $status, array $parameters): array
    {
        $operation = $this->operation($summary, $status);
        $operation['parameters'] = $parameters;

        return $operation;
    }

    private function operationWithSecurity(string $summary, int $status, array $security): array
    {
        $operation = $this->operation($summary, $status);
        $operation['security'] = $security;

        return $operation;
    }

    private function operation(string $summary, int $status): array
    {
        return [
            'summary' => $summary,
            'responses' => [
                (string) $status => [
                    'description' => $status >= 500 ? 'Intentional demo failure' : 'JSON response',
                    'content' => [
                        'application/json' => [
                            'schema' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function postmanCollection(): array
    {
        $base = rtrim((string) config('aptoria.demo.api_base_url'), '/');

        $makeItem = function (string $name, string $method, string $path, bool $auth = false) use ($base): array {
            $item = [
                'name' => $name,
                'request' => [
                    'method' => $method,
                    'header' => $auth ? [['key' => 'Authorization', 'value' => 'Bearer '.config('aptoria.demo.auth_token')]] : [],
                    'url' => $base.$path,
                ],
            ];

            return $item;
        };

        return [
            'info' => [
                'name' => 'Aptoria Demo API Collection',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [
                $makeItem('Health', 'GET', '/health'),
                $makeItem('Users', 'GET', '/users'),
                $makeItem('Private account', 'GET', '/security/private-account', true),
                $makeItem('Leaky token example', 'GET', '/security/leaky-token-example'),
                $makeItem('Server error', 'GET', '/errors/server-error'),
                $makeItem('Slow response', 'GET', '/errors/slow-response'),
                $makeItem('Scenario templates', 'GET', '/scenarios'),
                $makeItem('Security leak review scenario', 'GET', '/scenarios/security-leak-review'),
                $makeItem('Release gate scenario evidence', 'GET', '/scenarios/release-gate-decision/evidence.json'),
            ],
        ];
    }

    private function harEntry(string $url, int $status, int $bodySize): array
    {
        return [
            'startedDateTime' => now()->toIso8601String(),
            'time' => $status >= 500 ? 180 : 42,
            'request' => ['method' => 'GET', 'url' => $url, 'headers' => []],
            'response' => [
                'status' => $status,
                'statusText' => $status >= 500 ? 'Internal Server Error' : 'OK',
                'headers' => [['name' => 'Content-Type', 'value' => 'application/json']],
                'content' => ['size' => $bodySize, 'mimeType' => 'application/json'],
            ],
        ];
    }

    private function csvCell(string|int|float|null $value): string
    {
        $value = (string) $value;

        return '"'.str_replace('"', '""', $value).'"';
    }
}
