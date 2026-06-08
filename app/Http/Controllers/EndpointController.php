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
        $endpoint->load(['environment.authProfile', 'authProfile', 'project', 'latestScanResult.scanRun', 'testCases.testSuite', 'testCases.latestResult', 'contractValidationResults.run', 'findings.evidence']);
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
        ]);
    }

    public function previewImport(EndpointImportRequest $request, Project $project, EndpointImportService $importer): View
    {
        $project->load(['environments', 'authProfiles']);
        [$input, $payload] = $this->resolvedImportPayload($request, $importer);

        $preview = $importer->preview(
            $project,
            (string) $input['format'],
            $payload
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
            $input['auth_profile_id'] ?? null
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

        if ($input['import_source'] === 'url') {
            $payload = $importer->fetchRemotePayload((string) ($input['source_url'] ?? ''));
            $input['payload'] = $payload;
            $input['import_source'] = 'paste';
        } else {
            $payload = (string) ($input['payload'] ?? '');
        }

        return [$input, $payload];
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
