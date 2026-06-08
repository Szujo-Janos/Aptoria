@if($rows->isEmpty())
    <div class="p-md text-muted">{{ __('messages.release_readiness.no_endpoint_rows') }}</div>
@else
    <table class="table table-striped table-condensed m-b-none">
        <thead><tr><th>{{ __('messages.endpoints.method') }}</th><th>{{ __('messages.endpoints.path') }}</th><th>HTTP</th><th>{{ __('messages.scans.response_time') }}</th><th>{{ __('messages.endpoints.risk_level') }}</th><th>{{ __('messages.assertions.title') }}</th></tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td><span class="label label-default">{{ $row['endpoint']->method }}</span></td>
                <td><a href="{{ route('projects.endpoints.show', [$row['endpoint']->project, $row['endpoint']]) }}"><code>{{ $row['endpoint']->path }}</code></a></td>
                <td>{{ $row['latest']?->status_code ?: __('messages.common.not_available') }}</td>
                <td>{{ $row['latest']?->response_time_ms !== null ? $row['latest']->response_time_ms.' ms' : __('messages.common.not_available') }}</td>
                <td><span class="label label-{{ $row['analysis']['final_css'] ?? 'default' }}">{{ $row['analysis']['final_label'] ?? __('messages.common.not_available') }}</span></td>
                <td><span class="label label-{{ $row['assertion']['css'] ?? 'default' }}">{{ $row['assertion']['label'] ?? __('messages.common.not_available') }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
