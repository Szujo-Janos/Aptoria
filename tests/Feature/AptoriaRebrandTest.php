<?php

namespace Tests\Feature;

use Tests\TestCase;

class AptoriaRebrandTest extends TestCase
{
    public function test_public_branding_and_technical_namespaces_use_aptoria(): void
    {
        $this->assertSame('1.0.74', trim((string) file_get_contents(base_path('VERSION'))));
        $this->assertFileExists(config_path('aptoria.php'));
        $this->assertFileDoesNotExist(config_path(base64_decode('YXBpLXJhZGFyLnBocA==')));
        $this->assertFileExists(public_path('assets/aptoria/img/aptoria-logo-horizontal.png'));
        $this->assertFileExists(public_path('assets/aptoria/img/aptoria-logo-icon.png'));
        $this->assertDirectoryExists(public_path('assets/aptoria-ui/vendor'));
        $this->assertDirectoryDoesNotExist(public_path('assets/'.base64_decode('YXBpLXJhZGFy')));
        $this->assertDirectoryDoesNotExist(public_path('assets/'.base64_decode('cmFkYXItdWk=')));
    }

    public function test_legacy_public_product_name_does_not_remain_in_source_files(): void
    {
        $forbidden = array_map('base64_decode', [
            'QVBJIFJhZGFy',
            'QVBJLVJhZGFy',
            'YXBpLXJhZGFy',
            'QVBJX1JBREFS',
            'YXBpX3JhZGFy',
            'U3p1am8tSmFub3MvQVBJLVJhZGFy',
            'YXNzZXRzL2FwaS1yYWRhcg==',
            'YXNzZXRzL3JhZGFyLXVp',
        ]);

        $files = collect([
            base_path('README.md'),
            base_path('CHANGELOG.md'),
            base_path('composer.json'),
            base_path('.env.example'),
            base_path('.env.testing'),
            base_path('SERVER_INSTALLER.md'),
            base_path('docs/INSTALLATION.md'),
            base_path('docs/QA_CHECKLIST.md'),
            resource_path('lang/en/messages.php'),
            resource_path('lang/hu/messages.php'),
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/layouts/auth.blade.php'),
            public_path('index.php'),
            base_path('routes/console.php'),
        ]);

        foreach ($files as $file) {
            $content = file_get_contents($file);

            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString($needle, $content, "{$file} still contains legacy branding {$needle}");
            }
        }
    }
}
