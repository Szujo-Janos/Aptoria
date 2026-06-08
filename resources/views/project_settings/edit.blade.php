@extends('layouts.app')

@section('title', __('messages.project_settings.title'))

@section('content')
@php
    $get = fn (string $group, string $key, mixed $fallback = null) => old(str_replace('.', '_', $key), $settings[$group][$key]['value'] ?? $fallback);
    $checked = fn (string $group, string $key, bool $fallback = false) => (bool) ($settings[$group][$key]['value'] ?? $fallback);
    $csv = fn (string $group, string $key) => is_array($settings[$group][$key]['value'] ?? null)
        ? implode("\n", $settings[$group][$key]['value'])
        : (string) ($settings[$group][$key]['value'] ?? '');
@endphp

<div class="row">
    <div class="col-lg-9">
        <form method="POST" action="{{ route('projects.settings.update', $project) }}">
            @csrf
            <div class="hpanel">
                <div class="panel-heading hbuilt">
                    <div class="panel-tools">
                        <a href="{{ route('projects.settings.export', $project) }}" class="btn btn-xs btn-info">{{ __('messages.project_settings.export') }}</a>
                        <button type="submit" class="btn btn-xs btn-primary">{{ __('messages.project_settings.save_settings') }}</button>
                    </div>
                    {{ __('messages.project_settings.heading') }} — {{ $project->name }}
                </div>
                <div class="panel-body">
                    <p class="text-muted">{{ __('messages.project_settings.intro') }}</p>

                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#project-settings-general">{{ __('messages.project_settings.groups.general') }}</a></li>
                        <li><a data-toggle="tab" href="#project-settings-scan">{{ __('messages.project_settings.groups.scan_defaults') }}</a></li>
                        <li><a data-toggle="tab" href="#project-settings-safety">{{ __('messages.project_settings.groups.scan_safety') }}</a></li>
                        <li><a data-toggle="tab" href="#project-settings-path-parameters">{{ __('messages.path_parameters.title') }}</a></li>
                        <li><a data-toggle="tab" href="#project-settings-risk">{{ __('messages.project_settings.groups.risk_overrides') }}</a></li>
                        <li><a data-toggle="tab" href="#project-settings-retention">{{ __('messages.project_settings.groups.data_retention') }}</a></li>
                    </ul>

                    <div class="tab-content m-t-lg">
                        <div id="project-settings-general" class="tab-pane active">
                            <div class="form-group">
                                <label>{{ __('messages.common.notes') }}</label>
                                <textarea class="form-control" rows="5" name="project_notes">{{ old('project_notes', $get('general', 'project.notes', '')) }}</textarea>
                                <span class="help-block">{{ __('messages.project_settings.help.notes') }}</span>
                            </div>
                        </div>

                        <div id="project-settings-scan" class="tab-pane">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="scan_enabled" value="1" @checked($checked('scan_defaults', 'scan.enabled', true))>
                                            {{ __('messages.project_settings.fields.scan_enabled') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('messages.project_settings.fields.max_endpoints_per_scan') }}</label>
                                        <input type="number" min="1" max="2000" class="form-control" name="scan_max_endpoints_per_scan" value="{{ $get('scan_defaults', 'scan.max_endpoints_per_scan', 100) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('messages.project_settings.fields.default_environment') }}</label>
                                        <select class="form-control" name="scan_default_environment_id">
                                            <option value="">{{ __('messages.project_settings.use_endpoint_environment') }}</option>
                                            @foreach($project->environments as $environment)
                                                <option value="{{ $environment->id }}" @selected((string) $get('scan_defaults', 'scan.default_environment_id', '') === (string) $environment->id)>
                                                    {{ $environment->name }} — {{ $environment->base_url }}{{ $environment->is_production ? ' ['.__('messages.environments.production').']' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('messages.project_settings.fields.default_auth_profile') }}</label>
                                        <select class="form-control" name="scan_default_auth_profile_id">
                                            <option value="">{{ __('messages.project_settings.use_endpoint_auth') }}</option>
                                            @foreach($project->authProfiles as $authProfile)
                                                <option value="{{ $authProfile->id }}" @selected((string) $get('scan_defaults', 'scan.default_auth_profile_id', '') === (string) $authProfile->id)>
                                                    {{ $authProfile->name }} — {{ $authProfile->type_label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="project-settings-safety" class="tab-pane">
                            <div class="alert alert-warning">
                                <strong>{{ __('messages.project_settings.safety_warning_title') }}</strong>
                                {{ __('messages.project_settings.safety_warning_text') }}
                            </div>

                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="scan_allow_private_networks" value="1" @checked($checked('scan_safety', 'scan.allow_private_networks', false))>
                                    {{ __('messages.project_settings.fields.allow_private_networks') }}
                                </label>
                                <span class="help-block">{{ __('messages.project_settings.help.allow_private_networks') }}</span>
                            </div>

                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="scan_require_confirmation" value="1" @checked($checked('scan_safety', 'scan.require_confirmation', true))>
                                    {{ __('messages.project_settings.fields.require_confirmation') }}
                                </label>
                            </div>
                        </div>


                        <div id="project-settings-path-parameters" class="tab-pane">
                            <div class="alert alert-info">
                                <strong>{{ __('messages.path_parameters.title') }}</strong>
                                {{ __('messages.path_parameters.project_help') }}
                            </div>
                            <div class="form-group">
                                <label for="path_parameter_defaults">{{ __('messages.path_parameters.project_defaults') }}</label>
                                <textarea name="path_parameter_defaults" id="path_parameter_defaults" class="form-control" rows="8" placeholder="id=1&#10;userId=1&#10;postId=1">{{ old('path_parameter_defaults', $pathParameterDefaults ?? '') }}</textarea>
                                <span class="help-block">{{ __('messages.path_parameters.textarea_help') }}</span>
                            </div>
                        </div>

                        <div id="project-settings-risk" class="tab-pane">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('messages.project_settings.fields.sensitive_keywords') }}</label>
                                        <textarea class="form-control" rows="8" name="risk_sensitive_keywords">{{ old('risk_sensitive_keywords', $csv('risk_overrides', 'risk.sensitive_keywords')) }}</textarea>
                                        <span class="help-block">{{ __('messages.project_settings.help.keyword_override') }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('messages.project_settings.fields.internal_keywords') }}</label>
                                        <textarea class="form-control" rows="8" name="risk_internal_keywords">{{ old('risk_internal_keywords', $csv('risk_overrides', 'risk.internal_keywords')) }}</textarea>
                                        <span class="help-block">{{ __('messages.project_settings.help.keyword_override') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="project-settings-retention" class="tab-pane">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="scan_store_response_body_preview" value="1" @checked($checked('data_retention', 'scan.store_response_body_preview', true))>
                                    {{ __('messages.project_settings.fields.store_response_body_preview') }}
                                </label>
                                <span class="help-block">{{ __('messages.project_settings.help.response_preview') }}</span>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary">{{ __('messages.project_settings.save_settings') }}</button>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-3">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.project_settings.effective_scope') }}</div>
            <div class="panel-body">
                <p>{{ __('messages.project_settings.scope_text') }}</p>
                <ul>
                    <li>{{ __('messages.project_settings.safe_methods_locked') }}</li>
                    <li>{{ __('messages.project_settings.project_overrides_global') }}</li>
                </ul>
            </div>
        </div>

        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.project_settings.reset_title') }}</div>
            <div class="panel-body">
                <p>{{ __('messages.project_settings.reset_help') }}</p>
                <form method="POST" action="{{ route('projects.settings.reset', $project) }}" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.project_settings.confirm_reset') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.project_settings.reset_button') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('messages.project_settings.reset_button') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.assertion-rules.create', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.assertions.add_rule') }}</a>
                </div>
                {{ __('messages.assertions.default_rules') }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.assertions.default_rules_help') }}</p>
                @if($assertionRules->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.assertions.no_default_rules') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                            <tr>
                                <th>{{ __('messages.assertions.rule_key') }}</th>
                                <th>{{ __('messages.assertions.operator') }}</th>
                                <th>{{ __('messages.assertions.target_path') }}</th>
                                <th>{{ __('messages.assertions.expected_value') }}</th>
                                <th>{{ __('messages.assertions.severity') }}</th>
                                <th>{{ __('messages.assertions.enabled') }}</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($assertionRules as $rule)
                                <tr>
                                    <td>
                                        <strong>{{ $rule->rule_label }}</strong>
                                        @if(\App\Models\EndpointAssertionRule::isRepeatable($rule->rule_key))
                                            <span class="label label-info">{{ __('messages.assertions.repeatable') }}</span>
                                        @endif
                                        <br><small class="text-muted">{{ $rule->rule_help }}</small>
                                    </td>
                                    <td>{{ $rule->operator_label }}</td>
                                    <td>{{ $rule->target_path ?: __('messages.common.not_available') }}</td>
                                    <td>{{ $rule->expected_value ?: __('messages.common.not_available') }}</td>
                                    <td><span class="label label-{{ $rule->severity_css }}">{{ $rule->severity_label }}</span></td>
                                    <td>{{ $rule->enabled ? __('messages.common.yes') : __('messages.common.no') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('projects.assertion-rules.edit', [$project, $rule]) }}" class="btn btn-xs btn-default">{{ __('messages.common.edit') }}</a>
                                        <form method="POST" action="{{ route('projects.assertion-rules.destroy', [$project, $rule]) }}" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.assertions.confirm_delete') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">{{ __('messages.common.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
