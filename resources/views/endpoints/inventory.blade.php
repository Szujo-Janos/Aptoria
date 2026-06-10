@extends('layouts.app')

@section('title', __('messages.endpoint_inventory.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.snapshots.index', $project) }}" class="btn btn-xs btn-warning"><i class="fa fa-exchange"></i> {{ __('messages.snapshots.compare_snapshots') }}</a>
                    <a href="{{ route('projects.endpoints.import.form', $project) }}" class="btn btn-xs btn-info"><i class="fa fa-upload"></i> {{ __('messages.endpoints.import_title') }}</a>
                    <a href="{{ route('projects.endpoints.create', $project) }}" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> {{ __('messages.endpoints.new') }}</a>
                    <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-list"></i> {{ __('messages.endpoints.view_all') }}</a>
                </div>
                {{ __('messages.endpoint_inventory.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.endpoint_inventory.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row text-center">
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hblue"><div class="panel-body"><h2>{{ $summary['total'] }}</h2><small>{{ __('messages.endpoint_inventory.metrics.total') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hgreen"><div class="panel-body"><h2>{{ $summary['scan_coverage_percent'] }}%</h2><small>{{ __('messages.endpoint_inventory.metrics.scanned') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hyellow"><div class="panel-body"><h2>{{ $summary['review_queue'] }}</h2><small>{{ __('messages.endpoint_inventory.metrics.review_queue') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hred"><div class="panel-body"><h2>{{ $summary['open_findings'] }}</h2><small>{{ __('messages.endpoint_inventory.metrics.open_findings') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hblue"><div class="panel-body"><h2>{{ $summary['auth_required'] }}</h2><small>{{ __('messages.endpoint_inventory.metrics.auth_required') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hred"><div class="panel-body"><h2>{{ $summary['sensitive_data'] }}</h2><small>{{ __('messages.endpoint_inventory.metrics.sensitive_data') }}</small></div></div>
    </div>
</div>
<div class="row text-center">
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hred"><div class="panel-body"><h2>{{ $summary['broken_auth'] }}</h2><small>{{ __('messages.endpoint_inventory.metrics.broken_auth') }}</small></div></div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="hpanel hyellow"><div class="panel-body"><h2>{{ $summary['schema_drift'] }}</h2><small>{{ __('messages.endpoint_inventory.metrics.schema_drift') }}</small></div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.endpoint_inventory.filters.title') }}</div>
            <div class="panel-body">
                <form method="GET" action="{{ route('projects.endpoint-inventory.index', $project) }}">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>{{ __('messages.endpoint_inventory.filters.search') }}</label>
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="{{ __('messages.endpoint_inventory.filters.search_placeholder') }}">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.endpoints.method') }}</label>
                                <select name="method" class="form-control">
                                    <option value="">{{ __('messages.endpoint_inventory.filters.all_methods') }}</option>
                                    @foreach($filterOptions['methods'] as $method)
                                        <option value="{{ $method }}" @selected($filters['method'] === $method)>{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.endpoints.risk_level') }}</label>
                                <select name="risk" class="form-control">
                                    <option value="">{{ __('messages.endpoint_inventory.filters.all_risks') }}</option>
                                    @foreach($filterOptions['risks'] as $risk)
                                        <option value="{{ $risk }}" @selected($filters['risk'] === $risk)>{{ __('messages.endpoints.risks.'.$risk) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.environments.title') }}</label>
                                <select name="environment" class="form-control">
                                    <option value="">{{ __('messages.endpoint_inventory.filters.all_environments') }}</option>
                                    <option value="project_default" @selected($filters['environment'] === 'project_default')>{{ __('messages.endpoints.project_default') }}</option>
                                    @foreach($filterOptions['environments'] as $environment)
                                        <option value="{{ $environment->id }}" @selected($filters['environment'] === (string) $environment->id)>{{ $environment->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.endpoints.auth') }}</label>
                                <select name="auth" class="form-control">
                                    <option value="">{{ __('messages.endpoint_inventory.filters.all_auth') }}</option>
                                    @foreach($filterOptions['auth'] as $auth)
                                        <option value="{{ $auth }}" @selected($filters['auth'] === $auth)>{{ __('messages.endpoint_inventory.auth_filters.'.$auth) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.endpoint_inventory.scan') }}</label>
                                <select name="scan" class="form-control">
                                    <option value="">{{ __('messages.endpoint_inventory.filters.all_scan_states') }}</option>
                                    @foreach($filterOptions['scan'] as $scan)
                                        <option value="{{ $scan }}" @selected($filters['scan'] === $scan)>{{ __('messages.endpoint_inventory.scan_filters.'.$scan) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.findings.title') }}</label>
                                <select name="findings" class="form-control">
                                    <option value="">{{ __('messages.endpoint_inventory.filters.all_findings') }}</option>
                                    @foreach($filterOptions['findings'] as $finding)
                                        <option value="{{ $finding }}" @selected($filters['findings'] === $finding)>{{ __('messages.endpoint_inventory.finding_filters.'.$finding) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.endpoint_inventory.coverage') }}</label>
                                <select name="coverage" class="form-control">
                                    <option value="">{{ __('messages.endpoint_inventory.filters.all_coverage') }}</option>
                                    @foreach($filterOptions['coverage'] as $coverage)
                                        <option value="{{ $coverage }}" @selected($filters['coverage'] === $coverage)>{{ __('messages.endpoint_inventory.coverage_filters.'.$coverage) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.endpoint_inventory.source') }}</label>
                                <select name="source" class="form-control">
                                    <option value="">{{ __('messages.endpoint_inventory.filters.all_sources') }}</option>
                                    @foreach($filterOptions['source'] as $source)
                                        <option value="{{ $source }}" @selected($filters['source'] === $source)>{{ __('messages.endpoint_inventory.sources.'.$source) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.common.status') }}</label>
                                <select name="status" class="form-control">
                                    <option value="">{{ __('messages.endpoint_inventory.filters.all_statuses') }}</option>
                                    @foreach($filterOptions['status'] as $status)
                                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ __('messages.endpoint_inventory.status_filters.'.$status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{{ __('messages.endpoint_inventory.filters.sort') }}</label>
                                <select name="sort" class="form-control">
                                    @foreach($filterOptions['sort'] as $sort)
                                        <option value="{{ $sort }}" @selected($filters['sort'] === $sort)>{{ __('messages.endpoint_inventory.sort.'.$sort) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> {{ __('messages.common.filter') }}</button>
                    <a href="{{ route('projects.endpoint-inventory.index', $project) }}" class="btn btn-default">{{ __('messages.common.reset') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <span class="label label-default">{{ $endpoints->total() }} {{ __('messages.endpoint_inventory.rows') }}</span>
                </div>
                {{ __('messages.endpoint_inventory.table_title') }}
            </div>
            <div class="panel-body">
                @if($endpoints->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.endpoint_inventory.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.endpoint_inventory.empty_help') }}</p>
                        <a href="{{ route('projects.endpoints.import.form', $project) }}" class="btn btn-info">{{ __('messages.endpoints.import_title') }}</a>
                        <a href="{{ route('projects.endpoints.create', $project) }}" class="btn btn-success">{{ __('messages.endpoints.new') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover aptoria-endpoint-inventory-table">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.endpoints.method') }}</th>
                                    <th>{{ __('messages.endpoints.path') }}</th>
                                    <th>{{ __('messages.environments.title') }}</th>
                                    <th>{{ __('messages.endpoints.auth') }}</th>
                                    <th>{{ __('messages.endpoints.risk_level') }}</th>
                                    <th>{{ __('messages.endpoint_inventory.last_scan') }}</th>
                                    <th>{{ __('messages.scans.http_status') }}</th>
                                    <th>{{ __('messages.scans.response_time') }}</th>
                                    <th>{{ __('messages.sensitive_data.short_title') }}</th>
                                    <th>{{ __('messages.broken_auth.short_title') }}</th>
                                    <th>{{ __('messages.schema_drift.short_title') }}</th>
                                    <th>{{ __('messages.endpoint_inventory.findings_count') }}</th>
                                    <th>{{ __('messages.endpoint_inventory.source') }}</th>
                                    <th>{{ __('messages.endpoint_inventory.coverage') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($endpoints as $endpoint)
                                <tr>
                                    <td><span class="label label-default">{{ $endpoint->method }}</span></td>
                                    <td>
                                        <strong><code>{{ $endpoint->path }}</code></strong>
                                        @if($endpoint->name)<br><small class="text-muted">{{ $endpoint->name }}</small>@endif
                                        @foreach($endpoint->tag_list as $tag)
                                            <span class="label label-default">{{ $tag }}</span>
                                        @endforeach
                                    </td>
                                    <td>{{ $endpoint->environment?->name ?: __('messages.endpoints.project_default') }}</td>
                                    <td>
                                        @if($endpoint->auth_required)
                                            <span class="label label-warning">{{ __('messages.endpoints.auth_required_short') }}</span>
                                        @else
                                            <span class="label label-info">{{ __('messages.endpoints.public_or_unknown') }}</span>
                                        @endif
                                        @if($endpoint->authProfile)
                                            <br><small class="text-muted">{{ $endpoint->authProfile->name }}</small>
                                        @endif
                                    </td>
                                    <td><span class="label label-{{ $endpoint->risk_css }}">{{ $endpoint->risk_label }}</span></td>
                                    <td>
                                        <span class="label label-{{ $endpoint->inventory_scan_css }}">{{ $endpoint->inventory_scan_label }}</span>
                                        @if($endpoint->latestScanResult?->scanRun)
                                            <br><small class="text-muted">{{ $endpoint->latestScanResult->scanRun->created_at?->format('Y-m-d H:i') }}</small>
                                        @elseif($endpoint->latestScanResult)
                                            <br><small class="text-muted">{{ $endpoint->latestScanResult->created_at?->format('Y-m-d H:i') }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $endpoint->latestScanResult?->status_code ?: __('messages.common.not_available') }}</td>
                                    <td>{{ $endpoint->latestScanResult?->response_time_ms !== null ? $endpoint->latestScanResult->response_time_ms.' ms' : __('messages.common.not_available') }}</td>
                                    <td>
                                        @if($endpoint->latestScanResult?->sensitive_data_detected)
                                            <span class="label label-danger">{{ __('messages.sensitive_data.detected') }}</span>
                                            <br><small>{{ $endpoint->latestScanResult->sensitive_data_count }}</small>
                                        @else
                                            <span class="label label-success">{{ __('messages.sensitive_data.not_detected') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($endpoint->latestScanResult?->broken_auth_detected)
                                            <span class="label label-danger">{{ __('messages.broken_auth.detected') }}</span>
                                        @elseif(is_array($endpoint->latestScanResult?->broken_auth_summary_json))
                                            <span class="label label-success">{{ __('messages.broken_auth.not_detected') }}</span>
                                        @else
                                            <span class="label label-default">{{ __('messages.broken_auth.not_checked') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($endpoint->latestScanResult?->schema_drift_detected)
                                            <span class="label label-warning">{{ __('messages.schema_drift.detected') }}</span>
                                            <br><small>{{ $endpoint->latestScanResult->schema_drift_count }} · {{ $endpoint->latestScanResult->schema_drift_summary_label }}</small>
                                        @elseif(is_array($endpoint->latestScanResult?->schema_drift_summary_json))
                                            <span class="label label-success">{{ __('messages.schema_drift.not_detected') }}</span>
                                        @else
                                            <span class="label label-default">{{ __('messages.schema_drift.not_checked') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($endpoint->open_findings_count ?? 0) > 0)
                                            <a href="{{ route('projects.findings.index', ['project' => $project, 'endpoint_id' => $endpoint->id]) }}" class="label label-danger">{{ $endpoint->open_findings_count }}</a>
                                        @else
                                            <span class="label label-success">0</span>
                                        @endif
                                    </td>
                                    <td><span class="label label-{{ $endpoint->inventory_source['css'] }}">{{ $endpoint->inventory_source['label'] }}</span></td>
                                    <td>
                                        @if(empty($endpoint->inventory_flags))
                                            <span class="label label-success">{{ __('messages.endpoint_inventory.flags.ready') }}</span>
                                        @else
                                            @foreach($endpoint->inventory_flags as $flag)
                                                <span class="label label-{{ $flag['css'] }} m-r-xs">{{ $flag['label'] }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($endpoint->isProbeable())
                                            <form method="POST" action="{{ route('projects.endpoints.probe', [$project, $endpoint]) }}" style="display:inline" data-aptoria-scan-form="true">@csrf<button type="submit" class="btn btn-xs btn-success" data-aptoria-submit-label="{{ __('messages.scans.probing') }}">{{ __('messages.scans.probe') }}</button></form>
                                        @endif
                                        <a href="{{ route('projects.endpoints.show', [$project, $endpoint]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a>
                                        <a href="{{ route('projects.endpoints.edit', [$project, $endpoint]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $endpoints->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
