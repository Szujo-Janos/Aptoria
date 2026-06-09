@extends('layouts.app')

@section('title', __('messages.auth_profiles.edit_title'))

@section('content')
@php($authTestResult = session('auth_profile_test_result'))
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
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <i class="fa fa-plug"></i> {{ __('messages.auth_profiles.test_title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.auth_profiles.test_help') }}</p>
                <div class="alert alert-info">
                    <strong>{{ __('messages.auth_profiles.test_auth_summary') }}:</strong><br>
                    <code>{{ $authProfile->masked_summary }}</code>
                </div>
                <form method="POST" action="{{ route('projects.auth-profiles.test', [$project, $authProfile]) }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('messages.auth_profiles.test_target') }}</label>
                        <select name="test_target" class="form-control">
                            <option value="endpoint" @selected(old('test_target', $probeableEndpoints->isNotEmpty() ? 'endpoint' : 'custom') === 'endpoint')>{{ __('messages.auth_profiles.test_target_endpoint') }}</option>
                            <option value="custom" @selected(old('test_target', $probeableEndpoints->isEmpty() ? 'custom' : 'endpoint') === 'custom')>{{ __('messages.auth_profiles.test_target_custom') }}</option>
                        </select>
                        @error('test_target')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>{{ __('messages.auth_profiles.test_endpoint') }}</label>
                        <select name="test_endpoint_id" class="form-control">
                            <option value="">{{ __('messages.auth_profiles.test_endpoint_placeholder') }}</option>
                            @foreach($probeableEndpoints as $endpoint)
                                <option value="{{ $endpoint->id }}" @selected((string) old('test_endpoint_id') === (string) $endpoint->id)>
                                    {{ $endpoint->method }} {{ $endpoint->path }} — {{ $endpoint->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block">{{ __('messages.auth_profiles.test_endpoint_help') }}</span>
                        @error('test_endpoint_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>{{ __('messages.auth_profiles.test_method') }}</label>
                        <select name="test_method" class="form-control">
                            <option value="GET" @selected(old('test_method', 'GET') === 'GET')>GET</option>
                            <option value="HEAD" @selected(old('test_method') === 'HEAD')>HEAD</option>
                        </select>
                        @error('test_method')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.auth_profiles.test_url') }}</label>
                        <input type="url" name="test_url" class="form-control" value="{{ old('test_url', $project->defaultEnvironment()?->base_url ?: $project->base_url) }}" placeholder="https://api.example.com/health">
                        <span class="help-block">{{ __('messages.auth_profiles.test_url_help') }}</span>
                        @error('test_url')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="btn btn-info btn-block"><i class="fa fa-bolt"></i> {{ __('messages.auth_profiles.test_button') }}</button>
                </form>
            </div>
        </div>

        @if(is_array($authTestResult))
            <div class="hpanel h{{ $authTestResult['style'] === 'success' ? 'green' : ($authTestResult['style'] === 'warning' ? 'yellow' : 'red') }}">
                <div class="panel-heading hbuilt">
                    <span class="label label-{{ $authTestResult['style'] }} pull-right">{{ $authTestResult['status_label'] }}</span>
                    {{ __('messages.auth_profiles.test_result_title') }}
                </div>
                <div class="panel-body">
                    <p>{{ $authTestResult['message'] }}</p>
                    <dl class="dl-horizontal">
                        <dt>{{ __('messages.auth_profiles.test_result_profile') }}</dt><dd>{{ $authTestResult['profile_name'] }} · {{ $authTestResult['profile_type'] }}</dd>
                        <dt>{{ __('messages.auth_profiles.test_result_target') }}</dt><dd>{{ $authTestResult['target_label'] }}</dd>
                        <dt>{{ __('messages.auth_profiles.test_result_request') }}</dt><dd><code>{{ $authTestResult['method'] }} {{ $authTestResult['url'] }}</code></dd>
                        <dt>{{ __('messages.auth_profiles.test_result_status') }}</dt><dd>{{ $authTestResult['status'] ?? __('messages.common.not_available') }}</dd>
                        <dt>{{ __('messages.auth_profiles.test_result_time') }}</dt><dd>{{ $authTestResult['duration_ms'] !== null ? $authTestResult['duration_ms'].' ms' : __('messages.common.not_available') }}</dd>
                        <dt>{{ __('messages.auth_profiles.test_result_content_type') }}</dt><dd>{{ $authTestResult['content_type'] ?? __('messages.common.not_available') }}</dd>
                        <dt>{{ __('messages.auth_profiles.test_result_auth') }}</dt><dd>{{ $authTestResult['auth_applied'] ? __('messages.auth_profiles.applied') : __('messages.auth_profiles.not_applied') }}<br><code>{{ $authTestResult['auth_summary'] }}</code></dd>
                    </dl>
                    @if(!empty($authTestResult['response_headers']))
                        <h5>{{ __('messages.auth_profiles.test_response_headers') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped">
                                <tbody>
                                    @foreach($authTestResult['response_headers'] as $name => $value)
                                        <tr><th>{{ $name }}</th><td><code>{{ $value }}</code></td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <h5>{{ __('messages.auth_profiles.test_response_preview') }}</h5>
                    <pre class="aptoria-response-preview">{{ $authTestResult['response_preview'] ?? __('messages.common.not_available') }}</pre>
                </div>
            </div>
        @endif

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
    </div>
</div>
@endsection
