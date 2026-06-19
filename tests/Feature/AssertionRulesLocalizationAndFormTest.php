<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssertionRulesLocalizationAndFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_assertions_page_uses_translated_unified_form_copy(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create([
            'locale' => 'hu',
            'password_change_required' => false,
        ]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $endpoint = Endpoint::factory()->create([
            'project_id' => $project->id,
            'method' => 'GET',
            'path' => '/health',
        ]);

        EndpointAssertionRule::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'name' => 'Health check status',
            'rule_key' => 'status_code',
            'operator' => 'equals',
            'expected_value' => '200',
            'target_path' => 'status',
            'severity' => 'blocker',
            'enabled' => true,
            'description' => 'Release gate smoke evidence.',
        ]);

        $this->actingAs($user)
            ->get(route('projects.assertions.index', $project))
            ->assertOk()
            ->assertSee('Assertion szabályok')
            ->assertSee('Hozz létre újrahasználható assertion szabályt')
            ->assertSee('Szabályazonosítás')
            ->assertSee('Assertion feltétel')
            ->assertSee('Release hatás')
            ->assertSee('Célútvonal')
            ->assertSee('Blokkoló')
            ->assertDontSee('messages.assertions.form_help')
            ->assertDontSee('messages.assertions.target_path');
    }

    public function test_assertion_target_path_is_saved_from_the_form(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('projects.assertions.store', $project), [
                'name' => 'JSON status must be ok',
                'endpoint_id' => null,
                'rule_key' => 'body_contains',
                'operator' => 'contains',
                'expected_value' => 'ok',
                'target_path' => 'data.status',
                'severity' => 'warning',
                'enabled' => '1',
                'description' => 'Regression smoke evidence.',
            ])
            ->assertRedirect(route('projects.assertions.index', $project));

        $this->assertDatabaseHas('endpoint_assertion_rules', [
            'project_id' => $project->id,
            'name' => 'JSON status must be ok',
            'target_path' => 'data.status',
        ]);
    }
}
