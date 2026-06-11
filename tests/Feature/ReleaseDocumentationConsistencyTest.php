<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReleaseDocumentationConsistencyTest extends TestCase
{
    public function test_public_documentation_uses_current_release_version_and_short_zip_name(): void
    {
        $version = trim((string) file_get_contents(base_path('VERSION')));
        $shortZip = "aptoria-{$version}.zip";
        $rootFolder = "aptoria-{$version}";
        $tempFolder = "_temp_aptoria_{$version}";
        $canonicalRepo = 'https://github.com/Szujo-Janos/Aptoria.git';

        $documentation = [
            'README.md',
            'docs/INSTALLATION.md',
            'docs/QA_CHECKLIST.md',
            'SERVER_INSTALLER.md',
        ];

        foreach ($documentation as $path) {
            $content = file_get_contents(base_path($path));

            $this->assertStringContainsString($version, $content, "{$path} must reference the current release version.");
            $this->assertStringNotContainsString('aptoria-1.0.72-settings-functional-audit-cumulative.zip', $content, "{$path} must keep short release ZIP naming.");

            preg_match_all('/aptoria-\d+\.\d+\.\d+\.zip/', $content, $zipMatches);
            foreach ($zipMatches[0] as $zipName) {
                $this->assertSame($shortZip, $zipName, "{$path} must not reference old ZIP {$zipName}.");
            }

            preg_match_all('/_temp_aptoria_\d+\.\d+\.\d+/', $content, $tempMatches);
            foreach ($tempMatches[0] as $folderName) {
                $this->assertSame($tempFolder, $folderName, "{$path} must not reference old temp folder {$folderName}.");
            }

            preg_match_all('/aptoria-\d+\.\d+\.\d+\\\\\*/', $content, $rootMatches);
            foreach ($rootMatches[0] as $folderPattern) {
                $this->assertSame($rootFolder.'\\*', $folderPattern, "{$path} must not reference old root folder {$folderPattern}.");
            }
        }

        $this->assertStringContainsString('public/assets/aptoria/img/aptoria-logo-horizontal.png', file_get_contents(base_path('README.md')));
        $this->assertStringNotContainsString('<h1 align="center">Aptoria</h1>', file_get_contents(base_path('README.md')));
        $this->assertStringContainsString($shortZip, file_get_contents(base_path('docs/INSTALLATION.md')));
        $this->assertStringContainsString($shortZip, file_get_contents(base_path('docs/QA_CHECKLIST.md')));
        $this->assertStringContainsString($canonicalRepo, file_get_contents(base_path('README.md')));
        $this->assertStringContainsString($canonicalRepo, file_get_contents(base_path('docs/INSTALLATION.md')));
    }

    public function test_release_builder_requires_current_system_audit_document(): void
    {
        $version = trim((string) file_get_contents(base_path('VERSION')));
        $script = file_get_contents(base_path('scripts/build-release.ps1'));

        $this->assertStringContainsString('docs/SYSTEM_AUDIT_v$Version.md', $script);
        $this->assertFileExists(base_path("docs/SYSTEM_AUDIT_v{$version}.md"));
    }

    public function test_settings_defaults_do_not_reintroduce_user_facing_status_metadata(): void
    {
        $source = file_get_contents(app_path('Services/Settings/SettingService.php'));

        $this->assertStringNotContainsString("'status' => 'active'", $source);
        $this->assertStringNotContainsString("'status' => 'prepared'", $source);
        $this->assertStringNotContainsString("'status' => 'planned'", $source);
    }
}
