<?php

namespace App\Http\Controllers;

use App\Models\ProgramSetting;
use App\Services\AuditLogger;
use App\Services\DemoShowcaseWorkspaceService;
use App\Services\LiveDemoApiSandboxService;
use App\Services\WorkspaceModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramSettingsController extends Controller
{
    private const SUPPORTED_TIMEZONES = [
        'Europe/Budapest',
        'UTC',
        'Europe/London',
        'Europe/Berlin',
        'America/New_York',
        'America/Los_Angeles',
    ];

    public function edit(): View
    {
        return view('program_settings.edit', [
            'settings' => $this->settings(),
            'supportedLocales' => config('aptoria.supported_locale_names', ['en' => 'English', 'hu' => 'Magyar']),
            'supportedTimezones' => self::SUPPORTED_TIMEZONES,
        ]);
    }

    public function update(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'default_locale' => ['required', 'string', 'in:en,hu'],
            'timezone' => ['required', 'string', 'in:'.implode(',', self::SUPPORTED_TIMEZONES)],
            'session_timeout_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
        ]);

        ProgramSetting::set('app.name', $data['app_name']);
        ProgramSetting::set('app.default_locale', $data['default_locale']);
        ProgramSetting::set('app.timezone', $data['timezone']);
        ProgramSetting::set('security.session_timeout_minutes', $data['session_timeout_minutes']);

        $auditLogger->record('updated', __('messages.audit_messages.program_settings_updated'), null, [
            'subject_label' => 'Program settings',
            'app_name' => $data['app_name'],
            'default_locale' => $data['default_locale'],
            'timezone' => $data['timezone'],
            'session_timeout_minutes' => $data['session_timeout_minutes'],
        ], 'program_settings');

        return redirect()->route('program-settings.edit')->with('status', __('messages.program_settings.updated'));
    }

    public function buildDemoProject(DemoShowcaseWorkspaceService $demoProjectService): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = request()->user();
        $result = $demoProjectService->rebuild($user);

        session(['workspace_mode' => WorkspaceModeService::SANDBOX, 'current_project_id' => $result['project']->id]);

        return redirect()
            ->route('projects.show', $result['project'])
            ->with('status', __('messages.program_settings.demo_project_built', [
                'endpoints' => $result['summary']['endpoints'],
                'findings' => $result['summary']['findings'],
                'reports' => $result['summary']['report_versions'],
            ]));
    }

    public function buildDemoApiProject(LiveDemoApiSandboxService $sandboxService): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = request()->user();
        $result = $sandboxService->build($user);

        session(['workspace_mode' => WorkspaceModeService::SANDBOX, 'current_project_id' => $result['project']->id]);

        return redirect()
            ->route('projects.show', $result['project'])
            ->with('status', __('messages.program_settings.demo_api_project_built', [
                'endpoints' => $result['summary']['endpoints'],
                'evidence' => $result['summary']['evidence'],
                'tests' => $result['summary']['test_cases'],
            ]));
    }

    private function settings(): array
    {
        return [
            'app_name' => ProgramSetting::get('app.name', config('app.name', 'Aptoria')),
            'default_locale' => ProgramSetting::get('app.default_locale', config('aptoria.default_locale', 'en')),
            'timezone' => ProgramSetting::get('app.timezone', config('app.timezone', 'Europe/Budapest')),
            'session_timeout_minutes' => ProgramSetting::get('security.session_timeout_minutes', config('aptoria.security.session_timeout_minutes', 120)),
        ];
    }
}
