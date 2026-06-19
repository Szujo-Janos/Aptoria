<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class EnvironmentCheckService
{
    /** @return array{checks:array<int,array{key:string,label:string,detail:string,fix:?string,status:string}>,summary:array{ok:int,warnings:int,failed:int,info:int,can_continue:bool}} */
    public function report(): array
    {
        $checks = [
            $this->check('php', __('messages.setup.check_php'), PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>='), __('messages.setup.fix_php')),
            $this->check('pdo_sqlite', __('messages.setup.check_sqlite'), extension_loaded('pdo_sqlite') ? 'pdo_sqlite enabled' : 'pdo_sqlite missing', extension_loaded('pdo_sqlite'), __('messages.setup.fix_sqlite')),
            $this->check('storage', __('messages.setup.check_storage'), storage_path(), $this->writableOrCreatable(storage_path()), __('messages.setup.fix_storage')),
            $this->check('cache', __('messages.setup.check_cache'), base_path('bootstrap/cache'), $this->writableOrCreatable(base_path('bootstrap/cache')), __('messages.setup.fix_cache')),
            $this->info('env', __('messages.setup.check_env'), File::exists(base_path('.env')) ? '.env ready' : '.env will be created from .env.example', File::exists(base_path('.env')) || File::exists(base_path('.env.example'))),
            $this->info('database', __('messages.setup.check_database'), File::exists(database_path('database.sqlite')) ? 'database.sqlite ready' : 'database.sqlite will be created', $this->writableOrCreatable(database_path())),
            $this->info('lock', __('messages.setup.check_lock'), File::exists(storage_path('app/installed.lock')) ? 'installed.lock exists' : 'setup is open', ! File::exists(storage_path('app/installed.lock'))),
            $this->warning('app_debug', __('messages.setup.check_app_debug'), config('app.debug') ? __('messages.setup.check_app_debug_enabled') : __('messages.setup.check_app_debug_disabled'), ! config('app.debug'), __('messages.setup.fix_app_debug')),
            $this->warning('session_timeout', __('messages.setup.check_session_timeout'), __('messages.setup.check_session_timeout_detail', ['minutes' => (int) config('aptoria.security.session_timeout_minutes', 120)]), (int) config('aptoria.security.session_timeout_minutes', 120) > 0, __('messages.setup.fix_session_timeout')),
        ];

        $summary = ['ok' => 0, 'warnings' => 0, 'failed' => 0, 'info' => 0, 'can_continue' => true];
        foreach ($checks as $check) {
            if ($check['status'] === 'ok') { $summary['ok']++; }
            if ($check['status'] === 'warning') { $summary['warnings']++; }
            if ($check['status'] === 'failed') { $summary['failed']++; $summary['can_continue'] = false; }
            if ($check['status'] === 'info') { $summary['info']++; }
        }

        return ['checks' => $checks, 'summary' => $summary];
    }

    private function check(string $key, string $label, string $detail, bool $ok, string $fix): array
    {
        return ['key' => $key, 'label' => $label, 'detail' => $detail, 'fix' => $ok ? null : $fix, 'status' => $ok ? 'ok' : 'failed'];
    }

    private function info(string $key, string $label, string $detail, bool $ok): array
    {
        return ['key' => $key, 'label' => $label, 'detail' => $detail, 'fix' => $ok ? null : __('messages.setup.fix_runtime'), 'status' => $ok ? 'info' : 'warning'];
    }

    private function warning(string $key, string $label, string $detail, bool $ok, string $fix): array
    {
        return ['key' => $key, 'label' => $label, 'detail' => $detail, 'fix' => $ok ? null : $fix, 'status' => $ok ? 'ok' : 'warning'];
    }

    private function writableOrCreatable(string $path): bool
    {
        if (is_dir($path)) {
            return is_writable($path);
        }

        $parent = dirname($path);

        return is_dir($parent) ? is_writable($parent) : is_writable(dirname($parent));
    }
}
