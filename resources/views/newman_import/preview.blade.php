@extends('layouts.app')

@section('title', __('messages.newman_import.preview_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.newman_import.preview_title') }} — {{ $project->name }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.newman_import.preview_help') }}</p>
                <div class="row text-center">
                    <div class="col-md-3"><div class="well"><h3>{{ $preview['total'] }}</h3><small>{{ __('messages.newman_import.total_rows') }}</small></div></div>
                    <div class="col-md-3"><div class="well"><h3 class="text-success">{{ $preview['passes'] }}</h3><small>{{ __('messages.test_cases.run_statuses.pass') }}</small></div></div>
                    <div class="col-md-3"><div class="well"><h3 class="text-danger">{{ $preview['fails'] }}</h3><small>{{ __('messages.test_cases.run_statuses.fail') }}</small></div></div>
                    <div class="col-md-3"><div class="well"><h3>{{ $preview['skipped'] }}</h3><small>{{ __('messages.test_cases.run_statuses.skipped') }}</small></div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>{{ __('messages.test_suites.title') }}</th>
                                <th>{{ __('messages.test_cases.title') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.endpoints.method') }}</th>
                                <th>{{ __('messages.endpoints.path') }}</th>
                                <th>{{ __('messages.newman_import.duration') }}</th>
                                <th>{{ __('messages.newman_import.endpoint_match') }}</th>
                                <th>{{ __('messages.newman_import.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($preview['rows'] as $row)
                                <tr>
                                    <td>{{ $row['suite_name'] }}</td>
                                    <td>{{ $row['title'] }}</td>
                                    <td><span class="label label-{{ \App\Models\TestCase::runStatusCss($row['status']) }}">{{ __('messages.test_cases.run_statuses.'.$row['status']) }}</span></td>
                                    <td>{{ $row['method'] ?: '—' }}</td>
                                    <td><code>{{ $row['path'] ?: '—' }}</code></td>
                                    <td>{{ $row['duration_ms'] !== null ? $row['duration_ms'].' ms' : '—' }}</td>
                                    <td>{!! $row['endpoint_match'] ? '<span class="label label-success">'.e(__('messages.common.yes')).'</span>' : '<span class="label label-default">'.e(__('messages.common.no')).'</span>' !!}</td>
                                    <td><small>{{ \Illuminate\Support\Str::limit($row['actual_result'], 160) }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">{{ __('messages.newman_import.no_rows') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('projects.newman-import.store', $project) }}" class="m-t-md">
                    @csrf
                    <input type="hidden" name="format" value="{{ $input['format'] }}">
                    <input type="hidden" name="payload_encoded" value="{{ base64_encode($input['payload']) }}">
                    <input type="hidden" name="create_findings" value="{{ !empty($input['create_findings']) ? 1 : 0 }}">
                    <button type="submit" class="btn btn-primary" @disabled($preview['total'] === 0)>{{ __('messages.newman_import.confirm_import') }}</button>
                    <a href="{{ route('projects.newman-import.create', $project) }}" class="btn btn-default">{{ __('messages.import_preview.back_to_import') }}</a>
                    <a href="{{ route('projects.test-execution.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
