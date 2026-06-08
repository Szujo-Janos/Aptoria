@csrf
@if($authProfile->exists)
    <div class="alert alert-info">
        <strong>{{ __('messages.auth_profiles.scan_runtime_status') }}:</strong>
        <span class="label label-{{ $authProfile->scan_ready_css }}">{{ $authProfile->scan_ready_label }}</span>
        <br>
        <code>{{ $authProfile->masked_summary }}</code>
    </div>
@endif
<div class="form-group">
    <label for="name">{{ __('messages.common.name') }}</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $authProfile->name) }}" required maxlength="100" placeholder="{{ __('messages.auth_profiles.name_placeholder') }}">
    <span class="help-block">{{ __('messages.auth_profiles.name_help') }}</span>
</div>
<div class="form-group">
    <label for="type">{{ __('messages.common.type') }}</label>
    <select name="type" id="type" class="form-control" required>
        @foreach($types as $type)
            <option value="{{ $type }}" @selected(old('type', $authProfile->type) === $type)>
                {{ __('messages.auth_profiles.types.'.$type) }}
            </option>
        @endforeach
    </select>
    <span class="help-block">{{ __('messages.auth_profiles.type_help') }}</span>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="token">{{ __('messages.auth_profiles.token') }}</label>
            <input type="password" name="token" id="token" class="form-control">
            <span class="help-block">{{ __('messages.auth_profiles.token_help') }}</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="username">{{ __('messages.auth_profiles.username') }}</label>
            <input type="text" name="username" id="username" class="form-control" value="{{ old('username', $authProfile->username) }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="password">{{ __('messages.auth_profiles.password') }}</label>
            <input type="password" name="password" id="password" class="form-control">
            <span class="help-block">{{ __('messages.auth_profiles.password_help') }}</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="header_name">{{ __('messages.auth_profiles.header_name') }}</label>
            <input type="text" name="header_name" id="header_name" class="form-control" value="{{ old('header_name', $authProfile->header_name) }}" placeholder="X-API-Key">
        </div>
    </div>
</div>

<div class="form-group">
    <label for="header_value">{{ __('messages.auth_profiles.header_value') }}</label>
    <input type="password" name="header_value" id="header_value" class="form-control">
    <span class="help-block">{{ __('messages.auth_profiles.header_help') }}</span>
</div>

<div class="form-group">
    <label for="notes">{{ __('messages.common.notes') }}</label>
    <textarea name="notes" id="notes" rows="4" class="form-control">{{ old('notes', $authProfile->notes) }}</textarea>
    <span class="help-block">{{ __('messages.auth_profiles.notes_help') }}</span>
</div>
<div class="checkbox checkbox-success">
    <label>
        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $authProfile->is_default) ? 'checked' : '' }}>
        {{ __('messages.auth_profiles.is_default') }}
    </label>
</div>
<div class="alert alert-warning">
    {{ __('messages.auth_profiles.token_help') }}
</div>

<div class="hr-line-dashed"></div>
<button type="submit" class="btn btn-success">{{ __('messages.common.save') }}</button>
<a href="{{ route('projects.show', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
