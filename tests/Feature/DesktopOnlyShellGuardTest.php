<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesktopOnlyShellGuardTest extends TestCase
{
    public function test_sidebar_branding_keeps_text_but_no_logo_image(): void
    {
        $sidebar = file_get_contents(resource_path('views/partials/sidebar.blade.php')) ?: '';

        $this->assertStringContainsString('{{ $appName }}', $sidebar);
        $this->assertStringContainsString("__('messages.product.tagline')", $sidebar);
        $this->assertStringNotContainsString('aptoria-brand-logo-sidebar', $sidebar);
        $this->assertStringNotContainsString('logo-color.svg', $sidebar);
    }

    public function test_topbar_does_not_render_sidebar_collapse_button(): void
    {
        $topbar = file_get_contents(resource_path('views/partials/topbar.blade.php')) ?: '';

        $this->assertStringNotContainsString('button-collapse-toggle', $topbar);
        $this->assertStringNotContainsString('data-lucide="menu"', $topbar);
    }

    public function test_desktop_only_white_screen_guard_is_available_globally(): void
    {
        $head = file_get_contents(resource_path('views/partials/white-screen-head.blade.php')) ?: '';
        $loader = file_get_contents(resource_path('views/partials/white-screen-loader.blade.php')) ?: '';

        $this->assertStringContainsString('#aptoria-desktop-only-screen', $head);
        $this->assertStringContainsString('@media (max-width: 1399.98px)', $head);
        $this->assertStringContainsString('aptoria-desktop-only-screen', $loader);
        $this->assertStringContainsString("__('messages.desktop_only.title')", $loader);
        $this->assertStringContainsString("__('messages.desktop_only.message')", $loader);
        $this->assertStringContainsString("__('messages.desktop_only.minimum')", $loader);
    }
}
