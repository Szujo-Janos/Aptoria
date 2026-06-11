<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\EndpointBehaviorLink;
use App\Models\Project;
use App\Models\User;
use App\Services\Behavior\ApiBehaviorMapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBehaviorMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_behavior_map_detects_producer_consumer_dependency_and_persists_links(): void
    {
        [$admin, $project, $producer, $consumer] = $this->behaviorFixture('behavior-map-detect@example.com');

        $summary = app(ApiBehaviorMapService::class)->summarize($project->fresh());

        $this->assertSame(4, $summary['summary']['endpoints']);
        $this->assertGreaterThanOrEqual(1, $summary['summary']['producers']);
        $this->assertGreaterThanOrEqual(1, $summary['summary']['dependencies']);

        $producer->refresh();
        $consumer->refresh();

        $this->assertSame(Endpoint::BEHAVIOR_ROLE_PRODUCER, $producer->behavior_role);
        $this->assertSame('orders', $producer->behavior_resource);
        $this->assertSame(Endpoint::BEHAVIOR_ROLE_CONSUMER, $consumer->behavior_role);
        $this->assertTrue($consumer->sequence_candidate);

        $link = EndpointBehaviorLink::query()->firstOrFail();
        $this->assertSame($project->id, $link->project_id);
        $this->assertSame($producer->id, $link->producer_endpoint_id);
        $this->assertSame($consumer->id, $link->consumer_endpoint_id);
        $this->assertSame(EndpointBehaviorLink::TYPE_PATH_PARAMETER, $link->dependency_type);
        $this->assertSame('id', $link->path_parameter);
        $this->assertSame(85, $link->confidence);

        $this->actingAs($admin)
            ->get(route('projects.api-behavior.index', $project))
            ->assertOk()
            ->assertSee(__('messages.api_behavior.title'))
            ->assertSee('POST /orders')
            ->assertSee('GET /orders/{id}');
    }

    public function test_behavior_map_marks_destructive_endpoints_and_suggests_sequences(): void
    {
        [$admin, $project] = $this->behaviorFixture('behavior-map-sequence@example.com');

        $summary = app(ApiBehaviorMapService::class)->summarize($project->fresh());

        $deleteEndpoint = Endpoint::query()
            ->where('project_id', $project->id)
            ->where('method', Endpoint::METHOD_DELETE)
            ->firstOrFail();

        $deleteEndpoint->refresh();
        $this->assertTrue($deleteEndpoint->destructive_action);
        $this->assertSame(Endpoint::BEHAVIOR_ROLE_DESTRUCTIVE, $deleteEndpoint->behavior_role);
        $this->assertGreaterThanOrEqual(1, $summary['summary']['destructive']);
        $this->assertGreaterThanOrEqual(1, $summary['sequences']->count());

        $this->actingAs($admin)
            ->post(route('projects.api-behavior.refresh', $project))
            ->assertRedirect(route('projects.api-behavior.index', $project));

        $this->actingAs($admin)
            ->get(route('projects.endpoints.show', [$project, $deleteEndpoint]))
            ->assertOk()
            ->assertSee(__('messages.api_behavior.endpoint_panel_title'))
            ->assertSee(__('messages.api_behavior.flags.destructive'));
    }

    /** @return array{0: User, 1: Project, 2: Endpoint, 3: Endpoint} */
    private function behaviorFixture(string $email): array
    {
        $admin = User::query()->create([
            'name' => 'Behavior Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Behavior Map API',
            'slug' => str('behavior-map-api-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $producer = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_POST,
            'path' => '/orders',
            'name' => 'Create order',
            'auth_required' => true,
            'expected_status' => 201,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $consumer = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/orders/{id}',
            'name' => 'Show order',
            'auth_required' => true,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_REVIEW,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_PATCH,
            'path' => '/orders/{id}',
            'name' => 'Update order',
            'auth_required' => true,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_DELETE,
            'path' => '/orders/{id}',
            'name' => 'Delete order',
            'auth_required' => true,
            'expected_status' => 204,
            'risk_level' => Endpoint::RISK_CRITICAL,
            'is_active' => true,
            'excluded_from_scan' => true,
        ]);

        return [$admin, $project, $producer, $consumer];
    }
}
