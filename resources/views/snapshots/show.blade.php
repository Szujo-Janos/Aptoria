@extends('layouts.app')
@section('title', $snapshot->title . ' · ' . $project->name)
@section('page_title', __('messages.snapshots.snapshot_detail'))
@section('page_actions')
    <a href="{{ route('projects.snapshots.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.snapshots.title') }}</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-project-dashboard-hero border-{{ $snapshot->tone }}">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="avatar avatar-lg rounded text-bg-{{ $snapshot->tone }}"><span class="avatar-title"><i data-lucide="camera"></i></span></span>
                        <div class="min-w-0">
                            <span class="badge badge-soft-{{ $snapshot->tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $snapshot->status_label }}</span>
                            <h2 class="mb-1 fw-normal">{{ $snapshot->title }}</h2>
                            <p class="text-muted mb-0">{{ __('messages.snapshots.detail_copy') }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.snapshots.checksum') }}</div>
                        <code>{{ $snapshot->short_checksum }}</code>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>{{ __('messages.snapshots.source_batch') }}: #{{ $snapshot->endpoint_test_batch_id ?: '—' }}</span>
                <span>{{ __('messages.snapshots.captured_at') }}: {{ $snapshot->captured_at?->toDateTimeString() ?? '—' }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="bar-chart-3" class="me-2"></i>{{ __('messages.snapshots.totals') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.snapshots.total') }}</span><strong>{{ $snapshot->total }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.endpoints.batch_test_passed') }}</span><span class="badge badge-soft-success">{{ $snapshot->passed }}</span></div>
                    <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.endpoints.batch_test_warning') }}</span><span class="badge badge-soft-warning">{{ $snapshot->warning }}</span></div>
                    <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.endpoints.batch_test_failed') }}</span><span class="badge badge-soft-danger">{{ $snapshot->failed }}</span></div>
                    <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.endpoints.batch_test_skipped') }}</span><span class="badge badge-soft-secondary">{{ $snapshot->skipped }}</span></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.snapshots.fixed_evidence_footer') }}</div>
        </div>
    </div>
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="list-tree" class="me-2"></i>{{ __('messages.snapshots.items') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.snapshots.items_copy') }}</p>
        </div>
        <span class="badge badge-soft-primary">{{ $items->count() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="endpoint-snapshot-items" data-aptoria-paging="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.endpoints.endpoint') }}</th>
                        <th data-priority="2">{{ __('messages.common.status') }}</th>
                        <th data-priority="3">{{ __('messages.endpoints.quick_test_http_result') }}</th>
                        <th data-priority="4">{{ __('messages.assertions.title') }}</th>
                        <th data-priority="5">{{ __('messages.snapshots.item_checksum') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td><div class="d-flex align-items-center gap-2 min-w-0"><span class="badge text-bg-{{ in_array($item->method, ['GET','HEAD'], true) ? 'success' : 'warning' }}">{{ $item->method }}</span><div class="min-w-0"><span class="d-block text-truncate aptoria-endpoint-name-cell">{{ $item->endpoint_name ?: $item->endpoint?->name ?: __('messages.endpoints.unnamed') }}</span><small class="text-muted d-block text-truncate">{{ $item->path ?: $item->url }}</small></div></div></td>
                            <td><span class="badge badge-soft-{{ $item->tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $item->state_label }}</span></td>
                            <td><span>{{ $item->status_code ? 'HTTP '.$item->status_code : '—' }}</span><small class="text-muted d-block">{{ $item->response_time_ms ?? '—' }} {{ __('messages.common.milliseconds') }} · {{ $item->content_type ?? '—' }}</small></td>
                            <td><small class="text-muted">{{ $item->assertion_failed }} / {{ $item->assertion_total }}</small></td>
                            <td><code>{{ substr($item->item_checksum, 0, 12) }}</code></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">{{ __('messages.snapshots.items_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-3 aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div><h5 class="card-title mb-1"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.snapshots.copyable_snapshot') }}</h5><p class="text-muted mb-0 small">{{ __('messages.snapshots.copyable_snapshot_copy') }}</p></div>
        <button type="button" class="btn btn-sm btn-primary" data-aptoria-copy-target="#endpointSnapshotMarkdown"><i data-lucide="copy" class="me-1"></i>{{ __('messages.endpoints.copy_evidence') }}</button>
    </div>
    <div class="card-body"><textarea id="endpointSnapshotMarkdown" class="form-control font-monospace small" rows="14" readonly>{{ $snapshotMarkdown }}</textarea></div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3"><div id="aptoriaCopyToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">{{ __('messages.snapshots.copied') }}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('messages.common.close') }}"></button></div></div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-aptoria-copy-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector(button.getAttribute('data-aptoria-copy-target'));
            if (!target) { return; }
            target.select();
            target.setSelectionRange(0, target.value.length);
            var showToast = function () { var toastEl = document.getElementById('aptoriaCopyToast'); if (window.bootstrap && toastEl) { new bootstrap.Toast(toastEl).show(); } };
            if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(target.value).then(showToast); } else { document.execCommand('copy'); showToast(); }
        });
    });
});
</script>
@endpush
