<?php

namespace Tests\Feature;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthProfileTesterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_test_bearer_auth_profile_against_saved_endpoint(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Auth Tester API',
            'base_url' => 'https://auth.example.com',
            'is_active' => true,
        ]);
        $environment = $project->environments()->create([
            'name' => 'staging',
            'base_url' => 'https://auth.example.com',
            'is_production' => false,
        ]);
        $profile = $project->authProfiles()->create([
            'name' => 'Staging bearer',
            'type' => AuthProfile::TYPE_BEARER,
            'encrypted_token' => 'secret-token-value',
            'is_default' => true,
        ]);
        $endpoint = $project->endpoints()->create([
            'environment_id' => $environment->id,
            'auth_profile_id' => $profile->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/me',
            'name' => 'Current user',
            'auth_required' => true,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
        ]);

        Http::fake([
            'auth.example.com/me' => Http::response(['ok' => true, 'token' => 'secret-token-value'], 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $response = $this->actingAs($admin)->post(route('projects.auth-profiles.test', [$project, $profile]), [
            'test_target' => 'endpoint',
            'test_endpoint_id' => $endpoint->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('auth_profile_test_result');
        $result = session('auth_profile_test_result');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('GET', $result['method']);
        $this->assertStringContainsString('/me', $result['url']);
        $this->assertStringContainsString('Bearer', $result['auth_summary']);
        $this->assertStringNotContainsString('secret-token-value', $result['response_preview']);

        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer secret-token-value'));
    }

    public function test_auth_profile_tester_marks_unauthorized_response_as_failed(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Unauthorized Auth API',
            'base_url' => 'https://auth-fail.example.com',
            'is_active' => true,
        ]);
        $profile = $project->authProfiles()->create([
            'name' => 'Broken bearer',
            'type' => AuthProfile::TYPE_BEARER,
            'encrypted_token' => 'bad-token',
            'is_default' => true,
        ]);

        Http::fake([
            'auth-fail.example.com/protected' => Http::response(['error' => 'Unauthorized'], 401, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $response = $this->actingAs($admin)->post(route('projects.auth-profiles.test', [$project, $profile]), [
            'test_target' => 'custom',
            'test_method' => 'GET',
            'test_url' => 'https://auth-fail.example.com/protected',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('auth_profile_test_result');
        $result = session('auth_profile_test_result');

        $this->assertFalse($result['ok']);
        $this->assertSame(401, $result['status']);
        $this->assertSame(__('messages.auth_profiles.test_status_failed'), $result['status_label']);
    }

    public function test_auth_profile_tester_applies_basic_and_custom_header_profiles(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Header Auth API',
            'base_url' => 'https://headers.example.com',
            'is_active' => true,
        ]);
        $basic = $project->authProfiles()->create([
            'name' => 'Basic profile',
            'type' => AuthProfile::TYPE_BASIC,
            'username' => 'qa-user',
            'encrypted_password' => 'qa-password',
        ]);
        $custom = $project->authProfiles()->create([
            'name' => 'Header profile',
            'type' => AuthProfile::TYPE_CUSTOM_HEADER,
            'header_name' => 'X-API-Key',
            'encrypted_header_value' => 'custom-secret',
        ]);

        Http::fake([
            'headers.example.com/basic' => Http::response(['ok' => true], 200),
            'headers.example.com/custom' => Http::response(['ok' => true], 200),
        ]);

        $this->actingAs($admin)->post(route('projects.auth-profiles.test', [$project, $basic]), [
            'test_target' => 'custom',
            'test_method' => 'GET',
            'test_url' => 'https://headers.example.com/basic',
        ])->assertSessionHas('auth_profile_test_result');

        $this->actingAs($admin)->post(route('projects.auth-profiles.test', [$project, $custom]), [
            'test_target' => 'custom',
            'test_method' => 'GET',
            'test_url' => 'https://headers.example.com/custom',
        ])->assertSessionHas('auth_profile_test_result');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://headers.example.com/basic'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('qa-user:qa-password')));
        Http::assertSent(fn ($request): bool => $request->url() === 'https://headers.example.com/custom'
            && $request->hasHeader('X-API-Key', 'custom-secret'));
    }

    public function test_auth_profile_edit_page_renders_tester_and_result(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $profile = $project->authProfiles()->firstOrFail();

        $this->withSession([
            'auth_profile_test_result' => [
                'profile_name' => $profile->name,
                'profile_type' => $profile->type_label,
                'auth_summary' => $profile->masked_summary,
                'auth_applied' => false,
                'method' => 'GET',
                'url' => 'https://jsonplaceholder.typicode.com/todos/1',
                'target_label' => 'GET /todos/1',
                'status' => 200,
                'duration_ms' => 123,
                'content_type' => 'application/json',
                'response_preview' => '{"ok":true}',
                'response_headers' => ['Content-Type' => 'application/json'],
                'ok' => true,
                'style' => 'success',
                'status_label' => __('messages.auth_profiles.test_status_passed'),
                'message' => __('messages.auth_profiles.test_success', ['status' => 200, 'time' => 123]),
            ],
        ])->actingAs($admin)
            ->get(route('projects.auth-profiles.edit', [$project, $profile]))
            ->assertOk()
            ->assertSee(__('messages.auth_profiles.test_title'))
            ->assertSee(__('messages.auth_profiles.test_target_endpoint'))
            ->assertSee(__('messages.auth_profiles.test_response_preview'))
            ->assertSee('GET /todos/1')
            ->assertDontSee('messages.auth_profiles');
    }
}
