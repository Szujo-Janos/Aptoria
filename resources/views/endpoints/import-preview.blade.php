@extends('layouts.app')

@section('title', __('messages.import_preview.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.import_preview.title') }} — {{ $project->name }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.import_preview.intro') }}</p>

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
                                <tr><td colspan="10" class="text-center text-muted">{{ __('messages.import_preview.no_rows') }}</td></tr>
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
                    <textarea name="payload" class="hidden">{{ $input['payload'] }}</textarea>

                    <button type="submit" class="btn btn-primary" @disabled($preview['valid'] === 0)>{{ __('messages.import_preview.confirm_button') }}</button>
                    <a href="{{ route('projects.endpoints.import.form', $project) }}" class="btn btn-default">{{ __('messages.import_preview.back_to_import') }}</a>
                    <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
