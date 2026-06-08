@extends('layouts.app')

@section('title', __('messages.test_cases.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-cases.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.test_cases.create') }}</a>
                    <a href="{{ route('projects.test-suites.index', $project) }}" class="btn btn-xs btn-info">{{ __('messages.test_suites.title') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.test_cases.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <form method="GET" action="{{ route('projects.test-cases.index', $project) }}" class="row m-b-md">
                    <div class="col-sm-5">
                        <select name="suite_id" class="form-control">
                            <option value="">{{ __('messages.test_cases.all_suites') }}</option>
                            @foreach($suites as $suite)
                                <option value="{{ $suite->id }}" @selected((string) $suiteId === (string) $suite->id)>{{ $suite->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <select name="status" class="form-control">
                            <option value="">{{ __('messages.test_cases.all_run_statuses') }}</option>
                            @foreach(\App\Models\TestCase::RUN_STATUSES as $runStatus)
                                <option value="{{ $runStatus }}" @selected($status === $runStatus)>{{ __('messages.test_cases.run_statuses.'.$runStatus) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-primary btn-block">{{ __('messages.common.filter') }}</button>
                    </div>
                </form>

                @if($testCases->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.test_cases.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.test_cases.empty_help') }}</p>
                        <a href="{{ route('projects.test-cases.create', $project) }}" class="btn btn-success">{{ __('messages.test_cases.create') }}</a>
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
                                    <th>{{ __('messages.common.type') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.test_cases.last_run_status') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($testCases as $case)
                                <tr>
                                    <td><strong>{{ $case->title }}</strong><br><small class="text-muted">{{ \Illuminate\Support\Str::limit($case->description, 100) }}</small></td>
                                    <td><a href="{{ route('projects.test-suites.show', [$project, $case->testSuite]) }}">{{ $case->testSuite?->name }}</a></td>
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
                    {{ $testCases->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
