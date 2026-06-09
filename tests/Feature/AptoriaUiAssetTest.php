<?php

namespace Tests\Feature;

use Tests\TestCase;

class AptoriaUiAssetTest extends TestCase
{
    public function test_aptoria_ui_guards_optional_vendor_plugins(): void
    {
        $source = file_get_contents(public_path('assets/aptoria-ui/js/aptoria-ui.js'));

        $this->assertStringContainsString('$.fn.metisMenu', $source);
        $this->assertStringContainsString("$('#side-menu').length", $source);
        $this->assertStringContainsString('$.fn.slimScroll', $source);
        $this->assertStringContainsString('$.fn.animatePanel', $source);
    }
}
