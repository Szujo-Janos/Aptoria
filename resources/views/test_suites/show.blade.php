@extends('layouts.app')

@section('title', $testSuite->name)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-cases.create', ['project' => $project, 'test_suite_id' => $testSuite->id]) }}" class="btn btn-xs btn-success">{{ __('messages.test_cases.create') }}</a>
                    <a href="{{ route('projects.test-suites.edit', [$project, $testSuite]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                    <a href="{{ route('projects.test-suites.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.test_suites.detail_title') }}
            </div>
            <div class="panel-body">
                <h2 class="m-t-none">{{ $testSuite->name }} <span class="label label-{{ $testSuite->status_css }}">{{ $testSuite->status_label }}</span></h2>
                <p class="text-muted">{{ $testSuite->description ?: __('messages.test_suites.no_description') }}</p>
                <div class="row text-center">
                    <div class="col-sm-2"><h3>{{ $summary['total'] }}</h3><small>{{ __('messages.test_cases.total') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['statuses'][\App\Models\TestCase::RUN_PASS] }}</h3><small>{{ __('messages.test_cases.run_statuses.pass') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['statuses'][\App\Models\TestCase::RUN_FAIL] }}</h3><small>{{ __('messages.test_cases.run_statuses.fail') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['statuses'][\App\Models\TestCase::RUN_BLOCKED] }}</h3><small>{{ __('messages.test_cases.run_statuses.blocked') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['statuses'][\App\Models\TestCase::RUN_SKIPPED] }}</h3><small>{{ __('messages.test_cases.run_statuses.skipped') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $summary['statuses'][\App\Models\TestCase::RUN_NOT_RUN] }}</h3><small>{{ __('messages.test_cases.run_statuses.not_run') }}</small></div>
                </div>
            </div>
            <div class="panel-footer">
                <form method="POST" action="{{ route('projects.test-suites.destroy', [$project, $testSuite]) }}" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.test_suites.confirm_delete') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('messages.common.delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.test-cases.create', ['project' => $project, 'test_suite_id' => $testSuite->id]) }}" class="btn btn-xs btn-success">{{ __('messages.test_cases.create') }}</a></div>
                {{ __('messages.test_cases.title') }}
            </div>
            <div class="panel-body">
                @if($testSuite->testCases->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.test_cases.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.test_cases.empty_help') }}</p>
                        <a href="{{ route('projects.test-cases.create', ['project' => $project, 'test_suite_id' => $testSuite->id]) }}" class="btn btn-success">{{ __('messages.test_cases.create') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.test_cases.title_field') }}</th>
                                    <th>{{ __('messages.endpoints.title') }}</th>
                                    <th>{{ __('messages.test_cases.priority') }}</th>
                                    <th>{{ __('messages.common.type') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.test_cases.last_run_status') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($testSuite->testCases as $case)
                                <tr>
                                    <td><strong>{{ $case->title }}</strong><br><small class="text-muted">{{ \Illuminate\Support\Str::limit($case->description, 100) }}</small></td>
                                    <td>@if($case->endpoint)<a href="{{ route('projects.endpoints.show', [$project, $case->endpoint]) }}"><code>{{ $case->endpoint->method }} {{ $case->endpoint->path }}</code></a>@else<span class="text-muted">{{ __('messages.common.none') }}</span>@endif</td>
                                    <td><span class="label label-{{ $case->priority_css }}">{{ $case->priority_label }}</span></td>
                                    <td>{{ $case->type_label }}</td>
                                    <td><span class="label label-{{ $case->status_css }}">{{ $case->status_label }}</span></td>
                                    <td><span class="label label-{{ $case->last_run_status_css }}">{{ $case->last_run_status_label }}</span></td>
                                    <td class="text-right">
                                        <a href="{{ route('projects.test-cases.show', [$project, $case]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a>
                                        <a href="{{ route('projects.test-cases.edit', [$project, $case]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
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
