<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Settings\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SettingsFunctionalAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_visible_global_setting_is_rendered_saveable_and_persisted(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $settings = app(SettingService::class);
        $visible = collect($settings->grouped())->flatten(1);

        $response = $this->actingAs($admin)->get(route('settings.index'))->assertOk();

        foreach ($visible as $setting) {
            $response->assertSee('name="'.str_replace('.', '_', $setting['key']).'"', false);
        }

        Setting::query()->delete();

        $this->actingAs($admin)
            ->post(route('settings.update'), $this->payload($settings))
            ->assertRedirect(route('settings.index'));

        foreach (array_keys($settings->defaults()) as $key) {
            $this->assertDatabaseHas('settings', ['key' => $key]);
        }

        $this->assertSame(
            Setting::query()->where('key', 'scan.rate_limit_ms')->value('value'),
            Setting::query()->where('key', 'scan.delay_between_requests_ms')->value('value')
        );
    }

    public function test_every_visible_global_setting_has_a_runtime_consumer(): void
    {
        $settings = app(SettingService::class);
        $visibleKeys = collect($settings->grouped())->flatten(1)->pluck('key');
        $excluded = [
            app_path('Services/Settings/SettingService.php'),
            app_path('Http/Controllers/SettingsController.php'),
            resource_path('views/settings/index.blade.php'),
        ];
        $files = array_merge(File::allFiles(app_path()), File::allFiles(resource_path('views')));
        $runtimeSource = collect($files)
            ->reject(fn ($file): bool => in_array($file->getRealPath(), $excluded, true))
            ->map(fn ($file): string => File::get($file->getRealPath()))
            ->implode("\n");

        foreach ($visibleKeys as $key) {
            $this->assertStringContainsString($key, $runtimeSource, "No runtime consumer found for visible setting {$key}");
        }
    }

    public function test_settings_page_has_no_misleading_activation_copy_in_either_language(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        foreach (['en', 'hu'] as $locale) {
            $content = strtolower($this->withSession(['locale' => $locale])
                ->actingAs($admin)
                ->get(route('settings.index'))
                ->assertOk()
                ->getContent());

            foreach (['partial', 'not wired', 'broken', 'prepared', 'coming soon'] as $misleadingCopy) {
                $this->assertStringNotContainsString($misleadingCopy, $content);
            }

            $this->assertStringNotContainsString('messages.settings.', $content);
        }
    }

    public function test_ui_settings_change_rendered_dashboard_behavior(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $settings = app(SettingService::class);
        $settings->set('ui.theme', 'dark');
        $settings->set('ui.table_density', 'compact');
        $settings->set('ui.default_sidebar_state', 'collapsed');
        $settings->set('ui.compact_dashboard', true);
        $settings->set('ui.show_scan_summary_cards', false);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aptoria-theme-dark', false)
            ->assertSee('aptoria-table-compact', false)
            ->assertSee('aptoria-sidebar-collapsed', false)
            ->assertSee('aptoria-compact-dashboard', false)
            ->assertDontSee('aptoria-kpi-row', false);
    }

    public function test_session_timeout_setting_expires_an_inactive_session(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        app(SettingService::class)->set('security.session_timeout_minutes', 5);

        $this->withSession(['aptoria_last_activity_at' => time() - 361])
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** @return array<string, mixed> */
    private function payload(SettingService $settings): array
    {
        return collect($settings->grouped())
            ->flatten(1)
            ->mapWithKeys(function (array $setting): array {
                $value = $setting['value'];

                if ($setting['type'] === 'boolean') {
                    $value = $value ? '1' : '0';
                } elseif ($setting['type'] === 'csv' && is_array($value)) {
                    $value = implode(',', $value);
                }

                return [str_replace('.', '_', $setting['key']) => $value];
            })
            ->all();
    }
}
