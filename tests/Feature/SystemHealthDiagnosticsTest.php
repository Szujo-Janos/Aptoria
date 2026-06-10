<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\System\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHealthDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_health_service_returns_summary_categories_and_checks(): void
    {
        $this->seed();

        $report = app(SystemHealthService::class)->report();

        $this->assertSame('Aptoria', $report['product']);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('categories', $report);
        $this->assertArrayHasKey('runtime', $report['categories']);
        $this->assertArrayHasKey('database', $report['categories']);
        $this->assertArrayHasKey('cache', $report['categories']);
        $this->assertArrayHasKey('import_export', $report['categories']);
        $this->assertArrayHasKey('reporting', $report['categories']);
        $this->assertArrayHasKey('queue', $report['categories']);
        $this->assertNotEmpty($report['checks']);
        $this->assertArrayHasKey('system_info', $report);
    }

    public function test_admin_can_view_system_health_page(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('system.health.index'))
            ->assertOk()
            ->assertSee(__('messages.system_health.heading'))
            ->assertSee(__('messages.system_health.categories.runtime'))
            ->assertSee(__('messages.system_health.categories.database'))
            ->assertSee(__('messages.system_health.categories.storage'))
            ->assertSee(__('messages.system_health.categories.reporting'))
            ->assertSee(__('messages.system_health.cli_title'))
            ->assertSee('artisan aptoria:health')
            ->assertSee(route('system.health.json'), false)
            ->assertDontSee('messages.system_health');
    }


    public function test_system_health_page_has_translations_for_english_and_hungarian(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        foreach (['en', 'hu'] as $locale) {
            app()->setLocale($locale);

            $this->actingAs($admin)
                ->get(route('system.health.index'))
                ->assertOk()
                ->assertSee(__('messages.system_health.heading'))
                ->assertSee(__('messages.system_health.status.ok'))
                ->assertSee(__('messages.system_health.categories.runtime'))
                ->assertDontSee('messages.system_health');
        }
    }

    public function test_system_health_json_export_returns_machine_readable_report(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->getJson(route('system.health.json'))
            ->assertOk()
            ->assertJsonPath('product', 'Aptoria')
            ->assertJsonStructure([
                'summary' => ['status', 'score', 'ok', 'warnings', 'failed', 'info', 'total'],
                'categories' => ['runtime', 'cache', 'database', 'security', 'import_export', 'reporting', 'automation', 'queue'],
                'checks',
                'system_info',
                'next_steps',
            ]);
    }

    public function test_system_health_report_contains_deployment_specific_checks(): void
    {
        $this->seed();

        $report = app(SystemHealthService::class)->report();
        $keys = collect($report['checks'])->pluck('key')->all();

        $this->assertContains('cache_write_read', $keys);
        $this->assertContains('database_export_payload', $keys);
        $this->assertContains('simple_pdf_renderer', $keys);
        $this->assertContains('temporary_upload_directory', $keys);
        $this->assertContains('queue_connection_configured', $keys);
    }

    public function test_system_health_is_reachable_from_global_navigation(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('system.health.index'), false)
            ->assertSee(__('messages.nav.system_health'));
    }
}
