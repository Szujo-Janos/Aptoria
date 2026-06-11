@extends('layouts.app')

@section('title', __('messages.contract_validations.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.projects.details') }}</a>
                    <a href="{{ route('projects.contract-validations.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.contract_validations.new') }}</a>
                    <a href="{{ route('projects.contract-reality.index', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.contract_reality.short_title') }}</a>
                </div>
                {{ __('messages.contract_validations.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                @if($runs->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.contract_validations.empty_help') }}</p>
                        <a href="{{ route('projects.contract-validations.create', $project) }}" class="btn btn-success btn-sm">{{ __('messages.contract_validations.new') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.common.created') }}</th>
                                    <th>{{ __('messages.contract_validations.source_name') }}</th>
                                    <th>{{ __('messages.scans.title') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.contract_validations.breaking') }}</th>
                                    <th>{{ __('messages.contract_validations.failed') }}</th>
                                    <th>{{ __('messages.contract_validations.warnings') }}</th>
                                    <th>{{ __('messages.contract_validations.passed') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($runs as $run)
                                <tr>
                                    <td>{{ $run->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $run->source_name ?: __('messages.contract_validations.manual_source') }}</td>
                                    <td>
                                        @if($run->scanRun)
                                            <a href="{{ route('projects.scans.show', [$project, $run->scanRun]) }}">#{{ $run->scanRun->id }}</a>
                                            <small class="text-muted">{{ $run->scanRun->environment?->name ?: __('messages.endpoints.project_default') }}</small>
                                        @else
                                            <span class="text-muted">{{ __('messages.common.not_available') }}</span>
                                        @endif
                                    </td>
                                    <td><span class="label label-{{ $run->status_css }}">{{ $run->health_label }}</span></td>
                                    <td>{{ $run->breaking_count }}</td>
                                    <td>{{ $run->failed_count }}</td>
                                    <td>{{ $run->warning_count }}</td>
                                    <td>{{ $run->passed_count }}</td>
                                    <td class="text-right"><a href="{{ route('projects.contract-validations.show', [$project, $run]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $runs->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
