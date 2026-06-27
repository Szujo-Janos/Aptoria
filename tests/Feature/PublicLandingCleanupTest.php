<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLandingCleanupTest extends TestCase
{
    public function test_landing_role_renders_public_product_entry_points_only(): void
    {
        config([
            'aptoria.domain.role' => 'landing',
            'aptoria.domain.landing_url' => 'https://aptoria.dev',
            'aptoria.domain.demo_url' => 'https://demo.aptoria.dev',
            'aptoria.links.github_url' => 'https://github.com/Szujo-Janos',
            'aptoria.links.download_url' => 'https://github.com/Szujo-Janos/aptoria/releases',
            'aptoria.links.docs_url' => 'https://github.com/Szujo-Janos/aptoria/tree/main/docs',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://demo.aptoria.dev/demo-guide', false)
            ->assertSee('https://github.com/Szujo-Janos', false)
            ->assertSee('https://github.com/Szujo-Janos/aptoria/releases', false)
            ->assertSee('https://github.com/Szujo-Janos/aptoria/tree/main/docs', false)
            ->assertSee('Downloadable QA workspace')
            ->assertSee('Run Aptoria where your QA evidence belongs.')
            ->assertDontSee('Subdomain-ready deployment')
            ->assertDontSee('admin.aptoria.dev', false)
            ->assertDontSee('license.aptoria.dev', false)
            ->assertDontSee('href="/login"', false)
            ->assertDontSee('href="/setup"', false)
            ->assertDontSee('/license/activate', false)
            ->assertDontSee('href="/demo-api/health"', false);
    }

    public function test_local_role_keeps_small_runtime_shortcuts_for_development(): void
    {
        config(['aptoria.domain.role' => 'local']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Local runtime actions')
            ->assertSee('href="http://localhost/login"', false)
            ->assertSee('href="http://localhost/setup"', false);
    }

    public function test_landing_view_has_single_typewriter_timer_call(): void
    {
        $view = file_get_contents(resource_path('views/landing.blade.php'));

        $this->assertSame(1, substr_count($view, 'setTimeout(type, delay);'));
    }
}
