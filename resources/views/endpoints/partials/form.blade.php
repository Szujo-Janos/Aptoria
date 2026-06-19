@php
    $endpoint = $endpoint ?? null;
@endphp
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.endpoints.method') }}</label>
        <select name="method" class="form-select" required>
            @foreach (\App\Models\Endpoint::METHODS as $method)
                <option value="{{ $method }}" @selected(old('method', $endpoint?->method ?? 'GET') === $method)>{{ $method }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label">{{ __('messages.endpoints.path') }}</label>
        <input type="text" name="path" class="form-control" value="{{ old('path', $endpoint?->path ?? '/') }}" placeholder="{{ __('messages.endpoints.path_placeholder') }}" required>
        <div class="form-text">{{ __('messages.endpoints.path_help') }}</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.endpoints.name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $endpoint?->name) }}" placeholder="{{ __('messages.endpoints.name_placeholder') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.endpoints.tags') }}</label>
        <input type="text" name="tags" class="form-control" value="{{ old('tags', $endpoint?->tags) }}" placeholder="{{ __('messages.endpoints.tags_placeholder') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.nav.environments') }}</label>
        <select name="environment_id" class="form-select">
            <option value="">{{ __('messages.endpoints.use_default_environment') }}</option>
            @foreach ($environments as $environment)
                <option value="{{ $environment->id }}" @selected((int) old('environment_id', $endpoint?->environment_id) === (int) $environment->id)>{{ $environment->name }} · {{ $environment->type_label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.nav.auth_profiles') }}</label>
        <select name="auth_profile_id" class="form-select">
            <option value="">{{ __('messages.auth_profiles.no_auth_preview') }}</option>
            @foreach ($authProfiles as $profile)
                <option value="{{ $profile->id }}" @selected((int) old('auth_profile_id', $endpoint?->auth_profile_id) === (int) $profile->id)>{{ $profile->name }} · {{ $profile->type_label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.endpoints.expected_status') }}</label>
        <input type="number" name="expected_status" min="100" max="599" class="form-control" value="{{ old('expected_status', $endpoint?->expected_status ?? 200) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.endpoints.content_type') }}</label>
        <input type="text" name="expected_content_type" class="form-control" value="{{ old('expected_content_type', $endpoint?->expected_content_type ?? 'application/json') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.endpoints.risk') }}</label>
        <select name="risk_level" class="form-select">
            @foreach (\App\Models\Endpoint::RISK_LEVELS as $risk)
                <option value="{{ $risk }}" @selected(old('risk_level', $endpoint?->risk_level ?? 'low') === $risk)>{{ __('messages.endpoints.risk_levels.'.$risk) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('messages.endpoints.description') }}</label>
        <textarea name="description" class="form-control" rows="2" placeholder="{{ __('messages.endpoints.description_placeholder') }}">{{ old('description', $endpoint?->description) }}</textarea>
    </div>
    <div class="col-12">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="auth_required" value="1" id="authRequired{{ $endpoint?->id ?? 'New' }}" @checked(old('auth_required', $endpoint?->auth_required ?? false))>
                    <label class="form-check-label" for="authRequired{{ $endpoint?->id ?? 'New' }}">{{ __('messages.endpoints.auth_required') }}</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive{{ $endpoint?->id ?? 'New' }}" @checked(old('is_active', $endpoint?->is_active ?? true))>
                    <label class="form-check-label" for="isActive{{ $endpoint?->id ?? 'New' }}">{{ __('messages.endpoints.is_active') }}</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="excluded_from_scan" value="1" id="excludedFromScan{{ $endpoint?->id ?? 'New' }}" @checked(old('excluded_from_scan', $endpoint?->excluded_from_scan ?? false))>
                    <label class="form-check-label" for="excludedFromScan{{ $endpoint?->id ?? 'New' }}">{{ __('messages.endpoints.excluded_from_scan') }}</label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('messages.common.notes') }}</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('messages.endpoints.notes_placeholder') }}">{{ old('notes', $endpoint?->notes) }}</textarea>
    </div>
</div>
