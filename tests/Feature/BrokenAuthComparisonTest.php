<?php

namespace Tests\Feature;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\User;
use App\Services\SafeProbeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrokenAuthComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_safe_probe_compares_auth_required_endpoint_without_credentials_and_records_finding(): void
    {
        $admin = User::query()->create([
            'name' => 'Broken Auth Admin',
            'email' => 'broken-auth@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Broken Auth API',
            'slug' => 'broken-auth-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $profile = $project->authProfiles()->create([
            'name' => 'Bearer profile',
            'type' => AuthProfile::TYPE_BEARER,
            'encrypted_token' => 'secret-token-value',
            'is_default' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'auth_profile_id' => $profile->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/account',
            'name' => 'Account details',
            'auth_required' => true,
            'expected_status' => 200,
            'expected_content_type' => 'application/json',
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        Http::fake(function ($request) {
            if ($request->hasHeader('Authorization', 'Bearer secret-token-value')) {
                return Http::response(['ok' => true, 'account' => 'authorized'], 200, ['Content-Type' => 'application/json']);
            }

            return Http::response(['ok' => true, 'email' => 'person@example.com'], 200, ['Content-Type' => 'application/json']);
        });

        $scanRun = app(SafeProbeService::class)->runEndpoint($project, $endpoint, $admin);
        $result = $scanRun->results()->firstOrFail();

        $this->assertSame(ScanResult::STATUS_COMPLETED, $result->status);
        $this->assertTrue((bool) $result->broken_auth_detected);
        $this->assertSame(200, $result->broken_auth_summary_json['auth_status_code']);
        $this->assertSame(200, $result->broken_auth_summary_json['no_auth_status_code']);
        $this->assertTrue((bool) $result->broken_auth_summary_json['no_auth_sensitive_data_detected']);
        $this->assertStringNotContainsString('person@example.com', json_encode($result->broken_auth_summary_json));

        $finding = Finding::query()
            ->where('scan_result_id', $result->id)
            ->where('title', 'like', '%broken auth%')
            ->firstOrFail();

        $this->assertSame(Finding::SOURCE_SCAN, $finding->source);
        $this->assertContains($finding->severity, [Finding::SEVERITY_HIGH, Finding::SEVERITY_CRITICAL]);
        $this->assertSame(1, $finding->evidence()->count());

        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer secret-token-value'));
        Http::assertSent(fn ($request): bool => ! $request->hasHeader('Authorization'));
    }

    public function test_endpoint_inventory_can_filter_broken_auth_findings(): void
    {
        $admin = User::query()->create([
            'name' => 'Broken Auth Inventory Admin',
            'email' => 'broken-auth-inventory@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Broken Auth Inventory API',
            'slug' => 'broken-auth-inventory-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/profile',
            'auth_required' => true,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $scanRun = $project->scanRuns()->create([
            'created_by' => $admin->id,
            'status' => 'completed',
            'mode' => 'safe',
        ]);

        $scanRun->results()->create([
            'endpoint_id' => $endpoint->id,
            'method' => Endpoint::METHOD_GET,
            'url' => 'https://api.example.test/profile',
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
            'broken_auth_detected' => true,
            'broken_auth_summary_json' => ['summary' => 'Unauthenticated request returned a successful response.', 'reason' => 'no_auth_success'],
            'risk_level' => Endpoint::RISK_HIGH,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.endpoint-inventory.index', ['project' => $project, 'coverage' => 'broken_auth']))
            ->assertOk()
            ->assertSee('/profile')
            ->assertSee(__('messages.broken_auth.detected'));
    }
}
