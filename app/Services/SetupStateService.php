<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class SetupStateService
{
    public function isInstalled(): bool
    {
        return $this->isLocked();
    }

    public function isLocked(): bool
    {
        return File::exists($this->lockPath());
    }

    public function canUseApplication(): bool
    {
        return $this->isLocked();
    }

    public function lockPath(): string
    {
        return config('aptoria.installed_lock_path', storage_path('app/installed.lock'));
    }

    public function installationHint(): string
    {
        return $this->isLocked()
            ? 'storage/app/installed.lock exists; setup is closed.'
            : 'Setup is open until storage/app/installed.lock is written.';
    }

    public function markInstalled(array $payload = []): void
    {
        $this->writeLock((string) ($payload['source'] ?? 'guided-web-installer'), $payload);
    }

    public function writeLock(string $source = 'guided-web-installer', array $payload = []): void
    {
        File::ensureDirectoryExists(dirname($this->lockPath()));

        File::put($this->lockPath(), json_encode(array_merge([
            'installed_at' => now()->toIso8601String(),
            'version' => config('aptoria.version'),
            'source' => $source,
        ], $payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
