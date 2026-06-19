@extends('layouts.app')
@section('title', __('messages.program_settings.title'))
@section('page_title', __('messages.program_settings.title'))
@section('page_actions')
    <a href="{{ route('dashboard') }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <form method="POST" action="{{ route('program-settings.update') }}" data-aptoria-form-scope="program_settings" data-aptoria-form-plugin>
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header border-light justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">{{ __('messages.program_settings.identity') }}</h5>
                        <p class="text-muted mb-0 small">{{ __('messages.program_settings.identity_copy') }}</p>
                    </div>
                    <span class="badge badge-soft-primary badge-label">v0.0.12</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">{{ __('messages.program_settings.app_name') }}</label>
                            <input type="text" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" class="form-control" placeholder="Aptoria">
                            <div class="form-text">{{ __('messages.program_settings.app_name_help') }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.program_settings.identity_help') }}</div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-light justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1"><i class="ti ti-shield-lock me-1"></i>{{ __('messages.program_settings.security_title') }}</h5>
                        <p class="text-muted mb-0 small">{{ __('messages.program_settings.security_copy') }}</p>
                    </div>
                    <span class="badge badge-soft-warning badge-label">{{ __('messages.program_settings.security_badge') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.program_settings.session_timeout_minutes') }}</label>
                            <input type="number" min="15" max="1440" name="session_timeout_minutes" value="{{ old('session_timeout_minutes', $settings['session_timeout_minutes']) }}" class="form-control" placeholder="120">
                            <div class="form-text">{{ __('messages.program_settings.session_timeout_minutes_help') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100 d-flex gap-2 align-items-start">
                                <span class="avatar avatar-xs rounded text-bg-success"><span class="avatar-title"><i class="ti ti-shield-check"></i></span></span>
                                <span><span class="d-block text-body">{{ __('messages.program_settings.security_headers_title') }}</span><small class="text-muted">{{ __('messages.program_settings.security_headers_copy') }}</small></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.program_settings.security_help') }}</div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">{{ __('messages.program_settings.localization') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.program_settings.default_locale') }}</label>
                            <select name="default_locale" class="form-select">
                                @foreach ($supportedLocales as $locale => $label)
                                    <option value="{{ $locale }}" @selected(old('default_locale', $settings['default_locale']) === $locale)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.program_settings.default_locale_help') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.program_settings.timezone') }}</label>
                            <select name="timezone" class="form-select">
                                @foreach ($supportedTimezones as $timezone)
                                    <option value="{{ $timezone }}" @selected(old('timezone', $settings['timezone']) === $timezone)>{{ $timezone }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.program_settings.timezone_help') }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-end gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-light">{{ __('messages.common.cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
                </div>
            </div>
        </form>

        <div class="card mt-3">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="database-zap" class="me-1"></i>{{ __('messages.program_settings.demo_project_title') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.program_settings.demo_project_copy') }}</p>
                </div>
                <span class="badge badge-soft-success badge-label">{{ __('messages.program_settings.demo_project_badge') }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3">
                            <div class="d-flex gap-2 align-items-start">
                                <span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="layers-3"></i></span></span>
                                <div>
                                    <div class="fw-medium">{{ __('messages.program_settings.demo_project_scope_title') }}</div>
                                    <div class="text-muted small">{{ __('messages.program_settings.demo_project_scope_copy') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3">
                            <div class="d-flex gap-2 align-items-start">
                                <span class="avatar avatar-xs rounded text-bg-warning"><span class="avatar-title"><i data-lucide="rotate-ccw"></i></span></span>
                                <div>
                                    <div class="fw-medium">{{ __('messages.program_settings.demo_project_rebuild_title') }}</div>
                                    <div class="text-muted small">{{ __('messages.program_settings.demo_project_rebuild_copy') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex flex-wrap justify-content-between gap-2 align-items-center">
                <span class="text-muted small">{{ __('messages.program_settings.demo_project_help') }}</span>
                <form method="POST" action="{{ route('program-settings.demo-project') }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.program_settings.demo_project_confirm_title') }}" data-confirm-text="{{ __('messages.program_settings.demo_project_confirm_text') }}" data-confirm-button="{{ __('messages.program_settings.demo_project_button') }}">
                    @csrf
                    <button type="submit" class="btn btn-success"><i data-lucide="database-zap" class="me-1"></i>{{ __('messages.program_settings.demo_project_button') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header border-light">
                <h5 class="card-title mb-0">{{ __('messages.program_settings.scope_title') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex gap-3">
                        <span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="tool"></i></span></span>
                        <span><span class="d-block text-body">{{ __('messages.program_settings.title') }}</span><small class="text-muted">{{ __('messages.program_settings.scope_program') }}</small></span>
                    </div>
                    @if (auth()->user()?->isAdmin())
                        <a href="{{ route('users.index') }}" class="list-group-item list-group-item-action d-flex gap-3">
                            <span class="avatar avatar-xs rounded text-bg-info"><span class="avatar-title"><i data-lucide="user-cog"></i></span></span>
                            <span><span class="d-block text-body">{{ __('messages.users.title') }}</span><small class="text-muted">{{ __('messages.program_settings.scope_users') }}</small></span>
                        </a>
                    @endif
                    <div class="list-group-item d-flex gap-3">
                        <span class="avatar avatar-xs rounded text-bg-light"><span class="avatar-title"><i data-lucide="folder-settings"></i></span></span>
                        <span><span class="d-block text-body">{{ __('messages.project_settings.title') }}</span><small class="text-muted">{{ __('messages.program_settings.scope_project') }}</small></span>
                    </div>
                    <div class="list-group-item d-flex gap-3">
                        <span class="avatar avatar-xs rounded text-bg-success"><span class="avatar-title"><i data-lucide="check"></i></span></span>
                        <span><span class="d-block text-body">{{ __('messages.program_settings.current_version') }}</span><small class="text-muted">v{{ $aptoriaVersion }}</small></span>
                    </div>
                    <div class="list-group-item d-flex gap-3">
                        <span class="avatar avatar-xs rounded text-bg-warning"><span class="avatar-title"><i data-lucide="lock-keyhole"></i></span></span>
                        <span><span class="d-block text-body">{{ __('messages.program_settings.setup_lock') }}</span><small class="text-muted">storage/app/installed.lock</small></span>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.program_settings.scope_help') }}</div>
        </div>
    </div>
</div>
@endsection
