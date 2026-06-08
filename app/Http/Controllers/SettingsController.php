<?php

namespace App\Http\Controllers;

use App\Services\Settings\SettingService;
use App\Services\Security\SecurityStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(SettingService $settings, SecurityStatusService $securityStatus): View
    {
        $securityChecks = $securityStatus->checks();

        return view('settings.index', [
            'settings' => $settings->grouped(),
            'groups' => $settings->groups(),
            'systemInfo' => $this->systemInfo(),
            'securityChecks' => $securityChecks,
            'securitySummary' => $securityStatus->summary($securityChecks),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $defaults = $settings->defaults();
        $rules = [];

        foreach ($defaults as $key => $meta) {
            $field = $this->fieldName($key);
            $type = (string) ($meta['type'] ?? 'string');

            $rules[$field] = match ($type) {
                'boolean' => ['nullable', 'boolean'],
                'integer' => array_values(array_filter([
                    'nullable',
                    'integer',
                    array_key_exists('min', $meta) ? 'min:'.$meta['min'] : null,
                    array_key_exists('max', $meta) ? 'max:'.$meta['max'] : null,
                ])),
                'csv' => ['nullable', 'string', 'max:5000'],
                default => array_values(array_filter([
                    'nullable',
                    'string',
                    'max:'.((int) ($meta['max'] ?? 255)),
                    ! empty($meta['options']) ? Rule::in($meta['options']) : null,
                ])),
            };
        }

        $validated = $request->validate($rules);
        $values = [];

        foreach ($defaults as $key => $meta) {
            $field = $this->fieldName($key);
            $type = (string) ($meta['type'] ?? 'string');

            if ($type === 'boolean') {
                $values[$key] = $request->boolean($field);
                continue;
            }

            $values[$key] = array_key_exists($field, $validated)
                ? $validated[$field]
                : ($meta['value'] ?? null);
        }

        if ((int) $values['risk.medium_threshold'] <= (int) $values['risk.low_threshold']
            || (int) $values['risk.high_threshold'] <= (int) $values['risk.medium_threshold']
            || (int) $values['risk.critical_threshold'] <= (int) $values['risk.high_threshold']) {
            return back()
                ->withErrors(['risk_thresholds' => __('messages.settings.validation.risk_threshold_order')])
                ->withInput();
        }

        if ((int) $values['risk.very_slow_response_ms'] <= (int) $values['risk.slow_response_ms']) {
            return back()
                ->withErrors(['risk_response_thresholds' => __('messages.settings.validation.response_threshold_order')])
                ->withInput();
        }

        $values['scan.delay_between_requests_ms'] = $values['scan.rate_limit_ms'] ?? $values['scan.delay_between_requests_ms'] ?? 250;

        $settings->updateMany($values);

        return redirect()
            ->route('settings.index')
            ->with('success', __('messages.settings.saved'));
    }

    private function fieldName(string $key): string
    {
        return str_replace('.', '_', $key);
    }

    public function reset(SettingService $settings): RedirectResponse
    {
        $settings->resetToDefaults();

        return redirect()
            ->route('settings.index')
            ->with('success', __('messages.settings.reset_done'));
    }

    public function resetGroup(string $group, SettingService $settings): RedirectResponse
    {
        abort_unless(in_array($group, $settings->groups(), true), 404);

        $settings->resetGroupToDefaults($group);

        return redirect()
            ->route('settings.index')
            ->with('success', __('messages.settings.group_reset_done', ['group' => __('messages.settings.groups.'.$group)]));
    }

    public function export(SettingService $settings): JsonResponse
    {
        return response()->json([
            'version' => config('aptoria.version'),
            'scope' => 'global',
            'settings' => $settings->exportGrouped(),
        ]);
    }

    /** @return array<string, string> */
    private function systemInfo(): array
    {
        return [
            __('messages.settings.system.version') => 'Aptoria v'.config('aptoria.version'),
            __('messages.settings.system.laravel') => app()->version(),
            __('messages.settings.system.php') => PHP_VERSION,
            __('messages.settings.system.database_driver') => (string) config('database.default'),
            __('messages.settings.system.app_env') => (string) config('app.env'),
            __('messages.settings.system.debug') => config('app.debug') ? __('messages.common.yes') : __('messages.common.no'),
            __('messages.settings.system.storage_writable') => is_writable(storage_path()) ? __('messages.common.yes') : __('messages.common.no'),
            __('messages.settings.system.bootstrap_cache_writable') => is_writable(base_path('bootstrap/cache')) ? __('messages.common.yes') : __('messages.common.no'),
            __('messages.settings.system.composer_lock') => is_file(base_path('composer.lock')) ? __('messages.common.yes') : __('messages.common.no'),
            __('messages.settings.system.vendor_installed') => is_dir(base_path('vendor')) ? __('messages.common.yes') : __('messages.common.no'),
        ];
    }
}
