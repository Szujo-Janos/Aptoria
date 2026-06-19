<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ReportDeliveryService;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportVisualStandardTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_html_report_download_uses_shared_visual_standard(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create([
            'password_change_required' => false,
            'report_prepared_by' => 'János Szujó',
            'report_role_title' => 'QA Lead',
            'report_organization' => 'Aptoria QA',
        ]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $report = $project->reportVersions()->create([
            'generated_by_user_id' => $user->id,
            'type' => 'technical_summary',
            'status' => 'draft',
            'title' => 'Technical QA summary',
            'content_markdown' => '# Technical QA summary',
            'content_html' => '<article class="aptoria-report-html"><h1>Technical QA summary</h1><p>Evidence body.</p></article>',
            'data_json' => [
                'latest_readiness' => ['score' => 72, 'blocker_count' => 1, 'warning_count' => 2],
                'metrics' => ['evidence' => 3, 'open_findings' => 1],
            ],
            'checksum' => hash('sha256', 'technical-summary'),
            'generated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('projects.reports.download', [$project, $report, 'html']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('data-aptoria-report-standard="report-visual-standard-v1.1"', false)
            ->assertSee('class="report-header"', false)
            ->assertSee('data:image/svg+xml;base64', false)
            ->assertDontSee('messages.projects.project')
            ->assertSee('Executive Summary')
            ->assertSee('Evidence Summary')
            ->assertSee('Technical Appendix')
            ->assertSee('class="meta-table"', false)
            ->assertSee('class="summary-strip"', false)
            ->assertSee('class="report-footer"', false)
            ->assertSee('Technical QA summary');
    }

    public function test_public_client_portal_html_report_download_uses_same_visual_standard(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $report = $project->reportVersions()->create([
            'generated_by_user_id' => $user->id,
            'approved_by_user_id' => $user->id,
            'type' => 'release_decision',
            'status' => 'approved',
            'title' => 'Approved release decision report',
            'content_markdown' => '# Approved release decision report',
            'content_html' => '<article class="aptoria-report-html"><h1>Approved</h1></article>',
            'data_json' => ['readiness_metrics' => ['score' => 91, 'blocker_count' => 0, 'warning_count' => 0]],
            'checksum' => hash('sha256', 'approved-standard'),
            'generated_at' => now(),
            'approved_at' => now(),
            'approval_signoff_name' => 'János Szujó',
            'approval_signoff_statement' => 'Approved as the release evidence package.',
            'approval_signed_at' => now(),
        ]);

        $access = app(ReportDeliveryService::class)->createDeliveryLink($project, $report, $user, [
            'name' => 'Client handoff',
            'acknowledge_required' => true,
        ]);

        $this->get(route('client-portal.reports.download', [$access->token, $report, 'html']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('data-aptoria-report-standard="report-visual-standard-v1.1"', false)
            ->assertSee('class="summary-strip"', false)
            ->assertSee('data:image/svg+xml;base64', false)
            ->assertDontSee('messages.projects.project')
            ->assertSee('Release Decision')
            ->assertSee('Approval sign-off')
            ->assertSee('Approved release decision report');
    }
}
