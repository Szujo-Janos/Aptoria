<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_how_it_works_page_renders_complete_tutorial(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('how-it-works'))
            ->assertOk()
            ->assertSee('How it works')
            ->assertSee('Complete Aptoria workflow')
            ->assertSee('Create an API project')
            ->assertSee('Export reports');
    }

    public function test_help_center_renders_searchable_documentation(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Help Center')
            ->assertSee('data-aptoria-help-search="true"', false)
            ->assertSee('Endpoint inventory')
            ->assertSee('Settings Center')
            ->assertSee('Assertion Rules and Regression Monitoring');
    }

    public function test_help_center_can_filter_documentation_by_keyword(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('help.index', ['q' => 'snapshot']))
            ->assertOk()
            ->assertSee('Search results for')
            ->assertSee('Snapshots and compare')
            ->assertSee('Reports and exports');
    }

    public function test_help_center_can_find_assertion_documentation(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('help.index', ['q' => 'assertion']))
            ->assertOk()
            ->assertSee('Assertion Rules and Regression Monitoring')
            ->assertSee('PASS, WARNING and FAIL');
    }
}
