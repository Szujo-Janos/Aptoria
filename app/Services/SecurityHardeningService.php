<?php

namespace App\Services;

use App\Models\ProgramSetting;

class SecurityHardeningService
{
    /** @return array<int,array{key:string,icon:string,label:string,detail:string,status:string,tone:string}> */
    public function checklist(): array
    {
        return [
            $this->item(
                'debug',
                'bug-off',
                __('messages.security.check_debug_title'),
                config('app.debug') ? __('messages.security.check_debug_risky') : __('messages.security.check_debug_ok'),
                ! config('app.debug')
            ),
            $this->item(
                'app_key',
                'key',
                __('messages.security.check_app_key_title'),
                trim((string) config('app.key')) !== '' ? __('messages.security.check_app_key_ok') : __('messages.security.check_app_key_missing'),
                trim((string) config('app.key')) !== ''
            ),
            $this->item(
                'session_timeout',
                'clock-shield',
                __('messages.security.check_timeout_title'),
                __('messages.security.check_timeout_detail', ['minutes' => $this->sessionTimeoutMinutes()]),
                $this->sessionTimeoutMinutes() > 0
            ),
            $this->item(
                'setup_lock',
                'lock-check',
                __('messages.security.check_setup_lock_title'),
                is_file((string) config('aptoria.installed_lock_path')) ? __('messages.security.check_setup_lock_ok') : __('messages.security.check_setup_lock_open'),
                is_file((string) config('aptoria.installed_lock_path'))
            ),
        ];
    }

    public function sessionTimeoutMinutes(): int
    {
        return max(0, (int) ProgramSetting::get(
            'security.session_timeout_minutes',
            config('aptoria.security.session_timeout_minutes', 120)
        ));
    }

    /** @return array{key:string,icon:string,label:string,detail:string,status:string,tone:string} */
    private function item(string $key, string $icon, string $label, string $detail, bool $ok): array
    {
        return [
            'key' => $key,
            'icon' => $icon,
            'label' => $label,
            'detail' => $detail,
            'status' => $ok ? __('messages.common.ok') : __('messages.common.needs_fix'),
            'tone' => $ok ? 'success' : 'warning',
        ];
    }
}
