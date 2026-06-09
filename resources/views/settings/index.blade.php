@extends('layouts.app')

@section('title', __('messages.settings.title'))

@section('content')
@php
    $fieldName = fn (string $key): string => str_replace('.', '_', $key);
    $translated = function (string $translationKey, string $fallback): string {
        $text = __($translationKey);
        return $text === $translationKey ? __('messages.settings.translation_missing', ['key' => $fallback]) : $text;
    };
    $groupLabel = fn (string $group): string => $translated('messages.settings.groups.'.$group, $group);
    $valueLabel = fn (string $option): string => $translated('messages.settings.values.'.$option, $option);
    $sections = collect($settings)->map(fn (array $group): array => array_keys($group))->all();
    $value = function (string $group, string $key, mixed $fallback = null) use ($settings, $fieldName): mixed {
        return old($fieldName($key), $settings[$group][$key]['value'] ?? $fallback);
    };
    $checked = function (string $group, string $key, bool $fallback = false) use ($settings): bool {
        return (bool) ($settings[$group][$key]['value'] ?? $fallback);
    };
    $csvValue = function (string $group, string $key) use ($settings): string {
        $raw = $settings[$group][$key]['value'] ?? '';
        return is_array($raw) ? implode("\n", $raw) : (string) $raw;
    };
@endphp

<form method="POST" action="{{ route('settings.update') }}" id="settings-main-form">
    @csrf
    <div class="row">
        <div class="col-lg-9">
            <div class="hpanel">
                <div class="panel-heading hbuilt">
                    <div class="panel-tools">
                        <a href="{{ route('settings.export') }}" class="btn btn-xs btn-info">{{ __('messages.settings.export') }}</a>
                        <button type="submit" class="btn btn-xs btn-primary">{{ __('messages.settings.save_settings') }}</button>
                    </div>
                    {{ __('messages.settings.center_title') }}
                </div>
                <div class="panel-body">
                    <p class="text-muted">{{ __('messages.settings.center_intro') }}</p>

                    <ul class="nav nav-tabs aptoria-settings-tabs">
                        @foreach($sections as $group => $keys)
                            <li class="{{ $loop->first ? 'active' : '' }}">
                                <a data-toggle="tab" href="#settings-{{ $group }}">{{ $groupLabel($group) }}</a>
                            </li>
                        @endforeach
                        <li><a data-toggle="tab" href="#settings-security-status">{{ __('messages.security_status.title') }}</a></li>
                        <li><a data-toggle="tab" href="#settings-system">{{ __('messages.settings.groups.system_info') }}</a></li>
                        <li><a data-toggle="tab" href="#settings-database-maintenance">{{ __('messages.data_maintenance.title') }}</a></li>
                    </ul>

                    <div class="tab-content m-t-lg">
                        @foreach($sections as $group => $keys)
                            <div id="settings-{{ $group }}" class="tab-pane {{ $loop->first ? 'active' : '' }}">
                                @php
                                    $groupHelpKey = 'messages.settings.group_help.'.$group;
                                    $groupHelp = __($groupHelpKey);
                                    $groupHelp = $groupHelp === $groupHelpKey ? __('messages.settings.translation_missing', ['key' => 'group_help.'.$group]) : $groupHelp;
                                @endphp
                                <div class="row m-b-md">
                                    <div class="col-sm-8">
                                        <h4 class="m-t-none">{{ $groupLabel($group) }}</h4>
                                        <p class="text-muted">{{ $groupHelp }}</p>
                                    </div>
                                    <div class="col-sm-4 text-right">
                                        <button type="submit"
                                                form="settings-reset-{{ $group }}-form"
                                                class="btn btn-default btn-sm">
                                            <i class="fa fa-refresh"></i> {{ __('messages.settings.reset_group_button') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="row">
                                    @foreach($keys as $key)
                                        @php
                                            $setting = $settings[$group][$key] ?? ['type' => 'string', 'description' => '', 'options' => []];
                                            $name = $fieldName($key);
                                            $type = $setting['type'];
                                            $labelKey = 'messages.settings.fields.'.$fieldName($key);
                                            $labelText = $translated($labelKey, $key);
                                            $helpText = __('messages.settings.field_help.'.$fieldName($key));
                                            $isHelpTranslated = $helpText !== 'messages.settings.field_help.'.$fieldName($key);
                                            $hasHelpText = $isHelpTranslated && trim((string) $helpText) !== '';
                                            $options = $setting['options'] ?? [];
                                        @endphp

                                        <div class="col-md-6">
                                            @if($type === 'boolean')
                                                <div class="checkbox aptoria-settings-checkbox">
                                                    <label>
                                                        <input type="checkbox" name="{{ $name }}" value="1" @checked($checked($group, $key, (bool)($setting['value'] ?? false)))>
                                                        <strong>{{ $labelText }}</strong>
                                                    </label>
                                                    @if($hasHelpText)
                                                        <span class="help-block">{{ $helpText }}</span>
                                                    @endif
                                                </div>
                                            @elseif($type === 'csv')
                                                <div class="form-group">
                                                    <label>{{ $labelText }}</label>
                                                    <textarea name="{{ $name }}" rows="4" class="form-control">{{ old($name, $csvValue($group, $key)) }}</textarea>
                                                    @if($hasHelpText)
                                                        <span class="help-block">{{ $helpText }}</span>
                                                    @endif
                                                </div>
                                            @elseif(! empty($options))
                                                <div class="form-group">
                                                    <label>{{ $labelText }}</label>
                                                    <select name="{{ $name }}" class="form-control">
                                                        @foreach($options as $optionValue)
                                                            <option value="{{ $optionValue }}" @selected((string) $value($group, $key) === (string) $optionValue)>{{ $valueLabel((string) $optionValue) }}</option>
                                                        @endforeach
                                                    </select>
                                                    @if($hasHelpText)
                                                        <span class="help-block">{{ $helpText }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="form-group">
                                                    <label>{{ $labelText }}</label>
                                                    <input type="{{ $type === 'integer' ? 'number' : 'text' }}"
                                                           class="form-control"
                                                           name="{{ $name }}"
                                                           value="{{ $value($group, $key) }}">
                                                    @if($hasHelpText)
                                                        <span class="help-block">{{ $helpText }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div id="settings-security-status" class="tab-pane">
                            <div class="row m-b-md">
                                <div class="col-sm-8">
                                    <h4 class="m-t-none">{{ __('messages.security_status.title') }}</h4>
                                    <p class="text-muted">{{ __('messages.security_status.intro') }}</p>
                                </div>
                                <div class="col-sm-4 text-right">
                                    <span class="label label-{{ $securitySummary['css'] }}">{{ strtoupper($securitySummary['status']) }}</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ __('messages.security_status.check') }}</th>
                                            <th>{{ __('messages.security_status.status') }}</th>
                                            <th>{{ __('messages.security_status.detail') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($securityChecks as $check)
                                        <tr>
                                            <td><strong>{{ $check['label'] }}</strong></td>
                                            <td><span class="label label-{{ $check['css'] }}">{{ strtoupper($check['status']) }}</span></td>
                                            <td>{{ $check['detail'] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="settings-system" class="tab-pane">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <tbody>
                                    @foreach($systemInfo as $label => $infoValue)
                                        <tr><th>{{ $label }}</th><td>{{ $infoValue }}</td></tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="settings-database-maintenance" class="tab-pane">
                            <div class="row m-b-md">
                                <div class="col-sm-8">
                                    <h4 class="m-t-none">{{ __('messages.data_maintenance.title') }}</h4>
                                    <p class="text-muted">{{ __('messages.data_maintenance.intro') }}</p>
                                </div>
                                <div class="col-sm-4 text-right">
                                    <a href="{{ route('settings.database.export') }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-download"></i> {{ __('messages.data_maintenance.export_button') }}
                                    </a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="hpanel hblue">
                                        <div class="panel-heading hbuilt">{{ __('messages.data_maintenance.export_title') }}</div>
                                        <div class="panel-body">
                                            <p>{{ __('messages.data_maintenance.export_help') }}</p>
                                            <ul class="m-b-md">
                                                <li>{{ __('messages.data_maintenance.driver') }}: <strong>{{ $databaseSummary['driver'] }}</strong></li>
                                                <li>{{ __('messages.data_maintenance.tables') }}: <strong>{{ $databaseSummary['table_count'] }}</strong></li>
                                                <li>{{ __('messages.data_maintenance.rows') }}: <strong>{{ $databaseSummary['row_count'] }}</strong></li>
                                                <li>{{ __('messages.data_maintenance.schema_hash') }}: <code>{{ substr($databaseSummary['schema_hash'], 0, 16) }}</code></li>
                                            </ul>
                                            <a href="{{ route('settings.database.export') }}" class="btn btn-primary">{{ __('messages.data_maintenance.export_button') }}</a>
                                            <p class="help-block m-t-sm">{{ __('messages.data_maintenance.export_note') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="hpanel hyellow">
                                        <div class="panel-heading hbuilt">{{ __('messages.data_maintenance.import_title') }}</div>
                                        <div class="panel-body">
                                            <p>{{ __('messages.data_maintenance.import_help') }}</p>
                                            <div class="form-group">
                                                <label>{{ __('messages.data_maintenance.import_file') }}</label>
                                                <input type="file" name="database_export" class="form-control" form="database-import-form" accept="application/json,.json" required>
                                            </div>
                                            <div class="form-group">
                                                <label>{{ __('messages.data_maintenance.confirm_import_label') }}</label>
                                                <input type="text" name="confirm_import" class="form-control" form="database-import-form" placeholder="IMPORT DATABASE" required>
                                                <span class="help-block">{{ __('messages.data_maintenance.confirm_import_help') }}</span>
                                            </div>
                                            <button type="submit" form="database-import-form" class="btn btn-warning">{{ __('messages.data_maintenance.import_button') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="hpanel hred">
                                <div class="panel-heading hbuilt">{{ __('messages.data_maintenance.hard_reset_title') }}</div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <p>{{ __('messages.data_maintenance.hard_reset_help') }}</p>
                                            <p class="text-danger"><strong>{{ __('messages.data_maintenance.hard_reset_warning') }}</strong></p>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{ __('messages.data_maintenance.confirm_hard_reset_label') }}</label>
                                                <input type="text" name="confirm_hard_reset" class="form-control" form="hard-reset-form" placeholder="HARD RESET" required>
                                            </div>
                                            <button type="submit" form="hard-reset-form" class="btn btn-danger btn-block">{{ __('messages.data_maintenance.hard_reset_button') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary">{{ __('messages.settings.save_settings') }}</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                    <a href="{{ route('settings.export') }}" class="btn btn-info">{{ __('messages.settings.export') }}</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="hpanel hblue">
                <div class="panel-heading hbuilt">{{ __('messages.settings.current_scope') }}</div>
                <div class="panel-body">
                    <p>{{ __('messages.settings.scope_text') }}</p>
                    <ul class="m-b-sm">
                        @foreach($sections as $group => $keys)
                            <li>{{ $groupLabel($group) }} <span class="text-muted">({{ count($keys) }})</span></li>
                        @endforeach
                    </ul>
                    <p class="m-b-none text-muted">{{ __('messages.settings.http_note') }}</p>
                </div>
            </div>
            <div class="hpanel hyellow">
                <div class="panel-heading hbuilt">{{ __('messages.settings.active_summary') }}</div>
                <div class="panel-body">
                    <p><strong>{{ __('messages.settings.fields.scan_timeout_seconds') }}:</strong> {{ $value('scan', 'scan.timeout_seconds') }}s</p>
                    <p><strong>{{ __('messages.settings.fields.scan_rate_limit_ms') }}:</strong> {{ $value('scan', 'scan.rate_limit_ms') }}ms</p>
                    <p><strong>{{ __('messages.settings.fields.assertions_enabled') }}:</strong> {{ $checked('assertions', 'assertions.enabled', true) ? __('messages.common.yes') : __('messages.common.no') }}</p>
                    <p><strong>{{ __('messages.settings.fields.security_hide_tokens_in_exports') }}:</strong> {{ $checked('security', 'security.hide_tokens_in_exports', true) ? __('messages.common.yes') : __('messages.common.no') }}</p>
                    <p><strong>{{ __('messages.settings.fields.release_security_audit_must_pass') }}:</strong> {{ $checked('release_readiness', 'release.security_audit_must_pass', true) ? __('messages.common.yes') : __('messages.common.no') }}</p>
                </div>
            </div>
            <div class="hpanel h{{ $securitySummary['css'] === 'danger' ? 'red' : ($securitySummary['css'] === 'warning' ? 'yellow' : 'green') }}">
                <div class="panel-heading hbuilt">{{ __('messages.security_status.title') }}</div>
                <div class="panel-body">
                    <p><strong>{{ __('messages.security_status.summary') }}:</strong> <span class="label label-{{ $securitySummary['css'] }}">{{ strtoupper($securitySummary['status']) }}</span></p>
                    <p>{{ __('messages.security_status.failed') }}: {{ $securitySummary['failed'] }} / {{ __('messages.security_status.warnings') }}: {{ $securitySummary['warnings'] }}</p>
                    <p class="m-b-none text-muted">{{ __('messages.security_status.settings_hint') }}</p>
                </div>
            </div>
            <div class="hpanel hred">
                <div class="panel-heading hbuilt">{{ __('messages.settings.reset_title') }}</div>
                <div class="panel-body">
                    <p>{{ __('messages.settings.reset_help') }}</p>
                    <button type="submit" form="settings-reset-form" class="btn btn-danger btn-sm">{{ __('messages.settings.reset_button') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>

<form id="settings-reset-form" method="POST" action="{{ route('settings.reset') }}" class="hide" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.settings.confirm_reset') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.settings.reset_button') }}">@csrf</form>
@foreach($sections as $group => $keys)
    <form id="settings-reset-{{ $group }}-form" method="POST" action="{{ route('settings.reset-group', $group) }}" class="hide" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.settings.confirm_reset_group', ['group' => $groupLabel($group)]) }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.settings.reset_group_button') }}">@csrf</form>
@endforeach
<form id="database-import-form" method="POST" action="{{ route('settings.database.import') }}" enctype="multipart/form-data" class="hide" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.data_maintenance.confirm_import_dialog') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.data_maintenance.import_button') }}">@csrf</form>
<form id="hard-reset-form" method="POST" action="{{ route('settings.hard-reset') }}" class="hide" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.data_maintenance.hard_reset_title') }}" data-aptoria-confirm-text="{{ __('messages.data_maintenance.confirm_hard_reset_dialog') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.data_maintenance.hard_reset_button') }}">@csrf</form>
@endsection
