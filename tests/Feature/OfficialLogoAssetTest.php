<?php

namespace Tests\Feature;

use Tests\TestCase;

class OfficialLogoAssetTest extends TestCase
{
    public function test_application_uses_single_official_svg_logo_asset(): void
    {
        $logoPath = public_path('assets/aptoria-ui/assets/images/logo-color.svg');

        $this->assertFileExists($logoPath);
        $this->assertStringContainsString('<svg', file_get_contents($logoPath));

        foreach ([
            'aptoria-logo.png',
            'aptoria-logo-sm.png',
            'aptoria-logo-white.png',
            'logo.png',
            'logo-sm.png',
            'logo-black.png',
        ] as $deprecatedLogo) {
            $this->assertFileDoesNotExist(public_path('assets/aptoria-ui/assets/images/'.$deprecatedLogo));
        }
    }
}
