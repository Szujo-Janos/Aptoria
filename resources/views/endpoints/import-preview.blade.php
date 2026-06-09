@extends('layouts.app')

@section('title', __('messages.import_preview.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.import_preview.title') }} — {{ $project->name }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.import_preview.intro') }}</p>

                @if(($preview['metadata']['format'] ?? null) === 'postman')
                    <div class="alert alert-info">
                        <strong>{{ __('messages.import_preview.postman_plan_title') }}</strong>
                        <div class="row m-t-sm">
                            <div class="col-md-3"><small>{{ __('messages.import_preview.postman_collection') }}</small><br><strong>{{ $preview['metadata']['collection_name'] ?? __('messages.common.not_available') }}</strong></div>
                            <div class="col-md-3"><small>{{ __('messages.import_preview.postman_environment') }}</small><br><strong>{{ $preview['metadata']['environment_name'] ?? __('messages.common.not_available') }}</strong></div>
                            <div class="col-md-3"><small>{{ __('messages.common.base_url') }}</small><br><code>{{ $preview['metadata']['environment_base_url'] ?? '—' }}</code></div>
                            <div class="col-md-3"><small>{{ __('messages.import_preview.postman_variables') }}</small><br><strong>{{ $preview['metadata']['variables_count'] ?? 0 }}</strong> <small>({{ __('messages.import_preview.postman_globals') }}: {{ $preview['metadata']['global_variables_count'] ?? 0 }})</small></div>
                        </div>
                        <div class="m-t-sm">
                            <span class="label label-info">{{ __('messages.import_preview.postman_auth_profiles') }}: {{ $preview['metadata']['auth_profiles_count'] ?? 0 }}</span>
                            <span class="label label-primary">{{ __('messages.import_preview.postman_assertions') }}: {{ $preview['metadata']['assertions_count'] ?? 0 }}</span>
                            <span class="label label-success">{{ __('messages.import_preview.postman_examples') }}: {{ $preview['metadata']['response_examples_count'] ?? 0 }}</span>
                            <span class="label label-default">{{ __('messages.import_preview.postman_test_suites') }}: {{ $preview['metadata']['test_suites_count'] ?? 0 }}</span>
                            @if(!empty($preview['metadata']['postman_schema']))
                                <span class="label label-info">{{ __('messages.import_preview.postman_schema') }}: {{ $preview['metadata']['postman_schema'] }}</span>
                            @endif
                            @if(!empty($preview['metadata']['unsupported_auth_types']))
                                <span class="label label-warning">{{ __('messages.import_preview.postman_unsupported_auth') }}: {{ implode(', ', $preview['metadata']['unsupported_auth_types']) }}</span>
                            @endif
                            @if(($preview['metadata']['unsupported_scripts_count'] ?? 0) > 0)
                                <span class="label label-warning">{{ __('messages.import_preview.postman_unsupported_scripts') }}: {{ $preview['metadata']['unsupported_scripts_count'] }}</span>
                            @endif
                        </div>
                        @if(!empty($preview['metadata']['compatibility_warnings']))
                            <ul class="m-t-sm text-warning">
                                @foreach($preview['metadata']['compatibility_warnings'] as $warning)
                                    <li><i class="fa fa-warning"></i> {{ $warning }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if(!empty($preview['metadata']['unresolved_variables']))
                            <div class="m-t-sm text-warning"><i class="fa fa-warning"></i> {{ __('messages.import_preview.postman_unresolved_variables') }}: {{ implode(', ', $preview['metadata']['unresolved_variables']) }}</div>
                        @endif
                    </div>
                @endif

                <div class="row text-center m-b-lg">
                    <div class="col-md-2 col-sm-4"><div class="well"><h3 class="m-t-xs">{{ $preview['total'] }}</h3><small>{{ __('messages.import_preview.total_rows') }}</small></div></div>
                    <div class="col-md-2 col-sm-4"><div class="well"><h3 class="m-t-xs text-success">{{ $preview['created'] }}</h3><small>{{ __('messages.import_preview.to_create') }}</small></div></div>
                    <div class="col-md-2 col-sm-4"><div class="well"><h3 class="m-t-xs text-info">{{ $preview['updated'] }}</h3><small>{{ __('messages.import_preview.to_update') }}</small></div></div>
                    <div class="col-md-2 col-sm-4"><div class="well"><h3 class="m-t-xs text-warning">{{ $preview['skipped'] }}</h3><small>{{ __('messages.import_preview.to_skip') }}</small></div></div>
                    <div class="col-md-2 col-sm-4"><div class="well"><h3 class="m-t-xs text-danger">{{ $preview['invalid'] }}</h3><small>{{ __('messages.import_preview.invalid') }}</small></div></div>
                    <div class="col-md-2 col-sm-4"><div class="well"><h3 class="m-t-xs text-warning">{{ $preview['duplicates'] }}</h3><small>{{ __('messages.import_preview.duplicates') }}</small></div></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>{{ __('messages.import_preview.row') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.endpoints.method') }}</th>
                                <th>{{ __('messages.endpoints.path') }}</th>
                                <th>{{ __('messages.common.name') }}</th>
                                <th>{{ __('messages.endpoints.risk_level') }}</th>
                                <th>{{ __('messages.endpoints.expected_status') }}</th>
                                <th>{{ __('messages.path_parameters.title') }}</th>
                                <th>{{ __('messages.endpoints.auth') }}</th>
                                <th>{{ __('messages.endpoints.request_metadata') }}</th>
                                <th>{{ __('messages.import_preview.postman_enrichment') }}</th>
                                <th>{{ __('messages.import_preview.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($preview['rows'] as $row)
                                @php
                                    $statusClass = match ($row['status']) {
                                        'create' => 'success',
                                        'update' => 'info',
                                        default => 'warning',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $row['row_number'] }}</td>
                                    <td><span class="label label-{{ $statusClass }}">{{ __('messages.import_preview.statuses.'.$row['status']) }}</span></td>
                                    <td><span class="label label-default">{{ $row['method'] }}</span></td>
                                    <td><code>{{ $row['path'] }}</code></td>
                                    <td>{{ $row['name'] ?: __('messages.common.none') }}</td>
                                    <td>{{ __('messages.endpoints.risks.'.$row['risk_level']) }}</td>
                                    <td>{{ $row['expected_status'] ?: '—' }}</td>
                                    <td>
                                        @if(!empty($row['path_parameters']))
                                            @foreach($row['path_parameters'] as $parameter)
                                                <span class="label label-info">{{ $parameter }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['auth_required'] ? __('messages.common.yes') : __('messages.common.no') }}</td>
                                    <td>
                                        @if(!empty($row['request_headers']))
                                            <span class="label label-info">{{ trans_choice('messages.endpoints.request_headers_count', count($row['request_headers']), ['count' => count($row['request_headers'])]) }}</span><br>
                                        @endif
                                        @if(!empty($row['request_body_type']))
                                            <span class="label label-default">{{ __('messages.endpoints.request_body') }}: {{ $row['request_body_type'] }}</span>
                                        @endif
                                        @if(empty($row['request_headers']) && empty($row['request_body_type']))
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($row['postman_suite_name']))
                                            <span class="label label-default">{{ __('messages.import_preview.postman_suite') }}: {{ $row['postman_suite_name'] }}</span><br>
                                        @endif
                                        @if(!empty($row['postman_auth_profile']))
                                            <span class="label label-info">{{ __('messages.auth_profiles.title') }}: {{ $row['postman_auth_profile']['name'] ?? __('messages.common.not_available') }}</span><br>
                                        @endif
                                        @if(($row['postman_response_examples_count'] ?? 0) > 0)
                                            <span class="label label-success">{{ __('messages.import_preview.postman_examples') }}: {{ $row['postman_response_examples_count'] }}</span><br>
                                        @endif
                                        @if(!empty($row['postman_assertions']))
                                            <span class="label label-primary">{{ __('messages.import_preview.postman_assertions') }}: {{ count($row['postman_assertions']) }}</span><br>
                                        @endif
                                        @if(!empty($row['postman_unresolved_variables']))
                                            <span class="text-warning"><i class="fa fa-warning"></i> {{ implode(', ', $row['postman_unresolved_variables']) }}</span><br>
                                        @endif
                                        @if(!empty($row['postman_unsupported_auth_types']))
                                            <span class="text-warning"><i class="fa fa-warning"></i> {{ __('messages.import_preview.postman_unsupported_auth') }}: {{ implode(', ', $row['postman_unsupported_auth_types']) }}</span><br>
                                        @endif
                                        @if(($row['postman_unsupported_scripts_count'] ?? 0) > 0)
                                            <span class="text-warning"><i class="fa fa-code"></i> {{ __('messages.import_preview.postman_unsupported_scripts') }}: {{ $row['postman_unsupported_scripts_count'] }}</span>
                                        @endif
                                        @if(empty($row['postman_suite_name']) && empty($row['postman_auth_profile']) && empty($row['postman_response_examples_count']) && empty($row['postman_assertions']) && empty($row['postman_unresolved_variables']))
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row['reasons'])
                                            <ul class="m-b-none p-l-md">
                                                @foreach($row['reasons'] as $reason)
                                                    <li>{{ $reason }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">{{ $row['exists'] ? __('messages.import_preview.will_update') : __('messages.import_preview.will_create') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="text-center text-muted">{{ __('messages.import_preview.no_rows') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('projects.endpoints.import', $project) }}" class="m-t-md">
                    @csrf
                    <input type="hidden" name="format" value="{{ $input['format'] }}">
                    <input type="hidden" name="import_source" value="paste">
                    <input type="hidden" name="source_url" value="{{ $input['source_url'] ?? '' }}">
                    <input type="hidden" name="environment_id" value="{{ $input['environment_id'] ?? '' }}">
                    <input type="hidden" name="auth_profile_id" value="{{ $input['auth_profile_id'] ?? '' }}">
                    <input type="hidden" name="payload_encoded" value="{{ base64_encode($input['payload']) }}">
                    <input type="hidden" name="postman_environment_payload_encoded" value="{{ base64_encode((string) ($input['postman_environment_payload'] ?? '')) }}">
                    <input type="hidden" name="postman_globals_payload_encoded" value="{{ base64_encode((string) ($input['postman_globals_payload'] ?? '')) }}">
                    <input type="hidden" name="postman_create_environment" value="{{ !empty($input['postman_create_environment']) ? 1 : 0 }}">
                    <input type="hidden" name="postman_create_auth_profile" value="{{ !empty($input['postman_create_auth_profile']) ? 1 : 0 }}">
                    <input type="hidden" name="postman_create_assertions" value="{{ !empty($input['postman_create_assertions']) ? 1 : 0 }}">
                    <input type="hidden" name="postman_create_test_suites" value="{{ !empty($input['postman_create_test_suites']) ? 1 : 0 }}">

                    <button type="submit" class="btn btn-primary" @disabled($preview['valid'] === 0)>{{ __('messages.import_preview.confirm_button') }}</button>
                    <a href="{{ route('projects.endpoints.import.form', $project) }}" class="btn btn-default">{{ __('messages.import_preview.back_to_import') }}</a>
                    <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
