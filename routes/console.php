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
