@extends('layouts.app')

@section('title', __('messages.endpoints.import_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.endpoints.import_title') }} — {{ $project->name }}</div>
            <div class="panel-body">
                <div class="alert alert-info">{{ __('messages.endpoints.import_help') }}</div>
                <form method="POST" action="{{ route('projects.endpoints.import', $project) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="format">{{ __('messages.endpoints.import_format') }}</label>
                                <select name="format" id="format" class="form-control" required>
                                    <option value="csv" @selected(old('format') === 'csv')>CSV</option>
                                    <option value="json" @selected(old('format') === 'json')>JSON</option>
                                    <option value="openapi" @selected(old('format') === 'openapi')>{{ __('messages.endpoints.import_format_openapi') }}</option>
                                    <option value="postman" @selected(old('format') === 'postman')>{{ __('messages.endpoints.import_format_postman') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="environment_id">{{ __('messages.environments.title') }}</label>
                                <select name="environment_id" id="environment_id" class="form-control">
                                    <option value="">{{ __('messages.endpoints.project_default') }}</option>
                                    @foreach($project->environments as $environment)
                                        <option value="{{ $environment->id }}" @selected((int) old('environment_id') === $environment->id)>{{ $environment->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="auth_profile_id">{{ __('messages.auth_profiles.title') }}</label>
                                <select name="auth_profile_id" id="auth_profile_id" class="form-control">
                                    <option value="">{{ __('messages.common.none') }}</option>
                                    @foreach($project->authProfiles as $authProfile)
                                        <option value="{{ $authProfile->id }}" @selected((int) old('auth_profile_id') === $authProfile->id)>{{ $authProfile->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.endpoints.import_source') }}</label>
                                <div class="radio radio-info m-t-none">
                                    <label><input type="radio" name="import_source" value="paste" @checked(old('import_source', 'paste') !== 'url')> {{ __('messages.endpoints.import_source_paste') }}</label>
                                </div>
                                <div class="radio radio-info">
                                    <label><input type="radio" name="import_source" value="url" @checked(old('import_source') === 'url')> {{ __('messages.endpoints.import_source_url') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="source_url">{{ __('messages.endpoints.source_url') }}</label>
                                <input type="url" name="source_url" id="source_url" class="form-control" value="{{ old('source_url') }}" placeholder="https://example.com/openapi.yaml or postman_collection.json">
                                <span class="help-block">{{ __('messages.endpoints.source_url_help') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="payload">{{ __('messages.endpoints.import_payload') }}</label>
                        @php($csvSample = "method,path,name,risk_level,auth_required,expected_status,tags\nGET,/api/v1/health,Health check,low,false,200,health\nGET,/api/v1/users,List users,high,true,200,users")
                        <textarea name="payload" id="payload" class="form-control code-input" rows="14">{{ old('payload', $csvSample) }}</textarea>
                        <span class="help-block">{{ __('messages.endpoints.collection_import_help') }}</span>
                        <div class="well well-sm m-t-sm">
                            <strong>{{ __('messages.endpoints.openapi_sample_title') }}</strong>
                            <p class="m-b-xs text-muted">{{ __('messages.endpoints.openapi_sample_help') }}</p>
                            <button type="button" class="btn btn-xs btn-default" id="use-openapi-sample">{{ __('messages.endpoints.use_openapi_sample') }}</button>
                            <button type="button" class="btn btn-xs btn-default" id="use-openapi-yaml-sample">{{ __('messages.endpoints.use_openapi_yaml_sample') }}</button>
                            <button type="button" class="btn btn-xs btn-default" id="use-postman-sample">{{ __('messages.endpoints.use_postman_sample') }}</button>
                        </div>
                    </div>

                    <div class="hpanel hblue">
                        <div class="panel-heading hbuilt">{{ __('messages.endpoints.postman_advanced_title') }}</div>
                        <div class="panel-body">
                            <p class="text-muted">{{ __('messages.endpoints.postman_advanced_help') }}</p>
                            <div class="form-group">
                                <label for="postman_environment_payload">{{ __('messages.endpoints.postman_environment_payload') }}</label>
                                <textarea name="postman_environment_payload" id="postman_environment_payload" class="form-control code-input" rows="8">{{ old('postman_environment_payload') }}</textarea>
                                <span class="help-block">{{ __('messages.endpoints.postman_environment_help') }}</span>
                                <button type="button" class="btn btn-xs btn-default" id="use-postman-environment-sample">{{ __('messages.endpoints.use_postman_environment_sample') }}</button>
                            </div>
                            <div class="form-group">
                                <label for="postman_globals_payload">{{ __('messages.endpoints.postman_globals_payload') }}</label>
                                <textarea name="postman_globals_payload" id="postman_globals_payload" class="form-control code-input" rows="6">{{ old('postman_globals_payload') }}</textarea>
                                <span class="help-block">{{ __('messages.endpoints.postman_globals_help') }}</span>
                                <button type="button" class="btn btn-xs btn-default" id="use-postman-globals-sample">{{ __('messages.endpoints.use_postman_globals_sample') }}</button>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="checkbox checkbox-info"><label><input type="checkbox" name="postman_create_environment" value="1" @checked(old('postman_create_environment', '1'))> {{ __('messages.endpoints.postman_create_environment') }}</label></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="checkbox checkbox-info"><label><input type="checkbox" name="postman_create_auth_profile" value="1" @checked(old('postman_create_auth_profile', '1'))> {{ __('messages.endpoints.postman_create_auth_profile') }}</label></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="checkbox checkbox-info"><label><input type="checkbox" name="postman_create_assertions" value="1" @checked(old('postman_create_assertions', '1'))> {{ __('messages.endpoints.postman_create_assertions') }}</label></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="checkbox checkbox-info"><label><input type="checkbox" name="postman_create_test_suites" value="1" @checked(old('postman_create_test_suites'))> {{ __('messages.endpoints.postman_create_test_suites') }}</label></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-info" formaction="{{ route('projects.endpoints.import.preview', $project) }}">{{ __('messages.import_preview.preview_button') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.endpoints.import_button') }}</button>
                    <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
    (function () {
        var button = document.getElementById('use-openapi-sample');
        var format = document.getElementById('format');
        var payload = document.getElementById('payload');
        var sample = @json($sampleOpenApiPayload);
        var yamlSample = @json($sampleOpenApiYamlPayload);
        var postmanSample = @json($samplePostmanPayload);
        var postmanEnvironmentSample = @json($samplePostmanEnvironmentPayload);
        var postmanGlobalsSample = @json($samplePostmanGlobalsPayload);
        var yamlButton = document.getElementById('use-openapi-yaml-sample');
        var postmanButton = document.getElementById('use-postman-sample');
        var postmanEnvironmentButton = document.getElementById('use-postman-environment-sample');
        var postmanGlobalsButton = document.getElementById('use-postman-globals-sample');
        var postmanEnvironmentPayload = document.getElementById('postman_environment_payload');
        var postmanGlobalsPayload = document.getElementById('postman_globals_payload');
        if (!button || !format || !payload) {
            return;
        }
        button.addEventListener('click', function () {
            format.value = 'openapi';
            payload.value = sample;
            document.querySelector('input[name="import_source"][value="paste"]').checked = true;
            payload.focus();
        });
        if (yamlButton) {
            yamlButton.addEventListener('click', function () {
                format.value = 'openapi';
                payload.value = yamlSample;
                document.querySelector('input[name="import_source"][value="paste"]').checked = true;
                payload.focus();
            });
        }
        if (postmanButton) {
            postmanButton.addEventListener('click', function () {
                format.value = 'postman';
                payload.value = postmanSample;
                if (postmanEnvironmentPayload) {
                    postmanEnvironmentPayload.value = postmanEnvironmentSample;
                }
                if (postmanGlobalsPayload) {
                    postmanGlobalsPayload.value = postmanGlobalsSample;
                }
                document.querySelector('input[name="import_source"][value="paste"]').checked = true;
                payload.focus();
            });
        }
        if (postmanEnvironmentButton && postmanEnvironmentPayload) {
            postmanEnvironmentButton.addEventListener('click', function () {
                format.value = 'postman';
                postmanEnvironmentPayload.value = postmanEnvironmentSample;
                postmanEnvironmentPayload.focus();
            });
        }
        if (postmanGlobalsButton && postmanGlobalsPayload) {
            postmanGlobalsButton.addEventListener('click', function () {
                format.value = 'postman';
                postmanGlobalsPayload.value = postmanGlobalsSample;
                postmanGlobalsPayload.focus();
            });
        }
    })();
</script>
@endpush
