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
    $payload = [
        'version' => config('aptoria.version'),
        'installed' => $setup->isInstalled(),
        'env_exists' => File::exists(base_path('.env')),
        'sqlite_exists' => File::exists(database_path('database.sqlite')),
        'storage_writable' => is_writable(storage_path()),
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

Artisan::command('aptoria:demo-reset {--user=}', function (): int {
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
    $this->line('Live demo sandbox reset completed.');
    $this->line('Project: '.$result['project']->name.' (#'.$result['project']->id.')');
    $this->line('Deleted previous demo projects: '.$result['deleted_projects']);
    $this->line('Endpoints: '.$result['summary']['endpoints']);
    $this->line('Evidence: '.$result['summary']['evidence']);
    $this->line('Native tests: '.$result['summary']['test_cases']);

    return self::SUCCESS;
})->purpose('Reset the Aptoria Sandbox API project safely');
