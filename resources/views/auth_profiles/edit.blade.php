@extends('layouts.app')

@section('title', __('messages.auth_profiles.edit_title'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.auth_profiles.edit_title') }}: {{ $authProfile->name }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.auth-profiles.update', [$project, $authProfile]) }}">
                    @method('PUT')
                    @include('auth_profiles._form')
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.common.delete') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.auth_profiles.default') }}</p>
                <form method="POST" action="{{ route('projects.auth-profiles.destroy', [$project, $authProfile]) }}" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.auth_profiles.confirm_delete') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">{{ __('messages.common.delete') }}</button>
                </form>
            </div>
        </div>
    
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.auth_profiles.test_title') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.auth_profiles.test_help') }}</p>
                <form method="POST" action="{{ route('projects.auth-profiles.test', [$project, $authProfile]) }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('messages.auth_profiles.test_method') }}</label>
                        <select name="test_method" class="form-control">
                            <option value="GET">GET</option>
                            <option value="HEAD">HEAD</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.auth_profiles.test_url') }}</label>
                        <input type="url" name="test_url" class="form-control" value="{{ old('test_url', $project->defaultEnvironment()?->base_url ?: $project->base_url) }}" placeholder="https://api.example.com/health">
                    </div>
                    <button type="submit" class="btn btn-info btn-block">{{ __('messages.auth_profiles.test_button') }}</button>
                </form>
            </div>
        </div>
</div>
</div>
@endsection
