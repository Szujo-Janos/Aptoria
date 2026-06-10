<?php

namespace App\Services\Reports;

use App\Models\Project;
use App\Services\Exports\ExportCreditService;

class ReportPresentationService
{
    public function __construct(private readonly ExportCreditService $credits)
    {
    }

    public function htmlFromMarkdown(string $markdown, string $title, ?Project $project = null): string
    {
        $generatedAt = now()->format('Y-m-d H:i:s');
        $reportTitle = $this->cleanReportTitle($title);
        $markdown = $this->stripGeneratedMarkdownFooter($markdown);
        $markdown = $this->stripLeadingTitleHeading($markdown, $reportTitle);
        $markdown = $this->stripLeadingReportMetadataBlock($markdown);
        $body = $this->markdownToHtml($markdown);
        $identity = $this->credits->reportIdentity($project);
        $logo = $this->embeddedLogo($identity);
        $footerLines = $this->credits->footerLines($project);
        $safeTitle = e($reportTitle);
        $projectName = $project?->name ?: 'Report export';
        $baseUrl = $project?->display_base_url ?: 'Not configured';

        $organization = $identity['organization'] !== '' ? $identity['organization'] : 'Not configured';
        $clientName = $identity['client_name'] !== '' ? $identity['client_name'] : $organization;

        $metaItems = [
            ['Project', $projectName],
            ['Client', $clientName],
            ['Organization / client', $organization],
            ['Base URL', $baseUrl],
            ['Generated', $generatedAt],
            ['Aptoria version', (string) config('aptoria.version')],
        ];

        $identityRows = [
            ['Prepared by', $identity['prepared_by']],
        ];
        if ($identity['role_title'] !== '') {
            $identityRows[] = ['Role / title', $identity['role_title']];
        }
        if ($identity['confidentiality_label'] !== '') {
            $identityRows[] = ['Confidentiality', $identity['confidentiality_label']];
        }

        return '<!doctype html>' . "\n"
            . '<html lang="en">' . "\n"
            . '<head>' . "\n"
            . '<meta charset="utf-8">' . "\n"
            . '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n"
            . '<title>'.$safeTitle.'</title>' . "\n"
            . '<style>' . $this->htmlCss() . '</style>' . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . '<div class="report-shell">' . "\n"
            . '<header class="report-cover">' . "\n"
            . '<div class="report-branding"><div class="brand-logo">'.$logo.'</div><div class="report-heading"><span class="report-eyebrow">Professional QA / audit report</span><h1>'.$safeTitle.'</h1><p>'.e($projectName).'</p></div></div>' . "\n"
            . '<div class="report-identity">'.$this->identityRowsHtml($identityRows).'</div>' . "\n"
            . '</header>' . "\n"
            . '<section class="report-meta-bar">'.$this->metaItemsHtml($metaItems).'</section>' . "\n"
            . '<main class="report-content">' . "\n"
            . $this->disclaimerHtml((string) ($identity['disclaimer'] ?? ''))
            . $body . "\n"
            . '</main>' . "\n"
            . '<footer class="report-footer">'.$this->footerHtml($footerLines).'</footer>' . "\n"
            . '</div>' . "\n"
            . '</body>' . "\n"
            . '</html>' . "\n";
    }

    public function pdfFromMarkdown(string $markdown, string $title, ?Project $project = null): string
    {
        $reportTitle = $this->cleanReportTitle($title);
        $markdown = $this->stripGeneratedMarkdownFooter($markdown);
        $markdown = $this->stripLeadingTitleHeading($markdown, $reportTitle);
        $renderer = new SimplePdfReportRenderer(
            (string) config('aptoria.product_name', 'Aptoria'),
            (string) config('aptoria.version'),
            $this->credits->footerLines($project),
            $this->credits->reportIdentity($project),
            now()->format('Y-m-d H:i:s')
        );

        return $renderer->render($markdown, $reportTitle, $project?->name, $project?->display_base_url);
    }

    private function disclaimerHtml(string $disclaimer): string
    {
        $disclaimer = trim($disclaimer);
        if ($disclaimer === '') {
            return '';
        }

        return '<section class="report-disclaimer"><strong>Report disclaimer</strong><p>'.e($disclaimer).'</p></section>' . "\n";
    }

    /** @param array<int, array{0: string, 1: string}> $rows */
    private function identityRowsHtml(array $rows): string
    {
        $html = [];
        foreach ($rows as [$label, $value]) {
            if ($value === '') {
                continue;
            }
            $html[] = '<div class="identity-row"><span>'.e($label).'</span><strong>'.e($value).'</strong></div>';
        }

        return implode('', $html);
    }

    /** @param array<int, array{0: string, 1: string}> $items */
    private function metaItemsHtml(array $items): string
    {
        return implode('', array_map(
            fn (array $item): string => '<div class="meta-chip"><span>'.e($item[0]).'</span><strong>'.e($item[1]).'</strong></div>',
            $items
        ));
    }

    /** @param array<int, string> $lines */
    private function footerHtml(array $lines): string
    {
        if ($lines === []) {
            return '';
        }

        $html = [];
        foreach ($lines as $index => $line) {
            $class = $index === 0 ? 'footer-main' : 'footer-muted';
            $html[] = '<div class="'.$class.'">'.e($line).'</div>';
        }

        return implode('', $html);
    }

    private function cleanReportTitle(string $title): string
    {
        $title = trim($title);
        $title = preg_replace('/^Aptoria\s+/i', '', $title) ?? $title;

        return $title !== '' ? $title : 'QA Report';
    }

    private function stripGeneratedMarkdownFooter(string $markdown): string
    {
        $pattern = '/\n---\s*\n\s*\nGenerated\s+(?:by|with)\s+\*\*Aptoria\s+v[\s\S]*$/i';
        $clean = preg_replace($pattern, '', $markdown);

        return rtrim((string) ($clean ?? $markdown))."\n";
    }

    private function stripLeadingTitleHeading(string $markdown, string $title): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        if ($lines === []) {
            return $markdown;
        }

        $first = trim((string) ($lines[0] ?? ''));
        if (! preg_match('/^#\s+(.+)$/', $first, $match)) {
            return $markdown;
        }

        $heading = trim($match[1]);
        if (strcasecmp($this->cleanReportTitle($heading), $this->cleanReportTitle($title)) !== 0) {
            return $markdown;
        }

        array_shift($lines);
        while ($lines !== [] && trim((string) $lines[0]) === '') {
            array_shift($lines);
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    private function stripLeadingReportMetadataBlock(string $markdown): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        if ($lines === []) {
            return $markdown;
        }

        while ($lines !== [] && trim((string) $lines[0]) === '') {
            array_shift($lines);
        }

        $labels = [
            'project',
            'client',
            'organization',
            'organization / client',
            'confidentiality',
            'disclaimer',
            'report logo',
            'base url',
            'generated',
            'report type',
            'aptoria version',
            'prepared by',
            'role / title',
        ];

        $removed = false;
        while ($lines !== []) {
            $line = trim((string) $lines[0]);
            if ($line === '') {
                if ($removed) {
                    array_shift($lines);
                    continue;
                }
                break;
            }

            if (! preg_match('/^\*\*([^:*]+):\*\*/', $line, $match)) {
                break;
            }

            $label = strtolower(trim($match[1]));
            if (! in_array($label, $labels, true)) {
                break;
            }

            array_shift($lines);
            $removed = true;
        }

        while ($removed && $lines !== [] && trim((string) $lines[0]) === '') {
            array_shift($lines);
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    private function reportContextHtml(?Project $project): string
    {
        $endpointCount = $this->projectCount($project, 'endpoints');
        $environmentCount = $this->projectCount($project, 'environments');
        $scanCount = $this->projectCount($project, 'scanRuns');
        $snapshotCount = $this->projectCount($project, 'snapshots');
        $findingCount = $this->projectCount($project, 'findings');

        $cards = [
            ['Report scope', 'Project-level QA evidence package'],
            ['Inventory baseline', $endpointCount.' endpoints · '.$environmentCount.' environments'],
            ['Evidence captured', $scanCount.' scans · '.$snapshotCount.' snapshots · '.$findingCount.' findings'],
            ['Review focus', 'Readiness, regression, schema drift and security signals'],
        ];

        $html = '<section class="report-context-grid">';
        foreach ($cards as [$label, $value]) {
            $html .= '<div class="context-card"><span>'.e($label).'</span><strong>'.e($value).'</strong></div>';
        }
        $html .= '</section>';

        return $html;
    }

    private function projectCount(?Project $project, string $relation): string
    {
        if (! $project instanceof Project || ! $project->exists) {
            return 'n/a';
        }

        $countAttribute = $relation.'_count';
        if (array_key_exists($countAttribute, $project->getAttributes())) {
            return (string) (int) $project->getAttribute($countAttribute);
        }

        try {
            if (method_exists($project, $relation)) {
                return (string) $project->{$relation}()->count();
            }
        } catch (\Throwable) {
            return 'n/a';
        }

        return 'n/a';
    }

    private function markdownToHtml(string $markdown): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $html = [];
        $paragraph = [];
        $listOpen = false;
        $table = [];

        $flushParagraph = function () use (&$html, &$paragraph): void {
            if ($paragraph === []) {
                return;
            }
            $html[] = '<p>'.$this->inline(implode(' ', $paragraph)).'</p>';
            $paragraph = [];
        };
        $closeList = function () use (&$html, &$listOpen): void {
            if ($listOpen) {
                $html[] = '</ul>';
                $listOpen = false;
            }
        };
        $flushTable = function () use (&$html, &$table): void {
            if ($table === []) {
                return;
            }
            $maxCells = max(array_map('count', $table));
            $wrapClass = $maxCells >= 7 ? ' report-table-very-wide' : ($maxCells >= 5 ? ' report-table-wide' : '');
            $html[] = '<div class="report-table-wrap'.$wrapClass.'"><table>';
            foreach ($table as $index => $row) {
                $tag = $index === 0 ? 'th' : 'td';
                $html[] = '<tr>'.implode('', array_map(fn (string $cell): string => '<'.$tag.'>'.$this->inline(trim($cell)).'</'.$tag.'>', $row)).'</tr>';
            }
            $html[] = '</table></div>';
            $table = [];
        };

        foreach ($lines as $line) {
            $raw = rtrim((string) $line);
            $trimmed = trim($raw);

            if ($trimmed === '') {
                $flushParagraph();
                $flushTable();
                $closeList();
                continue;
            }

            if (preg_match('/^\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?$/', $trimmed)) {
                continue;
            }

            if (str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|')) {
                $flushParagraph();
                $closeList();
                $cells = array_map('trim', explode('|', trim($trimmed, '|')));
                $table[] = $cells;
                continue;
            }

            $flushTable();

            if (preg_match('/^(#{1,4})\s+(.*)$/', $trimmed, $match)) {
                $flushParagraph();
                $closeList();
                $level = min(4, strlen($match[1]));
                $html[] = '<h'.$level.'>'.$this->inline($match[2]).'</h'.$level.'>';
                continue;
            }

            if ($trimmed === '---') {
                $flushParagraph();
                $closeList();
                $html[] = '<hr>';
                continue;
            }

            if (preg_match('/^-\s+(.*)$/', $trimmed, $match)) {
                $flushParagraph();
                if (! $listOpen) {
                    $html[] = '<ul>';
                    $listOpen = true;
                }
                $html[] = '<li>'.$this->inline($match[1]).'</li>';
                continue;
            }

            $paragraph[] = $trimmed;
        }

        $flushParagraph();
        $flushTable();
        $closeList();

        return implode("\n", $html);
    }

    private function inline(string $text): string
    {
        $text = e($text);
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/_([^_]+)_/', '<em>$1</em>', $text) ?? $text;

        return $text;
    }

    /** @param array<string, string> $identity */
    private function embeddedLogo(array $identity = []): string
    {
        if (($identity['logo_data_uri'] ?? '') !== '') {
            return '<img src="'.e($identity['logo_data_uri']).'" alt="Report logo" class="logo">';
        }

        $path = public_path('assets/aptoria/img/aptoria-logo-horizontal.png');
        if (is_file($path)) {
            $data = base64_encode((string) file_get_contents($path));
            return '<img src="data:image/png;base64,'.$data.'" alt="Aptoria" class="logo">';
        }

        return '<div class="logo-fallback">A</div>';
    }

    private function htmlCss(): string
    {
        return <<<'CSS'
:root{--ink:#111827;--muted:#5f6b7a;--line:#d6dbe3;--soft:#f7f8fa;--panel:#f3f5f7;--accent:#2f3a4a;--white:#fff;}
*{box-sizing:border-box}
body{margin:0;background:#f1f3f5;color:var(--ink);font-family:Calibri,"Segoe UI",Helvetica,Arial,sans-serif;font-size:13.2px;line-height:1.56}
.report-shell{max-width:980px;margin:28px auto;background:var(--white);border:1px solid var(--line);box-shadow:0 8px 24px rgba(17,24,39,.07)}
.report-cover{display:flex;align-items:flex-start;justify-content:space-between;gap:28px;padding:28px 40px 22px;border-top:4px solid var(--accent);border-bottom:1px solid var(--line);background:#fff}
.report-branding{display:flex;align-items:flex-start;gap:22px;min-width:0}.brand-logo{flex:0 0 auto}.logo{width:138px;max-height:42px;object-fit:contain}.logo-fallback{width:42px;height:42px;border:1px solid var(--accent);color:var(--accent);display:grid;place-items:center;font-size:22px;font-weight:700}
.report-eyebrow{display:block;margin:0 0 7px;color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.13em}.report-heading h1{margin:0;color:#111827;font-size:24px;line-height:1.18;letter-spacing:-.015em;font-weight:700}.report-heading p{margin:7px 0 0;color:var(--muted);font-size:13px}
.report-identity{min-width:220px;text-align:right;border-left:1px solid var(--line);padding-left:22px}.identity-row{margin:0 0 7px}.identity-row span{display:block;color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.08em}.identity-row strong{display:block;color:#1f2937;font-size:13px;font-weight:600}
.report-meta-bar{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));border-bottom:1px solid var(--line);background:#fbfbfc}.meta-chip{min-width:0;padding:11px 13px;border-right:1px solid var(--line)}.meta-chip:last-child{border-right:0}.meta-chip span{display:block;color:var(--muted);font-size:9.2px;text-transform:uppercase;letter-spacing:.08em;white-space:nowrap;margin-bottom:3px}.meta-chip strong{display:block;color:#111827;font-size:11.8px;font-weight:600;word-break:break-word;overflow-wrap:anywhere}
.report-context-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;border-bottom:1px solid var(--line);background:#fff}.context-card{padding:13px 15px;border-right:1px solid var(--line);min-width:0}.context-card:last-child{border-right:0}.context-card span{display:block;color:var(--muted);font-size:9.3px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px}.context-card strong{display:block;color:#172033;font-size:12.4px;line-height:1.35;font-weight:600;overflow-wrap:anywhere}
.report-content{padding:30px 40px 36px}.report-content h1{font-size:24px;margin:0 0 18px;border-bottom:1px solid var(--line);padding-bottom:11px;color:#111827}.report-content h2{font-size:18px;margin:27px 0 11px;color:#111827;border-bottom:1px solid #eceff3;padding-bottom:6px}.report-content h3{font-size:15px;margin:21px 0 8px;color:#1f2937}.report-content h4{font-size:13.5px;margin:17px 0 7px;color:#1f2937}.report-content p{margin:0 0 11px}
.report-table-wrap{width:100%;max-width:100%;overflow-x:auto;margin:13px 0 22px}.report-table-wrap table,.report-content table{width:100%;max-width:100%;border-collapse:collapse;margin:0;font-size:11.2px;table-layout:fixed}.report-table-wrap.report-table-wide table{font-size:10.2px}.report-table-wrap.report-table-very-wide table{font-size:9.2px}.report-content th{background:#f3f5f7;color:#111827;text-align:left;font-weight:700}.report-content th,.report-content td{border:1px solid var(--line);padding:6px 7px;vertical-align:top;line-height:1.34;overflow-wrap:anywhere;word-break:break-word;hyphens:auto}.report-content tr:nth-child(even) td{background:#fafbfc}
.report-disclaimer{margin:0 0 18px;padding:12px 14px;border:1px solid var(--line);background:#fbfbfc}.report-disclaimer strong{display:block;margin-bottom:5px;color:#111827}.report-disclaimer p{margin:0;color:var(--muted)}
.report-content code{background:#f3f5f7;border:1px solid #dde2ea;border-radius:3px;padding:1px 4px;font-family:Consolas,Monaco,monospace;font-size:11px;overflow-wrap:anywhere;word-break:break-word}.report-content ul{padding-left:20px;margin-top:0}.report-content li{margin:0 0 5px}.report-content hr{border:0;border-top:1px solid var(--line);margin:26px 0}.report-footer{padding:14px 40px 16px;border-top:1px solid var(--line);background:var(--soft);font-size:10.5px;line-height:1.45}.footer-main{color:#273244;font-weight:600;margin-bottom:3px}.footer-muted{color:var(--muted)}
@media(max-width:900px){.report-meta-bar{grid-template-columns:repeat(2,minmax(0,1fr))}.meta-chip{border-bottom:1px solid var(--line)}.report-context-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.context-card{border-bottom:1px solid var(--line)}}
@media(max-width:760px){.report-cover{display:block;padding:24px}.report-branding{align-items:flex-start}.report-identity{text-align:left;margin-top:18px;border-left:0;border-top:1px solid var(--line);padding:14px 0 0}.report-meta-bar,.report-context-grid{display:block}.meta-chip,.context-card{border-right:0;border-bottom:1px solid var(--line)}.report-content{padding:24px}.logo{width:130px}}
@media print{@page{size:A4;margin:12mm}body{background:#fff;font-size:11.2px}.report-shell{margin:0;border:0;box-shadow:none;max-width:none}.report-cover{border-top:3px solid #000;padding:18px 0 14px}.report-cover,.report-footer,.report-meta-bar,.report-context-grid{background:#fff}.report-content{padding:18px 0 20px}.report-heading h1{font-size:20px}.report-context-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.context-card{padding:8px 9px}.report-table-wrap{overflow:visible;margin:9px 0 16px}.report-table-wrap table,.report-content table{font-size:8.6px;table-layout:fixed}.report-table-wrap.report-table-wide table{font-size:7.8px}.report-table-wrap.report-table-very-wide table{font-size:7.0px}.report-content th,.report-content td{padding:4px 5px;line-height:1.22}a{color:inherit}.meta-chip,.context-card{break-inside:avoid}.report-content table{break-inside:auto}.report-content tr{break-inside:avoid}}
CSS;
    }
}
