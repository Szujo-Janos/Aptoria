<?php

namespace App\Http\Controllers;

use App\Models\AuthProfile;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use App\Services\Endpoints\EndpointImportService;
use App\Services\Settings\ProjectSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectWizardController extends Controller
{
    public function create(): View
    {
        return view('projects.wizard', [
            'authTypes' => AuthProfile::TYPES,
            'samplePayload' => $this->samplePayload(),
            'sampleOpenApiPayload' => $this->sampleOpenApiPayload(),
            'sampleOpenApiYamlPayload' => $this->sampleOpenApiYamlPayload(),
        ]);
    }

    public function store(Request $request, EndpointImportService $importer, ProjectSettingService $projectSettings): RedirectResponse
    {
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
            'token' => ['nullable', 'string', 'max:2000'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:2000'],
            'header_name' => ['nullable', 'string', 'max:255'],
            'header_value' => ['nullable', 'string', 'max:2000'],
            'auth_notes' => ['nullable', 'string', 'max:2000'],
            'format' => ['required', Rule::in(['csv', 'json', 'openapi'])],
            'import_source' => ['required', Rule::in(['paste', 'url'])],
            'source_url' => ['nullable', 'required_if:import_source,url', 'url', 'max:1000'],
            'payload' => ['nullable', 'required_if:import_source,paste', 'string', 'max:200000'],
            'assert_status_code' => ['nullable', 'boolean'],
            'assert_status_code_value' => ['nullable', 'integer', 'min:100', 'max:599'],
            'assert_response_time' => ['nullable', 'boolean'],
            'assert_response_time_value' => ['nullable', 'integer', 'min:1'],
            'assert_required_content_type' => ['nullable', 'boolean'],
            'assert_https' => ['nullable', 'boolean'],
            'assert_max_risk' => ['nullable', 'boolean'],
            'assert_max_risk_value' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        if (($validated['import_source'] ?? 'paste') === 'url' && $validated['format'] !== 'openapi') {
            return back()
                ->withErrors(['format' => __('messages.import_preview.reason_url_openapi_only')])
                ->withInput();
        }

        if (($validated['import_source'] ?? 'paste') === 'url') {
            $validated['payload'] = $importer->fetchRemotePayload((string) ($validated['source_url'] ?? ''));
        }

        $project = DB::transaction(function () use ($validated, $request, $importer, $projectSettings): Project {
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

            $importer->import(
                $project,
                $validated['format'],
                $validated['payload'],
                $environment->id,
                $authProfile->id
            );

            $this->seedAssertionRules($project, $request);

            return $project;
        });

        return redirect()
            ->route('projects.show', $project)
            ->with('success', __('messages.wizard.created'));
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
