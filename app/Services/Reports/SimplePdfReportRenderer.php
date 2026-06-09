<?php

namespace App\Services\Reports;

class SimplePdfReportRenderer
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;
    private const MARGIN_X = 54.0;
    private const TOP_Y = 790.0;
    private const BOTTOM_Y = 62.0;
    private const FOOTER_Y = 38.0;

    /** @var array<int, array<int, array{font: string, size: float, x: float, y: float, text: string}>> */
    private array $pages = [];

    public function __construct(
        private readonly string $productName,
        private readonly string $version,
        private readonly string $footer
    ) {
    }

    public function render(string $markdown, string $title, ?string $projectName = null): string
    {
        $this->pages = [[]];
        $y = self::TOP_Y;
        $this->addLine($y, $title, 'F2', 17);
        $y -= 22;
        $subtitle = $this->productName.' v'.$this->version;
        if ($projectName !== null && $projectName !== '') {
            $subtitle .= ' - '.$projectName;
        }
        $this->addLine($y, $subtitle, 'F1', 10);
        $y -= 20;

        foreach ($this->markdownToTextLines($markdown) as $line) {
            [$text, $font, $size, $spacingBefore, $spacingAfter] = $line;
            if ($spacingBefore > 0) {
                $y -= $spacingBefore;
            }
            foreach ($this->wrap($text, $size, $font === 'F2' ? 0.54 : 0.50) as $wrapped) {
                if ($y < self::BOTTOM_Y) {
                    $this->newPage();
                    $y = self::TOP_Y;
                }
                $this->addLine($y, $wrapped, $font, $size);
                $y -= max(11, $size + 4);
            }
            if ($spacingAfter > 0) {
                $y -= $spacingAfter;
            }
        }

        return $this->buildPdf();
    }

    /** @return array<int, array{0: string, 1: string, 2: float, 3: float, 4: float}> */
    private function markdownToTextLines(string $markdown): array
    {
        $result = [];
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        foreach ($lines as $raw) {
            $line = trim((string) $raw);
            if ($line === '') {
                $result[] = ['', 'F1', 10.0, 0.0, 2.0];
                continue;
            }
            if (preg_match('/^\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?$/', $line)) {
                continue;
            }
            if ($line === '---') {
                $result[] = [str_repeat('-', 78), 'F1', 8.0, 4.0, 4.0];
                continue;
            }
            if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $match)) {
                $level = strlen($match[1]);
                $size = match ($level) {
                    1 => 16.0,
                    2 => 13.0,
                    default => 11.0,
                };
                $result[] = [$this->plain($match[2]), 'F2', $size, $level === 1 ? 8.0 : 6.0, 3.0];
                continue;
            }
            if (str_starts_with($line, '|') && str_ends_with($line, '|')) {
                $cells = array_map(fn (string $cell): string => $this->plain(trim($cell)), explode('|', trim($line, '|')));
                $result[] = [implode('   |   ', $cells), 'F1', 8.6, 0.0, 1.0];
                continue;
            }
            if (preg_match('/^-\s+(.*)$/', $line, $match)) {
                $result[] = ['- '.$this->plain($match[1]), 'F1', 9.5, 0.0, 1.0];
                continue;
            }
            $result[] = [$this->plain($line), 'F1', 9.5, 0.0, 1.0];
        }

        return $result;
    }

    /** @return array<int, string> */
    private function wrap(string $text, float $size, float $factor): array
    {
        if ($text === '') {
            return [''];
        }
        $maxChars = max(24, (int) floor((self::PAGE_WIDTH - (self::MARGIN_X * 2)) / max(4, $size * $factor)));
        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            if ($current === '') {
                $current = $word;
                continue;
            }
            if (strlen($current.' '.$word) > $maxChars) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current .= ' '.$word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [''] : $lines;
    }

    private function addLine(float $y, string $text, string $font, float $size): void
    {
        $pageIndex = count($this->pages) - 1;
        $this->pages[$pageIndex][] = [
            'font' => $font,
            'size' => $size,
            'x' => self::MARGIN_X,
            'y' => $y,
            'text' => $this->pdfSafeText($text),
        ];
    }

    private function newPage(): void
    {
        $this->pages[] = [];
    }

    private function buildPdf(): string
    {
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageKids = [];
        $contentObjectIds = [];
        $pageObjectIds = [];
        $nextId = 3;

        foreach ($this->pages as $index => $lines) {
            $pageObjectId = $nextId++;
            $contentObjectId = $nextId++;
            $pageObjectIds[] = $pageObjectId;
            $contentObjectIds[] = $contentObjectId;
            $pageKids[] = $pageObjectId.' 0 R';
        }

        $objects[] = '<< /Type /Pages /Kids ['.implode(' ', $pageKids).'] /Count '.count($pageKids).' >>';

        foreach ($this->pages as $index => $lines) {
            $pageObjectId = $pageObjectIds[$index];
            $contentObjectId = $contentObjectIds[$index];
            $objects[$pageObjectId - 1] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << /Font << /F1 '.($nextId).' 0 R /F2 '.($nextId + 1).' 0 R >> >> /Contents '.$contentObjectId.' 0 R >>';
            $objects[$contentObjectId - 1] = $this->streamObject($this->pageStream($lines, $index + 1, count($this->pages)));
        }

        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Title ('.$this->escapePdfString($this->pdfSafeText($this->productName.' report')).') /Producer (Aptoria) >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R /Info ".count($objects)." 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF\n";

        return $pdf;
    }

    /** @param array<int, array{font: string, size: float, x: float, y: float, text: string}> $lines */
    private function pageStream(array $lines, int $page, int $pages): string
    {
        $commands = [];
        foreach ($lines as $line) {
            $commands[] = 'BT /'.$line['font'].' '.$line['size'].' Tf 1 0 0 1 '.sprintf('%.2F %.2F', $line['x'], $line['y']).' Tm ('.$this->escapePdfString($line['text']).') Tj ET';
        }
        $footer = $this->footer !== '' ? $this->footer : $this->productName.' v'.$this->version;
        $footer .= ' - Page '.$page.' / '.$pages;
        $commands[] = 'BT /F1 7 Tf 1 0 0 1 '.sprintf('%.2F %.2F', self::MARGIN_X, self::FOOTER_Y).' Tm ('.$this->escapePdfString($this->pdfSafeText($footer)).') Tj ET';

        return implode("\n", $commands)."\n";
    }

    private function streamObject(string $stream): string
    {
        return '<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream";
    }

    private function plain(string $text): string
    {
        $text = preg_replace('/`([^`]+)`/', '$1', $text) ?? $text;
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text) ?? $text;
        $text = preg_replace('/_([^_]+)_/', '$1', $text) ?? $text;
        $text = str_replace(['|'], [' '], $text);
        return trim($text);
    }

    private function pdfSafeText(string $text): string
    {
        $map = [
            'ő' => 'o', 'Ő' => 'O', 'ű' => 'u', 'Ű' => 'U',
            '–' => '-', '—' => '-', '·' => '-', '•' => '-',
            '“' => '"', '”' => '"', '„' => '"', '’' => "'", '‘' => "'",
            '…' => '...', '×' => 'x', '✓' => 'OK', '✗' => 'X',
        ];
        $text = strtr($text, $map);
        $converted = function_exists('iconv') ? @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) : false;
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
        }

        return (string) $converted;
    }

    private function escapePdfString(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $text);
    }
}
