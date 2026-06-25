<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrivateLicenseIssuerToolTest extends TestCase
{
    public function test_private_license_issuer_scripts_are_present(): void
    {
        $base = base_path('tools/license-issuer');

        $this->assertFileExists($base.'/generate-keypair.php');
        $this->assertFileExists($base.'/issue-license.php');
        $this->assertFileExists($base.'/verify-license.php');
        $this->assertFileExists($base.'/README.md');
        $this->assertFileExists($base.'/.gitignore');
        $this->assertFileExists($base.'/examples/license-request.example.json');
    }

    public function test_private_license_issuer_does_not_bundle_generated_keys(): void
    {
        $base = base_path('tools/license-issuer');

        $this->assertDirectoryDoesNotExist($base.'/keys');
        $this->assertFileDoesNotExist($base.'/aptoria-license-private.pem');
        $this->assertFileDoesNotExist($base.'/aptoria-license-public.pem');
        $this->assertFileDoesNotExist($base.'/aptoria-license.json');
    }
}
