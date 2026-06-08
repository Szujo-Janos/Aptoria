<?php

namespace App\Services\Setup;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SetupStateService
{
    public function lockPath(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'installed.lock');
    }

    public function hasLockFile(): bool
    {
        return is_file($this->lockPath());
    }

    public function isLocked(): bool
    {
        if (app()->runningUnitTests()) {
            return false;
        }

        return $this->hasLockFile();
    }

    public function isInstalled(): bool
    {
        if ($this->isLocked()) {
            return true;
        }

        try {
            return Schema::hasTable('users') && User::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function installationHint(): string
    {
        if ($this->isLocked()) {
            return 'locked';
        }

        try {
            if (Schema::hasTable('users') && User::query()->exists()) {
                return 'users_exist_without_lock';
            }
        } catch (Throwable) {
            return 'not_ready';
        }

        return 'not_installed';
    }

    public function writeLock(?string $createdBy = null): void
    {
        $directory = dirname($this->lockPath());

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $payload = [
            'installed_at' => now()->toIso8601String(),
            'created_by' => $createdBy ?: 'setup',
            'version' => config('aptoria.version'),
            'app_url' => config('app.url'),
        ];

        file_put_contents(
            $this->lockPath(),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }
}
