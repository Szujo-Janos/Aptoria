@extends('layouts.app')

@section('title', __('messages.release_readiness.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $summary['css'] === 'danger' ? 'red' : ($summary['css'] === 'warning' ? 'yellow' : 'green') }}">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.projects.details') }}</a>
                    <a href="{{ route('projects.scans.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.scans.new') }}</a>
                    <a href="{{ route('projects.reports.release-readiness.markdown', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.release_readiness.download_report') }}</a>
                    <a href="{{ route('projects.release-gates.create', $project) }}" class="btn btn-xs btn-danger">{{ __('messages.release_gates.create') }}</a>
                </div>
                {{ __('messages.release_readiness.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-2"><h2><span class="label label-{{ $summary['css'] }}">{{ $summary['label'] }}</span></h2><small>{{ __('messages.release_readiness.overall_status') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $summary['score'] }}</h2><small>{{ __('messages.release_readiness.score') }} / 100</small></div>
                    <div class="col-sm-2"><h2>{{ $summary['grade'] }}</h2><small>{{ __('messages.release_readiness.grade') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $summary['coverage_percent'] }}%</h2><small>{{ __('messages.release_readiness.endpoint_coverage') }}</small></div>
                    <div class="col-sm-2"><h2>{{ count($summary['blocking_issues']) }}</h2><small>{{ __('messages.release_readiness.blocking_issues') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $summary['finding_counts']['open'] ?? 0 }}</h2><small>{{ __('messages.findings.open_findings') }}</small></div>
                </div>
                <hr>
                <p class="text-muted m-b-none">{{ __('messages.release_readiness.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><span class="label label-primary">{{ $summary['score'] }}/100</span></div>
                {{ __('messages.release_readiness.score_breakdown_title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.release_readiness.score_breakdown_intro') }}</p>
                <div class="table-responsive">
                    <table class="table table-striped table-condensed m-b-none">
                        <thead>
                        <tr>
                            <th>{{ __('messages.release_readiness.readiness_basis') }}</th>
                            <th>{{ __('messages.release_readiness.earned_points') }}</th>
                            <th>{{ __('messages.release_readiness.max_points') }}</th>
                            <th>{{ __('messages.common.status') }}</th>
                            <th style="width: 28%">{{ __('messages.release_readiness.score') }}</th>
                            <th>{{ __('messages.common.details') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($summary['score_components'] as $component)
                            <tr>
                                <td><strong>{{ $component['label'] }}</strong></td>
                                <td>{{ $component['earned_points'] }}</td>
                                <td>{{ $component['max_points'] }}</td>
                                <td><span class="label label-{{ $component['css'] }}">{{ $component['status_label'] }}</span></td>
                                <td>
                                    <div class="progress full progress-small m-b-none">
                                        <div style="width: {{ $component['percent'] }}%" aria-valuenow="{{ $component['percent'] }}" aria-valuemin="0" aria-valuemax="100" role="progressbar" class="progress-bar progress-bar-{{ $component['css'] }}">
                                            <span class="sr-only">{{ $component['percent'] }}%</span>
                                        </div>
                                    </div>
                                    <small>{{ $component['percent'] }}%</small>
                                </td>
                                <td>
                                    @foreach($component['checks'] as $check)
                                        <div class="small text-muted">{{ $check }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.evidence') }}</div>
            <div class="panel-body">
                <dl class="dl-horizontal m-b-none">
                    <dt>{{ __('messages.scans.latest') }}</dt><dd>@if($summary['latest_scan']) <a href="{{ route('projects.scans.show', [$project, $summary['latest_scan']]) }}">#{{ $summary['latest_scan']->id }}</a> — {{ $summary['latest_scan']->status_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                    <dt>{{ __('messages.snapshots.title') }}</dt><dd>{{ $summary['latest_snapshot']?->name ?: __('messages.common.not_available') }}</dd>
                    <dt>{{ __('messages.snapshots.recent_compares') }}</dt><dd>@if($summary['latest_compare']) <a href="{{ route('projects.snapshots.compares.show', [$project, $summary['latest_compare']]) }}">#{{ $summary['latest_compare']->id }}</a> @else {{ __('messages.common.not_available') }} @endif</dd>
                    <dt>{{ __('messages.regressions.regression_status') }}</dt><dd><span class="label label-{{ $summary['regression']['css'] }}">{{ $summary['regression']['label'] }}</span></dd>
                    <dt>{{ __('messages.contract_validations.short_title') }}</dt><dd>@if($summary['latest_contract_validation']) <a href="{{ route('projects.contract-validations.show', [$project, $summary['latest_contract_validation']]) }}">#{{ $summary['latest_contract_validation']->id }}</a> — {{ $summary['latest_contract_validation']->health_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                    <dt>{{ __('messages.release_readiness.covered_endpoints') }}</dt><dd>{{ $summary['coverage_count'] }} / {{ $summary['endpoint_count'] }}</dd>
                    <dt>{{ __('messages.findings.open_findings') }}</dt><dd><a href="{{ route('projects.findings.index', $project) }}">{{ $summary['finding_counts']['open'] ?? 0 }}</a></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.assertion_summary') }}</div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-xs-3"><h3>{{ $summary['assertion_counts']['pass'] ?? 0 }}</h3><small>{{ __('messages.assertions.statuses.pass') }}</small></div>
                    <div class="col-xs-3"><h3>{{ $summary['assertion_counts']['warning'] ?? 0 }}</h3><small>{{ __('messages.assertions.statuses.warning') }}</small></div>
                    <div class="col-xs-3"><h3>{{ $summary['assertion_counts']['fail'] ?? 0 }}</h3><small>{{ __('messages.assertions.statuses.fail') }}</small></div>
                    <div class="col-xs-3"><h3>{{ $summary['assertion_counts']['not_configured'] ?? 0 }}</h3><small>{{ __('messages.assertions.statuses.not_configured') }}</small></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hred aptoria-risk-summary-panel">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.risk_summary') }}</div>
            <div class="panel-body">
                @php
                    $riskOrder = ['critical', 'high', 'review', 'public', 'low'];
                    $riskClassMap = [
                        'critical' => 'danger',
                        'high' => 'warning',
                        'review' => 'default',
                        'public' => 'info',
                        'low' => 'success',
                    ];
                    $riskTotal = max(1, array_sum($summary['risk_counts'] ?? []));
                @endphp
                <div class="aptoria-risk-summary-list">
                    @foreach($riskOrder as $risk)
                        @php
                            $count = (int) ($summary['risk_counts'][$risk] ?? 0);
                            $labelClass = $riskClassMap[$risk] ?? 'default';
                            $percent = min(100, max(0, (int) round(($count / $riskTotal) * 100)));
                        @endphp
                        <div class="aptoria-risk-summary-item">
                            <div class="aptoria-risk-summary-copy">
                                <span class="label label-{{ $labelClass }}">{{ __('messages.endpoints.risks.'.$risk) }}</span>
                                <strong>{{ $count }}</strong>
                            </div>
                            <div class="progress aptoria-risk-summary-progress m-b-none">
                                <div class="progress-bar progress-bar-{{ $labelClass }}" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $percent }}%">
                                    <span class="sr-only">{{ $percent }}%</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.findings.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.findings.view_all') }}</a></div>
                {{ __('messages.findings.title') }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-xs-3"><h3>{{ $summary['finding_counts']['open'] ?? 0 }}</h3><small>{{ __('messages.findings.open_findings') }}</small></div>
                    <div class="col-xs-3"><h3>{{ $summary['finding_counts']['critical_open'] ?? 0 }}</h3><small>{{ __('messages.findings.severities.critical') }}</small></div>
                    <div class="col-xs-3"><h3>{{ $summary['finding_counts']['high_open'] ?? 0 }}</h3><small>{{ __('messages.findings.severities.high') }}</small></div>
                    <div class="col-xs-3"><h3>{{ $summary['finding_counts']['medium_open'] ?? 0 }}</h3><small>{{ __('messages.findings.severities.medium') }}</small></div>
                </div>
                <hr>
                <div class="row text-center small">
                    <div class="col-xs-3"><span class="label label-danger">{{ $summary['finding_counts']['reopened'] ?? 0 }}</span><br><small>{{ __('messages.findings.statuses.reopened') }}</small></div>
                    <div class="col-xs-3"><span class="label label-success">{{ $summary['finding_counts']['fixed'] ?? 0 }}</span><br><small>{{ __('messages.findings.statuses.fixed') }}</small></div>
                    <div class="col-xs-3"><span class="label label-default">{{ $summary['finding_counts']['false_positive'] ?? 0 }}</span><br><small>{{ __('messages.findings.statuses.false_positive') }}</small></div>
                    <div class="col-xs-3"><span class="label label-warning">{{ $summary['finding_counts']['accepted_risk'] ?? 0 }}</span><br><small>{{ __('messages.findings.statuses.accepted_risk') }}</small></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.contract_validations.short_title') }}</div>
            <div class="panel-body">
                @if($summary['latest_contract_validation'])
                    <div class="row text-center">
                        <div class="col-xs-3"><h3>{{ $summary['latest_contract_validation']->breaking_count }}</h3><small>{{ __('messages.contract_validations.breaking') }}</small></div>
                        <div class="col-xs-3"><h3>{{ $summary['latest_contract_validation']->failed_count }}</h3><small>{{ __('messages.contract_validations.failed') }}</small></div>
                        <div class="col-xs-3"><h3>{{ $summary['latest_contract_validation']->warning_count }}</h3><small>{{ __('messages.contract_validations.warnings') }}</small></div>
                        <div class="col-xs-3"><h3>{{ $summary['latest_contract_validation']->passed_count }}</h3><small>{{ __('messages.contract_validations.passed') }}</small></div>
                    </div>
                @else
                    <p class="text-muted m-b-none">{{ __('messages.release_readiness.warnings.no_contract_validation') }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.test-execution.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.test_execution.open_dashboard') }}</a></div>
                {{ __('messages.test_execution.short_title') }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-xs-3"><h3>{{ $summary['test_execution']['execution_percent'] ?? 0 }}%</h3><small>{{ __('messages.test_execution.execution_coverage') }}</small></div>
                    <div class="col-xs-3"><h3>{{ $summary['test_execution']['pass_rate'] ?? 0 }}%</h3><small>{{ __('messages.test_execution.pass_rate') }}</small></div>
                    <div class="col-xs-3"><h3>{{ $summary['test_execution']['run_counts'][\App\Models\TestCase::RUN_FAIL] ?? 0 }}</h3><small>{{ __('messages.test_cases.run_statuses.fail') }}</small></div>
                    <div class="col-xs-3"><h3>{{ $summary['test_execution']['run_counts'][\App\Models\TestCase::RUN_BLOCKED] ?? 0 }}</h3><small>{{ __('messages.test_cases.run_statuses.blocked') }}</small></div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.qa-coverage.index', $project) }}" class="btn btn-xs btn-success">{{ __('messages.qa_coverage.open_matrix') }}</a></div>
                {{ __('messages.qa_coverage.summary_title') }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-2"><h3>{{ $summary['qa_coverage']['coverage_percent'] ?? 0 }}%</h3><small>{{ __('messages.qa_coverage.coverage_percent') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['qa_coverage']['fully_covered'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.fully_covered') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['qa_coverage']['missing_tests'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.gap_filters.missing_test_cases') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['qa_coverage']['missing_assertions'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.gap_filters.missing_assertions') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['qa_coverage']['not_scanned'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.gap_filters.not_scanned') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['qa_coverage']['blocked'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.statuses.blocked') }}</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.blocking_issues') }}</div>
            <div class="panel-body">
                @if(empty($summary['blocking_issues']))
                    <p class="text-muted m-b-none">{{ __('messages.release_readiness.no_blockers') }}</p>
                @else
                    <ul class="m-b-none">
                        @foreach($summary['blocking_issues'] as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.warning_items') }}</div>
            <div class="panel-body">
                @if(empty($summary['warnings']))
                    <p class="text-muted m-b-none">{{ __('messages.release_readiness.no_warnings') }}</p>
                @else
                    <ul class="m-b-none">
                        @foreach($summary['warnings'] as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.findings.open_findings') }}</div>
            <div class="panel-body no-padding">
                @if($summary['open_findings']->isEmpty())
                    <div class="p-md"><p class="text-muted m-b-none">{{ __('messages.findings.no_open_findings') }}</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed m-b-none">
                            <thead><tr><th>{{ __('messages.findings.severity') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.findings.title_field') }}</th><th>{{ __('messages.endpoints.title') }}</th><th>{{ __('messages.findings.evidence') }}</th><th>{{ __('messages.findings.attachment') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($summary['open_findings'] as $finding)
                                <tr>
                                    <td><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span></td>
                                    <td><span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></td>
                                    <td><strong>{{ $finding->title }}</strong></td>
                                    <td>@if($finding->endpoint)<code>{{ $finding->endpoint->method }} {{ $finding->endpoint->path }}</code>@else<span class="text-muted">{{ __('messages.common.none') }}</span>@endif</td>
                                    <td>{{ $finding->evidence->count() }}</td>
                                    <td>{{ $finding->evidence->filter(fn ($evidence) => $evidence->has_attachment)->count() }}</td>
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
    <div class="col-lg-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.top_failing_endpoints') }}</div>
            <div class="panel-body no-padding">
                @include('reports.partials.readiness-endpoint-table', ['rows' => $summary['failed_endpoints']])
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.top_slow_endpoints') }}</div>
            <div class="panel-body no-padding">
                @include('reports.partials.readiness-endpoint-table', ['rows' => $summary['top_slow_endpoints']])
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.security_header_issues') }}</div>
            <div class="panel-body no-padding">
                @include('reports.partials.readiness-endpoint-table', ['rows' => $summary['security_header_issues']])
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.recommended_actions') }}</div>
            <div class="panel-body">
                <ul class="m-b-none">
                    @foreach($summary['recommended_actions'] as $action)
                        <li>{{ $action }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.risk_trend') }}</div>
            <div class="panel-body">
                <div class="aptoria-chart-box">
                    <canvas class="aptoria-chart" id="aptoria-readiness-risk-trend" data-chart-type="line" data-labels='@json(array_column($summary['risk_trend'], "label"))' data-values='@json(array_column($summary['risk_trend'], "value"))'></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.regression_trend') }}</div>
            <div class="panel-body">
                <div class="aptoria-chart-box">
                    <canvas class="aptoria-chart" id="aptoria-readiness-regression-trend" data-chart-type="bar" data-labels='@json(array_column($summary['regression_trend'], "label"))' data-values='@json(array_column($summary['regression_trend'], "value"))'></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
