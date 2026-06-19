@php
    $selectedType = old('type', $profile?->type ?? 'none');
@endphp
<div class="row g-3 aptoria-auth-profile-form">
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.auth_profiles.name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $profile?->name) }}" placeholder="{{ __('messages.auth_profiles.name_placeholder') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.auth_profiles.type') }}</label>
        <select name="type" class="form-select aptoria-auth-type-select" required>
            @foreach (['none','bearer','basic','custom_header'] as $type)
                <option value="{{ $type }}" @selected($selectedType === $type)>{{ __('messages.auth_profiles.types.'.$type) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 aptoria-auth-fields aptoria-auth-bearer">
        <label class="form-label">{{ __('messages.auth_profiles.token') }}</label>
        <input type="password" name="token" class="form-control" placeholder="{{ $profile && $profile->type === 'bearer' ? __('messages.auth_profiles.keep_existing_secret') : __('messages.auth_profiles.token_placeholder') }}" autocomplete="new-password">
        <div class="form-text">{{ __('messages.auth_profiles.secret_help') }}</div>
    </div>

    <div class="col-md-6 aptoria-auth-fields aptoria-auth-basic">
        <label class="form-label">{{ __('messages.auth_profiles.username') }}</label>
        <input type="text" name="username" class="form-control" value="{{ old('username', $profile?->username) }}" autocomplete="off">
    </div>
    <div class="col-md-6 aptoria-auth-fields aptoria-auth-basic">
        <label class="form-label">{{ __('messages.auth_profiles.password') }}</label>
        <input type="password" name="password" class="form-control" placeholder="{{ $profile && $profile->type === 'basic' ? __('messages.auth_profiles.keep_existing_secret') : '' }}" autocomplete="new-password">
    </div>

    <div class="col-md-5 aptoria-auth-fields aptoria-auth-custom_header">
        <label class="form-label">{{ __('messages.auth_profiles.header_name') }}</label>
        <input type="text" name="header_name" class="form-control" value="{{ old('header_name', $profile?->header_name) }}" placeholder="{{ __('messages.auth_profiles.header_name_placeholder') }}">
    </div>
    <div class="col-md-7 aptoria-auth-fields aptoria-auth-custom_header">
        <label class="form-label">{{ __('messages.auth_profiles.header_value') }}</label>
        <input type="password" name="header_value" class="form-control" placeholder="{{ $profile && $profile->type === 'custom_header' ? __('messages.auth_profiles.keep_existing_secret') : '' }}" autocomplete="new-password">
    </div>

    <div class="col-md-6">
        <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="is_default" value="1" id="authDefault{{ $profile?->id ?? 'new' }}" @checked(old('is_default', $profile?->is_default))>
            <label for="authDefault{{ $profile?->id ?? 'new' }}" class="form-check-label">{{ __('messages.auth_profiles.is_default') }}</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('messages.common.notes') }}</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('messages.auth_profiles.notes_placeholder') }}">{{ old('notes', $profile?->notes) }}</textarea>
    </div>
</div>
