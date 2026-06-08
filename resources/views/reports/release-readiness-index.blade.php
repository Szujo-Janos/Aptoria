@extends('layouts.app')

@section('title', __('messages.release_readiness.overview_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-default">{{ __('messages.nav.reports') }}</a>
                </div>
                <i class="fa fa-check-circle"></i> {{ __('messages.release_readiness.overview_title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.release_readiness.overview_intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.project_scores') }}</div>
            <div class="panel-body no-padding">
                @if($projects->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.projects.no_project') }}</h4>
                        <p class="text-muted">{{ __('messages.release_readiness.no_projects_help') }}</p>
                        <a href="{{ route('projects.create') }}" class="btn btn-success">{{ __('messages.projects.create_title') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                            <tr>
                                <th>{{ __('messages.common.name') }}</th>
                                <th>{{ __('messages.release_readiness.overall_status') }}</th>
                                <th>{{ __('messages.release_readiness.score') }}</th>
                                <th>{{ __('messages.release_readiness.grade') }}</th>
                                <th>{{ __('messages.release_readiness.endpoint_coverage') }}</th>
                                <th>{{ __('messages.release_readiness.blocking_issues') }}</th>
                                <th>{{ __('messages.release_readiness.warning_items') }}</th>
                                <th class="text-right">{{ __('messages.common.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($projects as $project)
                                @php($summary = $summaries[$project->id] ?? null)
                                <tr>
                                    <td><strong>{{ $project->name }}</strong><br><small class="text-muted"><code>{{ $project->base_url }}</code></small></td>
                                    <td>@if($summary)<span class="label label-{{ $summary['css'] }}">{{ $summary['label'] }}</span>@else {{ __('messages.common.not_available') }} @endif</td>
                                    <td>{{ $summary['score'] ?? 0 }}/100</td>
                                    <td>{{ $summary['grade'] ?? '-' }}</td>
                                    <td>{{ $summary['coverage_percent'] ?? 0 }}%</td>
                                    <td>{{ isset($summary) ? count($summary['blocking_issues']) : 0 }}</td>
                                    <td>{{ isset($summary) ? count($summary['warnings']) : 0 }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.release_readiness.open_dashboard') }}</a>
                                        <a href="{{ route('projects.reports.release-readiness.markdown', $project) }}" class="btn btn-xs btn-default">{{ __('messages.release_readiness.download_report') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer">{{ $projects->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
