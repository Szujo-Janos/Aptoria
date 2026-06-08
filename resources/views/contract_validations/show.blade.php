@extends('layouts.app')

@section('title', __('messages.contract_validations.result_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $contractValidation->status_css === 'danger' ? 'red' : ($contractValidation->status_css === 'warning' ? 'yellow' : 'green') }}">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.contract-validations.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.contract_validations.new') }}</a>
                    <a href="{{ route('projects.contract-validations.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.contract_validations.view_all') }}</a>
                    <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.reports.title') }}</a>
                </div>
                {{ __('messages.contract_validations.result_title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-2"><h3><span class="label label-{{ $contractValidation->status_css }}">{{ $contractValidation->health_label }}</span></h3><small>{{ __('messages.common.status') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $contractValidation->total_checks }}</h3><small>{{ __('messages.contract_validations.total_checks') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $contractValidation->breaking_count }}</h3><small>{{ __('messages.contract_validations.breaking') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $contractValidation->failed_count }}</h3><small>{{ __('messages.contract_validations.failed') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $contractValidation->warning_count }}</h3><small>{{ __('messages.contract_validations.warnings') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $contractValidation->passed_count }}</h3><small>{{ __('messages.contract_validations.passed') }}</small></div>
                </div>
                <hr>
                <dl class="dl-horizontal">
                    <dt>{{ __('messages.contract_validations.source_name') }}</dt><dd>{{ $contractValidation->source_name ?: __('messages.contract_validations.manual_source') }}</dd>
                    <dt>{{ __('messages.contract_validations.contract_hash') }}</dt><dd><code>{{ $contractValidation->contract_hash }}</code></dd>
                    <dt>{{ __('messages.scans.title') }}</dt><dd>
                        @if($contractValidation->scanRun)
                            <a href="{{ route('projects.scans.show', [$project, $contractValidation->scanRun]) }}">#{{ $contractValidation->scanRun->id }}</a>
                        @else
                            {{ __('messages.common.not_available') }}
                        @endif
                    </dd>
                    <dt>{{ __('messages.common.created') }}</dt><dd>{{ $contractValidation->created_at->format('Y-m-d H:i:s') }}</dd>
                    @if($contractValidation->error_message)
                        <dt>{{ __('messages.common.error') }}</dt><dd class="text-danger">{{ $contractValidation->error_message }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.contract_validations.results') }}</div>
            <div class="panel-body">
                @if($contractValidation->results->isEmpty())
                    <p class="text-muted">{{ __('messages.contract_validations.no_results') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="contract-results-table">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.endpoints.method') }}</th>
                                    <th>{{ __('messages.endpoints.path') }}</th>
                                    <th>{{ __('messages.contract_validations.check_type') }}</th>
                                    <th>{{ __('messages.contract_validations.severity') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.contract_validations.message') }}</th>
                                    <th>{{ __('messages.contract_validations.expected_label') }}</th>
                                    <th>{{ __('messages.contract_validations.actual_label') }}</th>
                                    <th>{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($contractValidation->results as $result)
                                <tr>
                                    <td>@if($result->method)<span class="label label-default">{{ $result->method }}</span>@endif</td>
                                    <td>
                                        @if($result->endpoint)
                                            <a href="{{ route('projects.endpoints.show', [$project, $result->endpoint]) }}"><code>{{ $result->path }}</code></a>
                                        @else
                                            <code>{{ $result->path }}</code>
                                        @endif
                                    </td>
                                    <td>{{ $result->check_type_label }}</td>
                                    <td><span class="label label-{{ $result->severity_css }}">{{ $result->severity_label }}</span></td>
                                    <td><span class="label label-{{ $result->status_css }}">{{ $result->status_label }}</span></td>
                                    <td>{{ $result->message }}</td>
                                    <td><small>{{ $result->expected ?: __('messages.common.not_available') }}</small></td>
                                    <td><small>{{ $result->actual ?: __('messages.common.not_available') }}</small></td>
                                    <td class="text-right">
                                        @if(in_array($result->status, [\App\Models\ContractValidationResult::STATUS_FAIL, \App\Models\ContractValidationResult::STATUS_WARNING], true))
                                            <a href="{{ route('projects.findings.create', ['project' => $project, 'contract_validation_result_id' => $result->id]) }}" class="btn btn-xs btn-danger">{{ __('messages.findings.create') }}</a>
                                        @else
                                            <span class="text-muted">{{ __('messages.common.none') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($result->evidence_json)
                                    <tr>
                                        <td></td>
                                        <td colspan="8">
                                            <details>
                                                <summary>{{ __('messages.contract_validations.evidence') }}</summary>
                                                <pre class="code-block"><code>{{ json_encode($result->evidence_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                            </details>
                                        </td>
                                    </tr>
                                @endif
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
        if ($('#contract-results-table').length) {
            $('#contract-results-table').DataTable({
                paging: false,
                searching: true,
                info: false,
                order: [[4, 'asc'], [3, 'asc'], [1, 'asc']]
            });
        }
    });
</script>
@endpush
