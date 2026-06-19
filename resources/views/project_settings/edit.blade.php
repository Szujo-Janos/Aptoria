@extends('layouts.app')
@section('title', __('messages.project_settings.title') . ' · ' . $project->name)
@section('page_title', __('messages.project_settings.title'))
@section('page_actions')
    <a href="{{ route('program-settings.edit') }}" class="btn btn-light"><i data-lucide="tool" class="me-1"></i>{{ __('messages.nav.program_settings') }}</a>
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
@endsection

@section('content')
<form method="POST" action="{{ route('projects.settings.update', $project) }}" data-aptoria-form-scope="project_settings" data-aptoria-form-plugin>
    <div class="alert alert-primary bg-primary-subtle border-primary-subtle">
        <div class="d-flex gap-2 align-items-start">
            <i data-lucide="folder-kanban" class="mt-1"></i>
            <div>
                <strong>{{ __('messages.project_settings.scope_notice_title') }}</strong><br>
                {{ __('messages.project_settings.scope_notice_copy', ['project' => $project->name]) }}
            </div>
        </div>
    </div>
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-light justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">{{ __('messages.project_settings.scan_defaults') }}</h5>
                        <p class="text-muted mb-0 small">{{ __('messages.project_settings.scan_defaults_copy') }}</p>
                    </div>
                    <span class="badge badge-soft-primary badge-label">v0.0.12</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.project_settings.default_environment') }}</label>
                            <select name="default_environment_id" class="form-select">
                                <option value="">{{ __('messages.project_settings.no_environment_option') }}</option>
                                @foreach ($environments as $environment)
                                    <option value="{{ $environment->id }}" @selected((string) $settings['default_environment_id'] === (string) $environment->id)>{{ $environment->name }} · {{ $environment->type_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.project_settings.default_auth_profile') }}</label>
                            <select name="default_auth_profile_id" class="form-select">
                                <option value="">{{ __('messages.project_settings.no_auth_option') }}</option>
                                @foreach ($authProfiles as $profile)
                                    <option value="{{ $profile->id }}" @selected((string) $settings['default_auth_profile_id'] === (string) $profile->id)>{{ $profile->name }} · {{ $profile->type_label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.project_settings.defaults_help') }}</div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.project_settings.scan_safety') }}</h5></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <label class="list-group-item d-flex justify-content-between align-items-start gap-3">
                            <span><span class="d-block text-body">{{ __('messages.project_settings.require_confirmation') }}</span><small class="text-muted">{{ __('messages.project_settings.require_confirmation_help') }}</small></span>
                            <input type="checkbox" class="form-check-input" name="scan_require_confirmation" value="1" @checked($settings['scan_require_confirmation'])>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-start gap-3">
                            <span><span class="d-block text-body">{{ __('messages.project_settings.safe_methods_only') }}</span><small class="text-muted">{{ __('messages.project_settings.safe_methods_only_help') }}</small></span>
                            <input type="checkbox" class="form-check-input" name="scan_safe_methods_only" value="1" @checked($settings['scan_safe_methods_only'])>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-start gap-3">
                            <span><span class="d-block text-body">{{ __('messages.project_settings.allow_private_networks') }}</span><small class="text-muted">{{ __('messages.project_settings.allow_private_networks_help') }}</small></span>
                            <input type="checkbox" class="form-check-input" name="scan_allow_private_networks" value="1" @checked($settings['scan_allow_private_networks'])>
                        </label>
                    </div>
                </div>
                <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-end gap-2">
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-light">{{ __('messages.common.cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.project_settings.readiness_preview') }}</h5></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex gap-3"><span class="avatar avatar-xs rounded {{ $environments->isNotEmpty() ? 'text-bg-success' : 'text-bg-light' }}"><span class="avatar-title"><i data-lucide="server"></i></span></span><span>{{ __('messages.environments.title') }}: {{ $environments->count() }}</span></div>
                        <div class="list-group-item d-flex gap-3"><span class="avatar avatar-xs rounded {{ $authProfiles->isNotEmpty() ? 'text-bg-success' : 'text-bg-light' }}"><span class="avatar-title"><i data-lucide="key-round"></i></span></span><span>{{ __('messages.auth_profiles.title') }}: {{ $authProfiles->count() }}</span></div>
                        <div class="list-group-item d-flex gap-3"><span class="avatar avatar-xs rounded text-bg-success"><span class="avatar-title"><i data-lucide="shield-check"></i></span></span><span>{{ __('messages.project_settings.safe_methods_only') }}</span></div>
                        <div class="list-group-item d-flex gap-3"><span class="avatar avatar-xs rounded {{ $settings['scan_allow_private_networks'] ? 'text-bg-danger' : 'text-bg-success' }}"><span class="avatar-title"><i data-lucide="network"></i></span></span><span>{{ __('messages.project_settings.private_network_policy') }}</span></div>
                    </div>
                </div>
                <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.project_settings.readiness_help') }}</div>
            </div>
        </div>
    </div>
</form>
@endsection
