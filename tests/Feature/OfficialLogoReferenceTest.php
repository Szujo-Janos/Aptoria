<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class OfficialLogoReferenceTest extends TestCase
{
    public function test_application_sources_do_not_reference_deprecated_logo_pngs(): void
    {
        $deprecated = [
            'aptoria-logo.png',
            'aptoria-logo-sm.png',
            'aptoria-logo-white.png',
            'logo.png',
            'logo-sm.png',
            'logo-black.png',
        ];

        $roots = [app_path(), resource_path('views'), base_path('public/index.php'), base_path('docs/REPORT_VISUAL_STANDARD.md')];

        foreach ($roots as $root) {
            foreach ($this->textFiles($root) as $file) {
                $contents = file_get_contents($file->getPathname()) ?: '';

                foreach ($deprecated as $name) {
                    $this->assertStringNotContainsString($name, $contents, $file->getPathname().' still references '.$name);
                }
            }
        }
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function textFiles(string $path): iterable
    {
        if (is_file($path)) {
            yield new SplFileInfo($path);

            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $extension = $file->getExtension();
            if (in_array($extension, ['php', 'css', 'js', 'md'], true) || str_ends_with($file->getFilename(), '.blade.php')) {
                yield $file;
            }
        }
    }
}
