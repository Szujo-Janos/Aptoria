@extends('layouts.app')
@section('title', __('messages.endpoints.quick_test_detail_title') . ' · ' . $project->name)
@section('page_title', __('messages.endpoints.quick_test_detail_title'))
@section('page_actions')
    <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100 aptoria-project-dashboard-hero">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="avatar avatar-lg rounded text-bg-primary"><span class="avatar-title"><i data-lucide="file-check-2"></i></span></span>
                        <div class="min-w-0">
                            <span class="badge badge-soft-primary badge-label mb-2"><i class="ti ti-point-filled"></i>v0.0.18</span>
                            <h2 class="mb-1 fw-normal">{{ __('messages.endpoints.quick_test_evidence') }}</h2>
                            <p class="text-muted mb-0">{{ __('messages.endpoints.quick_test_detail_copy') }}</p>
                        </div>
                    </div>
                    <span class="badge badge-soft-{{ $run->tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $run->state_label }}</span>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>{{ __('messages.workspace.current_project') }}: {{ $project->name }}</span>
                <span>{{ __('messages.endpoints.checked_at') }}: {{ $run->checked_at?->toDateTimeString() ?? __('messages.common.not_available') }}</span>
                @if ($run->batch)
                    <a href="{{ route('projects.endpoint-test-batches.show', [$project, $run->batch]) }}" class="btn btn-sm btn-light"><i data-lucide="package-check" class="me-1"></i>{{ __('messages.endpoints.view_batch_evidence') }}</a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.endpoints.expectation_result') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.common.status') }}</span><span class="badge badge-soft-{{ $run->tone }}">{{ $run->state_label }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.expected_status') }}</span><span class="badge {{ $run->status_matched === false ? 'badge-soft-warning' : 'badge-soft-secondary' }}">{{ $run->expected_status ?? __('messages.common.not_available') }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.quick_test_http_result') }}</span><span class="badge {{ $run->status_matched === true ? 'badge-soft-success' : ($run->status_matched === false ? 'badge-soft-warning' : 'badge-soft-secondary') }}">{{ $run->status_summary }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.content_type') }}</span><span class="badge {{ $run->content_type_matched === false ? 'badge-soft-warning' : 'badge-soft-secondary' }}">{{ $run->expected_content_type ?? __('messages.common.not_available') }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.actual_content_type') }}</span><span class="badge {{ $run->content_type_matched === true ? 'badge-soft-success' : ($run->content_type_matched === false ? 'badge-soft-warning' : 'badge-soft-secondary') }}">{{ $run->content_type ?? __('messages.common.not_available') }}</span></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.endpoints.quick_test_safe_note') }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mt-0">
    <div class="col-xl-6">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.endpoints.evidence_context') }}</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small mb-1">{{ __('messages.endpoints.endpoint') }}</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-{{ in_array($run->method, ['GET', 'HEAD'], true) ? 'success' : 'warning' }}">{{ $run->method }}</span>
                            <span class="fw-normal text-truncate">{{ $run->endpoint?->name ?: __('messages.endpoints.unnamed') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small mb-1">{{ __('messages.endpoints.path') }}</div>
                        <code class="aptoria-endpoint-path-cell">{{ $run->endpoint?->path ?? __('messages.common.not_available') }}</code>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small mb-1">{{ __('messages.common.url') }}</div>
                        <code class="aptoria-endpoint-path-cell d-block text-wrap">{{ $run->url ?? __('messages.common.not_available') }}</code>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small mb-1">{{ __('messages.nav.environments') }}</div>
                        <span class="badge badge-soft-secondary">{{ $run->environment?->name ?? __('messages.auth_profiles.project_base_url') }}</span>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small mb-1">{{ __('messages.nav.auth_profiles') }}</div>
                        <span class="badge badge-soft-secondary">{{ $run->authProfile?->name ?? __('messages.auth_profiles.no_auth_preview') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.endpoints.execution_context') }}</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100">
                            <div class="text-muted small mb-1">{{ __('messages.endpoints.quick_test_http_result') }}</div>
                            <h4 class="fw-light mb-0">{{ $run->status_code ?? __('messages.common.not_available') }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100">
                            <div class="text-muted small mb-1">{{ __('messages.endpoints.response_time') }}</div>
                            <h4 class="fw-light mb-0">{{ $run->response_time_ms ?? __('messages.common.not_available') }} <span class="fs-sm text-muted">{{ __('messages.common.milliseconds') }}</span></h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100">
                            <div class="text-muted small mb-1">{{ __('messages.endpoints.response_size') }}</div>
                            <h4 class="fw-light mb-0">{{ $run->response_size ?? __('messages.common.not_available') }}</h4>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border mb-0">
                            <strong>{{ __('messages.endpoints.result_message') }}:</strong>
                            {{ $run->message ?: __('messages.endpoints.quick_test_no_message') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@if (($run->assertion_total ?? 0) > 0)
    <div class="card mt-3 aptoria-panel-card">
        <div class="card-header border-light justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1">{{ __('messages.assertions.title') }}</h5>
                <p class="text-muted mb-0 small">{{ __('messages.assertions.copy') }}</p>
            </div>
            <span class="badge badge-soft-{{ ($run->assertion_failed ?? 0) > 0 ? 'warning' : 'success' }} badge-label"><i class="ti ti-point-filled"></i>{{ $run->assertion_passed }}/{{ $run->assertion_total }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom table-striped table-nowrap table-centered w-100 aptoria-resource-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.assertions.rule') }}</th>
                            <th>{{ __('messages.assertions.expectation') }}</th>
                            <th>{{ __('messages.assertions.severity') }}</th>
                            <th>{{ __('messages.common.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (($run->assertion_summary_json['items'] ?? []) as $item)
                            <tr>
                                <td>{{ $item['name'] ?? __('messages.assertions.rule') }}<div class="text-muted small">{{ $item['rule_label'] ?? '' }}</div></td>
                                <td><code>{{ $item['operator'] ?? '' }}</code> <span class="text-muted">{{ $item['expected'] ?? '' }}</span><div class="small text-muted">{{ __('messages.common.status') }}: {{ $item['actual'] ?? __('messages.common.not_available') }}</div></td>
                                <td><span class="badge badge-soft-{{ ($item['severity'] ?? 'warning') === 'blocker' ? 'danger' : (($item['severity'] ?? 'warning') === 'warning' ? 'warning' : 'info') }}">{{ __('messages.assertions.severities.'.($item['severity'] ?? 'warning')) }}</span></td>
                                <td><span class="badge badge-soft-{{ ($item['passed'] ?? false) ? 'success' : 'danger' }}">{{ ($item['passed'] ?? false) ? 'PASS' : 'FAIL' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if ($run->body_preview)
    <div class="card mt-3 aptoria-panel-card">
        <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.auth_profiles.body_preview') }}</h5></div>
        <div class="card-body">
            <div class="bg-light border rounded p-2 small aptoria-result-preview"><pre class="mb-0 text-wrap">{{ $run->body_preview }}</pre></div>
        </div>
        <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.endpoints.masked_preview_note') }}</div>
    </div>
@endif

<div class="card mt-3 aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.endpoints.copyable_evidence') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.endpoints.copyable_evidence_copy') }}</p>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-aptoria-copy-target="#endpointTestEvidenceMarkdown"><i data-lucide="copy" class="me-1"></i>{{ __('messages.endpoints.copy_evidence') }}</button>
    </div>
    <div class="card-body">
        <textarea id="endpointTestEvidenceMarkdown" class="form-control font-monospace small" rows="14" readonly>{{ $evidenceMarkdown }}</textarea>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
        <span>{{ __('messages.endpoints.copyable_evidence_footer') }}</span>
        <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-sm btn-light"><i data-lucide="plug-connected" class="me-1"></i>{{ __('messages.endpoints.title') }}</a>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="aptoriaCopyToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">{{ __('messages.endpoints.evidence_copied') }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('messages.common.close') }}"></button>
        </div>
    </div>
</div>
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
            var copied = false;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(target.value).then(function () {
                    var toastEl = document.getElementById('aptoriaCopyToast');
                    if (window.bootstrap && toastEl) { new bootstrap.Toast(toastEl).show(); }
                });
                copied = true;
            }
            if (!copied) {
                document.execCommand('copy');
                var toastEl = document.getElementById('aptoriaCopyToast');
                if (window.bootstrap && toastEl) { new bootstrap.Toast(toastEl).show(); }
            }
        });
    });
});
</script>
@endpush
