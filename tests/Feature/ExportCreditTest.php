<?php

namespace Tests\Feature;

use App\Services\Exports\ExportCreditService;
use Tests\TestCase;

class ExportCreditTest extends TestCase
{
    public function test_export_credit_metadata_contains_public_attribution(): void
    {
        $credits = app(ExportCreditService::class);
        $metadata = $credits->metadata('test_export');

        $this->assertSame('Aptoria', $metadata['application']);
        $this->assertSame(trim((string) file_get_contents(base_path('VERSION'))), $metadata['version']);
        $this->assertSame('https://github.com/Szujo-Janos/Aptoria', $metadata['repository']);
        $this->assertSame('János Szujó', $metadata['author']);
        $this->assertSame('János Szujó', $metadata['prepared_by']);
        $this->assertStringContainsString('Source-available', $metadata['license']);
        $this->assertSame('test_export', $metadata['export_type']);
    }

    public function test_markdown_footer_contains_professional_credit_block(): void
    {
        $footer = app(ExportCreditService::class)->markdownFooter('markdown_report');

        $this->assertStringContainsString('Generated with **Aptoria v', $footer);
        $this->assertStringContainsString('Repository: `https://github.com/Szujo-Janos/Aptoria`', $footer);
        $this->assertStringContainsString('Prepared by: **János Szujó**', $footer);
        $this->assertStringContainsString('Application author: **János Szujó**', $footer);
        $this->assertStringContainsString('Commercial redistribution, hosted resale or derivative product use requires written permission', $footer);
    }

    public function test_report_and_export_sources_use_centralized_credits(): void
    {
        $files = [
            app_path('Services/Reports/ReportExportService.php'),
            app_path('Services/Reports/FullQaReportBuilderService.php'),
            app_path('Services/Reports/QaEvidencePackService.php'),
            app_path('Services/ReleaseReadinessService.php'),
            app_path('Services/ReleaseGates/QaReleaseGateService.php'),
            app_path('Http/Controllers/SettingsController.php'),
            app_path('Http/Controllers/ProjectSettingsController.php'),
            app_path('Http/Controllers/CalendarController.php'),
        ];

        foreach ($files as $file) {
            $this->assertStringContainsString('ExportCreditService', file_get_contents($file), $file.' does not reference ExportCreditService.');
        }

        $this->assertStringContainsString('APTORIA_CREDITS.txt', file_get_contents(app_path('Services/Reports/QaEvidencePackService.php')));
        $this->assertStringContainsString('generated_by', file_get_contents(app_path('Http/Controllers/SettingsController.php')));
        $this->assertStringContainsString('generated_by', file_get_contents(app_path('Http/Controllers/ProjectSettingsController.php')));
        $this->assertStringContainsString('X-WR-CALDESC', file_get_contents(app_path('Http/Controllers/CalendarController.php')));
    }
}
