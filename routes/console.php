<?php

use App\Services\SetupStateService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('aptoria:version', function (): int {
    $this->line('Aptoria '.config('aptoria.version'));
    return self::SUCCESS;
})->purpose('Display the installed Aptoria version');

Artisan::command('aptoria:health {--json}', function (): int {
    $setup = app(SetupStateService::class);
    $hostingDiagnostics = app(\App\Services\HostingProfileDiagnosticsService::class)->run();
    $subdomainSmoke = app(\App\Services\SubdomainSmokeResultService::class)->latest();
    $payload = [
        'version' => config('aptoria.version'),
        'installed' => $setup->isInstalled(),
        'env_exists' => File::exists(base_path('.env')),
        'sqlite_exists' => File::exists(database_path('database.sqlite')),
        'storage_writable' => is_writable(storage_path()),
        'hosting_profile_status' => $hostingDiagnostics['status'] ?? 'unknown',
        'hosting_profile_role' => $hostingDiagnostics['role'] ?? 'unknown',
        'hosting_profile_errors' => $hostingDiagnostics['summary']['errors'] ?? 0,
        'hosting_profile_warnings' => $hostingDiagnostics['summary']['warnings'] ?? 0,
        'deployment_readiness_status' => ($deploymentReadiness = app(\App\Services\DeploymentReadinessService::class)->run('runtime'))['status'] ?? 'unknown',
        'deployment_readiness_score' => $deploymentReadiness['score'] ?? 0,
        'deployment_readiness_errors' => $deploymentReadiness['summary']['errors'] ?? 0,
        'deployment_readiness_warnings' => $deploymentReadiness['summary']['warnings'] ?? 0,
        'subdomain_smoke_status' => $subdomainSmoke['status'] ?? 'missing',
        'subdomain_smoke_generated_at' => $subdomainSmoke['generated_at'] ?? null,
        'subdomain_smoke_failed' => $subdomainSmoke['summary']['failed'] ?? null,
    ];

    if ($this->option('json')) {
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }

    foreach ($payload as $key => $value) {
        $this->line(sprintf('%s: %s', $key, is_bool($value) ? ($value ? 'yes' : 'no') : $value));
    }

    return self::SUCCESS;
})->purpose('Run a basic Aptoria installation health check');

Artisan::command('aptoria:license-fingerprint {--json}', function (): int {
    $fingerprints = app(\App\Services\LicenseFingerprintService::class)->current();

    if ($this->option('json')) {
        $this->line(json_encode($fingerprints, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }

    foreach ($fingerprints as $key => $fingerprint) {
        $this->line(sprintf('%s: %s', $key, $fingerprint['value'] ?? 'n/a'));
    }

    return self::SUCCESS;
})->purpose('Display Aptoria machine and portable USB license fingerprints');

Artisan::command('aptoria:license-status {--json}', function (): int {
    $status = app(\App\Services\LicenseGuardService::class)->status();

    if ($this->option('json')) {
        $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return (bool) ($status['valid'] ?? false) || ! (bool) ($status['enforced'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    $this->line('state: '.($status['state'] ?? 'unknown'));
    $this->line('valid: '.(($status['valid'] ?? false) ? 'yes' : 'no'));
    $this->line('enforced: '.(($status['enforced'] ?? false) ? 'yes' : 'no'));
    $this->line('message: '.($status['message'] ?? ''));
    $this->line('machine: '.($status['fingerprints']['machine']['value'] ?? 'n/a'));
    $this->line('usb: '.($status['fingerprints']['usb']['value'] ?? 'n/a'));

    return (bool) ($status['valid'] ?? false) || ! (bool) ($status['enforced'] ?? false) ? self::SUCCESS : self::FAILURE;
})->purpose('Display Aptoria license guard status');

Artisan::command('aptoria:license-request {--output=} ', function (): int {
    $json = app(\App\Services\LicenseRequestService::class)->toJson();
    $output = $this->option('output');

    if (is_string($output) && trim($output) !== '') {
        $path = trim($output);
        if (! str_starts_with($path, '/') && ! preg_match('/^[A-Z]:[\\\\\/]/i', $path)) {
            $path = base_path($path);
        }
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $json);
        $this->line('License request written to: '.$path);
        return self::SUCCESS;
    }

    $this->line($json);
    return self::SUCCESS;
})->purpose('Generate an Aptoria license request JSON file');

Artisan::command('aptoria:demo-api-project {--user=}', function (): int {
    $email = $this->option('user');
    $query = \App\Models\User::query();
    $user = is_string($email) && trim($email) !== ''
        ? $query->where('email', trim($email))->first()
        : $query->where('role', 'admin')->first();

    if (! $user) {
        $this->error('No Aptoria user found. Create an admin user first or pass --user=email@example.com.');
        return self::FAILURE;
    }

    $result = app(\App\Services\LiveDemoApiSandboxService::class)->build($user);
    $this->line('Live demo API sandbox project built.');
    $this->line('Project: '.$result['project']->name.' (#'.$result['project']->id.')');
    $this->line('Base URL: '.$result['summary']['base_url']);
    $this->line('Endpoints: '.$result['summary']['endpoints']);
    $this->line('Evidence: '.$result['summary']['evidence']);
    $this->line('Native tests: '.$result['summary']['test_cases']);
    $sandbox = app(\App\Services\LiveDemoApiSandboxService::class);
    $this->line('Demo viewer: '.$sandbox->demoUserEmail().' / '.$sandbox->demoUserPassword());

    return self::SUCCESS;
})->purpose('Build the Aptoria Sandbox API project');


Artisan::command('aptoria:demo-showcase {--user=}', function (): int {
    $email = $this->option('user');
    $query = \App\Models\User::query();
    $user = is_string($email) && trim($email) !== ''
        ? $query->where('email', trim($email))->first()
        : $query->where('role', 'admin')->first();

    if (! $user) {
        $this->error('No Aptoria user found. Create an admin user first or pass --user=email@example.com.');
        return self::FAILURE;
    }

    $result = app(\App\Services\DemoShowcaseWorkspaceService::class)->rebuild($user);
    $this->line('Full showcase demo workspace built.');
    $this->line('Project: '.$result['project']->name.' (#'.$result['project']->id.')');
    $this->line('Slug: '.$result['project']->slug);
    $this->line('Deleted previous demo projects: '.($result['deleted_projects'] ?? 0));
    $this->line('Demo viewer: '.$result['demo_user']->email.' / '.app(\App\Services\DemoShowcaseWorkspaceService::class)->demoUserPassword());
    $this->line('Summary:');
    foreach (($result['summary'] ?? []) as $key => $value) {
        $this->line('  '.$key.': '.$value);
    }

    return self::SUCCESS;
})->purpose('Build the Aptoria Full Showcase Demo Workspace');

Artisan::command('aptoria:demo-reset {--user=} {--no-prune-storage} {--no-flush-cache}', function (): int {
    $email = $this->option('user');
    $query = \App\Models\User::query();
    $user = is_string($email) && trim($email) !== ''
        ? $query->where('email', trim($email))->first()
        : $query->where('role', 'admin')->first();

    if (! $user) {
        $this->error('No Aptoria user found. Create an admin user first or pass --user=email@example.com.');
        return self::FAILURE;
    }

    $pruneStorage = (bool) config('aptoria.demo.reset_prune_storage', true) && ! $this->option('no-prune-storage');
    $flushCache = (bool) config('aptoria.demo.reset_flush_cache', true) && ! $this->option('no-flush-cache');

    $result = app(\App\Services\DemoEnvironmentResetService::class)->reset($user, $pruneStorage, $flushCache);
    $this->line(((string) config('aptoria.demo.viewer_mode', 'readonly') === 'showcase') ? 'Full showcase demo reset completed.' : 'Live demo sandbox reset completed.');
    $this->line('Project: '.$result['project']->name.' (#'.$result['project']->id.')');
    $this->line('Deleted previous demo projects: '.$result['deleted_projects']);
    $this->line('Deleted storage paths: '.(empty($result['deleted_storage_paths']) ? 'none' : implode(', ', $result['deleted_storage_paths'])));
    $this->line('Cache flushed: '.($result['cache_flushed'] ? 'yes' : 'no'));
    $this->line('Demo viewer: '.$result['demo_user']->email.' / '.app(\App\Services\LiveDemoApiSandboxService::class)->demoUserPassword());
    $this->line('Demo viewer mode: '.($result['viewer_mode'] ?? 'readonly'));
    $this->line('Demo viewer read-only: '.(($result['viewer_read_only'] ?? true) ? 'yes' : 'no'));
    $this->line('Endpoints: '.($result['summary']['endpoints'] ?? 0));
    $this->line('Evidence: '.($result['summary']['evidence'] ?? 0));
    $this->line('Native tests: '.($result['summary']['native_test_cases'] ?? $result['summary']['test_cases'] ?? 0));
    $this->line('Release gates: '.($result['summary']['release_gates'] ?? 0));
    $this->line('Evidence packs: '.($result['summary']['evidence_packs'] ?? 0));

    return self::SUCCESS;
})->purpose('Reset the Aptoria Sandbox API project safely');

Artisan::command('aptoria:hosting-diagnostics {--json} {--strict}', function (): int {
    $diagnostics = app(\App\Services\HostingProfileDiagnosticsService::class)->run();

    if ($this->option('json')) {
        $this->line(json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    } else {
        $this->line('Aptoria hosting profile diagnostics');
        $this->line('Version: '.$diagnostics['version']);
        $this->line('Role: '.$diagnostics['role']);
        $this->line('Status: '.$diagnostics['status']);
        $this->line('APP_URL: '.$diagnostics['app_url']);
        $this->newLine();

        foreach ($diagnostics['checks'] as $check) {
            $mark = ($check['passed'] ?? false) ? '[OK]' : strtoupper('['.($check['severity'] ?? 'warning').']');
            $this->line($mark.' '.$check['id'].' - '.$check['message']);
            if (! ($check['passed'] ?? false)) {
                $this->line('     actual: '.$check['actual']);
                $this->line('     fix: '.$check['remediation']);
            }
        }
    }

    $summary = $diagnostics['summary'] ?? ['errors' => 0, 'warnings' => 0];
    if ((int) ($summary['errors'] ?? 0) > 0) {
        return self::FAILURE;
    }

    if ($this->option('strict') && (int) ($summary['warnings'] ?? 0) > 0) {
        return self::FAILURE;
    }

    return self::SUCCESS;
})->purpose('Validate the active Aptoria hosting profile and runtime configuration');


Artisan::command('aptoria:deployment-preflight {--json} {--strict} {--installer}', function (): int {
    $mode = $this->option('installer') ? 'installer' : 'runtime';
    $readiness = app(\App\Services\DeploymentReadinessService::class)->run($mode);

    if ($this->option('json')) {
        $this->line(json_encode($readiness, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    } else {
        $this->line('Aptoria deployment readiness preflight');
        $this->line('Version: '.$readiness['version']);
        $this->line('Mode: '.$readiness['mode']);
        $this->line('Role: '.$readiness['role']);
        $this->line('Status: '.$readiness['status']);
        $this->line('Score: '.$readiness['score'].'/100');
        $this->line('APP_URL: '.$readiness['app_url']);
        $this->newLine();

        foreach ($readiness['stages'] as $stage) {
            $this->line(strtoupper('['.$stage['status'].']').' '.$stage['title'].' - passed '.$stage['summary']['passed'].', warnings '.$stage['summary']['warnings'].', errors '.$stage['summary']['errors']);
            foreach ($stage['checks'] as $check) {
                if (($check['status'] ?? 'pass') === 'pass' || ($check['status'] ?? 'info') === 'info') {
                    continue;
                }
                $this->line('  '.strtoupper('['.$check['status'].']').' '.$check['id'].' - '.$check['message']);
                $this->line('      actual: '.$check['actual']);
                $this->line('      fix: '.$check['remediation']);
            }
        }

        if (! empty($readiness['next_steps'])) {
            $this->newLine();
            $this->line('Next steps:');
            foreach ($readiness['next_steps'] as $step) {
                $this->line('- '.$step);
            }
        }
    }

    $summary = $readiness['summary'] ?? ['errors' => 0, 'warnings' => 0];
    if ((int) ($summary['errors'] ?? 0) > 0) {
        return self::FAILURE;
    }

    if ($this->option('strict') && (int) ($summary['warnings'] ?? 0) > 0) {
        return self::FAILURE;
    }

    return self::SUCCESS;
})->purpose('Run Aptoria deployment readiness and installer preflight checks');


Artisan::command('aptoria:subdomain-smoke-import {path} {--json}', function (): int {
    try {
        $result = app(\App\Services\SubdomainSmokeResultService::class)->importFromPath((string) $this->argument('path'), 'cli');
    } catch (\InvalidArgumentException $exception) {
        $this->error($exception->getMessage());
        return self::FAILURE;
    }

    if ($this->option('json')) {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return ((int) ($result['summary']['failed'] ?? 0)) > 0 ? self::FAILURE : self::SUCCESS;
    }

    $this->line('Subdomain smoke result imported.');
    $this->line('ID: '.$result['id']);
    $this->line('Generated: '.$result['generated_at']);
    $this->line('Status: '.$result['status']);
    $this->line('Passed: '.$result['summary']['passed'].' / '.$result['summary']['total']);
    $this->line('Failed: '.$result['summary']['failed']);

    return ((int) ($result['summary']['failed'] ?? 0)) > 0 ? self::FAILURE : self::SUCCESS;
})->purpose('Import a JSON result produced by scripts/smoke-subdomains');

Artisan::command('aptoria:subdomain-deployment {--json}', function (): int {
    $dashboard = app(\App\Services\SubdomainSmokeResultService::class)->dashboard();

    if ($this->option('json')) {
        $this->line(json_encode($dashboard, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return (($dashboard['summary']['status'] ?? 'missing') === 'passed') ? self::SUCCESS : self::FAILURE;
    }

    $this->line('Aptoria subdomain deployment dashboard');
    $this->line('Version: '.$dashboard['version']);
    $this->line('Latest status: '.($dashboard['summary']['status'] ?? 'missing'));
    $this->line('Generated: '.($dashboard['latest']['generated_at'] ?? 'not imported'));
    $this->line('Freshness: '.($dashboard['freshness']['status'] ?? 'missing').' - '.($dashboard['freshness']['message'] ?? ''));
    $this->newLine();

    foreach (($dashboard['domains'] ?? []) as $key => $domain) {
        $this->line(sprintf('%-8s %-8s %s passed / %s failed - %s', $key, $domain['status'] ?? 'missing', $domain['passed'] ?? 0, $domain['failed'] ?? 0, $domain['url'] ?? ''));
    }

    return (($dashboard['summary']['status'] ?? 'missing') === 'passed') ? self::SUCCESS : self::FAILURE;
})->purpose('Show the latest imported subdomain smoke result summary');
