<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\EvidencePackService;
use App\Services\ReportVisualStandardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidencePackBuilderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_evidence_pack_service_generates_manifest_and_checksum(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $pack = app(EvidencePackService::class)->create($project, [
            'title' => 'Demo evidence pack',
            'pack_type' => 'release_evidence',
            'sections' => ['readiness', 'findings', 'evidence', 'manifest'],
        ], $user);

        $this->assertNotEmpty($pack->checksum);
        $this->assertSame('Demo evidence pack', $pack->title);
        $this->assertContains('manifest', $pack->included_sections_json);
        $this->assertStringContainsString('data-aptoria-report-standard="report-visual-standard-v1.1"', $pack->content_html);
        $this->assertStringContainsString('summary-strip', $pack->content_html);
        $this->assertStringContainsString('Aptoria QA Evidence Package', $pack->content_html);
    }

    public function test_evidence_pack_html_and_pdf_downloads_use_report_visual_standard(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $pack = app(EvidencePackService::class)->create($project, [
            'title' => 'Standardized delivery pack',
            'pack_type' => 'client_delivery',
            'sections' => ['readiness', 'findings', 'evidence', 'manifest'],
        ], $user);

        $html = app(ReportVisualStandardService::class)->exportEvidencePackHtml($pack);
        $pdf = app(ReportVisualStandardService::class)->exportEvidencePackPdf($pack);

        $this->assertStringContainsString('<main class="report" data-aptoria-report-standard="report-visual-standard-v1.1">', $html);
        $this->assertStringContainsString('meta-table', $html);
        $this->assertStringContainsString('Checksum', $html);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('Report standard', $pdf);
        $this->assertStringContainsString(' re B', $pdf);
        $this->assertStringContainsString('/Helvetica-Bold', $pdf);
        $this->assertStringContainsString('/DCTDecode', $pdf);
        $this->assertStringContainsString('/W 900 /H 284', $pdf);
        $this->assertStringNotContainsString('(APT) Tj', $pdf);
    }

    public function test_evidence_pack_zip_download_is_real_zip_without_ziparchive_fallback(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $pack = app(EvidencePackService::class)->create($project, [
            'title' => 'ZIP delivery pack',
            'pack_type' => 'release_evidence',
            'sections' => ['readiness', 'findings', 'evidence', 'manifest'],
        ], $user);

        $zip = app(EvidencePackService::class)->zipBinary($pack);

        $this->assertStringStartsWith('PK', $zip);
        $this->assertStringContainsString('report.html', $zip);
        $this->assertStringContainsString('report.pdf', $zip);
        $this->assertStringContainsString('checksum.sha256', $zip);
        $this->assertStringContainsString('%PDF-1.4', $zip);
        $this->assertStringNotContainsString('Content-Type: text/markdown', $zip);
    }
}
