@extends('layouts.app')

@section('title', $testCase->title)

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-cases.edit', [$project, $testCase]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                    <a href="{{ route('projects.test-suites.show', [$project, $testCase->testSuite]) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.test_cases.detail_title') }}
            </div>
            <div class="panel-body">
                <h2 class="m-t-none">{{ $testCase->title }}</h2>
                <p class="text-muted">{{ $testCase->description ?: __('messages.test_cases.no_description') }}</p>

                <div class="row m-b-md">
                    <div class="col-sm-3"><strong>{{ __('messages.test_suites.single') }}</strong><br><a href="{{ route('projects.test-suites.show', [$project, $testCase->testSuite]) }}">{{ $testCase->testSuite?->name }}</a></div>
                    <div class="col-sm-3"><strong>{{ __('messages.common.type') }}</strong><br>{{ $testCase->type_label }}</div>
                    <div class="col-sm-3"><strong>{{ __('messages.test_cases.priority') }}</strong><br><span class="label label-{{ $testCase->priority_css }}">{{ $testCase->priority_label }}</span></div>
                    <div class="col-sm-3"><strong>{{ __('messages.common.status') }}</strong><br><span class="label label-{{ $testCase->status_css }}">{{ $testCase->status_label }}</span></div>
                </div>

                <div class="row m-b-md">
                    <div class="col-sm-6">
                        <strong>{{ __('messages.endpoints.title') }}</strong><br>
                        @if($testCase->endpoint)
                            <a href="{{ route('projects.endpoints.show', [$project, $testCase->endpoint]) }}"><code>{{ $testCase->endpoint->method }} {{ $testCase->endpoint->path }}</code></a>
                        @else
                            <span class="text-muted">{{ __('messages.common.none') }}</span>
                        @endif
                    </div>
                    <div class="col-sm-3"><strong>{{ __('messages.test_cases.last_run_status') }}</strong><br><span class="label label-{{ $testCase->last_run_status_css }}">{{ $testCase->last_run_status_label }}</span></div>
                    <div class="col-sm-3"><strong>{{ __('messages.test_cases.last_run_at') }}</strong><br>{{ $testCase->last_run_at?->format('Y-m-d H:i') ?: __('messages.common.not_available') }}</div>
                </div>

                <hr>
                <h4>{{ __('messages.test_cases.preconditions') }}</h4>
                <p>{!! nl2br(e($testCase->preconditions ?: __('messages.common.not_available'))) !!}</p>

                <h4>{{ __('messages.test_cases.steps') }}</h4>
                <div class="well well-sm">{!! nl2br(e($testCase->steps)) !!}</div>

                <h4>{{ __('messages.test_cases.expected_result') }}</h4>
                <div class="well well-sm">{!! nl2br(e($testCase->expected_result)) !!}</div>

                <h4>{{ __('messages.test_cases.actual_result') }}</h4>
                <div class="well well-sm">{!! nl2br(e($testCase->actual_result ?: __('messages.common.not_available'))) !!}</div>
            </div>
            <div class="panel-footer">
                <form method="POST" action="{{ route('projects.test-cases.destroy', [$project, $testCase]) }}" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.test_cases.confirm_delete') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('messages.common.delete') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.test_cases.mark_result') }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.test-cases.results.store', [$project, $testCase]) }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('messages.common.status') }}</label>
                        <select name="status" class="form-control" required>
                            @foreach(\App\Models\TestCaseResult::STATUSES as $status)
                                <option value="{{ $status }}">{{ __('messages.test_cases.run_statuses.'.$status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.test_cases.scan_result_link') }}</label>
                        <select name="scan_result_id" class="form-control">
                            <option value="">{{ __('messages.test_cases.no_scan_result_link') }}</option>
                            @foreach($scanResults as $scanResult)
                                <option value="{{ $scanResult->id }}">
                                    #{{ $scanResult->id }} — {{ $scanResult->endpoint?->method }} {{ $scanResult->endpoint?->path }} — {{ $scanResult->status_code ?: $scanResult->status }} — {{ $scanResult->created_at->format('Y-m-d H:i') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.test_cases.actual_result') }}</label>
                        <textarea name="actual_result" class="form-control" rows="4" placeholder="{{ __('messages.test_cases.actual_result_placeholder') }}">{{ old('actual_result') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.common.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">{{ __('messages.test_cases.record_result') }}</button>
                </form>
            </div>
        </div>

        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.test_cases.latest_result') }}</div>
            <div class="panel-body">
                @if($testCase->latestResult)
                    <span class="label label-{{ $testCase->latestResult->status_css }}">{{ $testCase->latestResult->status_label }}</span>
                    <p class="m-t-sm"><strong>{{ __('messages.test_cases.executed_at') }}:</strong> {{ $testCase->latestResult->executed_at?->format('Y-m-d H:i') }}</p>
                    @if($testCase->latestResult->scanResult)
                        <p><strong>{{ __('messages.test_cases.evidence') }}:</strong> <a href="{{ route('projects.scans.show', [$project, $testCase->latestResult->scanResult->scanRun]) }}">{{ __('messages.scans.last_result') }} #{{ $testCase->latestResult->scanResult->id }}</a></p>
                    @endif
                    <p class="m-b-none">{{ $testCase->latestResult->notes ?: __('messages.common.not_available') }}</p>
                @else
                    <p class="text-muted m-b-none">{{ __('messages.test_cases.no_results_yet') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.findings.create', ['project' => $project, 'test_case_id' => $testCase->id, 'endpoint_id' => $testCase->endpoint_id, 'source' => 'test_case']) }}" class="btn btn-xs btn-danger">{{ __('messages.findings.create') }}</a>
                </div>
                {{ __('messages.findings.linked_title') }}
            </div>
            <div class="panel-body">
                @if($testCase->findings->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.findings.no_test_case_findings') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead><tr><th>{{ __('messages.findings.title_field') }}</th><th>{{ __('messages.findings.severity') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.findings.evidence') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($testCase->findings as $finding)
                                <tr>
                                    <td><strong>{{ $finding->title }}</strong></td>
                                    <td><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span></td>
                                    <td><span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></td>
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
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.test_cases.result_history') }}</div>
            <div class="panel-body">
                @if($testCase->results->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.test_cases.no_results_yet') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.test_cases.executed_at') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.test_cases.actual_result') }}</th>
                                    <th>{{ __('messages.common.notes') }}</th>
                                    <th>{{ __('messages.test_cases.evidence') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($testCase->results->sortByDesc('executed_at') as $result)
                                <tr>
                                    <td>{{ $result->executed_at?->format('Y-m-d H:i') ?: $result->created_at->format('Y-m-d H:i') }}</td>
                                    <td><span class="label label-{{ $result->status_css }}">{{ $result->status_label }}</span></td>
                                    <td>{!! nl2br(e($result->actual_result ?: __('messages.common.not_available'))) !!}</td>
                                    <td>{!! nl2br(e($result->notes ?: __('messages.common.not_available'))) !!}</td>
                                    <td>
                                        @if($result->scanResult && $result->scanResult->scanRun)
                                            <a href="{{ route('projects.scans.show', [$project, $result->scanResult->scanRun]) }}">{{ __('messages.test_cases.scan_result_link') }} #{{ $result->scanResult->id }}</a>
                                        @elseif($result->scanRun)
                                            <a href="{{ route('projects.scans.show', [$project, $result->scanRun]) }}">{{ __('messages.scans.title') }} #{{ $result->scanRun->id }}</a>
                                        @else
                                            <span class="text-muted">{{ __('messages.common.none') }}</span>
                                        @endif
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
