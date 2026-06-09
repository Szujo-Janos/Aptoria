@extends('layouts.app')

@section('title', __('messages.snapshots.compare_details_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.snapshots.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                    <a href="{{ route('projects.reports.compares.markdown', [$project, $compareRun]) }}" class="btn btn-xs btn-primary">MD</a>
                    <a href="{{ route('projects.reports.compares.html', [$project, $compareRun]) }}" class="btn btn-xs btn-default">HTML</a>
                    <a href="{{ route('projects.reports.compares.pdf', [$project, $compareRun]) }}" class="btn btn-xs btn-default">PDF</a>
                </div>
                {{ __('messages.snapshots.compare_details_title') }}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-5">
                        <dl class="dl-horizontal">
                            <dt>{{ __('messages.snapshots.baseline_snapshot') }}</dt><dd>{{ $compareRun->snapshotA?->name }}</dd>
                            <dt>{{ __('messages.snapshots.target_snapshot') }}</dt><dd>{{ $compareRun->snapshotB?->name }}</dd>
                            <dt>{{ __('messages.common.created') }}</dt><dd>{{ $compareRun->created_at->format('Y-m-d H:i:s') }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-7">
                        <div class="row text-center">
                            <div class="col-sm-2"><h3>{{ $compareRun->summary_json['total_changes'] ?? 0 }}</h3><small>{{ __('messages.snapshots.total_changes') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $compareRun->summary_json['new_count'] ?? 0 }}</h3><small>{{ __('messages.snapshots.change_types.new') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $compareRun->summary_json['removed_count'] ?? 0 }}</h3><small>{{ __('messages.snapshots.change_types.removed') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $compareRun->summary_json['changed_count'] ?? 0 }}</h3><small>{{ __('messages.snapshots.change_types.changed') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $compareRun->summary_json['critical_count'] ?? 0 }}</h3><small>{{ __('messages.snapshots.severities.critical') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $compareRun->summary_json['high_count'] ?? 0 }}</h3><small>{{ __('messages.snapshots.severities.high') }}</small></div>
                        </div>
                    </div>
                </div>
                @if(($compareRun->summary_json['total_changes'] ?? 0) === 0)
                    <hr>
                    <div class="alert alert-success m-b-none">{{ __('messages.snapshots.no_changes_detected') }}</div>
                @endif
                <hr>
                <div class="row text-center">
                    <div class="col-sm-3"><h3><span class="label label-{{ $regressionEvaluation['css'] }}">{{ $regressionEvaluation['label'] }}</span></h3><small>{{ __('messages.regressions.regression_status') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $regressionEvaluation['detected_count'] }}</h3><small>{{ __('messages.regressions.detected_count') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $regressionEvaluation['warning_count'] }}</h3><small>{{ __('messages.regressions.warning_count') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $regressionEvaluation['recovered_count'] }}</h3><small>{{ __('messages.regressions.recovered_count') }}</small></div>
                    <div class="col-sm-3"><h3>{{ $regressionEvaluation['improved_count'] }}</h3><small>{{ __('messages.regressions.improved_count') }}</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.snapshots.detected_changes') }}</div>
            <div class="panel-body">
                @if($compareRun->items->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.snapshots.no_changes_detected') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="compare-items-table">
                            <thead>
                            <tr>
                                <th>{{ __('messages.snapshots.change_type') }}</th>
                                <th>{{ __('messages.snapshots.severity') }}</th>
                                <th>{{ __('messages.endpoints.method') }}</th>
                                <th>{{ __('messages.endpoints.path') }}</th>
                                <th>{{ __('messages.snapshots.field_changed') }}</th>
                                <th>{{ __('messages.snapshots.old_value') }}</th>
                                <th>{{ __('messages.snapshots.new_value') }}</th>
                                <th>{{ __('messages.assertions.assertion_status') }}</th>
                                <th>{{ __('messages.regressions.regression_status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($compareRun->items as $item)
                                @php($endpointKey = strtoupper($item->method).' '.strtolower($item->path))
                                @php($assertion = $assertionEvaluations[$endpointKey] ?? null)
                                @php($regression = $regressionEvaluation['endpoints'][$endpointKey] ?? null)
                                <tr>
                                    <td><span class="label label-{{ $item->change_css }}">{{ $item->change_label }}</span></td>
                                    <td><span class="label label-{{ $item->severity_css }}">{{ $item->severity_label }}</span></td>
                                    <td><span class="label label-default">{{ $item->method }}</span></td>
                                    <td><code>{{ $item->path }}</code></td>
                                    <td>{{ $item->field_changed ? __('messages.snapshots.fields.'.$item->field_changed) : __('messages.common.not_available') }}</td>
                                    <td>{{ $item->old_value ?: __('messages.common.not_available') }}</td>
                                    <td>{{ $item->new_value ?: __('messages.common.not_available') }}</td>
                                    <td><span class="label label-{{ $assertion['css'] ?? 'default' }}">{{ $assertion['label'] ?? __('messages.assertions.statuses.not_configured') }}</span></td>
                                    <td><span class="label label-{{ $regression['css'] ?? 'success' }}">{{ $regression['label'] ?? __('messages.regressions.statuses.none') }}</span></td>
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

@push('scripts')
<script>
    $(function () {
        $('#compare-items-table').DataTable({ pageLength: 25, order: [[1, 'asc'], [0, 'asc']] });
    });
</script>
@endpush
