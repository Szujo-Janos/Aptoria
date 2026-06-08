@extends('layouts.app')

@section('title', __('messages.scans.details_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.scans.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                    <a href="{{ route('projects.reports.scans.markdown', [$project, $scanRun]) }}" class="btn btn-xs btn-primary">{{ __('messages.reports.download_markdown') }}</a>
                    <a href="{{ route('projects.scans.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.scans.new') }}</a>
                </div>
                {{ __('messages.scans.details_title') }}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <h3 class="m-t-none">{{ $project->name }}</h3>
                        <p><span class="label label-{{ $scanRun->status_css }}">{{ $scanRun->status_label }}</span> <span class="label label-info">{{ strtoupper($scanRun->mode) }}</span></p>
                    </div>
                    <div class="col-md-8">
                        <div class="row text-center">
                            <div class="col-sm-2"><h3>{{ $scanRun->total_endpoints }}</h3><small>{{ __('messages.scans.total') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $scanRun->scanned_count }}</h3><small>{{ __('messages.scans.scanned') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $scanRun->skipped_count }}</h3><small>{{ __('messages.scans.skipped') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $scanRun->success_count }}</h3><small>{{ __('messages.scans.success') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $scanRun->warning_count }}</h3><small>{{ __('messages.scans.warnings') }}</small></div>
                            <div class="col-sm-2"><h3>{{ $scanRun->error_count }}</h3><small>{{ __('messages.scans.errors') }}</small></div>
                        </div>
                    </div>
                </div>
                <hr>
                <dl class="dl-horizontal">
                    <dt>{{ __('messages.scans.started_at') }}</dt><dd>{{ $scanRun->started_at?->format('Y-m-d H:i:s') ?: __('messages.common.not_available') }}</dd>
                    <dt>{{ __('messages.scans.finished_at') }}</dt><dd>{{ $scanRun->finished_at?->format('Y-m-d H:i:s') ?: __('messages.common.not_available') }}</dd>
                    <dt>{{ __('messages.scans.duration') }}</dt><dd>{{ $scanRun->duration_label }}</dd>
                    <dt>{{ __('messages.environments.title') }}</dt><dd>{{ $scanRun->environment?->name ?: __('messages.endpoints.project_default') }}</dd>
                </dl>
                @if($scanRun->error_message)
                    <div class="alert alert-danger">{{ $scanRun->error_message }}</div>
                @endif
                <hr>
                @if($scanRun->snapshot)
                    <div class="alert alert-info m-b-none">
                        {{ __('messages.snapshots.already_saved') }}
                        <a href="{{ route('projects.snapshots.show', [$project, $scanRun->snapshot]) }}" class="alert-link">{{ $scanRun->snapshot->name }}</a>
                    </div>
                @else
                    <form method="POST" action="{{ route('projects.scans.snapshots.store', [$project, $scanRun]) }}" class="form-inline">
                        @csrf
                        <div class="form-group m-r-sm">
                            <input type="text" name="name" class="form-control input-sm" placeholder="{{ __('messages.snapshots.name_placeholder') }}">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('messages.snapshots.save_from_scan') }}</button>
                        <span class="help-block m-b-none">{{ __('messages.snapshots.save_from_scan_help') }}</span>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.risk.scan_summary') }}</div>
            <div class="panel-body">
                <div class="row text-center">
                    @foreach(\App\Models\Endpoint::RISKS as $level)
                        <div class="col-sm-2">
                            <h3>{{ $riskSummary[$level] }}</h3>
                            <small><span class="label label-{{ match($level) { 'critical' => 'danger', 'high' => 'warning', 'public' => 'info', 'low' => 'success', default => 'default' } }}">{{ __('messages.endpoints.risks.'.$level) }}</span></small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.risk.top_risky_endpoints') }}</div>
            <div class="panel-body">
                @if($topRiskyResults->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.risk.no_risky_results') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.endpoints.method') }}</th>
                                    <th>{{ __('messages.endpoints.path') }}</th>
                                    <th>{{ __('messages.risk.final_level') }}</th>
                                    <th>{{ __('messages.risk.score') }}</th>
                                    <th>{{ __('messages.risk.detected_signals') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($topRiskyResults as $result)
                                @php($analysis = $resultAnalyses[$result->id])
                                <tr>
                                    <td><span class="label label-default">{{ $result->method }}</span></td>
                                    <td>
                                        @if($result->endpoint)
                                            <a href="{{ route('projects.endpoints.show', [$project, $result->endpoint]) }}"><code>{{ $result->endpoint->path }}</code></a>
                                        @else
                                            <code>{{ $result->url }}</code>
                                        @endif
                                    </td>
                                    <td><span class="label label-{{ $analysis['final_css'] }}">{{ $analysis['final_label'] }}</span></td>
                                    <td>{{ $analysis['score'] }}</td>
                                    <td>{{ count($analysis['signals']) }}</td>
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

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.scans.results') }}</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="scan-results-table">
                        <thead>
                            <tr>
                                <th>{{ __('messages.endpoints.method') }}</th>
                                <th>{{ __('messages.endpoints.path') }}</th>
                                <th>{{ __('messages.auth_profiles.title') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.scans.http_status') }}</th>
                                <th>{{ __('messages.scans.response_time') }}</th>
                                <th>{{ __('messages.scans.content_type') }}</th>
                                <th>{{ __('messages.risk.final_level') }}</th>
                                <th>{{ __('messages.assertions.assertion_status') }}</th>
                                <th>{{ __('messages.regressions.regression_status') }}</th>
                                <th>{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($scanRun->results as $result)
                            @php($analysis = $resultAnalyses[$result->id])
                            @php($assertion = $assertionEvaluations[$result->id] ?? null)
                            @php($regression = $regressionEvaluations[$result->id] ?? null)
                            <tr>
                                <td><span class="label label-default">{{ $result->method }}</span></td>
                                <td>
                                    @if($result->endpoint)
                                        <a href="{{ route('projects.endpoints.show', [$project, $result->endpoint]) }}"><code>{{ $result->endpoint->path }}</code></a>
                                    @else
                                        <code>{{ $result->url }}</code>
                                    @endif
                                    <br><small class="text-muted">{{ $result->url }}</small>
                                    @if($result->error_message)<br><small class="text-danger">{{ $result->error_message }}</small>@endif
                                </td>
                                <td><code>{{ $result->auth_summary ?: __('messages.common.none') }}</code><br><small>{{ $result->auth_applied ? __('messages.auth_profiles.applied') : __('messages.auth_profiles.not_applied') }}</small></td>
                                <td><span class="label label-{{ $result->status_css }}">{{ $result->status_label }}</span></td>
                                <td>{{ $result->status_code ?: __('messages.common.not_available') }}</td>
                                <td>{{ $result->response_time_ms !== null ? $result->response_time_ms.' ms' : __('messages.common.not_available') }}</td>
                                <td>{{ $result->content_type ?: __('messages.common.not_available') }}</td>
                                <td>
                                    <span class="label label-{{ $analysis['final_css'] }}">{{ $analysis['final_label'] }}</span>
                                    <br><small>{{ __('messages.risk.score') }}: {{ $analysis['score'] }} / {{ __('messages.risk.signals_count') }}: {{ count($analysis['signals']) }}</small>
                                </td>
                                <td>
                                    <span class="label label-{{ $assertion['css'] ?? 'default' }}">{{ $assertion['label'] ?? __('messages.assertions.statuses.not_configured') }}</span>
                                    @if($assertion && ($assertion['failed_rules'] || $assertion['warning_rules']))
                                        <br><small>{{ count($assertion['failed_rules']) }} {{ __('messages.assertions.failed_rules') }} / {{ count($assertion['warning_rules']) }} {{ __('messages.assertions.warning_rules') }}</small>
                                        <ul class="m-b-none p-l-sm">
                                            @foreach(array_slice(array_merge($assertion['failed_rules'], $assertion['warning_rules']), 0, 3) as $assertionMessage)
                                                <li><small>{{ $assertionMessage['message'] }}</small></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                                <td><span class="label label-{{ $regression['css'] ?? 'success' }}">{{ $regression['label'] ?? __('messages.regressions.statuses.none') }}</span></td>
                                <td class="text-right">
                                    @if($result->status !== \App\Models\ScanResult::STATUS_COMPLETED || ($assertion && (($assertion['status'] ?? '') === \App\Services\AssertionEvaluationService::STATUS_FAIL || ($assertion['status'] ?? '') === \App\Services\AssertionEvaluationService::STATUS_WARNING)))
                                        <a href="{{ route('projects.findings.create', ['project' => $project, 'scan_result_id' => $result->id]) }}" class="btn btn-xs btn-danger">{{ __('messages.findings.create') }}</a>
                                    @else
                                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @if($result->headers_json || $result->body_preview)
                                <tr>
                                    <td></td>
                                    <td colspan="10">
                                        <details>
                                            <summary>{{ __('messages.scans.response_preview') }}</summary>
                                            @if($result->headers_json)
                                                <strong>{{ __('messages.scans.headers') }}</strong>
                                                <pre class="code-block"><code>{{ json_encode($result->headers_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                            @endif
                                            @if($result->body_preview)
                                                <strong>{{ __('messages.scans.body_preview') }}</strong>
                                                <pre class="code-block"><code>{{ $result->body_preview }}</code></pre>
                                            @endif
                                        </details>
                                    </td>
                                </tr>
                            @endif
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
        if ($('#scan-results-table').length) {
            $('#scan-results-table').DataTable({
                paging: false,
                searching: true,
                info: false,
                order: [[3, 'asc'], [1, 'asc']]
            });
        }
    });
</script>
@endpush
