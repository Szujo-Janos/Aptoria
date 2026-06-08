@csrf
<div class="form-group">
    <label for="name">{{ __('messages.common.name') }}</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $project->name) }}" required maxlength="150" placeholder="{{ __('messages.projects.project_name_placeholder') }}">
    <span class="help-block">{{ __('messages.projects.name_help') }}</span>
</div>
<div class="form-group">
    <label for="base_url">{{ __('messages.common.base_url') }}</label>
    <input type="url" name="base_url" id="base_url" class="form-control" value="{{ old('base_url', $project->base_url) }}" required placeholder="https://api.example.com">
    <span class="help-block">{{ __('messages.projects.base_url_help') }}</span>
</div>
<div class="form-group">
    <label for="description">{{ __('messages.common.description') }}</label>
    <textarea name="description" id="description" class="form-control" rows="5">{{ old('description', $project->description) }}</textarea>
    <span class="help-block">{{ __('messages.projects.description_help') }}</span>
</div>
<div class="checkbox checkbox-success">
    <label>
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $project->is_active) ? 'checked' : '' }}> {{ __('messages.projects.is_active') }}
    </label>
    <span class="help-block">{{ __('messages.projects.active_help') }}</span>
</div>
<div class="hr-line-dashed"></div>
<button type="submit" class="btn btn-success">{{ __('messages.common.save') }}</button>
<a href="{{ route('projects.index') }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
