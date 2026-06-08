@extends('layouts.app')

@section('title', __('messages.snapshots.details_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.snapshots.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                    <a href="{{ route('projects.reports.snapshots.json', [$project, $snapshot]) }}" class="btn btn-xs btn-primary">{{ __('messages.reports.download_json') }}</a>
                    @if($snapshot->scanRun)
                        <a href="{{ route('projects.scans.show', [$project, $snapshot->scanRun]) }}" class="btn btn-xs btn-info">{{ __('messages.scans.open_scan') }}</a>
                    @endif
                </div>
                {{ __('messages.snapshots.details_title') }}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-5">
                        <h3 class="m-t-none">{{ $snapshot->name }}</h3>
                        <p class="text-muted">{{ $snapshot->description ?: __('messages.common.no') }}</p>
                        <dl class="dl-horizontal">
                            <dt>{{ __('messages.environments.title') }}</dt><dd>{{ $snapshot->environment?->name ?: __('messages.endpoints.project_default') }}</dd>
                            <dt>{{ __('messages.snapshots.hash') }}</dt><dd><code>{{ $snapshot->short_hash }}</code></dd>
                            <dt>{{ __('messages.common.created') }}</dt><dd>{{ $snapshot->created_at->format('Y-m-d H:i:s') }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-7">
                        <div class="row text-center">
                            <div class="col-sm-2"><h3>{{ $snapshot->summary_json['endpoint_count'] ?? $snapshot->endpoint_count }}</h3><small>{{ __('messages.snapshots.endpoint_count') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $snapshot->summary_json['critical_count'] ?? 0 }}</h3><small>{{ __('messages.endpoints.risks.critical') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $snapshot->summary_json['high_count'] ?? 0 }}</h3><small>{{ __('messages.endpoints.risks.high') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $snapshot->summary_json['review_count'] ?? 0 }}</h3><small>{{ __('messages.endpoints.risks.review') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $snapshot->summary_json['status_4xx_count'] ?? 0 }}</h3><small>4xx</small></div>
                            <div class="col-sm-2"><h3>{{ $snapshot->summary_json['status_5xx_count'] ?? 0 }}</h3><small>5xx</small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.snapshots.snapshot_items') }}</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="snapshot-items-table">
                        <thead>
                        <tr>
                            <th>{{ __('messages.endpoints.method') }}</th>
                            <th>{{ __('messages.endpoints.path') }}</th>
                            <th>{{ __('messages.endpoints.auth') }}</th>
                            <th>{{ __('messages.endpoints.risk_level') }}</th>
                            <th>{{ __('messages.scans.http_status') }}</th>
                            <th>{{ __('messages.scans.content_type') }}</th>
                            <th>{{ __('messages.scans.response_time') }}</th>
                            <th>{{ __('messages.assertions.assertion_status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($snapshot->items as $item)
                            @php($assertion = $assertionEvaluations[$item->id] ?? null)
                            <tr>
                                <td><span class="label label-default">{{ $item->method }}</span></td>
                                <td><code>{{ $item->path }}</code></td>
                                <td>{{ $item->auth_required ? __('messages.common.yes') : __('messages.common.no') }}</td>
                                <td><span class="label label-{{ $item->risk_css }}">{{ $item->risk_label }}</span></td>
                                <td>{{ $item->status_code ?: __('messages.common.not_available') }}</td>
                                <td>{{ $item->content_type ?: __('messages.common.not_available') }}</td>
                                <td>{{ $item->response_time_ms !== null ? $item->response_time_ms.' ms' : __('messages.common.not_available') }}</td>
                                <td><span class="label label-{{ $assertion['css'] ?? 'default' }}">{{ $assertion['label'] ?? __('messages.assertions.statuses.not_configured') }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#snapshot-items-table').DataTable({ pageLength: 25, order: [[0, 'asc'], [1, 'asc']] });
    });
</script>
@endpush
