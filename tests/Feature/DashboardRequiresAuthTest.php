<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardRequiresAuthTest extends TestCase
{
    public function test_dashboard_redirects_to_login_for_guests(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
