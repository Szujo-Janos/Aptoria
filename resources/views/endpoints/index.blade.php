@extends('layouts.app')

@section('title', __('messages.endpoints.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.endpoint-inventory.index', $project) }}" class="btn btn-xs btn-primary"><i class="fa fa-list-alt"></i> {{ __('messages.endpoint_inventory.short_title') }}</a>
                    <a href="{{ route('projects.endpoints.import.form', $project) }}" class="btn btn-xs btn-info">{{ __('messages.endpoints.import_title') }}</a>
                    <a href="{{ route('projects.endpoints.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.endpoints.new') }}</a>
                </div>
                {{ __('messages.endpoints.inventory') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                @if($endpoints->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.endpoints.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.endpoints.empty_help') }}</p>
                        <a href="{{ route('projects.endpoints.create', $project) }}" class="btn btn-success">{{ __('messages.endpoints.create_title') }}</a>
                        <a href="{{ route('projects.endpoints.import.form', $project) }}" class="btn btn-info">{{ __('messages.endpoints.import_title') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="endpoints-table">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.endpoints.method') }}</th>
                                    <th>{{ __('messages.endpoints.path') }}</th>
                                    <th>{{ __('messages.endpoints.risk_level') }}</th>
                                    <th>{{ __('messages.endpoints.auth') }}</th>
                                    <th>{{ __('messages.environments.title') }}</th>
                                    <th>{{ __('messages.endpoints.expected_status') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.scans.last_result') }}</th>
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
                                    <td><span class="label label-{{ $endpoint->risk_css }}">{{ $endpoint->risk_label }}</span></td>
                                    <td>{!! $endpoint->auth_required ? '<span class="label label-warning">'.__('messages.endpoints.auth_required_short').'</span>' : '<span class="label label-info">'.__('messages.endpoints.public_or_unknown').'</span>' !!}</td>
                                    <td>{{ $endpoint->environment?->name ?: __('messages.endpoints.project_default') }}</td>
                                    <td>{{ $endpoint->expected_status ?: __('messages.common.not_available') }}</td>
                                    <td>{!! $endpoint->is_active ? '<span class="label label-success">'.__('messages.common.active').'</span>' : '<span class="label label-default">'.__('messages.common.inactive').'</span>' !!}</td>
                                    <td>
                                        @if($endpoint->latestScanResult)
                                            <span class="label label-{{ $endpoint->latestScanResult->status_css }}">{{ $endpoint->latestScanResult->status_label }}</span>
                                            @if($endpoint->latestScanResult->status_code) <small>{{ $endpoint->latestScanResult->status_code }}</small> @endif
                                        @else
                                            <span class="text-muted">{{ __('messages.common.not_available') }}</span>
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

@push('scripts')
<script>
    $(function () {
        if ($('#endpoints-table').length) {
            $('#endpoints-table').DataTable({
                paging: false,
                searching: true,
                info: false,
                order: [[2, 'asc'], [1, 'asc']]
            });
        }
    });
</script>
@endpush
