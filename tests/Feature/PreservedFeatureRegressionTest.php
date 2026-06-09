<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\SafeProbeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PreservedFeatureRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_aptoria_ui_vendor_assets_required_by_layouts_are_present(): void
    {
        foreach ([
            'bootstrap/css/bootstrap.min.css',
            'bootstrap/js/bootstrap.min.js',
            'jquery/jquery.min.js',
            'datatables/js/jquery.dataTables.min.js',
            'fontawesome/css/font-awesome.min.css',
            'metisMenu/js/metisMenu.min.js',
            'sweetalert/js/sweet-alert.min.js',
            'toastr/js/toastr.min.js',
        ] as $relativePath) {
            $this->assertFileExists(public_path('assets/aptoria-ui/vendor/'.$relativePath));
        }
    }

    public function test_documented_admin_can_log_in_and_see_version(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'change-me-now',
        ])->assertRedirect('/profile');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('v'.config('aptoria.version'));
    }

    public function test_seeded_endpoint_inventory_and_scan_modal_are_still_rendered(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('projects.endpoints.index', $project))
            ->assertOk()
            ->assertSee('/todos/1')
            ->assertSee('/users/1');

        $this->actingAs($admin)
            ->get(route('projects.scans.create', $project))
            ->assertOk()
            ->assertSee('data-aptoria-scan-form="true"', false)
            ->assertSee('id="aptoria-scan-modal"', false);
    }

    public function test_safe_probe_engine_executes_get_requests_and_records_results(): void
    {
        $this->seed();
        config()->set('aptoria.private_network_scan_default', true);

        Http::fake([
            '*' => Http::response(['ok' => true], 200, ['Content-Type' => 'application/json']),
        ]);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();

        $scanRun = app(SafeProbeService::class)->runProject($project, null, $admin);

        $this->assertSame(ScanRun::STATUS_COMPLETED, $scanRun->status);
        $this->assertSame(2, $scanRun->total_endpoints);
        $this->assertSame(2, $scanRun->scanned_count);
        $this->assertSame(0, $scanRun->skipped_count);
        $this->assertSame(2, ScanResult::query()->where('status', ScanResult::STATUS_COMPLETED)->count());

        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool => $request->method() === Endpoint::METHOD_GET);
    }

    public function test_safe_probe_engine_skips_destructive_methods_without_sending_a_request(): void
    {
        $this->seed();
        Http::fake();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->create([
            'method' => Endpoint::METHOD_POST,
            'path' => '/posts',
            'name' => 'Inventory-only POST endpoint',
            'risk_level' => Endpoint::RISK_REVIEW,
            'auth_required' => false,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $scanRun = app(SafeProbeService::class)->runEndpoint($project, $endpoint, $admin);
        $result = $scanRun->results()->firstOrFail();

        $this->assertSame(ScanRun::STATUS_COMPLETED, $scanRun->status);
        $this->assertSame(ScanResult::STATUS_SKIPPED, $result->status);
        Http::assertNothingSent();
    }
}
