<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ReportVersion;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\Reports\ReportVersioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportVersioningApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_versions_can_be_created_approved_exported_and_audited(): void
    {
        [$admin, $project] = $this->fixture();

        $this->actingAs($admin)
            ->get(route('projects.report-versions.index', $project))
            ->assertOk()
            ->assertSee(__('messages.report_versions.title'))
            ->assertSee(__('messages.report_versions.create_draft'));

        $response = $this->actingAs($admin)
            ->post(route('projects.report-versions.store', $project), [
                'title' => 'RC customer handoff evidence report',
                'report_type' => ReportVersion::TYPE_TECHNICAL,
            ]);

        $version = ReportVersion::query()->firstOrFail();
        $response->assertRedirect(route('projects.report-versions.show', [$project, $version]));

        $this->assertSame($project->id, $version->project_id);
        $this->assertSame($admin->id, $version->generated_by_user_id);
        $this->assertSame(ReportVersion::STATUS_DRAFT, $version->status);
        $this->assertNotEmpty($version->content_checksum);
        $this->assertNotEmpty($version->markdown_content);
        $this->assertContains(1, $version->source_scan_ids);
        $this->assertCount(1, $version->source_finding_state);

        $this->actingAs($admin)
            ->patch(route('projects.report-versions.approve', [$project, $version]))
            ->assertRedirect(route('projects.report-versions.show', [$project, $version]));

        $version->refresh();
        $this->assertSame(ReportVersion::STATUS_APPROVED, $version->status);
        $this->assertSame($admin->id, $version->approved_by_user_id);
        $this->assertNotNull($version->approved_at);

        $this->actingAs($admin)
            ->get(route('projects.report-versions.show', [$project, $version]))
            ->assertOk()
            ->assertSee('RC customer handoff evidence report')
            ->assertSee(__('messages.report_versions.approval'))
            ->assertSee($version->short_checksum);

        $this->actingAs($admin)
            ->get(route('projects.report-versions.markdown', [$project, $version]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('Aptoria Technical QA Evidence Report', false);

        $this->actingAs($admin)
            ->get(route('projects.report-versions.json', [$project, $version]))
            ->assertOk()
            ->assertJsonPath('status', ReportVersion::STATUS_APPROVED)
            ->assertJsonPath('sources.scan_ids.0', 1)
            ->assertJsonPath('content_checksum', $version->content_checksum);

        $this->assertTrue(AuditLog::query()
            ->where('event_type', AuditLog::EVENT_REPORT)
            ->where('subject_label', 'report_version')
            ->where('subject_name', 'RC customer handoff evidence report')
            ->exists());
    }

    public function test_report_versioning_service_builds_release_readiness_versions(): void
    {
        [$admin, $project] = $this->fixture('report-version-service@example.com');

        $version = app(ReportVersioningService::class)->create($project->fresh(), [
            'report_type' => ReportVersion::TYPE_RELEASE_READINESS,
        ], $admin);

        $this->assertSame(ReportVersion::TYPE_RELEASE_READINESS, $version->report_type);
        $this->assertStringContainsString('Aptoria Release Readiness Report', (string) $version->markdown_content);
        $this->assertNotEmpty($version->source_scan_ids);
    }

    /** @return array{0: User, 1: Project} */
    private function fixture(string $email = 'report-versioning@example.com'): array
    {
        $admin = User::query()->create([
            'name' => 'Report Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Versioned Reports API',
            'slug' => str('versioned-reports-api-'.$admin->id)->slug()->toString(),
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/orders/42',
            'name' => 'Order detail',
            'auth_required' => true,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $scanRun = ScanRun::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'status' => ScanRun::STATUS_COMPLETED,
            'total_endpoints' => 1,
            'scanned_count' => 1,
            'skipped_count' => 0,
            'success_count' => 1,
        ]);

        $scanRun->results()->create([
            'endpoint_id' => $endpoint->id,
            'method' => Endpoint::METHOD_GET,
            'url' => 'https://api.example.test/orders/42',
            'status' => 'completed',
            'status_code' => 200,
            'response_time_ms' => 84,
            'content_type' => 'application/json',
            'response_size' => 128,
            'body_preview' => '{"id":42,"status":"paid"}',
            'risk_level' => Endpoint::RISK_HIGH,
        ]);

        Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Order response requires release review',
            'source' => Finding::SOURCE_SCAN,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_OPEN,
        ]);

        return [$admin, $project];
    }
}
