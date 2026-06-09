<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\Reports\ReportPresentationService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HtmlPdfReportExportTest extends TestCase
{
    public function test_html_report_renderer_produces_branded_printable_document(): void
    {
        $project = new Project([
            'name' => 'Demo API',
            'base_url' => 'https://api.demo.example',
        ]);
        $markdown = "# Sample QA Report\n\n**Project:** Demo API\n\n| Metric | Value |\n|---|---:|\n| Endpoints | 3 |\n\n---\n\nGenerated with **Aptoria v".config('aptoria.version')."**\nThis markdown footer must not be duplicated inside HTML.\n";

        $html = app(ReportPresentationService::class)->htmlFromMarkdown($markdown, 'Aptoria Sample QA Report', $project);

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<title>Sample QA Report</title>', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('report-meta-bar', $html);
        $this->assertStringContainsString('Project', $html);
        $this->assertStringContainsString('Base URL', $html);
        $this->assertStringContainsString('https://api.demo.example', $html);
        $this->assertStringContainsString('Prepared by', $html);
        $this->assertStringContainsString('Organization / client', $html);
        $this->assertStringContainsString('Professional QA / audit report', $html);
        $this->assertStringNotContainsString('<main class="report-content">' . "\n" . '<h1>Sample QA Report</h1>', $html);
        $this->assertStringContainsString('report-footer', $html);
        $this->assertStringContainsString('@media print', $html);
        $this->assertStringNotContainsString('<strong>Aptoria</strong><span>', $html);
        $this->assertStringNotContainsString('This markdown footer must not be duplicated inside HTML.', $html);
    }

    public function test_html_and_pdf_reports_include_profile_organization_in_report_metadata(): void
    {
        $this->actingAs(new User([
            'name' => 'Default Admin',
            'email' => 'admin@example.test',
            'report_display_name' => 'QA Lead',
            'report_role_title' => 'Senior QA Engineer',
            'report_organization' => 'ACME Audit Client',
        ]));

        $project = new Project([
            'name' => 'Client API',
            'base_url' => 'https://client.example.test',
        ]);
        $markdown = "# Client QA Report\n\n## Executive Summary\n\nReady for stakeholder review.\n";

        $service = app(ReportPresentationService::class);
        $html = $service->htmlFromMarkdown($markdown, 'Aptoria Client QA Report', $project);
        $pdf = $service->pdfFromMarkdown($markdown, 'Aptoria Client QA Report', $project);

        $this->assertStringContainsString('Organization / client', $html);
        $this->assertStringContainsString('ACME Audit Client', $html);
        $this->assertStringContainsString('QA Lead', $html);
        $this->assertStringContainsString('Senior QA Engineer', $html);
        $this->assertStringContainsString('Organization / client: ACME Audit Client', $pdf);
        $this->assertStringContainsString('Prepared by: QA Lead', $pdf);
    }

    public function test_pdf_report_renderer_produces_pdf_bytes_with_attribution(): void
    {
        $project = new Project([
            'name' => 'Demo API',
            'base_url' => 'https://api.demo.example',
        ]);
        $markdown = "# Sample QA Report\n\n**Project:** Demo API\n\n- First check\n- Second check\n";

        $pdf = app(ReportPresentationService::class)->pdfFromMarkdown($markdown, 'Aptoria Sample QA Report', $project);

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('Aptoria', $pdf);
        $this->assertStringContainsString('Sample QA Report', $pdf);
        $this->assertStringContainsString('Project: Demo API', $pdf);
        $this->assertStringContainsString('Base URL: https://api.demo.example', $pdf);
        $this->assertStringContainsString('Prepared by:', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
    }

    public function test_core_report_formats_have_markdown_html_and_pdf_routes(): void
    {
        foreach ([
            'projects.reports.full-project.markdown',
            'projects.reports.full-project.html',
            'projects.reports.full-project.pdf',
            'projects.reports.release-readiness.markdown',
            'projects.reports.release-readiness.html',
            'projects.reports.release-readiness.pdf',
            'projects.release-gates.markdown',
            'projects.release-gates.html',
            'projects.release-gates.pdf',
            'projects.reports.scans.markdown',
            'projects.reports.scans.html',
            'projects.reports.scans.pdf',
            'projects.reports.compares.markdown',
            'projects.reports.compares.html',
            'projects.reports.compares.pdf',
            'projects.reports.builder.markdown',
            'projects.reports.builder.html',
            'projects.reports.builder.pdf',
        ] as $route) {
            $this->assertTrue(Route::has($route), $route.' route is missing.');
        }
    }

    public function test_report_center_views_link_to_html_and_pdf_exports(): void
    {
        $files = [
            resource_path('views/reports/project.blade.php'),
            resource_path('views/reports/builder/create.blade.php'),
            resource_path('views/projects/show.blade.php'),
            resource_path('views/scans/show.blade.php'),
            resource_path('views/snapshots/compare-show.blade.php'),
            resource_path('views/release_gates/show.blade.php'),
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('.html', $content, $file.' does not link HTML exports.');
            $this->assertStringContainsString('.pdf', $content, $file.' does not link PDF exports.');
        }
    }

    public function test_readme_links_to_changelog_instead_of_embedding_release_history(): void
    {
        $readme = file_get_contents(base_path('README.md'));

        $this->assertStringContainsString('CHANGELOG.md', $readme);
        $this->assertStringNotContainsString('## v1.0.87', $readme);
        $this->assertStringNotContainsString('## v1.0.84', $readme);
        $this->assertStringNotContainsString('HTML & PDF Report Export Pass.', $readme);
    }
}
