<?php

namespace App\Services;

use App\Models\EvidencePack;
use App\Models\Project;
use App\Models\ReportVersion;
use App\Models\ReleaseReadinessRun;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EvidencePackService
{
    public function __construct(private readonly ReportVisualStandardService $visualStandardService)
    {
    }

    public function create(Project $project, array $data, ?User $user = null): EvidencePack
    {
        $sections = array_values(array_intersect((array) ($data['sections'] ?? []), EvidencePack::SECTIONS));
        $sections = $sections ?: ['readiness', 'findings', 'evidence', 'manifest'];
        $readiness = $this->resolveReadiness($project, $data['release_readiness_run_id'] ?? null);
        $report = $this->resolveReport($project, $data['report_version_id'] ?? null);
        $manifest = $this->manifest($project, $sections, $readiness, $report);
        $markdown = $this->markdown($project, $sections, $manifest, $readiness, $report);
        $checksum = hash('sha256', $markdown.'|'.json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $pack = $project->evidencePacks()->create([
            'created_by_user_id' => $user?->id,
            'release_readiness_run_id' => $readiness?->id,
            'report_version_id' => $report?->id,
            'title' => $data['title'] ?: $project->name.' evidence pack '.now()->format('Y-m-d H:i'),
            'pack_type' => $data['pack_type'] ?? 'release_evidence',
            'status' => 'generated',
            'included_sections_json' => $sections,
            'manifest_json' => $manifest,
            'content_markdown' => $markdown,
            'content_html' => null,
            'checksum' => $checksum,
            'generated_at' => now(),
        ]);

        $pack->content_html = $this->visualStandardService->exportEvidencePackHtml($pack);
        $pack->save();

        return $pack->fresh(['createdBy', 'releaseReadinessRun', 'reportVersion']) ?? $pack;
    }

    public function zipPath(EvidencePack $pack): string
    {
        $dir = storage_path('app/aptoria-evidence-packs');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir.'/evidence-pack-'.$pack->id.'.zip';
        file_put_contents($path, $this->zipBinary($pack));

        return $path;
    }

    public function zipBinary(EvidencePack $pack): string
    {
        $html = $this->visualStandardService->exportEvidencePackHtml($pack);
        $pdf = $this->visualStandardService->exportEvidencePackPdf($pack);
        $manifest = json_encode($pack->manifest_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        $readme = $pack->content_markdown ?: '# Aptoria Evidence Pack'.PHP_EOL;
        $checksum = implode(PHP_EOL, [
            hash('sha256', $readme).'  README.md',
            hash('sha256', $manifest).'  manifest.json',
            hash('sha256', $html).'  report.html',
            hash('sha256', $pdf).'  report.pdf',
            ($pack->checksum ?: hash('sha256', $readme.'|'.$manifest)).'  evidence-pack-record',
            '',
        ]);

        return $this->buildStoredZip([
            'README.md' => $readme,
            'manifest.json' => $manifest,
            'report.html' => $html,
            'report.pdf' => $pdf,
            'checksum.sha256' => $checksum,
        ]);
    }

    /**
     * Build a standards-compliant ZIP archive without requiring ext-zip.
     * Files are stored uncompressed so local XAMPP installs without ZipArchive
     * still download a real .zip instead of falling back to Markdown.
     */
    private function buildStoredZip(array $files): string
    {
        $data = '';
        $centralDirectory = '';
        $entries = 0;
        $dosTime = $this->zipDosTime();
        $dosDate = $this->zipDosDate();

        foreach ($files as $name => $contents) {
            $name = str_replace('\\', '/', ltrim((string) $name, '/'));
            $contents = (string) $contents;
            $crc = (int) sprintf('%u', crc32($contents));
            $size = strlen($contents);
            $offset = strlen($data);
            $nameLength = strlen($name);

            $data .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0);
            $data .= $name.$contents;

            $centralDirectory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset);
            $centralDirectory .= $name;
            $entries++;
        }

        $centralOffset = strlen($data);
        $centralSize = strlen($centralDirectory);

        return $data
            .$centralDirectory
            .pack('VvvvvVVv', 0x06054b50, 0, 0, $entries, $entries, $centralSize, $centralOffset, 0);
    }

    private function zipDosTime(): int
    {
        $hour = (int) date('H');
        $minute = (int) date('i');
        $second = (int) floor(((int) date('s')) / 2);

        return ($hour << 11) | ($minute << 5) | $second;
    }

    private function zipDosDate(): int
    {
        $year = max(1980, (int) date('Y')) - 1980;
        $month = (int) date('n');
        $day = (int) date('j');

        return ($year << 9) | ($month << 5) | $day;
    }

    private function resolveReadiness(Project $project, mixed $id): ?ReleaseReadinessRun
    {
        if ($id) {
            return $project->releaseReadinessRuns()->whereKey((int) $id)->first();
        }
        return Schema::hasTable('release_readiness_runs') ? $project->releaseReadinessRuns()->latest()->first() : null;
    }

    private function resolveReport(Project $project, mixed $id): ?ReportVersion
    {
        if ($id) {
            return $project->reportVersions()->whereKey((int) $id)->first();
        }
        return Schema::hasTable('report_versions') ? $project->reportVersions()->latest()->first() : null;
    }

    private function manifest(Project $project, array $sections, ?ReleaseReadinessRun $readiness, ?ReportVersion $report): array
    {
        return [
            'project' => ['id' => $project->id, 'name' => $project->name, 'base_url' => $project->base_url],
            'generated_at' => now()->toDateTimeString(),
            'sections' => $sections,
            'release_readiness_run_id' => $readiness?->id,
            'report_version_id' => $report?->id,
            'counts' => [
                'findings' => Schema::hasTable('findings') ? $project->findings()->count() : 0,
                'evidence' => Schema::hasTable('finding_evidence') ? $project->evidence()->count() : 0,
                'evidence_verified' => Schema::hasTable('finding_evidence') && Schema::hasColumn('finding_evidence', 'repository_status') ? $project->evidence()->where('repository_status', 'verified')->count() : 0,
                'evidence_archived' => Schema::hasTable('finding_evidence') && Schema::hasColumn('finding_evidence', 'repository_status') ? $project->evidence()->where('repository_status', 'archived')->count() : 0,
                'imports' => Schema::hasTable('external_import_runs') ? $project->externalImportRuns()->count() : 0,
                'contract_runs' => Schema::hasTable('contract_validation_runs') ? $project->contractValidationRuns()->count() : 0,
                'risk_acceptances' => Schema::hasTable('risk_acceptances') ? $project->riskAcceptances()->count() : 0,
            ],
        ];
    }

    private function markdown(Project $project, array $sections, array $manifest, ?ReleaseReadinessRun $readiness, ?ReportVersion $report): string
    {
        $lines = ['# '.$project->name.' — '.__('messages.evidence_packs.title'), '', '**'.__('messages.evidence_packs.generated_at').':** '.now()->toDateTimeString(), '**'.__('messages.evidence_packs.checksum_pending').':** generated on save', ''];
        if (in_array('readiness', $sections, true)) {
            $lines[] = '## '.__('messages.nav.release_readiness');
            $lines[] = $readiness ? '- status: '.$readiness->status.'; score: '.$readiness->score.'; blockers: '.$readiness->blocker_count.'; warnings: '.$readiness->warning_count : __('messages.evidence_packs.no_readiness');
            if ($readiness?->readiness_profile_key) { $lines[] = '- profile: '.$readiness->readiness_profile_key; }
            $lines[] = '';
        }
        if (in_array('report', $sections, true)) {
            $lines[] = '## '.__('messages.nav.reports');
            $lines[] = $report ? '- '.$report->title.' ['.$report->status.'] checksum '.$report->checksum : __('messages.evidence_packs.no_report');
            $lines[] = '';
        }
        if (in_array('findings', $sections, true)) {
            $lines[] = '## '.__('messages.nav.findings');
            foreach ($project->findings()->with('endpoint')->latest()->limit(50)->get() as $finding) {
                $lines[] = '- ['.$finding->severity.' / '.$finding->status.'] '.$finding->title.' — '.trim(($finding->endpoint?->method ?? '').' '.($finding->endpoint?->path ?? ''));
            }
            $lines[] = '';
        }
        if (in_array('evidence', $sections, true)) {
            $lines[] = '## '.__('messages.nav.evidence');
            foreach ($project->evidence()->with('finding')->latest()->limit(50)->get() as $evidence) {
                $lines[] = '- ['.$evidence->type.' / '.($evidence->repository_status ?? 'active').'] '.$evidence->title.' — '.($evidence->finding?->title ?? '—').' — checksum: '.($evidence->sha256 ?: '—');
            }
            $lines[] = '';
        }
        if (in_array('risk_acceptance', $sections, true)) {
            $lines[] = '## '.__('messages.risk_acceptance.accepted_risk');
            foreach ($project->riskAcceptances()->with('finding')->latest()->limit(30)->get() as $risk) {
                $lines[] = '- ['.$risk->status.'] '.($risk->finding?->title ?? 'Finding #'.$risk->finding_id).' until '.($risk->accepted_until?->toDateString() ?? '—');
            }
            $lines[] = '';
        }
        if (in_array('imports', $sections, true)) {
            $lines[] = '## '.__('messages.nav.import_center');
            foreach ($project->externalImportRuns()->latest()->limit(20)->get() as $run) {
                $lines[] = '- ['.$run->status.'] '.$run->source_type.' — '.$run->source_name.' items: '.$run->item_count;
            }
            $lines[] = '';
        }
        if (in_array('contract', $sections, true)) {
            $lines[] = '## '.__('messages.nav.contract_validation');
            foreach ($project->contractValidationRuns()->latest()->limit(10)->get() as $run) {
                $lines[] = '- ['.$run->status.'] '.$run->source_name.' blockers: '.$run->blocker_count.' warnings: '.$run->warning_count;
            }
            $lines[] = '';
        }
        if (in_array('manifest', $sections, true)) {
            $lines[] = '## Manifest';
            $lines[] = '```json';
            $lines[] = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $lines[] = '```';
        }
        return implode("\n", $lines)."\n";
    }


}
