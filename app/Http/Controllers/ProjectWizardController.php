<?php

namespace App\Http\Controllers;

use App\Models\AuthProfile;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Services\Endpoints\EndpointImportService;
use App\Services\Reports\ReportExportService;
use App\Services\SafeProbeService;
use App\Services\Settings\ProjectSettingService;
use App\Services\Snapshots\SnapshotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProjectWizardController extends Controller
{
    public function create(): View
    {
        return view('projects.wizard', [
            'authTypes' => AuthProfile::TYPES,
            'samplePayload' => $this->samplePayload(),
            'sampleOpenApiPayload' => $this->sampleOpenApiPayload(),
            'sampleOpenApiYamlPayload' => $this->sampleOpenApiYamlPayload(),
            'samplePostmanPayload' => $this->samplePostmanPayload(),
            'samplePostmanEnvironmentPayload' => $this->samplePostmanEnvironmentPayload(),
        ]);
    }

    public function store(
        Request $request,
        EndpointImportService $importer,
        ProjectSettingService $projectSettings,
        SafeProbeService $safeProbeService,
        SnapshotService $snapshotService,
        ReportExportService $reports
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_url' => ['required', 'url', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'environment_name' => ['required', 'string', 'max:100'],
            'environment_base_url' => ['required', 'url', 'max:500'],
            'environment_is_production' => ['nullable', 'boolean'],
            'auth_name' => ['required', 'string', 'max:100'],
            'auth_type' => ['required', Rule::in(AuthProfile::TYPES)],
            'token' => ['nullable', 'required_if:auth_type,'.AuthProfile::TYPE_BEARER, 'string', 'max:2000'],
            'username' => ['nullable', 'required_if:auth_type,'.AuthProfile::TYPE_BASIC, 'string', 'max:255'],
            'password' => ['nullable', 'required_if:auth_type,'.AuthProfile::TYPE_BASIC, 'string', 'max:2000'],
            'header_name' => ['nullable', 'required_if:auth_type,'.AuthProfile::TYPE_CUSTOM_HEADER, 'string', 'max:255'],
            'header_value' => ['nullable', 'required_if:auth_type,'.AuthProfile::TYPE_CUSTOM_HEADER, 'string', 'max:2000'],
            'auth_notes' => ['nullable', 'string', 'max:2000'],
            'format' => ['required', Rule::in(['csv', 'json', 'openapi', 'postman'])],
            'import_source' => ['required', Rule::in(['paste', 'url'])],
            'source_url' => ['nullable', 'required_if:import_source,url', 'url', 'max:1000'],
            'payload' => ['nullable', 'required_if:import_source,paste', 'string', 'max:200000'],
            'postman_environment_payload' => ['nullable', 'string', 'max:200000'],
            'postman_create_assertions' => ['nullable', 'boolean'],
            'postman_create_test_suites' => ['nullable', 'boolean'],
            'assert_status_code' => ['nullable', 'boolean'],
            'assert_status_code_value' => ['nullable', 'integer', 'min:100', 'max:599'],
            'assert_response_time' => ['nullable', 'boolean'],
            'assert_response_time_value' => ['nullable', 'integer', 'min:1'],
            'assert_required_content_type' => ['nullable', 'boolean'],
            'assert_https' => ['nullable', 'boolean'],
            'assert_max_risk' => ['nullable', 'boolean'],
            'assert_max_risk_value' => ['nullable', 'integer', 'min:0', 'max:100'],
            'run_initial_scan' => ['nullable', 'boolean'],
            'create_initial_snapshot' => ['nullable', 'boolean'],
            'generate_initial_report' => ['nullable', 'boolean'],
        ], [
            'token.required_if' => __('messages.wizard.validation_bearer_token_required'),
            'username.required_if' => __('messages.wizard.validation_basic_username_required'),
            'password.required_if' => __('messages.wizard.validation_basic_password_required'),
            'header_name.required_if' => __('messages.wizard.validation_header_name_required'),
            'header_value.required_if' => __('messages.wizard.validation_header_value_required'),
        ]);

        if (($validated['import_source'] ?? 'paste') === 'url' && ! in_array($validated['format'], ['openapi', 'postman'], true)) {
            return back()
                ->withErrors(['format' => __('messages.import_preview.reason_url_collection_only')])
                ->withInput();
        }

        if (($validated['import_source'] ?? 'paste') === 'url') {
            $validated['payload'] = $importer->fetchRemotePayload((string) ($validated['source_url'] ?? ''));
        }

        $result = DB::transaction(function () use ($validated, $request, $importer, $projectSettings): array {
            $project = Project::query()->create([
                'user_id' => Auth::id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'base_url' => $validated['base_url'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            $environment = $project->environments()->create([
                'name' => $validated['environment_name'],
                'base_url' => $validated['environment_base_url'],
                'is_production' => $request->boolean('environment_is_production'),
            ]);

            $authProfile = $project->authProfiles()->create([
                'name' => $validated['auth_name'],
                'type' => $validated['auth_type'],
                'encrypted_token' => $validated['token'] ?? null,
                'username' => $validated['username'] ?? null,
                'encrypted_password' => $validated['password'] ?? null,
                'header_name' => $validated['header_name'] ?? null,
                'encrypted_header_value' => $validated['header_value'] ?? null,
                'notes' => $validated['auth_notes'] ?? ($validated['auth_type'] === AuthProfile::TYPE_NONE ? __('messages.auth_profiles.no_auth_summary') : null),
                'is_default' => true,
            ]);

            $environment->update(['auth_profile_id' => $authProfile->id]);

            $projectSettings->seedDefaults($project);
            $projectSettings->set($project, 'scan.default_environment_id', (string) $environment->id);
            $projectSettings->set($project, 'scan.default_auth_profile_id', (string) $authProfile->id);

            $importSummary = $importer->import(
                $project,
                $validated['format'],
                $validated['payload'],
                $environment->id,
                $authProfile->id,
                $validated['postman_environment_payload'] ?? null,
                [
                    'postman_create_environment' => false,
                    'postman_create_auth_profile' => false,
                    'postman_create_assertions' => $request->boolean('postman_create_assertions', true),
                    'postman_create_test_suites' => $request->boolean('postman_create_test_suites'),
                ]
            );

            if (($importSummary['valid'] ?? 0) < 1) {
                throw ValidationException::withMessages([
                    'payload' => __('messages.wizard.validation_at_least_one_endpoint'),
                ]);
            }

            $this->seedAssertionRules($project, $request);

            return [
                'project' => $project->refresh(),
                'environment' => $environment->refresh(),
                'importSummary' => $importSummary,
            ];
        });

        /** @var Project $project */
        $project = $result['project'];
        $environment = $result['environment'];
        $importSummary = $result['importSummary'];
        $scanRun = null;
        $snapshot = null;
        $reportGenerated = false;
        $warnings = [];

        if ($request->boolean('run_initial_scan', true)) {
            if ($environment->is_production) {
                $warnings[] = __('messages.wizard.production_scan_skipped');
            } else {
                try {
                    $scanRun = $safeProbeService->runProject($project, $environment, $request->user(), 'safe');
                } catch (Throwable $exception) {
                    $warnings[] = __('messages.wizard.initial_scan_failed', ['message' => $exception->getMessage()]);
                }
            }
        }

        if ($scanRun instanceof ScanRun && $request->boolean('create_initial_snapshot', true)) {
            if ($scanRun->status === ScanRun::STATUS_COMPLETED && $project->endpoints()->exists()) {
                try {
                    $snapshot = $snapshotService->createFromScanRun(
                        $scanRun,
                        $request->user(),
                        __('messages.wizard.initial_snapshot_name', ['project' => $project->name]),
                        __('messages.wizard.initial_snapshot_description')
                    );
                } catch (Throwable $exception) {
                    $warnings[] = __('messages.wizard.initial_snapshot_failed', ['message' => $exception->getMessage()]);
                }
            } else {
                $warnings[] = __('messages.wizard.initial_snapshot_skipped');
            }
        }

        if ($request->boolean('generate_initial_report', true)) {
            try {
                $reports->fullProjectMarkdown($project->refresh());
                $reportGenerated = true;
            } catch (Throwable $exception) {
                $warnings[] = __('messages.wizard.initial_report_failed', ['message' => $exception->getMessage()]);
            }
        }

        $redirect = redirect()->route('projects.wizard.complete', [
            'project' => $project,
            'scanRun' => $scanRun?->id,
            'snapshot' => $snapshot?->id,
            'report' => $reportGenerated ? '1' : '0',
            'imported' => (string) ($importSummary['valid'] ?? 0),
        ]);

        if ($warnings !== []) {
            return $redirect->with('warning', __('messages.wizard.created').' '.implode(' ', $warnings));
        }

        return $redirect->with('success', __('messages.wizard.created'));
    }

    public function complete(Request $request, Project $project): View
    {
        $project->load(['environments', 'authProfiles', 'endpoints']);
        $project->loadCount(['endpoints', 'scanRuns', 'snapshots']);

        $scanRun = null;
        if ($request->integer('scanRun') > 0) {
            $scanRun = $project->scanRuns()
                ->with(['environment', 'results.endpoint', 'snapshot'])
                ->whereKey($request->integer('scanRun'))
                ->first();
        }

        $scanRun ??= $project->scanRuns()
            ->with(['environment', 'results.endpoint', 'snapshot'])
            ->latest()
            ->first();

        $snapshot = null;
        if ($request->integer('snapshot') > 0) {
            $snapshot = $project->snapshots()
                ->with(['environment', 'scanRun'])
                ->whereKey($request->integer('snapshot'))
                ->first();
        }

        $snapshot ??= $project->snapshots()
            ->with(['environment', 'scanRun'])
            ->latest()
            ->first();

        return view('projects.wizard-complete', [
            'project' => $project,
            'scanRun' => $scanRun,
            'snapshot' => $snapshot,
            'reportGenerated' => $request->boolean('report', false),
            'importedEndpoints' => $request->integer('imported'),
        ]);
    }

    private function seedAssertionRules(Project $project, Request $request): void
    {
        $rules = [];

        if ($request->boolean('assert_status_code', true)) {
            $rules[] = [
                'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
                'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
                'expected_value' => (string) $request->integer('assert_status_code_value', 200),
                'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            ];
        }

        if ($request->boolean('assert_response_time', true)) {
            $rules[] = [
                'rule_key' => EndpointAssertionRule::RULE_MAX_RESPONSE_TIME_MS,
                'operator' => EndpointAssertionRule::OPERATOR_LESS_THAN_OR_EQUAL,
                'expected_value' => (string) $request->integer('assert_response_time_value', 2500),
                'severity' => EndpointAssertionRule::SEVERITY_WARNING,
            ];
        }

        if ($request->boolean('assert_required_content_type', true)) {
            $rules[] = [
                'rule_key' => EndpointAssertionRule::RULE_REQUIRED_HEADER,
                'operator' => EndpointAssertionRule::OPERATOR_EXISTS,
                'expected_value' => 'content-type',
                'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            ];
        }

        if ($request->boolean('assert_https', true)) {
            $rules[] = [
                'rule_key' => EndpointAssertionRule::RULE_HTTPS_REQUIRED,
                'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
                'expected_value' => 'true',
                'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            ];
        }

        if ($request->boolean('assert_max_risk', true)) {
            $rules[] = [
                'rule_key' => EndpointAssertionRule::RULE_MAX_RISK_SCORE,
                'operator' => EndpointAssertionRule::OPERATOR_LESS_THAN_OR_EQUAL,
                'expected_value' => (string) $request->integer('assert_max_risk_value', 70),
                'severity' => EndpointAssertionRule::SEVERITY_WARNING,
            ];
        }

        foreach ($rules as $rule) {
            $project->assertionRules()->create([
                ...$rule,
                'endpoint_id' => null,
                'enabled' => true,
            ]);
        }
    }

    private function samplePayload(): string
    {
        return "method,path,name,risk_level,auth_required,expected_status,expected_content_type,tags,description\n"
            ."GET,/posts,List posts,public,false,200,application/json,content,List all posts\n"
            ."GET,/posts/1,Single post,public,false,200,application/json,content,Read one post\n"
            ."GET,/users,List users,review,false,200,application/json,users,List users";
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
      "name": "Content",
      "item": [
        {
          "name": "List posts",
          "request": {
            "method": "GET",
            "header": [
              {"key": "Accept", "value": "application/json"}
            ],
            "url": {
              "raw": "{{baseUrl}}/posts",
              "host": ["{{baseUrl}}"],
              "path": ["posts"]
            }
          },
          "response": [
            {"name": "OK", "code": 200, "header": [{"key": "Content-Type", "value": "application/json"}]}
          ]
        },
        {
          "name": "Create post",
          "request": {
            "method": "POST",
            "header": [
              {"key": "Content-Type", "value": "application/json"}
            ],
            "body": {
              "mode": "raw",
              "raw": "{\"title\":\"demo\",\"body\":\"qa\"}"
            },
            "url": "{{baseUrl}}/posts"
          },
          "response": [
            {"name": "Created", "code": 201, "header": [{"key": "Content-Type", "value": "application/json"}]}
          ]
        }
      ]
    }
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
