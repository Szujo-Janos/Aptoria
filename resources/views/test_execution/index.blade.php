@extends('layouts.app')

@section('title', __('messages.test_execution.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-cases.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.test_cases.create') }}</a>
                    <a href="{{ route('projects.test-cases.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.test_cases.view_all') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.test_execution.title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.test_execution.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row text-center">
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hblue"><div class="panel-body"><h2>{{ $summary['total'] }}</h2><small>{{ __('messages.test_cases.total') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hgreen"><div class="panel-body"><h2>{{ $summary['execution_percent'] }}%</h2><small>{{ __('messages.test_execution.execution_coverage') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hgreen"><div class="panel-body"><h2>{{ $summary['pass_rate'] }}%</h2><small>{{ __('messages.test_execution.pass_rate') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hred"><div class="panel-body"><h2>{{ $summary['run_counts'][\App\Models\TestCase::RUN_FAIL] }}</h2><small>{{ __('messages.test_cases.run_statuses.fail') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hyellow"><div class="panel-body"><h2>{{ $summary['run_counts'][\App\Models\TestCase::RUN_BLOCKED] }}</h2><small>{{ __('messages.test_cases.run_statuses.blocked') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hred"><div class="panel-body"><h2>{{ $summary['critical_attention'] }}</h2><small>{{ __('messages.test_execution.critical_attention') }}</small></div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.test_execution.status_overview') }}</div>
            <div class="panel-body">
                <div class="row text-center">
                    @foreach(\App\Models\TestCase::RUN_STATUSES as $runStatus)
                        <div class="col-sm-2">
                            <h3 class="m-t-xs"><span class="label label-{{ \App\Models\TestCase::runStatusCss($runStatus) }}">{{ $summary['run_counts'][$runStatus] ?? 0 }}</span></h3>
                            <small>{{ __('messages.test_cases.run_statuses.'.$runStatus) }}</small>
                        </div>
                    @endforeach
                    <div class="col-sm-2">
                        <h3 class="m-t-xs">{{ $summary['ready_not_run'] }}</h3>
                        <small>{{ __('messages.test_execution.ready_not_run') }}</small>
                    </div>
                </div>
                <div class="progress m-t-md m-b-none">
                    <div class="progress-bar progress-bar-success" style="width: {{ $summary['execution_percent'] }}%">{{ $summary['execution_percent'] }}%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.test_execution.suite_matrix') }}</div>
            <div class="panel-body">
                @if($suiteSummaries->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.test_suites.empty_help') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.test_suites.single') }}</th>
                                    <th>{{ __('messages.test_execution.execution_coverage') }}</th>
                                    <th>{{ __('messages.test_execution.pass_rate') }}</th>
                                    <th>{{ __('messages.test_cases.run_statuses.pass') }}</th>
                                    <th>{{ __('messages.test_cases.run_statuses.fail') }}</th>
                                    <th>{{ __('messages.test_cases.run_statuses.blocked') }}</th>
                                    <th>{{ __('messages.test_cases.run_statuses.not_run') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($suiteSummaries as $row)
                                <tr>
                                    <td><strong>{{ $row['suite']->name }}</strong><br><small class="text-muted">{{ $row['total'] }} {{ strtolower(__('messages.test_cases.title')) }}</small></td>
                                    <td>{{ $row['execution_percent'] }}%</td>
                                    <td>{{ $row['pass_rate'] }}%</td>
                                    <td><span class="label label-success">{{ $row['run_counts'][\App\Models\TestCase::RUN_PASS] }}</span></td>
                                    <td><span class="label label-danger">{{ $row['run_counts'][\App\Models\TestCase::RUN_FAIL] }}</span></td>
                                    <td><span class="label label-warning">{{ $row['run_counts'][\App\Models\TestCase::RUN_BLOCKED] }}</span></td>
                                    <td><span class="label label-info">{{ $row['run_counts'][\App\Models\TestCase::RUN_NOT_RUN] }}</span></td>
                                    <td class="text-right"><a href="{{ route('projects.test-execution.index', ['project' => $project, 'suite_id' => $row['suite']->id]) }}" class="btn btn-xs btn-default">{{ __('messages.common.filter') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.test_execution.recent_results') }}</div>
            <div class="panel-body">
                @if($recentResults->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.test_cases.no_results_yet') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed">
                            <thead><tr><th>{{ __('messages.test_cases.executed_at') }}</th><th>{{ __('messages.test_cases.title_field') }}</th><th>{{ __('messages.common.status') }}</th></tr></thead>
                            <tbody>
                            @foreach($recentResults as $result)
                                <tr>
                                    <td>{{ $result->executed_at?->format('Y-m-d H:i') }}</td>
                                    <td><a href="{{ route('projects.test-cases.show', [$project, $result->testCase]) }}">{{ \Illuminate\Support\Str::limit($result->testCase?->title, 42) }}</a></td>
                                    <td><span class="label label-{{ $result->status_css }}">{{ $result->status_label }}</span></td>
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
            <div class="panel-heading hbuilt">{{ __('messages.test_execution.execution_queue') }}</div>
            <div class="panel-body">
                <form method="GET" action="{{ route('projects.test-execution.index', $project) }}" class="row m-b-md">
                    <div class="col-sm-3">
                        <select name="suite_id" class="form-control">
                            <option value="">{{ __('messages.test_cases.all_suites') }}</option>
                            @foreach($suites as $suite)
                                <option value="{{ $suite->id }}" @selected((string) $suiteId === (string) $suite->id)>{{ $suite->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <select name="status" class="form-control">
                            <option value="">{{ __('messages.test_cases.all_run_statuses') }}</option>
                            @foreach(\App\Models\TestCase::RUN_STATUSES as $runStatus)
                                <option value="{{ $runStatus }}" @selected($status === $runStatus)>{{ __('messages.test_cases.run_statuses.'.$runStatus) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <select name="priority" class="form-control">
                            <option value="">{{ __('messages.test_execution.all_priorities') }}</option>
                            @foreach(\App\Models\TestCase::PRIORITIES as $casePriority)
                                <option value="{{ $casePriority }}" @selected($priority === $casePriority)>{{ __('messages.test_cases.priorities.'.$casePriority) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <select name="type" class="form-control">
                            <option value="">{{ __('messages.test_execution.all_types') }}</option>
                            @foreach(\App\Models\TestCase::TYPES as $caseType)
                                <option value="{{ $caseType }}" @selected($type === $caseType)>{{ __('messages.test_cases.types.'.$caseType) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-primary">{{ __('messages.common.filter') }}</button>
                        <a href="{{ route('projects.test-execution.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                    </div>
                </form>

                @if($testCases->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.test_execution.empty_queue') }}</p>
                        <a href="{{ route('projects.test-cases.create', $project) }}" class="btn btn-success btn-sm">{{ __('messages.test_cases.create') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.test_cases.title_field') }}</th>
                                    <th>{{ __('messages.test_suites.single') }}</th>
                                    <th>{{ __('messages.endpoints.title') }}</th>
                                    <th>{{ __('messages.test_cases.priority') }}</th>
                                    <th>{{ __('messages.test_cases.last_run_status') }}</th>
                                    <th>{{ __('messages.test_execution.quick_result') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($testCases as $testCase)
                                <tr>
                                    <td>
                                        <strong>{{ $testCase->title }}</strong><br>
                                        <small class="text-muted">{{ __('messages.common.type') }}: {{ $testCase->type_label }} · {{ __('messages.common.status') }}: {{ $testCase->status_label }}</small>
                                        @if($testCase->findings->whereIn('status', \App\Models\Finding::OPEN_STATUSES)->isNotEmpty())
                                            <br><span class="label label-danger">{{ __('messages.test_execution.open_findings') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $testCase->testSuite?->name ?: __('messages.common.none') }}</td>
                                    <td>
                                        @if($testCase->endpoint)
                                            <a href="{{ route('projects.endpoints.show', [$project, $testCase->endpoint]) }}"><code>{{ $testCase->endpoint->method }} {{ $testCase->endpoint->path }}</code></a>
                                        @else
                                            <span class="text-muted">{{ __('messages.test_cases.no_endpoint_link') }}</span>
                                        @endif
                                    </td>
                                    <td><span class="label label-{{ $testCase->priority_css }}">{{ $testCase->priority_label }}</span></td>
                                    <td>
                                        <span class="label label-{{ $testCase->last_run_status_css }}">{{ $testCase->last_run_status_label }}</span>
                                        @if($testCase->last_run_at)
                                            <br><small class="text-muted">{{ $testCase->last_run_at->format('Y-m-d H:i') }}</small>
                                        @endif
                                    </td>
                                    <td style="min-width: 245px;">
                                        @foreach([\App\Models\TestCaseResult::STATUS_PASS, \App\Models\TestCaseResult::STATUS_FAIL, \App\Models\TestCaseResult::STATUS_BLOCKED, \App\Models\TestCaseResult::STATUS_SKIPPED] as $quickStatus)
                                            <form method="POST" action="{{ route('projects.test-execution.results.store', [$project, $testCase]) }}" style="display:inline">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $quickStatus }}">
                                                <input type="hidden" name="notes" value="{{ __('messages.test_execution.quick_mark_note') }}">
                                                <button type="submit" class="btn btn-xs btn-{{ \App\Models\TestCase::runStatusCss($quickStatus) }}" title="{{ __('messages.test_cases.run_statuses.'.$quickStatus) }}">{{ __('messages.test_execution.short_statuses.'.$quickStatus) }}</button>
                                            </form>
                                        @endforeach
                                    </td>
                                    <td class="text-right"><a href="{{ route('projects.test-cases.show', [$project, $testCase]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $testCases->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
