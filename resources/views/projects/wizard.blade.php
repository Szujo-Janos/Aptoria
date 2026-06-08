@extends('layouts.app')

@section('title', __('messages.wizard.title'))

@section('content')
<form method="POST" action="{{ route('projects.wizard.store') }}">
    @csrf
    <div class="row">
        <div class="col-lg-12">
            <div class="hpanel hgreen">
                <div class="panel-heading hbuilt">
                    <div class="panel-tools">
                        <a href="{{ route('projects.create') }}" class="btn btn-xs btn-default">{{ __('messages.projects.create_title') }}</a>
                    </div>
                    {{ __('messages.wizard.title') }}
                </div>
                <div class="panel-body">
                    <h3 class="m-t-none">{{ __('messages.wizard.heading') }}</h3>
                    <p class="text-muted">{{ __('messages.wizard.intro') }}</p>

                    <div class="row text-center m-t-md">
                        @foreach(__('messages.wizard.steps') as $step)
                            <div class="col-md-2 col-sm-4 m-b-sm">
                                <div class="well well-sm m-b-none"><strong>{{ $loop->iteration }}</strong><br><small>{{ $step }}</small></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="hpanel">
                <div class="panel-heading hbuilt">1. {{ __('messages.wizard.project_basics') }}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="name">{{ __('messages.common.name') }}</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required maxlength="150" placeholder="{{ __('messages.projects.wizard_name_placeholder') }}">
                    </div>
                    <div class="form-group">
                        <label for="base_url">{{ __('messages.common.base_url') }}</label>
                        <input type="url" name="base_url" id="base_url" class="form-control" value="{{ old('base_url') }}" required placeholder="https://jsonplaceholder.typicode.com">
                        <span class="help-block">{{ __('messages.wizard.base_url_help') }}</span>
                    </div>
                    <div class="form-group">
                        <label for="description">{{ __('messages.common.description') }}</label>
                        <textarea name="description" id="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="checkbox checkbox-success">
                        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1'))> {{ __('messages.projects.is_active') }}</label>
                    </div>
                </div>
            </div>

            <div class="hpanel hblue">
                <div class="panel-heading hbuilt">2. {{ __('messages.wizard.environment') }}</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="environment_name">{{ __('messages.common.name') }}</label>
                                <input type="text" name="environment_name" id="environment_name" class="form-control" value="{{ old('environment_name', 'staging') }}" required maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="environment_base_url">{{ __('messages.common.base_url') }}</label>
                                <input type="url" name="environment_base_url" id="environment_base_url" class="form-control" value="{{ old('environment_base_url') }}" required placeholder="https://jsonplaceholder.typicode.com">
                            </div>
                        </div>
                    </div>
                    <div class="checkbox checkbox-warning">
                        <label><input type="checkbox" name="environment_is_production" value="1" @checked(old('environment_is_production'))> {{ __('messages.environments.is_production') }}</label>
                        <span class="help-block">{{ __('messages.wizard.production_help') }}</span>
                    </div>
                </div>
            </div>

            <div class="hpanel hyellow">
                <div class="panel-heading hbuilt">3. {{ __('messages.wizard.auth_profile') }}</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="auth_name">{{ __('messages.common.name') }}</label>
                                <input type="text" name="auth_name" id="auth_name" class="form-control" value="{{ old('auth_name', __('messages.auth_profiles.no_auth')) }}" required maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="auth_type">{{ __('messages.common.type') }}</label>
                                <select name="auth_type" id="auth_type" class="form-control" required>
                                    @foreach($authTypes as $type)
                                        <option value="{{ $type }}" @selected(old('auth_type', 'none') === $type)>{{ __('messages.auth_profiles.types.'.$type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="token">{{ __('messages.auth_profiles.token') }}</label>
                                <input type="password" name="token" id="token" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">{{ __('messages.auth_profiles.username') }}</label>
                                <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">{{ __('messages.auth_profiles.password') }}</label>
                                <input type="password" name="password" id="password" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="header_name">{{ __('messages.auth_profiles.header_name') }}</label>
                                <input type="text" name="header_name" id="header_name" class="form-control" value="{{ old('header_name') }}" placeholder="X-API-Key">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="header_value">{{ __('messages.auth_profiles.header_value') }}</label>
                        <input type="password" name="header_value" id="header_value" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="auth_notes">{{ __('messages.common.notes') }}</label>
                        <textarea name="auth_notes" id="auth_notes" rows="3" class="form-control">{{ old('auth_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="hpanel hgreen">
                <div class="panel-heading hbuilt">4. {{ __('messages.wizard.endpoint_import') }}</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="format">{{ __('messages.endpoints.import_format') }}</label>
                                <select name="format" id="format" class="form-control" required>
                                    <option value="csv" @selected(old('format', 'csv') === 'csv')>CSV</option>
                                    <option value="json" @selected(old('format') === 'json')>JSON</option>
                                    <option value="openapi" @selected(old('format') === 'openapi')>{{ __('messages.endpoints.import_format_openapi') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>{{ __('messages.endpoints.import_source') }}</label>
                                <div class="radio radio-info m-t-none">
                                    <label><input type="radio" name="import_source" value="paste" @checked(old('import_source', 'paste') !== 'url')> {{ __('messages.endpoints.import_source_paste') }}</label>
                                </div>
                                <div class="radio radio-info">
                                    <label><input type="radio" name="import_source" value="url" @checked(old('import_source') === 'url')> {{ __('messages.endpoints.import_source_url') }}</label>
                                </div>
                                <input type="url" name="source_url" id="source_url" class="form-control" value="{{ old('source_url') }}" placeholder="https://example.com/openapi.yaml">
                                <span class="help-block">{{ __('messages.endpoints.source_url_help') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="payload">{{ __('messages.endpoints.import_payload') }}</label>
                        <textarea name="payload" id="payload" class="form-control code-input" rows="12">{{ old('payload', $samplePayload) }}</textarea>
                        <span class="help-block">{{ __('messages.endpoints.import_help') }} {{ __('messages.endpoints.openapi_import_help') }}</span>
                        <button type="button" class="btn btn-xs btn-default" id="wizard-openapi-sample">{{ __('messages.endpoints.use_openapi_sample') }}</button>
                        <button type="button" class="btn btn-xs btn-default" id="wizard-openapi-yaml-sample">{{ __('messages.endpoints.use_openapi_yaml_sample') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="hpanel hred">
                <div class="panel-heading hbuilt">5. {{ __('messages.wizard.default_assertions') }}</div>
                <div class="panel-body">
                    <p class="text-muted">{{ __('messages.wizard.assertion_help') }}</p>

                    <div class="checkbox checkbox-success">
                        <label><input type="checkbox" name="assert_status_code" value="1" @checked(old('assert_status_code', '1'))> {{ __('messages.assertions.rule_keys.status_code') }}</label>
                    </div>
                    <input type="number" name="assert_status_code_value" class="form-control m-b" value="{{ old('assert_status_code_value', 200) }}" min="100" max="599">

                    <div class="checkbox checkbox-warning">
                        <label><input type="checkbox" name="assert_response_time" value="1" @checked(old('assert_response_time', '1'))> {{ __('messages.assertions.rule_keys.max_response_time_ms') }}</label>
                    </div>
                    <input type="number" name="assert_response_time_value" class="form-control m-b" value="{{ old('assert_response_time_value', 2500) }}" min="1">

                    <div class="checkbox checkbox-success">
                        <label><input type="checkbox" name="assert_required_content_type" value="1" @checked(old('assert_required_content_type', '1'))> {{ __('messages.assertions.rule_keys.required_header') }}: content-type</label>
                    </div>

                    <div class="checkbox checkbox-success">
                        <label><input type="checkbox" name="assert_https" value="1" @checked(old('assert_https', '1'))> {{ __('messages.assertions.rule_keys.https_required') }}</label>
                    </div>

                    <div class="checkbox checkbox-warning">
                        <label><input type="checkbox" name="assert_max_risk" value="1" @checked(old('assert_max_risk', '1'))> {{ __('messages.assertions.rule_keys.max_risk_score') }}</label>
                    </div>
                    <input type="number" name="assert_max_risk_value" class="form-control m-b" value="{{ old('assert_max_risk_value', 70) }}" min="0" max="100">
                </div>
            </div>

            <div class="hpanel hgreen">
                <div class="panel-heading hbuilt">6. {{ __('messages.wizard.finish') }}</div>
                <div class="panel-body">
                    <p>{{ __('messages.wizard.finish_help') }}</p>
                    <button type="submit" class="btn btn-success btn-block btn-lg">{{ __('messages.wizard.create_button') }}</button>
                    <a href="{{ route('projects.index') }}" class="btn btn-default btn-block m-t-sm">{{ __('messages.common.cancel') }}</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        var baseInput = document.getElementById('base_url');
        var envInput = document.getElementById('environment_base_url');
        if (baseInput && envInput) {
            baseInput.addEventListener('change', function () {
                if (!envInput.value) {
                    envInput.value = baseInput.value;
                }
            });
        }

        var sampleButton = document.getElementById('wizard-openapi-sample');
        var format = document.getElementById('format');
        var payload = document.getElementById('payload');
        var openApiSample = @json($sampleOpenApiPayload);
        var openApiYamlSample = @json($sampleOpenApiYamlPayload);
        var yamlSampleButton = document.getElementById('wizard-openapi-yaml-sample');
        if (sampleButton && format && payload) {
            sampleButton.addEventListener('click', function () {
                format.value = 'openapi';
                payload.value = openApiSample;
                document.querySelector('input[name="import_source"][value="paste"]').checked = true;
                payload.focus();
            });
        }
        if (yamlSampleButton && format && payload) {
            yamlSampleButton.addEventListener('click', function () {
                format.value = 'openapi';
                payload.value = openApiYamlSample;
                document.querySelector('input[name="import_source"][value="paste"]').checked = true;
                payload.focus();
            });
        }
    })();
</script>
@endpush
