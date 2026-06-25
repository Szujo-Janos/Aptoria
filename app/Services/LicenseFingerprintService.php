<?php

namespace App\Services;

use Illuminate\Support\Str;

class LicenseFingerprintService
{
    public function current(): array
    {
        $machineComponents = $this->machineComponents();
        $usbComponents = $this->portableDriveComponents();

        return [
            'machine' => [
                'label' => 'Machine fingerprint',
                'value' => $this->hashComponents($machineComponents),
                'components' => $machineComponents,
            ],
            'usb' => [
                'label' => 'Portable drive fingerprint',
                'value' => $this->hashComponents($usbComponents),
                'components' => $usbComponents,
            ],
        ];
    }

    public function compact(): array
    {
        $current = $this->current();

        return [
            'machine' => $current['machine']['value'],
            'usb' => $current['usb']['value'],
        ];
    }

    private function machineComponents(): array
    {
        return array_filter([
            'hostname' => gethostname() ?: php_uname('n'),
            'os_family' => PHP_OS_FAMILY,
            'os_release' => php_uname('r'),
            'php_sapi' => PHP_SAPI,
            'app_root' => $this->normalizePath(base_path()),
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function portableDriveComponents(): array
    {
        $basePath = $this->normalizePath(base_path());
        $driveRoot = $this->driveRoot($basePath);

        return array_filter([
            'drive_root' => $driveRoot,
            'volume_serial' => $this->volumeSerial($driveRoot),
            'portable_anchor' => basename($basePath) ?: 'aptoria',
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function driveRoot(string $path): string
    {
        if (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Z]:/i', $path, $matches)) {
            return strtoupper($matches[0]).'\\';
        }

        return '/'.trim(explode('/', ltrim($path, '/'))[0] ?? '', '/');
    }

    private function volumeSerial(string $driveRoot): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows' || ! preg_match('/^[A-Z]:\\\\?$/i', $driveRoot)) {
            return null;
        }

        $drive = substr($driveRoot, 0, 2);
        $output = @shell_exec('vol '.escapeshellarg($drive).' 2>NUL');
        if (! is_string($output) || trim($output) === '') {
            return null;
        }

        if (preg_match('/([A-F0-9]{4}-[A-F0-9]{4})/i', $output, $matches)) {
            return strtoupper($matches[1]);
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', $output) ?? ''), 80, '');
    }

    private function hashComponents(array $components): string
    {
        ksort($components);

        return 'sha256:'.hash('sha256', json_encode($components, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function normalizePath(string $path): string
    {
        $real = realpath($path) ?: $path;

        return str_replace('\\', '/', $real);
    }
}
