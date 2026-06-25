@csrf
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('messages.projects.profile_card') }}</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">{{ __('messages.projects.name') }}</label>
                        <div class="input-group"><span class="input-group-text"><i data-lucide="folder-kanban"></i></span><input class="form-control" name="name" value="{{ old('name', $project->name) }}" required placeholder="{{ __('messages.projects.name_placeholder') }}"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('messages.projects.status') }}</label>
                        <select class="form-select" name="status">
                            <option value="draft" @selected(old('status', $project->status ?: 'draft') === 'draft')>{{ __('messages.projects.status_draft') }}</option>
                            <option value="active" @selected(old('status', $project->status) === 'active')>{{ __('messages.projects.status_active') }}</option>
                            <option value="paused" @selected(old('status', $project->status) === 'paused')>{{ __('messages.projects.status_paused') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('messages.workspace_mode.project_type') }}</label>
                        <select class="form-select" name="workspace_type">
                            <option value="live" @selected(old('workspace_type', $project->workspace_type ?: 'live') === 'live')>{{ __('messages.workspace_mode.live_short') }}</option>
                            <option value="sandbox" @selected(old('workspace_type', $project->workspace_type) === 'sandbox')>{{ __('messages.workspace_mode.sandbox_short') }}</option>
                        </select>
                        <div class="form-text">{{ __('messages.workspace_mode.project_type_help') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.projects.base_url') }}</label>
                        <div class="input-group"><span class="input-group-text"><i data-lucide="globe"></i></span><input class="form-control" name="base_url" value="{{ old('base_url', $project->base_url) }}" placeholder="{{ __('messages.projects.base_url_placeholder') }}"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('messages.projects.environment') }}</label>
                        <input class="form-control" name="environment_label" value="{{ old('environment_label', $project->environment_label) }}" placeholder="{{ __('messages.projects.environment_placeholder') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('messages.projects.qa_owner') }}</label>
                        <input class="form-control" name="qa_owner" value="{{ old('qa_owner', $project->qa_owner) }}" placeholder="{{ __('messages.projects.qa_owner_placeholder') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('messages.projects.description') }}</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="{{ __('messages.projects.description_placeholder') }}">{{ old('description', $project->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('messages.projects.release_goal') }}</label>
                        <textarea class="form-control" name="release_goal" rows="4" placeholder="{{ __('messages.projects.release_goal_placeholder') }}">{{ old('release_goal', $project->release_goal) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('messages.workspace.workspace_preview') }}</h5></div>
            <div class="card-body">
                <div class="aptoria-preview-step"><span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title">1</span></span><div><strong>{{ __('messages.workspace.preview_project') }}</strong><small>{{ __('messages.workspace.preview_project_copy') }}</small></div></div>
                <div class="aptoria-preview-step"><span class="avatar avatar-xs rounded text-bg-light"><span class="avatar-title">2</span></span><div><strong>{{ __('messages.workspace.preview_endpoint') }}</strong><small>{{ __('messages.workspace.preview_endpoint_copy') }}</small></div></div>
                <div class="aptoria-preview-step"><span class="avatar avatar-xs rounded text-bg-light"><span class="avatar-title">3</span></span><div><strong>{{ __('messages.workspace.preview_evidence') }}</strong><small>{{ __('messages.workspace.preview_evidence_copy') }}</small></div></div>
                <div class="alert alert-light border mt-3 mb-0"><i data-lucide="info" class="me-1"></i>{{ __('messages.projects.form_help') }}</div>
            </div>
        </div>
        <div class="d-grid gap-2 mt-3">
            <button class="btn btn-primary btn-lg" type="submit"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
            <a href="{{ route('projects.index') }}" class="btn btn-light">{{ __('messages.common.cancel') }}</a>
        </div>
    </div>
</div>
