@php
    $selectedRuleKey = old('rule_key', $rule->rule_key);
    $selectedOperator = old('operator', $rule->operator);
@endphp

<input type="hidden" name="project_id" value="{{ $project->id }}">

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="endpoint_id">{{ __('messages.assertions.scope') }}</label>
            <select class="form-control" id="endpoint_id" name="endpoint_id">
                <option value="">{{ __('messages.assertions.project_default_scope') }}</option>
                @foreach($project->endpoints->sortBy([['method', 'asc'], ['path', 'asc']]) as $endpoint)
                    <option value="{{ $endpoint->id }}" @selected((string) old('endpoint_id', $rule->endpoint_id) === (string) $endpoint->id)>
                        {{ $endpoint->method }} {{ $endpoint->path }}
                    </option>
                @endforeach
            </select>
            <span class="help-block">{{ __('messages.assertions.scope_help') }}</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="rule_key">{{ __('messages.assertions.rule_key') }}</label>
            <select class="form-control" id="rule_key" name="rule_key" required>
                @foreach(\App\Models\EndpointAssertionRule::RULE_KEYS as $key)
                    <option value="{{ $key }}" @selected($selectedRuleKey === $key)>{{ __('messages.assertions.rule_keys.'.$key) }}</option>
                @endforeach
            </select>
            <span class="help-block">{{ __('messages.assertions.rule_selector_help') }}</span>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <strong>{{ __('messages.assertions.rule_help_title') }}</strong>
    <p class="m-b-sm">{{ __('messages.assertions.precedence_help') }}</p>
    <div class="row">
        @foreach(\App\Models\EndpointAssertionRule::RULE_KEYS as $key)
            <div class="col-md-6 m-b-sm">
                <strong>{{ __('messages.assertions.rule_keys.'.$key) }}</strong><br>
                <small>{{ __('messages.assertions.rule_help.'.$key) }}</small><br>
                <small class="text-muted">{{ __('messages.assertions.example_label') }}: <code>{{ __('messages.assertions.example_values.'.$key) }}</code></small>
            </div>
        @endforeach
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="operator">{{ __('messages.assertions.operator') }}</label>
            <select class="form-control" id="operator" name="operator" required>
                @foreach(\App\Models\EndpointAssertionRule::OPERATORS as $operator)
                    <option value="{{ $operator }}" @selected($selectedOperator === $operator)>{{ __('messages.assertions.operators.'.$operator) }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="target_path">{{ __('messages.assertions.target_path') }}</label>
            <input type="text" class="form-control" id="target_path" name="target_path" value="{{ old('target_path', $rule->target_path) }}" placeholder="{{ __('messages.assertions.target_path_placeholder') }}">
            <span class="help-block">{{ __('messages.assertions.target_path_help') }}</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="expected_value">{{ __('messages.assertions.expected_value') }}</label>
            <input type="text" class="form-control" id="expected_value" name="expected_value" value="{{ old('expected_value', $rule->expected_value) }}" placeholder="{{ __('messages.assertions.expected_placeholder') }}">
            <span class="help-block">{{ __('messages.assertions.expected_help') }}</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="severity">{{ __('messages.assertions.severity') }}</label>
            <select class="form-control" id="severity" name="severity" required>
                @foreach(\App\Models\EndpointAssertionRule::SEVERITIES as $severity)
                    <option value="{{ $severity }}" @selected(old('severity', $rule->severity) === $severity)>{{ __('messages.assertions.severities.'.$severity) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="checkbox">
    <label>
        <input type="checkbox" name="enabled" value="1" @checked((bool) old('enabled', $rule->enabled))>
        {{ __('messages.assertions.enabled') }}
    </label>
</div>

<hr>
<button type="submit" class="btn btn-primary">{{ __('messages.assertions.save_rule') }}</button>
<a href="{{ $rule->endpoint_id ? route('projects.endpoints.show', [$project, $rule->endpoint_id]) : route('projects.settings.edit', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
