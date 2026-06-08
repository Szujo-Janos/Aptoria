<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReleaseDocumentationConsistencyTest extends TestCase
{
    public function test_public_documentation_uses_current_release_version_and_short_zip_name(): void
    {
        $version = trim((string) file_get_contents(base_path('VERSION')));
        $shortZip = "aptoria-{$version}.zip";
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
            $this->assertStringNotContainsString('aptoria-1.0.65.zip', $content, "{$path} must not reference the old v1.0.65 ZIP.");
            $this->assertStringNotContainsString('aptoria-1.0.68.zip', $content, "{$path} must not reference the old v1.0.68 ZIP.");
            $this->assertStringNotContainsString('aptoria-1.0.42.zip', $content, "{$path} must not reference the old v1.0.42 ZIP.");
            $this->assertStringNotContainsString('aptoria-1.0.72-settings-functional-audit-cumulative.zip', $content, "{$path} must keep short release ZIP naming.");
        }

        $this->assertStringContainsString($shortZip, file_get_contents(base_path('docs/INSTALLATION.md')));
        $this->assertStringContainsString($shortZip, file_get_contents(base_path('docs/QA_CHECKLIST.md')));
        $this->assertStringContainsString($canonicalRepo, file_get_contents(base_path('README.md')));
        $this->assertStringContainsString($canonicalRepo, file_get_contents(base_path('docs/INSTALLATION.md')));
    }

    public function test_settings_defaults_do_not_reintroduce_user_facing_status_metadata(): void
    {
        $source = file_get_contents(app_path('Services/Settings/SettingService.php'));

        $this->assertStringNotContainsString("'status' => 'active'", $source);
        $this->assertStringNotContainsString("'status' => 'prepared'", $source);
        $this->assertStringNotContainsString("'status' => 'planned'", $source);
    }
}
