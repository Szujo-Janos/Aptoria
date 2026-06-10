@csrf
<div class="form-group">
    <label for="name">{{ __('messages.common.name') }}</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $environment->name) }}" required maxlength="80" placeholder="local, staging, production">
    <span class="help-block">{{ __('messages.environments.name_help') }}</span>
</div>
<div class="form-group">
    <label for="environment_type">{{ __('messages.environments.environment_type') }}</label>
    <select name="environment_type" id="environment_type" class="form-control" required>
        @foreach(($typeOptions ?? \App\Models\Environment::typeOptions()) as $type => $label)
            <option value="{{ $type }}" @selected(old('environment_type', $environment->environment_type ?: \App\Models\Environment::TYPE_CUSTOM) === $type)>{{ $label }}</option>
        @endforeach
    </select>
    <span class="help-block">{{ __('messages.environments.environment_type_help') }}</span>
</div>
<div class="form-group">
    <label for="base_url">{{ __('messages.common.base_url') }}</label>
    <input type="url" name="base_url" id="base_url" class="form-control" value="{{ old('base_url', $environment->base_url) }}" required placeholder="https://staging.example.com">
    <span class="help-block">{{ __('messages.environments.base_url_help') }}</span>
</div>

<div class="form-group">
    <label for="auth_profile_id">{{ __('messages.environments.auth_profile') }}</label>
    <select name="auth_profile_id" id="auth_profile_id" class="form-control">
        <option value="">{{ __('messages.environments.use_project_default_auth') }}</option>
        @foreach(($authProfiles ?? $project->authProfiles) as $authProfile)
            <option value="{{ $authProfile->id }}" @selected((string) old('auth_profile_id', $environment->auth_profile_id) === (string) $authProfile->id)>
                {{ $authProfile->name }} — {{ $authProfile->type_label }}
            </option>
        @endforeach
    </select>
    <span class="help-block">{{ __('messages.environments.auth_profile_help') }}</span>
</div>

<div class="checkbox checkbox-danger">
    <label>
        <input type="checkbox" name="is_production" value="1" {{ old('is_production', $environment->is_production) ? 'checked' : '' }}>
        {{ __('messages.environments.is_production') }}
    </label>
    <span class="help-block">{{ __('messages.environments.is_production_help') }}</span>
</div>
<div class="checkbox checkbox-success">
    <label>
        <input type="checkbox" name="make_default" value="1" {{ old('make_default', $isDefaultEnvironment ?? false) ? 'checked' : '' }}>
        {{ __('messages.environments.make_default') }}
    </label>
    <span class="help-block">{{ __('messages.environments.make_default_help') }}</span>
</div>
<div class="alert alert-info">
    {{ __('messages.environments.manager_intro') }}
</div>
<div class="hr-line-dashed"></div>
<button type="submit" class="btn btn-success">{{ __('messages.common.save') }}</button>
<a href="{{ route('projects.environments.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
