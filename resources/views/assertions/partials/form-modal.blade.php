@php
    $rule = $rule ?? null;
    $selectedRuleKey = old('rule_key', $rule?->rule_key ?? 'status_code');
    $selectedOperator = old('operator', $rule?->operator ?? 'equals');
    $selectedSeverity = old('severity', $rule?->severity ?? 'warning');
    $enabledValue = old('enabled', $rule?->enabled ?? true);
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form class="modal-content aptoria-enhanced-form" method="POST" action="{{ $action }}" data-aptoria-form-scope="assertion" data-aptoria-form-plugin>
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="modal-header">
                <div class="d-flex align-items-start gap-2">
                    <span class="avatar avatar-sm rounded text-bg-primary flex-shrink-0"><span class="avatar-title"><i data-lucide="list-checks"></i></span></span>
                    <div>
                        <h5 class="modal-title mb-1">{{ $rule ? __('messages.assertions.edit') : __('messages.assertions.new') }}</h5>
                        <p class="text-muted small mb-0 aptoria-form-help-text">{{ __('messages.assertions.form_help') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="border rounded p-3 bg-light-subtle">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i data-lucide="badge-check" class="text-primary"></i>
                                <div>
                                    <h6 class="mb-0 fw-normal">{{ __('messages.assertions.sections.identity') }}</h6>
                                    <div class="text-muted small">{{ __('messages.assertions.sections.identity_help') }}</div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label" for="{{ $modalId }}Name">{{ __('messages.assertions.name') }} <span class="text-danger aptoria-required-marker">*</span></label>
                                    <input type="text" id="{{ $modalId }}Name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $rule?->name) }}" placeholder="{{ __('messages.assertions.placeholders.name') }}" maxlength="180" required>
                                    <div class="form-text">{{ __('messages.assertions.name_help') }}</div>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="{{ $modalId }}Endpoint">{{ __('messages.assertions.endpoint_scope') }}</label>
                                    <select id="{{ $modalId }}Endpoint" name="endpoint_id" class="form-select @error('endpoint_id') is-invalid @enderror">
                                        <option value="">{{ __('messages.assertions.project_level') }}</option>
                                        @foreach ($endpoints as $endpoint)
                                            <option value="{{ $endpoint->id }}" @selected((int) old('endpoint_id', $rule?->endpoint_id) === (int) $endpoint->id)>{{ $endpoint->method }} {{ $endpoint->path }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">{{ __('messages.assertions.endpoint_scope_help') }}</div>
                                    @error('endpoint_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="{{ $modalId }}Description">{{ __('messages.common.description') }}</label>
                                    <textarea id="{{ $modalId }}Description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="{{ __('messages.assertions.placeholders.description') }}">{{ old('description', $rule?->description) }}</textarea>
                                    <div class="form-text">{{ __('messages.assertions.description_help') }}</div>
                                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i data-lucide="sliders-horizontal" class="text-primary"></i>
                                <div>
                                    <h6 class="mb-0 fw-normal">{{ __('messages.assertions.sections.condition') }}</h6>
                                    <div class="text-muted small">{{ __('messages.assertions.sections.condition_help') }}</div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="{{ $modalId }}RuleKey">{{ __('messages.assertions.rule_key') }} <span class="text-danger aptoria-required-marker">*</span></label>
                                    <select id="{{ $modalId }}RuleKey" name="rule_key" class="form-select @error('rule_key') is-invalid @enderror" required>
                                        @foreach (\App\Models\EndpointAssertionRule::RULE_KEYS as $key)
                                            <option value="{{ $key }}" @selected($selectedRuleKey === $key)>{{ __('messages.assertions.rule_keys.'.$key) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">{{ __('messages.assertions.rule_key_help') }}</div>
                                    @error('rule_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="{{ $modalId }}Operator">{{ __('messages.assertions.operator') }} <span class="text-danger aptoria-required-marker">*</span></label>
                                    <select id="{{ $modalId }}Operator" name="operator" class="form-select @error('operator') is-invalid @enderror" required>
                                        @foreach (\App\Models\EndpointAssertionRule::OPERATORS as $operator)
                                            <option value="{{ $operator }}" @selected($selectedOperator === $operator)>{{ __('messages.assertions.operators.'.$operator) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">{{ __('messages.assertions.operator_help') }}</div>
                                    @error('operator')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="{{ $modalId }}ExpectedValue">{{ __('messages.assertions.expected_value') }} <span class="text-danger aptoria-required-marker">*</span></label>
                                    <input type="text" id="{{ $modalId }}ExpectedValue" name="expected_value" class="form-control @error('expected_value') is-invalid @enderror" value="{{ old('expected_value', $rule?->expected_value) }}" placeholder="{{ __('messages.assertions.placeholders.expected') }}" maxlength="1000" required>
                                    <div class="form-text">{{ __('messages.assertions.expected_value_help') }}</div>
                                    @error('expected_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="{{ $modalId }}TargetPath">{{ __('messages.assertions.target_path') }}</label>
                                    <input type="text" id="{{ $modalId }}TargetPath" name="target_path" class="form-control @error('target_path') is-invalid @enderror" value="{{ old('target_path', $rule?->target_path) }}" placeholder="{{ __('messages.assertions.placeholders.target_path') }}" maxlength="255">
                                    <div class="form-text">{{ __('messages.assertions.target_path_help') }}</div>
                                    @error('target_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3 bg-light-subtle">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i data-lucide="shield-alert" class="text-primary"></i>
                                <div>
                                    <h6 class="mb-0 fw-normal">{{ __('messages.assertions.sections.outcome') }}</h6>
                                    <div class="text-muted small">{{ __('messages.assertions.sections.outcome_help') }}</div>
                                </div>
                            </div>
                            <div class="row g-3 align-items-start">
                                <div class="col-md-6">
                                    <label class="form-label" for="{{ $modalId }}Severity">{{ __('messages.assertions.severity') }} <span class="text-danger aptoria-required-marker">*</span></label>
                                    <select id="{{ $modalId }}Severity" name="severity" class="form-select @error('severity') is-invalid @enderror" required>
                                        @foreach (\App\Models\EndpointAssertionRule::SEVERITIES as $severity)
                                            <option value="{{ $severity }}" @selected($selectedSeverity === $severity)>{{ __('messages.assertions.severities.'.$severity) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">{{ __('messages.assertions.severity_help') }}</div>
                                    @error('severity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block" for="{{ $modalId }}Enabled">{{ __('messages.common.status') }}</label>
                                    <div class="form-check form-switch border rounded p-3 ps-5 bg-body">
                                        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="{{ $modalId }}Enabled" @checked((bool) $enabledValue)>
                                        <label class="form-check-label" for="{{ $modalId }}Enabled">{{ __('messages.common.enabled') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('messages.assertions.enabled_help') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer aptoria-card-footer-subtle">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
            </div>
        </form>
    </div>
</div>
