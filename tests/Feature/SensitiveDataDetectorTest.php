<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\User;
use App\Services\SafeProbeService;
use App\Services\Security\SensitiveDataDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SensitiveDataDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_detector_finds_and_masks_sensitive_response_patterns(): void
    {
        $analysis = app(SensitiveDataDetector::class)->inspectResponse(
            json_encode([
                'email' => 'person@example.com',
                'access_token' => 'very-secret-token-123456',
                'profile' => ['phone' => '+36 30 123 4567'],
            ]),
            ['Set-Cookie' => ['session=abc123; HttpOnly']],
            'application/json'
        );

        $this->assertTrue($analysis['detected']);
        $this->assertGreaterThanOrEqual(3, $analysis['count']);
        $this->assertContains('sensitive_json_field', array_column($analysis['matches'], 'type'));
        $this->assertContains('set_cookie', array_column($analysis['matches'], 'type'));
        $this->assertStringNotContainsString('very-secret-token-123456', json_encode($analysis));
        $this->assertStringNotContainsString('person@example.com', json_encode($analysis));
    }

    public function test_safe_probe_records_sensitive_data_result_and_finding(): void
    {
        $admin = User::query()->create([
            'name' => 'Sensitive Admin',
            'email' => 'sensitive@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Sensitive API',
            'slug' => 'sensitive-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/1',
            'name' => 'Get user',
            'auth_required' => false,
            'expected_status' => 200,
            'expected_content_type' => 'application/json',
            'risk_level' => Endpoint::RISK_LOW,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        Http::fake([
            '*' => Http::response([
                'id' => 1,
                'email' => 'person@example.com',
                'password' => 'do-not-return-this',
                'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.signaturevalue',
            ], 200, ['Content-Type' => 'application/json']),
        ]);

        $scanRun = app(SafeProbeService::class)->runEndpoint($project, $endpoint, $admin);
        $result = $scanRun->results()->firstOrFail();

        $this->assertSame(ScanResult::STATUS_COMPLETED, $result->status);
        $this->assertTrue((bool) $result->sensitive_data_detected);
        $this->assertGreaterThanOrEqual(2, $result->sensitive_data_count);
        $this->assertStringNotContainsString('do-not-return-this', (string) $result->body_preview);
        $this->assertStringNotContainsString('person@example.com', (string) $result->body_preview);
        $this->assertStringNotContainsString('signaturevalue', json_encode($result->sensitive_data_summary_json));

        $finding = Finding::query()->where('scan_result_id', $result->id)->firstOrFail();
        $this->assertSame(Finding::SOURCE_SCAN, $finding->source);
        $this->assertContains($finding->severity, [Finding::SEVERITY_HIGH, Finding::SEVERITY_CRITICAL]);
        $this->assertSame(1, $finding->evidence()->count());
    }

    public function test_endpoint_inventory_can_filter_sensitive_data_findings(): void
    {
        $admin = User::query()->create([
            'name' => 'Sensitive Inventory Admin',
            'email' => 'sensitive-inventory@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Sensitive Inventory API',
            'slug' => 'sensitive-inventory-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/profile',
            'auth_required' => false,
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
            'sensitive_data_detected' => true,
            'sensitive_data_count' => 1,
            'sensitive_data_summary_json' => ['summary' => 'Email address (1)', 'matches' => []],
            'risk_level' => Endpoint::RISK_HIGH,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.endpoint-inventory.index', ['project' => $project, 'coverage' => 'sensitive_data']))
            ->assertOk()
            ->assertSee('/profile')
            ->assertSee(__('messages.sensitive_data.detected'));
    }
}
