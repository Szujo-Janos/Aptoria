<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="method">{{ __('messages.endpoints.method') }}</label>
            <select name="method" id="method" class="form-control" required>
                @foreach(\App\Models\Endpoint::METHODS as $method)
                    <option value="{{ $method }}" @selected(old('method', $endpoint->method) === $method)>{{ $method }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-9">
        <div class="form-group">
            <label for="path">{{ __('messages.endpoints.path') }}</label>
            <input type="text" name="path" id="path" class="form-control" value="{{ old('path', $endpoint->path) }}" placeholder="/api/v1/users" required>
            <span class="help-block">{{ __('messages.endpoints.path_help') }}</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">{{ __('messages.common.name') }}</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $endpoint->name) }}" placeholder="{{ __('messages.endpoints.endpoint_name_placeholder') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="environment_id">{{ __('messages.environments.title') }}</label>
            <select name="environment_id" id="environment_id" class="form-control">
                <option value="">{{ __('messages.endpoints.project_default') }}</option>
                @foreach($project->environments as $environment)
                    <option value="{{ $environment->id }}" @selected((int) old('environment_id', $endpoint->environment_id) === $environment->id)>{{ $environment->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="auth_profile_id">{{ __('messages.auth_profiles.title') }}</label>
            <select name="auth_profile_id" id="auth_profile_id" class="form-control">
                <option value="">{{ __('messages.endpoints.inherit_auth_profile') }}</option>
                @foreach($project->authProfiles as $authProfile)
                    <option value="{{ $authProfile->id }}" @selected((int) old('auth_profile_id', $endpoint->auth_profile_id) === $authProfile->id)>{{ $authProfile->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="risk_level">{{ __('messages.endpoints.risk_level') }}</label>
            <select name="risk_level" id="risk_level" class="form-control" required>
                @foreach(\App\Models\Endpoint::RISKS as $risk)
                    <option value="{{ $risk }}" @selected(old('risk_level', $endpoint->risk_level) === $risk)>{{ __('messages.endpoints.risks.'.$risk) }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="expected_status">{{ __('messages.endpoints.expected_status') }}</label>
            <input type="number" min="100" max="599" name="expected_status" id="expected_status" class="form-control" value="{{ old('expected_status', $endpoint->expected_status) }}" placeholder="200">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="expected_content_type">{{ __('messages.endpoints.expected_content_type') }}</label>
            <input type="text" name="expected_content_type" id="expected_content_type" class="form-control" value="{{ old('expected_content_type', $endpoint->expected_content_type) }}" placeholder="application/json">
        </div>
    </div>
</div>

<div class="form-group">
    <label for="tags">{{ __('messages.endpoints.tags') }}</label>
    <input type="text" name="tags" id="tags" class="form-control" value="{{ old('tags', $endpoint->tags) }}" placeholder="public, users, regression">
    <span class="help-block">{{ __('messages.endpoints.tags_help') }}</span>
</div>

<div class="form-group">
    <label for="description">{{ __('messages.common.description') }}</label>
    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $endpoint->description) }}</textarea>
</div>

<div class="form-group">
    <label for="risk_reason">{{ __('messages.endpoints.risk_reason') }}</label>
    <textarea name="risk_reason" id="risk_reason" class="form-control" rows="3" placeholder="{{ __('messages.endpoints.risk_reason_placeholder') }}">{{ old('risk_reason', $endpoint->risk_reason) }}</textarea>
</div>

<div class="form-group">
    <label for="qa_notes">{{ __('messages.endpoints.qa_notes') }}</label>
    <textarea name="qa_notes" id="qa_notes" class="form-control" rows="3">{{ old('qa_notes', $endpoint->qa_notes) }}</textarea>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="checkbox">
            <label><input type="checkbox" name="auth_required" value="1" @checked(old('auth_required', $endpoint->auth_required))> {{ __('messages.endpoints.auth_required') }}</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="checkbox">
            <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $endpoint->is_active ?? true))> {{ __('messages.endpoints.is_active') }}</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="checkbox">
            <label><input type="checkbox" name="excluded_from_scan" value="1" @checked(old('excluded_from_scan', $endpoint->excluded_from_scan))> {{ __('messages.endpoints.excluded_from_scan') }}</label>
        </div>
    </div>
</div>

<div class="hr-line-dashed"></div>
<button type="submit" class="btn btn-primary">{{ $buttonLabel }}</button>
<a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
