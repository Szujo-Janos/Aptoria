@extends('layouts.app')

@section('title', __('messages.snapshots.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.snapshots.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.snapshots.intro') }}</p>
                <div class="row text-center">
                    <div class="col-sm-3"><h3>{{ $snapshotOptions->count() }}</h3><small>{{ __('messages.snapshots.saved_snapshots') }}</small></div>
                    <div class="col-sm-3"><h3>{{ $compareRuns->count() }}</h3><small>{{ __('messages.snapshots.recent_compares') }}</small></div>
                    <div class="col-sm-3"><h3>{{ $snapshotOptions->sum('endpoint_count') }}</h3><small>{{ __('messages.snapshots.total_snapshot_items') }}</small></div>
                    <div class="col-sm-3"><h3>{{ config('aptoria.version') }}</h3><small>{{ __('messages.app.version') }}</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.snapshots.compare_snapshots') }}</div>
            <div class="panel-body">
                @if($snapshotOptions->count() < 2)
                    <p class="text-muted m-b-none">{{ __('messages.snapshots.compare_need_two') }}</p>
                @else
                    <form method="POST" action="{{ route('projects.snapshots.compare', $project) }}">
                        @csrf
                        <div class="form-group">
                            <label>{{ __('messages.snapshots.baseline_snapshot') }}</label>
                            <select name="snapshot_a_id" class="form-control" required>
                                @foreach($snapshotOptions as $snapshot)
                                    <option value="{{ $snapshot->id }}">#{{ $snapshot->id }} — {{ $snapshot->name }} — {{ $snapshot->created_at->format('Y-m-d H:i') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('messages.snapshots.target_snapshot') }}</label>
                            <select name="snapshot_b_id" class="form-control" required>
                                @foreach($snapshotOptions as $snapshot)
                                    <option value="{{ $snapshot->id }}" @selected($loop->first)>#{{ $snapshot->id }} — {{ $snapshot->name }} — {{ $snapshot->created_at->format('Y-m-d H:i') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">{{ __('messages.snapshots.run_compare') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.snapshots.recent_compares') }}</div>
            <div class="panel-body">
                @if($compareRuns->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.snapshots.no_compares') }}</p>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.snapshots.baseline_snapshot') }}</th><th>{{ __('messages.snapshots.target_snapshot') }}</th><th>{{ __('messages.snapshots.total_changes') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($compareRuns as $compareRun)
                            <tr>
                                <td>{{ $compareRun->snapshotA?->name }}</td>
                                <td>{{ $compareRun->snapshotB?->name }}</td>
                                <td>{{ $compareRun->summary_json['total_changes'] ?? 0 }}</td>
                                <td class="text-right"><a href="{{ route('projects.snapshots.compares.show', [$project, $compareRun]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.snapshots.saved_snapshots') }}</div>
            <div class="panel-body">
                @if($snapshots->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.snapshots.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.snapshots.empty_help') }}</p>
                        <a href="{{ route('projects.scans.index', $project) }}" class="btn btn-success">{{ __('messages.scans.view_all') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>{{ __('messages.common.name') }}</th>
                                <th>{{ __('messages.environments.title') }}</th>
                                <th>{{ __('messages.snapshots.endpoint_count') }}</th>
                                <th>{{ __('messages.snapshots.hash') }}</th>
                                <th>{{ __('messages.common.created') }}</th>
                                <th class="text-right">{{ __('messages.common.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($snapshots as $snapshot)
                                <tr>
                                    <td>{{ $snapshot->name }}</td>
                                    <td>{{ $snapshot->environment?->name ?: __('messages.endpoints.project_default') }}</td>
                                    <td>{{ $snapshot->endpoint_count }}</td>
                                    <td><code>{{ $snapshot->short_hash }}</code></td>
                                    <td>{{ $snapshot->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="text-right"><a href="{{ route('projects.snapshots.show', [$project, $snapshot]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $snapshots->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
