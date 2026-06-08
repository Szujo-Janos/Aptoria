<?php

namespace Tests\Feature;

use Tests\TestCase;

class SweetAlertConfirmationTest extends TestCase
{
    public function test_views_do_not_use_native_confirm_handlers(): void
    {
        $viewsPath = resource_path('views');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewsPath));

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            $this->assertStringNotContainsString('return confirm(', $contents, $file->getPathname());
            $this->assertStringNotContainsString('onclick="return confirm', $contents, $file->getPathname());
            $this->assertStringNotContainsString('onsubmit="return confirm', $contents, $file->getPathname());
        }
    }

    public function test_sweetalert_confirmation_handler_is_present(): void
    {
        $script = file_get_contents(public_path('assets/aptoria/js/app.js'));

        $this->assertStringContainsString('data-aptoria-confirm', $script);
        $this->assertStringContainsString('window.swal', $script);
        $this->assertStringContainsString('handleConfirmedSubmit', $script);
        $this->assertStringNotContainsString('window.confirm(', $script);
        $this->assertStringContainsString('showFlashMessage', $script);
    }
}
