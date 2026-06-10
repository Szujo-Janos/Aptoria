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
<div class="hr-line-dashed"></div>
<h4 class="m-t-md"><i class="fa fa-id-card-o"></i> {{ __('messages.projects.report_branding_title') }}</h4>
<p class="text-muted">{{ __('messages.projects.report_branding_intro') }}</p>
<div class="row">
    <div class="col-md-6">
        <div class="form-group @error('report_client_name') has-error @enderror">
            <label for="report_client_name">{{ __('messages.projects.report_client_name') }}</label>
            <input type="text" name="report_client_name" id="report_client_name" class="form-control" value="{{ old('report_client_name', $project->report_client_name) }}" maxlength="160" placeholder="Acme Client / Demo Client">
            <span class="help-block">{{ __('messages.projects.report_client_name_help') }}</span>
            @error('report_client_name')<span class="help-block">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group @error('report_organization') has-error @enderror">
            <label for="report_organization">{{ __('messages.projects.report_organization') }}</label>
            <input type="text" name="report_organization" id="report_organization" class="form-control" value="{{ old('report_organization', $project->report_organization) }}" maxlength="160" placeholder="Client organization / Portfolio QA Lab">
            <span class="help-block">{{ __('messages.projects.report_organization_help') }}</span>
            @error('report_organization')<span class="help-block">{{ $message }}</span>@enderror
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group @error('report_prepared_by') has-error @enderror">
            <label for="report_prepared_by">{{ __('messages.projects.report_prepared_by') }}</label>
            <input type="text" name="report_prepared_by" id="report_prepared_by" class="form-control" value="{{ old('report_prepared_by', $project->report_prepared_by) }}" maxlength="120" placeholder="{{ auth()->user()?->report_display_name ?: auth()->user()?->name }}">
            <span class="help-block">{{ __('messages.projects.report_prepared_by_help') }}</span>
            @error('report_prepared_by')<span class="help-block">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group @error('report_role_title') has-error @enderror">
            <label for="report_role_title">{{ __('messages.projects.report_role_title') }}</label>
            <input type="text" name="report_role_title" id="report_role_title" class="form-control" value="{{ old('report_role_title', $project->report_role_title) }}" maxlength="160" placeholder="QA Engineer / API Auditor">
            @error('report_role_title')<span class="help-block">{{ $message }}</span>@enderror
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group @error('report_confidentiality_label') has-error @enderror">
            <label for="report_confidentiality_label">{{ __('messages.projects.report_confidentiality_label') }}</label>
            <input type="text" name="report_confidentiality_label" id="report_confidentiality_label" class="form-control" value="{{ old('report_confidentiality_label', $project->report_confidentiality_label) }}" maxlength="120" placeholder="Internal / Confidential / Client draft">
            @error('report_confidentiality_label')<span class="help-block">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group @error('report_logo') has-error @enderror">
            <label for="report_logo">{{ __('messages.projects.report_logo') }}</label>
            <input type="file" name="report_logo" id="report_logo" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
            <span class="help-block">{{ __('messages.projects.report_logo_help') }}</span>
            @if($project->report_logo_original_name)
                <span class="help-block"><i class="fa fa-picture-o"></i> {{ __('messages.projects.current_report_logo') }}: {{ $project->report_logo_original_name }}</span>
                <div class="checkbox checkbox-danger m-t-xs">
                    <label><input type="checkbox" name="remove_report_logo" value="1"> {{ __('messages.projects.remove_report_logo') }}</label>
                </div>
            @endif
            @error('report_logo')<span class="help-block">{{ $message }}</span>@enderror
        </div>
    </div>
</div>
<div class="form-group @error('report_disclaimer') has-error @enderror">
    <label for="report_disclaimer">{{ __('messages.projects.report_disclaimer') }}</label>
    <textarea name="report_disclaimer" id="report_disclaimer" class="form-control" rows="4" maxlength="3000" placeholder="{{ __('messages.projects.report_disclaimer_placeholder') }}">{{ old('report_disclaimer', $project->report_disclaimer) }}</textarea>
    <span class="help-block">{{ __('messages.projects.report_disclaimer_help') }}</span>
    @error('report_disclaimer')<span class="help-block">{{ $message }}</span>@enderror
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
