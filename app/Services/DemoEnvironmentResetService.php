<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class DemoEnvironmentResetService
{
    public function __construct(private readonly LiveDemoApiSandboxService $sandbox, private readonly DemoShowcaseWorkspaceService $showcase)
    {
    }

    /** @return array<string,mixed> */
    public function reset(User $owner, bool $pruneStorage = true, bool $flushCache = true): array
    {
        $deletedStoragePaths = $pruneStorage ? $this->pruneStorage() : [];

        if ($flushCache) {
            Cache::flush();
        }

        $result = (string) config('aptoria.demo.viewer_mode', 'readonly') === 'showcase'
            ? $this->showcase->rebuild($owner)
            : $this->sandbox->build($owner);

        $demoUser = $result['demo_user'];
        $demoUser->forceFill([
            'role' => 'user',
            'password_change_required' => false,
        ])->save();

        return $result + [
            'deleted_storage_paths' => $deletedStoragePaths,
            'cache_flushed' => $flushCache,
            'viewer_mode' => (string) config('aptoria.demo.viewer_mode', 'readonly'),
            'viewer_read_only' => (bool) config('aptoria.demo.viewer_read_only', true),
        ];
    }

    /** @return list<string> */
    private function pruneStorage(): array
    {
        $deleted = [];
        $root = $this->normalizePath(realpath(storage_path('app')) ?: storage_path('app'));

        foreach ((array) config('aptoria.demo.reset_storage_paths', []) as $relativePath) {
            $relativePath = trim((string) $relativePath, " \/\\");
            if ($relativePath === '' || str_contains($relativePath, '..')) {
                continue;
            }

            $path = storage_path('app/'.$relativePath);
            $parent = $this->normalizePath(realpath(dirname($path)) ?: dirname($path));

            if (! str_starts_with($parent, $root)) {
                throw new InvalidArgumentException('Refusing to prune storage path outside storage/app: '.$relativePath);
            }

            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
                $deleted[] = $relativePath;
            } elseif (File::exists($path)) {
                File::delete($path);
                $deleted[] = $relativePath;
            }
        }

        return $deleted;
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/').'/';
    }
}
