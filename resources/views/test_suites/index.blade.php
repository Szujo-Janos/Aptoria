@extends('layouts.app')

@section('title', __('messages.test_suites.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-suites.builder', $project) }}" class="btn btn-xs btn-success"><i class="fa fa-magic"></i> {{ __('messages.regression_builder.short_title') }}</a>
                    <a href="{{ route('projects.test-suites.create', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.test_suites.create') }}</a>
                    <a href="{{ route('projects.test-cases.index', $project) }}" class="btn btn-xs btn-info">{{ __('messages.test_cases.title') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.test_suites.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.test_suites.intro') }}</p>
                @if($suites->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.test_suites.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.test_suites.empty_help') }}</p>
                        <a href="{{ route('projects.test-suites.builder', $project) }}" class="btn btn-success">{{ __('messages.regression_builder.short_title') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.common.name') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.test_cases.title') }}</th>
                                    <th>{{ __('messages.common.created') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($suites as $suite)
                                <tr>
                                    <td>
                                        <strong>{{ $suite->name }}</strong>
                                        @if($suite->description)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($suite->description, 100) }}</small>@endif
                                    </td>
                                    <td><span class="label label-{{ $suite->status_css }}">{{ $suite->status_label }}</span></td>
                                    <td>{{ $suite->test_cases_count }}</td>
                                    <td>{{ $suite->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('projects.test-suites.show', [$project, $suite]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a>
                                        <a href="{{ route('projects.test-suites.edit', [$project, $suite]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $suites->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
