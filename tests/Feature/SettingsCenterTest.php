<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_center_renders_advanced_tabs_and_system_info(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Advanced Settings Center')
            ->assertSee('Probe Safety')
            ->assertSee('Assertions')
            ->assertSee('Security &amp; Privacy', false)
            ->assertSee('System Info')
            ->assertSee('Aptoria v'.config('aptoria.version'));
    }

    public function test_advanced_settings_can_be_saved(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('settings.update'), $this->payload())
            ->assertRedirect(route('settings.index'));

        $this->assertSame('hu', Setting::query()->where('key', 'app.default_locale')->value('value'));
        $this->assertSame('50', Setting::query()->where('key', 'app.items_per_page')->value('value'));
        $this->assertSame('75', Setting::query()->where('key', 'scan.max_endpoints_per_scan')->value('value'));
        $this->assertSame('users,payments,secrets', Setting::query()->where('key', 'risk.sensitive_keywords')->value('value'));
        $this->assertSame('1', Setting::query()->where('key', 'scan.verify_ssl')->value('value'));
        $this->assertSame('technical', Setting::query()->where('key', 'report.default_type')->value('value'));
    }

    public function test_settings_group_can_be_reset(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        Setting::query()->updateOrCreate(['key' => 'scan.timeout_seconds'], [
            'value' => '99',
            'type' => 'integer',
            'group' => 'scan',
        ]);

        $this->actingAs($admin)
            ->post(route('settings.reset-group', 'scan'))
            ->assertRedirect(route('settings.index'));

        $this->assertSame('10', Setting::query()->where('key', 'scan.timeout_seconds')->value('value'));
    }

    private function payload(): array
    {
        return [
            'app_name' => 'Aptoria',
            'app_default_locale' => 'hu',
            'app_timezone' => 'Europe/Budapest',
            'app_date_format' => 'Y-m-d H:i',
            'app_items_per_page' => 50,
            'scan_timeout_seconds' => 12,
            'scan_connect_timeout_seconds' => 4,
            'scan_follow_redirects' => '1',
            'scan_verify_ssl' => '1',
            'scan_max_redirects' => 2,
            'scan_max_response_size_kb' => 2048,
            'scan_max_body_preview_kb' => 32,
            'scan_user_agent' => 'Aptoria/{version}',
            'scan_retry_count' => 1,
            'scan_retry_delay_ms' => 100,
            'scan_rate_limit_ms' => 100,
            'scan_max_endpoints_per_scan' => 75,
            'probe_safe_methods_only' => '1',
            'probe_block_destructive_methods' => '1',
            'scan_block_private_networks' => '1',
            'scan_require_confirmation' => '1',
            'scan_require_production_confirmation' => '1',
            'risk_low_threshold' => 0,
            'risk_medium_threshold' => 25,
            'risk_high_threshold' => 50,
            'risk_critical_threshold' => 75,
            'risk_slow_response_ms' => 900,
            'risk_very_slow_response_ms' => 3000,
            'risk_enable_security_header_checks' => '1',
            'risk_enable_https_checks' => '1',
            'risk_enable_response_size_checks' => '1',
            'risk_enable_exposure_checks' => '1',
            'risk_sensitive_keywords' => "users\npayments\nsecrets",
            'risk_internal_keywords' => "internal\ndebug",
            'assertions_enabled' => '1',
            'assertions_default_status_code' => 200,
            'assertions_treat_regression_as_failure' => '1',
            'scan_store_response_headers' => '1',
            'scan_store_response_body_preview' => '1',
            'exports_include_endpoint_details' => '1',
            'exports_include_timestamps' => '1',
            'report_default_type' => 'technical',
            'ui_show_header_logo' => '1',
            'ui_show_scan_summary_cards' => '1',
            'ui_default_sidebar_state' => 'expanded',
            'ui_enable_sweetalert' => '1',
            'security_mask_auth_secrets' => '1',
            'security_hide_tokens_in_ui' => '1',
            'security_hide_tokens_in_exports' => '1',
            'security_session_timeout_minutes' => 120,
            'scan_mask_secrets' => '1',
        ];
    }
}
