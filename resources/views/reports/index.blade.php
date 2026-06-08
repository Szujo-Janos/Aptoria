@extends('layouts.app')

@section('title', __('messages.reports.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.reports.title') }}</div>
            <div class="panel-body">
                <h3 class="m-t-none">{{ __('messages.reports.headline') }}</h3>
                <p class="text-muted">{{ __('messages.reports.intro') }}</p>
                <div class="alert alert-info m-b-none">{{ __('messages.reports.safe_notice') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.reports.project_reports') }}</div>
            <div class="panel-body">
                @if($projects->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.projects.no_project') }}</h4>
                        <p class="text-muted">{{ __('messages.reports.no_projects_help') }}</p>
                        <a href="{{ route('projects.create') }}" class="btn btn-success">{{ __('messages.projects.create_title') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>{{ __('messages.common.name') }}</th>
                                <th>{{ __('messages.projects.endpoints') }}</th>
                                <th>{{ __('messages.projects.scans') }}</th>
                                <th>{{ __('messages.snapshots.title') }}</th>
                                <th>{{ __('messages.snapshots.recent_compares') }}</th>
                                <th class="text-right">{{ __('messages.common.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($projects as $project)
                                <tr>
                                    <td><strong>{{ $project->name }}</strong><br><small class="text-muted"><code>{{ $project->base_url }}</code></small></td>
                                    <td>{{ $project->endpoints_count }}</td>
                                    <td>{{ $project->scan_runs_count }}</td>
                                    <td>{{ $project->snapshots_count }}</td>
                                    <td>{{ $project->compare_runs_count }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.reports.open_center') }}</a>
                                        <a href="{{ route('projects.reports.endpoints.csv', $project) }}" class="btn btn-xs btn-default">{{ __('messages.reports.endpoint_csv') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $projects->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
