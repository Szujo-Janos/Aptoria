<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_public_landing_page_is_available(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Aptoria');
        $response->assertSee('Self-hosted API QA workflow', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('assets/aptoria/css/landing.css', false);
        $response->assertSee('aptoria-release-gate-preview', false);
        $response->assertSee('aptoria-landing-signal-list', false);
        $response->assertDontSee('assets/aptoria/css/app.css', false);
    }

    public function test_dashboard_route_remains_separate_from_public_landing_page(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
