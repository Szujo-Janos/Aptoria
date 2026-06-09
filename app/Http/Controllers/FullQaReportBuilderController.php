<?php

namespace App\Http\Controllers;

use App\Http\Requests\FullQaReportBuilderRequest;
use App\Models\Project;
use App\Services\QaCoverageMatrixService;
use App\Services\ReleaseReadinessService;
use App\Services\Reports\FullQaReportBuilderService;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\ReportPresentationService;
use App\Services\Settings\SettingService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FullQaReportBuilderController extends Controller
{
    public function create(Project $project, ReleaseReadinessService $releaseReadiness, QaCoverageMatrixService $coverageMatrix, SettingService $settings): View
    {
        $project->loadCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'testSuites', 'testCases', 'contractValidationRuns', 'findings']);
        $summary = $releaseReadiness->summarize($project);
        $coverage = $coverageMatrix->summarize($project)['summary'];
        $latestScan = $project->scanRuns()->latest()->first();
        $latestContract = $project->contractValidationRuns()->latest()->first();

        return view('reports.builder.create', [
            'project' => $project,
            'summary' => $summary,
            'coverage' => $coverage,
            'latestScan' => $latestScan,
            'latestContract' => $latestContract,
            'sections' => FullQaReportBuilderService::SECTIONS,
            'defaultSections' => $this->defaultSectionsFromSettings($settings),
            'audiences' => FullQaReportBuilderService::audienceOptions(),
            'decisions' => FullQaReportBuilderService::decisionOptions(),
        ]);
    }

    public function markdown(Project $project, FullQaReportBuilderRequest $request, FullQaReportBuilderService $builder, ReportExportService $exports): Response
    {
        $options = $request->reportOptions();

        return response($builder->markdown($project, $options), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$exports->filename($project, 'custom-full-qa-report', 'md').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function html(Project $project, FullQaReportBuilderRequest $request, FullQaReportBuilderService $builder, ReportExportService $exports, ReportPresentationService $presentation): Response
    {
        $options = $request->reportOptions();
        $markdown = $builder->markdown($project, $options);

        return response($presentation->htmlFromMarkdown($markdown, 'Aptoria Custom QA Report', $project), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$exports->filename($project, 'custom-full-qa-report', 'html').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function pdf(Project $project, FullQaReportBuilderRequest $request, FullQaReportBuilderService $builder, ReportExportService $exports, ReportPresentationService $presentation): Response
    {
        $options = $request->reportOptions();
        $markdown = $builder->markdown($project, $options);

        return response($presentation->pdfFromMarkdown($markdown, 'Aptoria Custom QA Report', $project), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$exports->filename($project, 'custom-full-qa-report', 'pdf').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return array<int, string> */
    private function defaultSectionsFromSettings(SettingService $settings): array
    {
        $sections = FullQaReportBuilderService::defaultSections();

        $defaultType = $settings->string('report.default_type', 'technical');
        if ($defaultType === 'executive') {
            $sections = [
                FullQaReportBuilderService::SECTION_EXECUTIVE_SUMMARY,
                FullQaReportBuilderService::SECTION_RELEASE_READINESS,
                FullQaReportBuilderService::SECTION_RECOMMENDATIONS,
            ];
        } elseif ($defaultType === 'release_readiness') {
            $sections = [
                FullQaReportBuilderService::SECTION_EXECUTIVE_SUMMARY,
                FullQaReportBuilderService::SECTION_RELEASE_READINESS,
                FullQaReportBuilderService::SECTION_RELEASE_GATE,
                FullQaReportBuilderService::SECTION_QA_COVERAGE,
                FullQaReportBuilderService::SECTION_TEST_EXECUTION,
                FullQaReportBuilderService::SECTION_FINDINGS_EVIDENCE,
                FullQaReportBuilderService::SECTION_RECOMMENDATIONS,
            ];
        } elseif ($defaultType === 'evidence') {
            $sections = [
                FullQaReportBuilderService::SECTION_EXECUTIVE_SUMMARY,
                FullQaReportBuilderService::SECTION_QA_COVERAGE,
                FullQaReportBuilderService::SECTION_TEST_EXECUTION,
                FullQaReportBuilderService::SECTION_FINDINGS_EVIDENCE,
                FullQaReportBuilderService::SECTION_SCANS_SNAPSHOTS,
                FullQaReportBuilderService::SECTION_APPENDIX,
            ];
        }

        if (! $settings->boolean('report.include_executive_summary', true)) {
            $sections = array_values(array_diff($sections, [FullQaReportBuilderService::SECTION_EXECUTIVE_SUMMARY]));
        }
        if (! $settings->boolean('report.include_release_readiness', true)) {
            $sections = array_values(array_diff($sections, [FullQaReportBuilderService::SECTION_RELEASE_READINESS, FullQaReportBuilderService::SECTION_RELEASE_GATE]));
        }
        if (! $settings->boolean('report.include_qa_evidence', true)) {
            $sections = array_values(array_diff($sections, [FullQaReportBuilderService::SECTION_FINDINGS_EVIDENCE]));
        }
        if (! $settings->boolean('exports.include_endpoint_details', true)) {
            $sections = array_values(array_diff($sections, [FullQaReportBuilderService::SECTION_ENDPOINT_INVENTORY]));
        }

        return $sections === [] ? [FullQaReportBuilderService::SECTION_EXECUTIVE_SUMMARY] : $sections;
    }
}
