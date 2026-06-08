<?php

namespace Tests\Feature;

use App\Services\Setup\EnvironmentCheckService;
use App\Services\Setup\SetupStateService;
use Tests\TestCase;

class SelfInstallerTest extends TestCase
{
    public function test_setup_page_is_available(): void
    {
        $response = $this->get(route('setup.index'));

        $response->assertOk();
        $response->assertSee('Aptoria');
        $response->assertSee('Import comprehensive QA demo');
    }

    public function test_environment_check_returns_summary_and_checks(): void
    {
        $report = app(EnvironmentCheckService::class)->report();

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('checks', $report);
        $this->assertNotEmpty($report['checks']);
    }

    public function test_setup_lock_service_points_to_storage_lock_file(): void
    {
        $service = app(SetupStateService::class);

        $this->assertStringEndsWith('storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'installed.lock', $service->lockPath());
    }


    public function test_setup_lock_file_can_be_checked_directly_during_security_audit(): void
    {
        $service = app(SetupStateService::class);

        @mkdir(dirname($service->lockPath()), 0775, true);
        file_put_contents($service->lockPath(), '{}'.PHP_EOL);

        try {
            $this->assertTrue($service->hasLockFile());
            $this->assertFalse($service->isLocked());
        } finally {
            @unlink($service->lockPath());
        }
    }

    public function test_installer_scripts_are_present(): void
    {
        $this->assertFileExists(base_path('scripts/install-windows-xampp.ps1'));
        $this->assertFileExists(base_path('scripts/install-linux.sh'));
    }
}
