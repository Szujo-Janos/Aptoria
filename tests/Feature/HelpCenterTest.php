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
            ->assertSee('Import or document endpoints')
            ->assertSee('Export the right report')
            ->assertSee('Create regression suites and assertions')
            ->assertSee('Audit and maintain the system');
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
            ->assertSee('System health, audit log and demo data')
            ->assertSee('Findings, lifecycle and evidence')
            ->assertSee('Scheduled monitoring, alerts and notifications')
            ->assertSee('Safety and privacy model');
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
            ->assertSee('Reports, branding and exports');
    }

    public function test_help_center_can_find_assertion_documentation(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('help.index', ['q' => 'assertion']))
            ->assertOk()
            ->assertSee('Regression test suites and execution')
            ->assertSee('Assertions');
    }

    public function test_hungarian_help_and_workflow_are_translated(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['locale' => 'hu'])
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Súgóközpont')
            ->assertSee('Endpoint inventory')
            ->assertSee('Findingok, lifecycle és evidence')
            ->assertSee('Ütemezett monitorozás, alert és értesítés');

        $this->actingAs($admin)
            ->withSession(['locale' => 'hu'])
            ->get(route('how-it-works'))
            ->assertOk()
            ->assertSee('Hogyan működik?')
            ->assertSee('Teljes Aptoria workflow')
            ->assertSee('Megfelelő riport exportálása');
    }
}
