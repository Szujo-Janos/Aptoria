@extends('layouts.app')

@section('title', __('messages.qa_coverage.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-execution.index', $project) }}" class="btn btn-xs btn-info">{{ __('messages.test_execution.short_title') }}</a>
                    <a href="{{ route('projects.reports.full-project.markdown', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.reports.full_project_report_short') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.qa_coverage.title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.qa_coverage.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row text-center">
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hblue"><div class="panel-body"><h2>{{ $summary['endpoint_count'] }}</h2><small>{{ __('messages.projects.endpoints') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hgreen"><div class="panel-body"><h2>{{ $summary['coverage_percent'] }}%</h2><small>{{ __('messages.qa_coverage.coverage_percent') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hgreen"><div class="panel-body"><h2>{{ $summary['fully_covered'] }}</h2><small>{{ __('messages.qa_coverage.fully_covered') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hyellow"><div class="panel-body"><h2>{{ $summary['warning'] }}</h2><small>{{ __('messages.qa_coverage.statuses.warning') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hred"><div class="panel-body"><h2>{{ $summary['blocked'] }}</h2><small>{{ __('messages.qa_coverage.statuses.blocked') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hblue"><div class="panel-body"><h2>{{ $summary['average_score'] }}</h2><small>{{ __('messages.qa_coverage.average_score') }}</small></div></div>
    </div>
</div>

<div class="row text-center">
    <div class="col-md-2 col-sm-4"><div class="hpanel"><div class="panel-body"><h3>{{ $summary['missing_tests'] }}</h3><small>{{ __('messages.qa_coverage.gap_filters.missing_test_cases') }}</small></div></div></div>
    <div class="col-md-2 col-sm-4"><div class="hpanel"><div class="panel-body"><h3>{{ $summary['missing_assertions'] }}</h3><small>{{ __('messages.qa_coverage.gap_filters.missing_assertions') }}</small></div></div></div>
    <div class="col-md-2 col-sm-4"><div class="hpanel"><div class="panel-body"><h3>{{ $summary['not_scanned'] }}</h3><small>{{ __('messages.qa_coverage.gap_filters.not_scanned') }}</small></div></div></div>
    <div class="col-md-2 col-sm-4"><div class="hpanel"><div class="panel-body"><h3>{{ $summary['missing_contract'] }}</h3><small>{{ __('messages.qa_coverage.gap_filters.missing_contract') }}</small></div></div></div>
    <div class="col-md-2 col-sm-4"><div class="hpanel"><div class="panel-body"><h3>{{ $summary['open_findings'] }}</h3><small>{{ __('messages.findings.open_findings') }}</small></div></div></div>
    <div class="col-md-2 col-sm-4"><div class="hpanel"><div class="panel-body"><h3>{{ $rows->count() }}</h3><small>{{ __('messages.qa_coverage.filtered_rows') }}</small></div></div></div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.common.filter') }}</div>
            <div class="panel-body">
                <form method="GET" action="{{ route('projects.qa-coverage.index', $project) }}" class="row">
                    <div class="col-sm-3">
                        <select name="status" class="form-control">
                            <option value="">{{ __('messages.qa_coverage.all_statuses') }}</option>
                            @foreach([
                                \App\Services\QaCoverageMatrixService::STATUS_COVERED,
                                \App\Services\QaCoverageMatrixService::STATUS_WARNING,
                                \App\Services\QaCoverageMatrixService::STATUS_BLOCKED,
                            ] as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ __('messages.qa_coverage.statuses.'.$status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <select name="gap" class="form-control">
                            <option value="">{{ __('messages.qa_coverage.all_gaps') }}</option>
                            @foreach(['missing_test_cases', 'missing_assertions', 'not_scanned', 'missing_contract', 'open_findings'] as $gap)
                                <option value="{{ $gap }}" @selected($filters['gap'] === $gap)>{{ __('messages.qa_coverage.gap_filters.'.$gap) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <select name="risk" class="form-control">
                            <option value="">{{ __('messages.qa_coverage.all_risks') }}</option>
                            @foreach($risks as $risk)
                                <option value="{{ $risk }}" @selected($filters['risk'] === $risk)>{{ __('messages.endpoints.risks.'.$risk) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <button class="btn btn-primary" type="submit">{{ __('messages.common.filter') }}</button>
                        <a href="{{ route('projects.qa-coverage.index', $project) }}" class="btn btn-default">{{ __('messages.common.reset') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.qa_coverage.matrix') }}</div>
            <div class="panel-body">
                @if($rows->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.qa_coverage.empty') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.endpoints.single') }}</th>
                                    <th>{{ __('messages.endpoints.risk') }}</th>
                                    <th>{{ __('messages.test_cases.title') }}</th>
                                    <th>{{ __('messages.assertions.title') }}</th>
                                    <th>{{ __('messages.scans.title') }}</th>
                                    <th>{{ __('messages.contract_validations.short_title') }}</th>
                                    <th>{{ __('messages.findings.title') }}</th>
                                    <th>{{ __('messages.qa_coverage.score') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.qa_coverage.gaps_title') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('projects.endpoints.show', [$project, $row['endpoint']]) }}"><code>{{ $row['endpoint']->method }} {{ $row['endpoint']->path }}</code></a>
                                        <br><small class="text-muted">{{ $row['endpoint']->name ?: __('messages.common.not_available') }}</small>
                                    </td>
                                    <td><span class="label label-{{ $row['endpoint']->risk_css }}">{{ $row['endpoint']->risk_label }}</span></td>
                                    <td>
                                        <strong>{{ $row['test_cases_count'] }}</strong><br>
                                        <small class="text-muted">
                                            {{ __('messages.test_cases.run_statuses.pass') }}: {{ $row['test_run_counts'][\App\Models\TestCase::RUN_PASS] }},
                                            {{ __('messages.test_cases.run_statuses.fail') }}: {{ $row['test_run_counts'][\App\Models\TestCase::RUN_FAIL] }},
                                            {{ __('messages.test_cases.run_statuses.blocked') }}: {{ $row['test_run_counts'][\App\Models\TestCase::RUN_BLOCKED] }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="label label-{{ $row['assertion_css'] }}">{{ $row['assertion_label'] }}</span><br>
                                        <small class="text-muted">{{ $row['assertion_rules_count'] }} {{ strtolower(__('messages.assertions.title')) }}</small>
                                    </td>
                                    <td>
                                        @if($row['has_scan'])
                                            <span class="label label-{{ $row['latest_scan']->status_css }}">{{ $row['latest_scan']->status_label }}</span><br>
                                            <small class="text-muted">HTTP {{ $row['latest_scan']->status_code ?: 'n/a' }}</small>
                                        @else
                                            <span class="label label-default">{{ __('messages.qa_coverage.no_scan') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row['has_contract'])
                                            <span class="label label-success">{{ $row['contract_counts'][\App\Models\ContractValidationResult::STATUS_PASS] }}</span>
                                            <span class="label label-warning">{{ $row['contract_counts'][\App\Models\ContractValidationResult::STATUS_WARNING] }}</span>
                                            <span class="label label-danger">{{ $row['contract_counts'][\App\Models\ContractValidationResult::STATUS_FAIL] }}</span>
                                        @else
                                            <span class="label label-default">{{ __('messages.qa_coverage.no_contract') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row['open_findings_count'] > 0)
                                            <span class="label label-danger">{{ $row['open_findings_count'] }}</span>
                                        @else
                                            <span class="label label-success">0</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $row['score'] }}</strong></td>
                                    <td><span class="label label-{{ $row['status_css'] }}">{{ $row['status_label'] }}</span></td>
                                    <td>
                                        @forelse(array_merge($row['blockers'], $row['warnings']) as $message)
                                            <div><small>{{ $message }}</small></div>
                                        @empty
                                            <small class="text-success">{{ __('messages.qa_coverage.no_gaps') }}</small>
                                        @endforelse
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
