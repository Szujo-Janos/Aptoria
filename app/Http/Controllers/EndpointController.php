<?php

namespace App\Http\Controllers;

use App\Http\Requests\EndpointImportRequest;
use App\Http\Requests\EndpointRequest;
use App\Models\Endpoint;
use App\Models\Project;
use App\Services\AssertionEvaluationService;
use App\Services\RegressionEvaluationService;
use App\Services\Auth\AuthProfileRuntimeService;
use App\Services\Risk\RiskAnalyzer;
use App\Services\Settings\SettingService;
use App\Services\Endpoints\EndpointImportService;
use App\Services\Endpoints\PathParameterResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EndpointController extends Controller
{
    public function index(Project $project, SettingService $settings): View
    {
        $endpoints = $project->endpoints()
            ->with(['environment', 'authProfile', 'latestScanResult'])
            ->orderBy('risk_level')
            ->orderBy('method')
            ->orderBy('path')
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('endpoints.index', compact('project', 'endpoints'));
    }

    public function create(Project $project): View
    {
        $project->load(['environments', 'authProfiles']);

        return view('endpoints.create', [
            'project' => $project,
            'endpoint' => new Endpoint([
                'method' => Endpoint::METHOD_GET,
                'risk_level' => Endpoint::RISK_REVIEW,
                'auth_required' => false,
                'is_active' => true,
                'excluded_from_scan' => false,
            ]),
        ]);
    }

    public function store(EndpointRequest $request, Project $project): RedirectResponse
    {
        $endpoint = $project->endpoints()->create($this->payload($request));

        return redirect()
            ->route('projects.endpoints.show', [$project, $endpoint])
            ->with('success', __('messages.endpoints.created'));
    }

    public function show(
        Project $project,
        Endpoint $endpoint,
        RiskAnalyzer $riskAnalyzer,
        AssertionEvaluationService $assertions,
        RegressionEvaluationService $regressions,
        PathParameterResolver $pathParameters,
        AuthProfileRuntimeService $authRuntime
    ): View
    {
        $this->ensureEndpointBelongsToProject($project, $endpoint);
        $endpoint->load(['environment.authProfile', 'authProfile', 'project', 'latestScanResult.scanRun', 'testCases.testSuite', 'testCases.latestResult', 'contractValidationResults.run', 'findings.evidence', 'producedBehaviorLinks.consumerEndpoint', 'consumedBehaviorLinks.producerEndpoint']);
        $riskAnalysis = $riskAnalyzer->analyze($endpoint, $endpoint->latestScanResult);
        $qaBugReport = $riskAnalyzer->buildQaBugReport($endpoint, $endpoint->latestScanResult, $riskAnalysis);
        $developerReviewSnippet = $riskAnalyzer->buildDeveloperReviewSnippet($endpoint, $endpoint->latestScanResult, $riskAnalysis);
        $assertionRules = $assertions->rulesForDisplay($endpoint);
        $assertionEvaluation = $assertions->evaluate($endpoint, $endpoint->latestScanResult);
        $regressionEvaluation = $regressions->latestForEndpoint($endpoint);
        $pathParameterRows = $pathParameters->displayRows($endpoint);
        $pathParameterOverrideText = $pathParameters->formatText($project, $endpoint);
        $resolvedFullUrl = $pathParameters->buildUrl($project, $endpoint);
        $effectiveAuthProfile = $authRuntime->resolveForEndpoint($endpoint);
        $effectiveAuth = $authRuntime->maskedReadiness($effectiveAuthProfile);

        return view('endpoints.show', compact(
            'project',
            'endpoint',
            'riskAnalysis',
            'qaBugReport',
            'developerReviewSnippet',
            'assertionRules',
            'assertionEvaluation',
            'regressionEvaluation',
            'pathParameterRows',
            'pathParameterOverrideText',
            'resolvedFullUrl',
            'effectiveAuthProfile',
            'effectiveAuth'
        ));
    }

    public function edit(Project $project, Endpoint $endpoint): View
    {
        $this->ensureEndpointBelongsToProject($project, $endpoint);
        $project->load(['environments', 'authProfiles']);
        $endpoint->load(['environment', 'authProfile']);

        return view('endpoints.edit', compact('project', 'endpoint'));
    }

    public function update(EndpointRequest $request, Project $project, Endpoint $endpoint): RedirectResponse
    {
        $this->ensureEndpointBelongsToProject($project, $endpoint);
        $endpoint->update($this->payload($request));

        return redirect()
            ->route('projects.endpoints.show', [$project, $endpoint])
            ->with('success', __('messages.endpoints.updated'));
    }

    public function destroy(Project $project, Endpoint $endpoint): RedirectResponse
    {
        $this->ensureEndpointBelongsToProject($project, $endpoint);
        $endpoint->delete();

        return redirect()
            ->route('projects.endpoints.index', $project)
            ->with('success', __('messages.endpoints.deleted'));
    }

    public function importForm(Project $project): View
    {
        $project->load(['environments', 'authProfiles']);

        return view('endpoints.import', [
            'project' => $project,
            'sampleOpenApiPayload' => $this->sampleOpenApiPayload(),
            'sampleOpenApiYamlPayload' => $this->sampleOpenApiYamlPayload(),
            'samplePostmanPayload' => $this->samplePostmanPayload(),
            'samplePostmanEnvironmentPayload' => $this->samplePostmanEnvironmentPayload(),
            'samplePostmanGlobalsPayload' => $this->samplePostmanGlobalsPayload(),
        ]);
    }

    public function previewImport(EndpointImportRequest $request, Project $project, EndpointImportService $importer): View
    {
        $project->load(['environments', 'authProfiles']);
        [$input, $payload] = $this->resolvedImportPayload($request, $importer);

        $preview = $importer->preview(
            $project,
            (string) $input['format'],
            $payload,
            $input['postman_environment_payload'] ?? null,
            $this->postmanImportOptions($input) + ['postman_globals_payload' => $input['postman_globals_payload'] ?? null]
        );

        return view('endpoints.import-preview', [
            'project' => $project,
            'preview' => $preview,
            'input' => $input,
        ]);
    }

    public function import(EndpointImportRequest $request, Project $project, EndpointImportService $importer): RedirectResponse
    {
        [$input, $payload] = $this->resolvedImportPayload($request, $importer);

        $summary = $importer->import(
            $project,
            (string) $input['format'],
            $payload,
            $input['environment_id'] ?? null,
            $input['auth_profile_id'] ?? null,
            $input['postman_environment_payload'] ?? null,
            $this->postmanImportOptions($input) + ['postman_globals_payload' => $input['postman_globals_payload'] ?? null]
        );

        return redirect()
            ->route('projects.endpoints.index', $project)
            ->with('success', __('messages.endpoints.imported', [
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'skipped' => $summary['skipped'],
            ]));
    }

    /** @return array{0:array<string,mixed>,1:string} */
    private function resolvedImportPayload(EndpointImportRequest $request, EndpointImportService $importer): array
    {
        $input = $request->validated();
        $input['import_source'] = $input['import_source'] ?? 'paste';

        if (($input['import_source'] ?? 'paste') === 'paste' && empty($input['payload']) && ! empty($input['payload_encoded'])) {
            $decodedPayload = base64_decode((string) $input['payload_encoded'], true);
            if ($decodedPayload !== false) {
                $input['payload'] = $decodedPayload;
            }
        }

        if (empty($input['postman_environment_payload']) && ! empty($input['postman_environment_payload_encoded'])) {
            $decodedEnvironmentPayload = base64_decode((string) $input['postman_environment_payload_encoded'], true);
            if ($decodedEnvironmentPayload !== false) {
                $input['postman_environment_payload'] = $decodedEnvironmentPayload;
            }
        }

        if (empty($input['postman_globals_payload']) && ! empty($input['postman_globals_payload_encoded'])) {
            $decodedGlobalsPayload = base64_decode((string) $input['postman_globals_payload_encoded'], true);
            if ($decodedGlobalsPayload !== false) {
                $input['postman_globals_payload'] = $decodedGlobalsPayload;
            }
        }

        if ($input['import_source'] === 'url') {
            $payload = $importer->fetchRemotePayload((string) ($input['source_url'] ?? ''));
            $input['payload'] = $payload;
            $input['import_source'] = 'paste';
        } else {
            $payload = (string) ($input['payload'] ?? '');
        }

        return [$input, $payload];
    }


    /** @param array<string,mixed> $input */
    private function postmanImportOptions(array $input): array
    {
        return [
            'postman_create_environment' => filter_var($input['postman_create_environment'] ?? false, FILTER_VALIDATE_BOOL),
            'postman_create_auth_profile' => filter_var($input['postman_create_auth_profile'] ?? false, FILTER_VALIDATE_BOOL),
            'postman_create_test_suites' => filter_var($input['postman_create_test_suites'] ?? false, FILTER_VALIDATE_BOOL),
            'postman_create_assertions' => filter_var($input['postman_create_assertions'] ?? false, FILTER_VALIDATE_BOOL),
            'postman_globals_payload' => $input['postman_globals_payload'] ?? null,
        ];
    }

    private function payload(EndpointRequest $request): array
    {
        return [
            ...$request->validated(),
            'auth_required' => $request->boolean('auth_required'),
            'is_active' => $request->boolean('is_active'),
            'excluded_from_scan' => $request->boolean('excluded_from_scan'),
        ];
    }

    private function ensureEndpointBelongsToProject(Project $project, Endpoint $endpoint): void
    {
        abort_unless($endpoint->project_id === $project->id, 404);
    }



    private function samplePostmanPayload(): string
    {
        return <<<'JSON'
{
  "info": {
    "name": "Demo Postman Collection",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Users",
      "item": [
        {
          "name": "List users",
          "request": {
            "method": "GET",
            "header": [
              {"key": "Accept", "value": "application/json"}
            ],
            "url": {
              "raw": "{{baseUrl}}/users",
              "host": ["{{baseUrl}}"],
              "path": ["users"]
            }
          },
          "event": [
            {"listen": "test", "script": {"exec": ["pm.response.to.have.status(200);", "pm.expect(pm.response.responseTime).to.be.below(1000);", "pm.response.to.have.header('Content-Type');"]}}
          ],
          "response": [
            {"name": "OK", "code": 200, "header": [{"key": "Content-Type", "value": "application/json"}]}
          ]
        },
        {
          "name": "Update user",
          "request": {
            "method": "PATCH",
            "header": [
              {"key": "Authorization", "value": "Bearer {{token}}"},
              {"key": "Content-Type", "value": "application/json"}
            ],
            "body": {
              "mode": "raw",
              "raw": "{\"name\":\"Demo User\"}"
            },
            "url": "{{baseUrl}}/users/:id"
          },
          "auth": {
            "type": "bearer",
            "bearer": [{"key": "token", "value": "{{token}}", "type": "string"}]
          },
          "event": [
            {"listen": "test", "script": {"exec": ["pm.response.to.have.status(200);", "var jsonData = pm.response.json();", "pm.expect(jsonData.id).to.exist;"]}}
          ],
          "response": [
            {"name": "OK", "code": 200, "header": [{"key": "Content-Type", "value": "application/json"}]}
          ]
        }
      ]
    }
  ]
}
JSON;
    }

    private function samplePostmanGlobalsPayload(): string
    {
        return <<<'JSON'
{
  "name": "Demo Postman Globals",
  "values": [
    {"key": "apiVersion", "value": "v1", "enabled": true},
    {"key": "tenantId", "value": "demo-tenant", "enabled": true},
    {"key": "globalToken", "value": "demo-global-token", "enabled": true}
  ]
}
JSON;
    }


    private function samplePostmanEnvironmentPayload(): string
    {
        return <<<'JSON'
{
  "name": "Demo Postman Environment",
  "values": [
    {"key": "baseUrl", "value": "https://jsonplaceholder.typicode.com", "enabled": true},
    {"key": "token", "value": "demo-postman-token", "enabled": true},
    {"key": "userId", "value": "1", "enabled": true}
  ]
}
JSON;
    }


    private function sampleOpenApiPayload(): string
    {
        return <<<'JSON'
{
  "openapi": "3.0.3",
  "info": {
    "title": "Demo API",
    "version": "1.0.0"
  },
  "servers": [
    {
      "url": "https://jsonplaceholder.typicode.com"
    }
  ],
  "paths": {
    "/posts": {
      "get": {
        "summary": "List posts",
        "tags": ["content"],
        "responses": {
          "200": {
            "description": "OK",
            "content": {
              "application/json": {}
            }
          }
        }
      }
    },
    "/posts/{id}": {
      "get": {
        "summary": "Read one post",
        "tags": ["content"],
        "responses": {
          "200": {
            "description": "OK",
            "content": {
              "application/json": {}
            }
          }
        }
      }
    }
  }
}
JSON;
    }


    private function sampleOpenApiYamlPayload(): string
    {
        return <<<'YAML'
openapi: 3.0.3
info:
  title: Demo API
  version: 1.0.0
servers:
  - url: https://jsonplaceholder.typicode.com
paths:
  /posts:
    get:
      summary: List posts
      tags:
        - content
      responses:
        '200':
          description: OK
          content:
            application/json: {}
  /posts/{id}:
    get:
      summary: Read one post
      tags:
        - content
      responses:
        '200':
          description: OK
          content:
            application/json: {}
YAML;
    }


}
