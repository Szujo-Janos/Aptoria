@extends('layouts.app')

@section('title', __('messages.scans.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.scans.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.scans.new') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.scans.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                @if($scanRuns->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.scans.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.scans.empty_help') }}</p>
                        <a href="{{ route('projects.scans.create', $project) }}" class="btn btn-success">{{ __('messages.scans.new') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.scans.started_at') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.environments.title') }}</th>
                                    <th>{{ __('messages.scans.total') }}</th>
                                    <th>{{ __('messages.scans.scanned') }}</th>
                                    <th>{{ __('messages.scans.skipped') }}</th>
                                    <th>{{ __('messages.scans.duration') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($scanRuns as $scanRun)
                                <tr>
                                    <td>{{ $scanRun->started_at?->format('Y-m-d H:i:s') ?: $scanRun->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td><span class="label label-{{ $scanRun->status_css }}">{{ $scanRun->status_label }}</span></td>
                                    <td>{{ $scanRun->environment?->name ?: __('messages.endpoints.project_default') }}</td>
                                    <td>{{ $scanRun->total_endpoints }}</td>
                                    <td>{{ $scanRun->scanned_count }}</td>
                                    <td>{{ $scanRun->skipped_count }}</td>
                                    <td>{{ $scanRun->duration_label }}</td>
                                    <td class="text-right"><a href="{{ route('projects.scans.show', [$project, $scanRun]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $scanRuns->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
