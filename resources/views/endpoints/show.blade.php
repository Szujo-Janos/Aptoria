@extends('layouts.app')

@section('title', $endpoint->method.' '.$endpoint->path)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                    @if($endpoint->isProbeable())
                        <form method="POST" action="{{ route('projects.endpoints.probe', [$project, $endpoint]) }}" style="display:inline" data-aptoria-scan-form="true">@csrf<button type="submit" class="btn btn-xs btn-success" data-aptoria-submit-label="{{ __('messages.scans.probing') }}">{{ __('messages.scans.probe') }}</button></form>
                    @endif
                    <a href="{{ route('projects.endpoints.edit', [$project, $endpoint]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                </div>
                {{ __('messages.endpoints.details_title') }}
            </div>
            <div class="panel-body">
                <h2 class="m-t-none"><span class="label label-default">{{ $endpoint->method }}</span> <code>{{ $endpoint->path }}</code></h2>
                <p class="text-muted">{{ $endpoint->description ?: __('messages.endpoints.no_description') }}</p>
                <div class="row">
                    <div class="col-md-6">
                        <dl class="dl-horizontal">
                            <dt>{{ __('messages.projects.title') }}</dt><dd>{{ $project->name }}</dd>
                            <dt>{{ __('messages.endpoints.full_url') }}</dt><dd><code>{{ $resolvedFullUrl }}</code></dd>
                            <dt>{{ __('messages.environments.title') }}</dt><dd>{{ $endpoint->environment?->name ?: __('messages.endpoints.project_default') }}</dd>
                            <dt>{{ __('messages.auth_profiles.title') }}</dt><dd>{{ $endpoint->authProfile?->name ?: __('messages.endpoints.inherit_auth_profile') }}</dd>
                            <dt>{{ __('messages.auth_profiles.effective_profile') }}</dt><dd>{{ $effectiveAuthProfile?->name ?: __('messages.common.none') }} <span class="label label-{{ $effectiveAuth['css'] }}">{{ $effectiveAuth['label'] }}</span><br><code>{{ $effectiveAuth['summary'] }}</code></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="dl-horizontal">
                            <dt>{{ __('messages.risk.manual_level') }}</dt><dd><span class="label label-{{ $riskAnalysis['manual_css'] }}">{{ $riskAnalysis['manual_label'] }}</span></dd>
                            <dt>{{ __('messages.endpoints.auth_required') }}</dt><dd>{{ $endpoint->auth_required ? __('messages.common.yes') : __('messages.common.no') }}</dd>
                            <dt>{{ __('messages.endpoints.expected_status') }}</dt><dd>{{ $endpoint->expected_status ?: __('messages.common.not_available') }}</dd>
                            <dt>{{ __('messages.endpoints.expected_content_type') }}</dt><dd>{{ $endpoint->expected_content_type ?: __('messages.common.not_available') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!empty($endpoint->request_headers) || $endpoint->request_body_type || $endpoint->request_body_preview)
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.endpoints.imported_request_metadata') }}</div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>{{ __('messages.endpoints.request_headers') }}</h5>
                        @if(!empty($endpoint->request_headers))
                            <table class="table table-condensed table-bordered m-b-none">
                                <thead><tr><th>{{ __('messages.common.name') }}</th><th>{{ __('messages.common.value') }}</th></tr></thead>
                                <tbody>
                                @foreach($endpoint->request_headers as $header)
                                    <tr><td><code>{{ $header['key'] ?? '' }}</code></td><td><code>{{ $header['value'] ?? '' }}</code></td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted m-b-none">{{ __('messages.endpoints.no_request_metadata') }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h5>{{ __('messages.endpoints.request_body') }}</h5>
                        @if($endpoint->request_body_type || $endpoint->request_body_preview)
                            @if($endpoint->request_body_type)<p><span class="label label-default">{{ $endpoint->request_body_type }}</span></p>@endif
                            @if($endpoint->request_body_preview)<pre class="code-block m-b-none">{{ $endpoint->request_body_preview }}</pre>@endif
                        @else
                            <p class="text-muted m-b-none">{{ __('messages.endpoints.no_request_metadata') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif



<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hviolet">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.api-behavior.index', $project) }}" class="btn btn-xs btn-primary"><i class="fa fa-random"></i> {{ __('messages.api_behavior.short_title') }}</a>
                </div>
                {{ __('messages.api_behavior.endpoint_panel_title') }}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <dl class="dl-horizontal">
                            <dt>{{ __('messages.api_behavior.role') }}</dt><dd>{{ $endpoint->behavior_role_label }}</dd>
                            <dt>{{ __('messages.api_behavior.resource') }}</dt><dd><code>{{ $endpoint->behavior_resource ?: __('messages.common.not_available') }}</code></dd>
                            <dt>{{ __('messages.api_behavior.summary') }}</dt><dd>{{ $endpoint->behavior_summary }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-4">
                        <h5>{{ __('messages.api_behavior.flags.title') }}</h5>
                        @if($endpoint->destructive_action || $endpoint->auth_boundary || $endpoint->sequence_candidate)
                            @if($endpoint->destructive_action)<span class="label label-danger">{{ __('messages.api_behavior.flags.destructive') }}</span>@endif
                            @if($endpoint->auth_boundary)<span class="label label-warning">{{ __('messages.api_behavior.flags.auth_boundary') }}</span>@endif
                            @if($endpoint->sequence_candidate)<span class="label label-info">{{ __('messages.api_behavior.flags.sequence_candidate') }}</span>@endif
                        @else
                            <p class="text-muted m-b-none">{{ __('messages.api_behavior.no_behavior_detected') }}</p>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <h5>{{ __('messages.common.notes') }}</h5>
                        <p class="text-muted m-b-none">{{ $endpoint->behavior_notes ?: __('messages.api_behavior.no_behavior_detected') }}</p>
                    </div>
                </div>

                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h5>{{ __('messages.api_behavior.produces_for') }}</h5>
                        @forelse($endpoint->producedBehaviorLinks as $link)
                            <p><span class="label label-{{ $link->confidence_css }}">{{ $link->confidence }}%</span> <code>{{ $endpoint->method }} {{ $endpoint->path }}</code> → <code>{{ $link->consumerEndpoint->method }} {{ $link->consumerEndpoint->path }}</code></p>
                        @empty
                            <p class="text-muted m-b-none">{{ __('messages.api_behavior.no_produced_links') }}</p>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <h5>{{ __('messages.api_behavior.consumes_from') }}</h5>
                        @forelse($endpoint->consumedBehaviorLinks as $link)
                            <p><span class="label label-{{ $link->confidence_css }}">{{ $link->confidence }}%</span> <code>{{ $link->producerEndpoint->method }} {{ $link->producerEndpoint->path }}</code> → <code>{{ $endpoint->method }} {{ $endpoint->path }}</code></p>
                        @empty
                            <p class="text-muted m-b-none">{{ __('messages.api_behavior.no_consumed_links') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-cases.create', ['project' => $project, 'endpoint_id' => $endpoint->id]) }}" class="btn btn-xs btn-success">{{ __('messages.test_cases.create') }}</a>
                    <a href="{{ route('projects.test-cases.index', ['project' => $project]) }}" class="btn btn-xs btn-default">{{ __('messages.test_cases.view_all') }}</a>
                </div>
                {{ __('messages.test_cases.linked_title') }} <span class="label label-info">{{ $endpoint->testCases->count() }}</span>
            </div>
            <div class="panel-body">
                @if($endpoint->testCases->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.test_cases.no_linked_cases') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.test_cases.title_field') }}</th>
                                    <th>{{ __('messages.test_suites.single') }}</th>
                                    <th>{{ __('messages.test_cases.priority') }}</th>
                                    <th>{{ __('messages.common.type') }}</th>
                                    <th>{{ __('messages.test_cases.last_run_status') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($endpoint->testCases as $case)
                                <tr>
                                    <td><strong>{{ $case->title }}</strong></td>
                                    <td><a href="{{ route('projects.test-suites.show', [$project, $case->testSuite]) }}">{{ $case->testSuite?->name }}</a></td>
                                    <td><span class="label label-{{ $case->priority_css }}">{{ $case->priority_label }}</span></td>
                                    <td>{{ $case->type_label }}</td>
                                    <td><span class="label label-{{ $case->last_run_status_css }}">{{ $case->last_run_status_label }}</span></td>
                                    <td class="text-right"><a href="{{ route('projects.test-cases.show', [$project, $case]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
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


<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.contract-validations.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.contract_validations.new') }}</a>
                    <a href="{{ route('projects.contract-validations.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.contract_validations.view_all') }}</a>
                </div>
                {{ __('messages.contract_validations.linked_title') }} <span class="label label-info">{{ $endpoint->contractValidationResults->count() }}</span>
            </div>
            <div class="panel-body">
                @php($latestContractResults = $endpoint->contractValidationResults->sortByDesc('created_at')->take(10))
                @if($latestContractResults->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.contract_validations.no_endpoint_results') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead><tr><th>{{ __('messages.common.created') }}</th><th>{{ __('messages.contract_validations.check_type') }}</th><th>{{ __('messages.contract_validations.severity') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.contract_validations.message') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($latestContractResults as $result)
                                <tr>
                                    <td>{{ $result->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $result->check_type_label }}</td>
                                    <td><span class="label label-{{ $result->severity_css }}">{{ $result->severity_label }}</span></td>
                                    <td><span class="label label-{{ $result->status_css }}">{{ $result->status_label }}</span></td>
                                    <td>{{ $result->message }}</td>
                                    <td class="text-right">@if($result->run)<a href="{{ route('projects.contract-validations.show', [$project, $result->run]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a>@endif</td>
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


<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.path_parameters.title') }}</div>
            <div class="panel-body">
                @if(empty($pathParameterRows))
                    <p class="text-muted m-b-none">{{ __('messages.path_parameters.no_parameters') }}</p>
                @else
                    <p class="text-muted">{{ __('messages.path_parameters.endpoint_help') }}</p>
                    <p><strong>{{ __('messages.path_parameters.resolved_url') }}</strong><br><code>{{ $resolvedFullUrl }}</code></p>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>{{ __('messages.path_parameters.parameter') }}</th>
                                <th>{{ __('messages.path_parameters.project_default') }}</th>
                                <th>{{ __('messages.path_parameters.endpoint_override') }}</th>
                                <th>{{ __('messages.path_parameters.effective_value') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($pathParameterRows as $row)
                                <tr>
                                    <td><code>{{ '{'.$row['name'].'}' }}</code></td>
                                    <td>{{ $row['project_value'] ?: __('messages.common.not_available') }}</td>
                                    <td>{{ $row['endpoint_value'] ?: __('messages.common.not_available') }}</td>
                                    <td><strong>{{ $row['effective_value'] ?: __('messages.common.not_available') }}</strong><br><small class="text-muted">{{ __('messages.path_parameters.sources.'.$row['source']) }}</small></td>
                                    <td><span class="label label-{{ $row['resolved'] ? 'success' : 'danger' }}">{{ $row['resolved'] ? __('messages.path_parameters.resolved') : __('messages.path_parameters.unresolved') }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <form method="POST" action="{{ route('projects.endpoints.path-parameters.update', [$project, $endpoint]) }}">
                        @csrf
                        <div class="form-group">
                            <label for="path_parameter_overrides">{{ __('messages.path_parameters.endpoint_overrides') }}</label>
                            <textarea name="path_parameter_overrides" id="path_parameter_overrides" class="form-control" rows="4" placeholder="id=1&#10;userId=1">{{ old('path_parameter_overrides', $pathParameterOverrideText) }}</textarea>
                            <span class="help-block">{{ __('messages.path_parameters.textarea_help') }}</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('messages.path_parameters.save_endpoint_overrides') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.risk.analysis_title') }}</div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-3">
                        <h3><span class="label label-{{ $riskAnalysis['manual_css'] }}">{{ $riskAnalysis['manual_label'] }}</span></h3>
                        <small>{{ __('messages.risk.manual_level') }}</small>
                    </div>
                    <div class="col-sm-3">
                        <h3><span class="label label-{{ $riskAnalysis['calculated_css'] }}">{{ $riskAnalysis['calculated_label'] }}</span></h3>
                        <small>{{ __('messages.risk.calculated_level') }}</small>
                    </div>
                    <div class="col-sm-3">
                        <h3><span class="label label-{{ $riskAnalysis['final_css'] }}">{{ $riskAnalysis['final_label'] }}</span></h3>
                        <small>{{ __('messages.risk.final_level') }}</small>
                    </div>
                    <div class="col-sm-3">
                        <h3>{{ $riskAnalysis['score'] }}</h3>
                        <small>{{ __('messages.risk.score') }}</small>
                    </div>
                </div>
                <hr>
                <p>{{ $riskAnalysis['explanation'] }}</p>

                <h4>{{ __('messages.risk.detected_signals') }}</h4>
                @if($riskAnalysis['signals'])
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.risk.signal') }}</th>
                                    <th>{{ __('messages.endpoints.risk_level') }}</th>
                                    <th>{{ __('messages.risk.score') }}</th>
                                    <th>{{ __('messages.risk.why_it_matters') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($riskAnalysis['signals'] as $signal)
                                <tr>
                                    <td><strong>{{ $signal['label'] }}</strong></td>
                                    <td><span class="label label-{{ $signal['css'] }}">{{ $signal['level_label'] }}</span></td>
                                    <td>+{{ $signal['score'] }}</td>
                                    <td>{{ $signal['explanation'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">{{ __('messages.risk.no_signals') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.risk.why_it_matters') }}</div>
            <div class="panel-body">
                <p class="m-b-none">{{ $riskAnalysis['why_it_matters'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.risk.suggested_qa_action') }}</div>
            <div class="panel-body">
                <ul class="m-b-none">
                    @foreach($riskAnalysis['qa_actions'] as $action)
                        <li>{{ $action }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.risk.suggested_developer_action') }}</div>
            <div class="panel-body">
                <ul class="m-b-none">
                    @foreach($riskAnalysis['developer_actions'] as $action)
                        <li>{{ $action }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.findings.create', ['project' => $project, 'endpoint_id' => $endpoint->id, 'source' => 'manual']) }}" class="btn btn-xs btn-danger">{{ __('messages.findings.create') }}</a>
                    <a href="{{ route('projects.findings.index', ['project' => $project, 'status' => 'open']) }}" class="btn btn-xs btn-default">{{ __('messages.findings.view_all') }}</a>
                </div>
                {{ __('messages.findings.linked_title') }}
            </div>
            <div class="panel-body">
                @if($endpoint->findings->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.findings.no_endpoint_findings') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead><tr><th>{{ __('messages.findings.title_field') }}</th><th>{{ __('messages.findings.severity') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.findings.source') }}</th><th>{{ __('messages.findings.evidence') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($endpoint->findings as $finding)
                                <tr>
                                    <td><strong>{{ $finding->title }}</strong><br><small class="text-muted">{{ \Illuminate\Support\Str::limit($finding->description, 120) }}</small></td>
                                    <td><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span></td>
                                    <td><span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></td>
                                    <td>{{ $finding->source_label }}</td>
                                    <td>{{ $finding->evidence->count() }}</td>
                                    <td class="text-right"><a href="{{ route('projects.findings.show', [$project, $finding]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
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

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.assertion-rules.create', ['project' => $project, 'endpoint_id' => $endpoint->id]) }}" class="btn btn-xs btn-primary">{{ __('messages.assertions.add_rule') }}</a>
                </div>
                {{ __('messages.assertions.title') }}
            </div>
            <div class="panel-body">
                <div class="row m-b-md">
                    <div class="col-sm-6">
                        <strong>{{ __('messages.assertions.assertion_status') }}</strong><br>
                        <span class="label label-{{ $assertionEvaluation['css'] }}">{{ $assertionEvaluation['label'] }}</span>
                    </div>
                    <div class="col-sm-6">
                        <strong>{{ __('messages.regressions.regression_status') }}</strong><br>
                        <span class="label label-{{ $regressionEvaluation['css'] }}">{{ $regressionEvaluation['label'] }}</span>
                    </div>
                </div>

                @if($assertionRules->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.assertions.no_rules') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                            <tr>
                                <th>{{ __('messages.assertions.rule_key') }}</th>
                                <th>{{ __('messages.assertions.scope') }}</th>
                                <th>{{ __('messages.assertions.assertion_status') }}</th>
                                <th>{{ __('messages.assertions.target_path') }}</th>
                                <th>{{ __('messages.assertions.expected_value') }}</th>
                                <th>{{ __('messages.assertions.actual_value') }}</th>
                                <th>{{ __('messages.assertions.severity') }}</th>
                                <th>{{ __('messages.assertions.enabled') }}</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($assertionRules as $rule)
                                @php($result = collect($assertionEvaluation['results'])->firstWhere('rule_id', $rule->id))
                                <tr>
                                    <td>
                                        <strong>{{ $rule->rule_label }}</strong>
                                        @if(\App\Models\EndpointAssertionRule::isRepeatable($rule->rule_key))
                                            <span class="label label-info">{{ __('messages.assertions.repeatable') }}</span>
                                        @endif
                                        <br><small>{{ $rule->operator_label }}</small>
                                        <br><small class="text-muted">{{ $rule->rule_help }}</small>
                                    </td>
                                    <td>{{ $rule->scope_label }}</td>
                                    <td>
                                        @if($result)
                                            <span class="label label-{{ $result['status_css'] }}">{{ $result['status_label'] }}</span>
                                        @else
                                            <span class="label label-default">{{ $rule->enabled ? __('messages.assertions.overridden') : __('messages.assertions.disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $rule->target_path ?: __('messages.common.not_available') }}</td>
                                    <td>{{ $result['expected'] ?? ($rule->expected_value ?: __('messages.common.not_available')) }}</td>
                                    <td>{{ $result['actual'] ?? __('messages.common.not_available') }}</td>
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

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.scans.last_result') }}</div>
            <div class="panel-body">
                @if($endpoint->latestScanResult)
                    <div class="row">
                        <div class="col-md-3"><strong>{{ __('messages.common.status') }}</strong><br><span class="label label-{{ $endpoint->latestScanResult->status_css }}">{{ $endpoint->latestScanResult->status_label }}</span></div>
                        <div class="col-md-3"><strong>{{ __('messages.scans.http_status') }}</strong><br>{{ $endpoint->latestScanResult->status_code ?: __('messages.common.not_available') }}</div>
                        <div class="col-md-3"><strong>{{ __('messages.scans.response_time') }}</strong><br>{{ $endpoint->latestScanResult->response_time_ms !== null ? $endpoint->latestScanResult->response_time_ms.' ms' : __('messages.common.not_available') }}</div>
                        <div class="col-md-3"><strong>{{ __('messages.risk.final_level') }}</strong><br><span class="label label-{{ $riskAnalysis['final_css'] }}">{{ $riskAnalysis['final_label'] }}</span></div>
                    </div>
                    @if($endpoint->latestScanResult->error_message)
                        <hr><p class="text-danger m-b-none">{{ $endpoint->latestScanResult->error_message }}</p>
                    @endif
                    @if($endpoint->latestScanResult->scanRun)
                        <hr><a href="{{ route('projects.scans.show', [$project, $endpoint->latestScanResult->scanRun]) }}" class="btn btn-xs btn-default">{{ __('messages.scans.open_scan') }}</a>
                    @endif
                @else
                    <p class="text-muted m-b-none">{{ __('messages.scans.no_result_yet') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.endpoints.qa_notes') }}</div>
            <div class="panel-body">
                <p class="m-b-none">{{ $endpoint->qa_notes ?: __('messages.common.not_available') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.endpoints.risk_reason') }}</div>
            <div class="panel-body">
                <p class="m-b-none">{{ $endpoint->risk_reason ?: $endpoint->buildRiskExplanation() }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.endpoints.developer_fix_snippet') }}</div>
            <div class="panel-body">
                <pre class="code-block"><code>{{ $developerReviewSnippet }}</code></pre>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.endpoints.qa_bug_draft') }}</div>
            <div class="panel-body">
                <pre class="code-block"><code>{{ $qaBugReport }}</code></pre>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.projects.danger_zone') }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.endpoints.destroy', [$project, $endpoint]) }}" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.common.confirm_delete_endpoint') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">{{ __('messages.endpoints.delete_endpoint') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
