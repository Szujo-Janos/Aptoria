<?php

namespace Tests\Feature;

use Tests\TestCase;

class SetupVisualPolishTest extends TestCase
{
    public function test_setup_auth_layout_loads_sweetalert_assets(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/auth.blade.php')) ?: '';

        $this->assertStringContainsString('plugins/sweetalert2/sweetalert2.min.css', $layout);
        $this->assertStringContainsString('plugins/sweetalert2/sweetalert2.min.js', $layout);
    }

    public function test_setup_uses_new_desktop_wizard_brand_layout(): void
    {
        $view = file_get_contents(resource_path('views/setup/index.blade.php')) ?: '';
        $css = file_get_contents(public_path('assets/aptoria-ui/assets/css/aptoria.css')) ?: '';

        $this->assertStringContainsString('aptoria-setup-brandbar', $view);
        $this->assertStringContainsString('aptoria-setup-brand-logo', $view);
        $this->assertStringContainsString('logo-color.svg', $view);
        $this->assertStringContainsString('aptoria-setup-grid', $view);
        $this->assertStringContainsString('data-step-target="config"', $view);
        $this->assertStringContainsString('data-step-target="admin"', $view);
        $this->assertStringContainsString('grid-template-columns: 318px minmax(0, 1fr)', $css);
        $this->assertStringContainsString('.aptoria-setup-brandbar', $css);
        $this->assertStringContainsString('min-width: 1400px', $css);
    }

    public function test_setup_environment_rows_keep_animation_and_progress_ui(): void
    {
        $view = file_get_contents(resource_path('views/setup/index.blade.php')) ?: '';
        $css = file_get_contents(public_path('assets/aptoria-ui/assets/css/aptoria.css')) ?: '';

        $this->assertStringContainsString('aptoria-env-check-progress', $view);
        $this->assertStringContainsString('aptoria-mini-spinner', $view);
        $this->assertStringContainsString('runChecksAnimation', $view);
        $this->assertStringContainsString('@keyframes aptoria-spinner-rotate', $css);
        $this->assertStringContainsString('@keyframes aptoria-dot-pulse', $css);
    }

    public function test_setup_sweetalert_is_styled_and_not_native_only(): void
    {
        $view = file_get_contents(resource_path('views/setup/index.blade.php')) ?: '';
        $css = file_get_contents(public_path('assets/aptoria-ui/assets/css/aptoria.css')) ?: '';

        $this->assertStringContainsString('window.Swal.fire', $view);
        $this->assertStringContainsString('buttonsStyling: false', $view);
        $this->assertStringContainsString('aptoria-setup-swal-popup', $view);
        $this->assertStringContainsString('aptoria-swal-confirm', $view);
        $this->assertStringContainsString('.aptoria-swal-popup', $css);
        $this->assertStringContainsString('.aptoria-setup-page .swal2-container', $css);
    }

    public function test_setup_uses_tabler_icons_and_no_installation_status_card(): void
    {
        $view = file_get_contents(resource_path('views/setup/index.blade.php')) ?: '';
        $css = file_get_contents(public_path('assets/aptoria-ui/assets/css/aptoria.css')) ?: '';

        $this->assertStringContainsString('class="ti ti-server-cog"', $view);
        $this->assertStringContainsString('class="ti ti-database-cog"', $view);
        $this->assertStringContainsString('class="ti ti-user-cog"', $view);
        $this->assertStringContainsString('class="ti ti-rocket"', $view);
        $this->assertStringContainsString('class="ti ti-{{ $checkIcon }}"', $view);
        $this->assertStringNotContainsString('data-lucide=', $view);
        $this->assertStringNotContainsString('aptoria-setup-status-box', $view);
        $this->assertStringContainsString('aptoria-install-action-buttons {', $css);
        $this->assertStringContainsString('grid-column: 1 / -1', $css);
    }

    public function test_setup_translation_has_lock_blocker_text(): void
    {
        $en = include resource_path('lang/en/messages.php');
        $hu = include resource_path('lang/hu/messages.php');

        $this->assertIsString($en['setup']['admin_required_before_lock'] ?? null);
        $this->assertIsString($hu['setup']['admin_required_before_lock'] ?? null);
        $this->assertStringNotContainsString('messages.setup', $en['setup']['admin_required_before_lock']);
        $this->assertStringNotContainsString('messages.setup', $hu['setup']['admin_required_before_lock']);
    }
}
