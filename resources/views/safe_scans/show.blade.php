@extends('layouts.app')
@section('title', __('messages.safe_scan.run_details') . ' #' . $scanRun->id)
@section('page_title', __('messages.safe_scan.run_details'))
@section('page_actions')
    <a href="{{ route('projects.safe-scans.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.safe_scan.run') }} #{{ $scanRun->id }}</h5>
                    <p class="text-muted mb-0 small">{{ $scanRun->created_at?->format('Y-m-d H:i') }} · {{ $project->name }}</p>
                </div>
                <span class="badge badge-soft-{{ $scanRun->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $scanRun->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-3"><div class="border rounded p-3"><span class="text-muted small">{{ __('messages.safe_scan.passed') }}</span><h4 class="mb-0 fw-light text-success">{{ $scanRun->summary_value['passed'] ?? 0 }}</h4></div></div>
                    <div class="col-sm-3"><div class="border rounded p-3"><span class="text-muted small">{{ __('messages.safe_scan.warning') }}</span><h4 class="mb-0 fw-light text-warning">{{ $scanRun->summary_value['warning'] ?? 0 }}</h4></div></div>
                    <div class="col-sm-3"><div class="border rounded p-3"><span class="text-muted small">{{ __('messages.safe_scan.failed') }}</span><h4 class="mb-0 fw-light text-danger">{{ $scanRun->summary_value['failed'] ?? 0 }}</h4></div></div>
                    <div class="col-sm-3"><div class="border rounded p-3"><span class="text-muted small">{{ __('messages.safe_scan.skipped') }}</span><h4 class="mb-0 fw-light text-secondary">{{ $scanRun->summary_value['skipped'] ?? 0 }}</h4></div></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-between flex-wrap gap-2 text-muted">
                <span>{{ __('messages.nav.environments') }}: {{ $scanRun->environment?->name ?? __('messages.safe_scan.auto_target') }}</span>
                <span>{{ __('messages.safe_scan.duration') }}: {{ $scanRun->duration_ms ? $scanRun->duration_ms.' ms' : '—' }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.safe_scan.evidence_summary') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex gap-3"><span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="key-round"></i></span></span><span>{{ $scanRun->authProfile?->name ?? __('messages.auth_profiles.no_auth_preview') }}</span></div>
                    <div class="list-group-item d-flex gap-3"><span class="avatar avatar-xs rounded text-bg-success"><span class="avatar-title"><i data-lucide="shield-check"></i></span></span><span>{{ __('messages.safe_scan.safe_methods_only') }}</span></div>
                    <div class="list-group-item d-flex gap-3"><span class="avatar avatar-xs rounded text-bg-warning"><span class="avatar-title"><i data-lucide="certificate"></i></span></span><span>{{ __('messages.safe_scan.raw_response_preview_limited') }}</span></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.safe_scan.evidence_footer') }}</div>
        </div>
    </div>
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.safe_scan.results_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.safe_scan.results_copy') }}</p>
        </div>
        <div class="card-action"><span class="badge badge-soft-primary badge-label">{{ __('messages.safe_scan.results') }}: {{ $results->count() }}</span></div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="safe-scan-results" data-aptoria-paging="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.endpoints.endpoint') }}</th>
                        <th data-priority="2">{{ __('messages.safe_scan.url') }}</th>
                        <th data-priority="3">{{ __('messages.safe_scan.status_code') }}</th>
                        <th data-priority="4">{{ __('messages.safe_scan.response_time') }}</th>
                        <th data-priority="5">{{ __('messages.safe_scan.content_type') }}</th>
                        <th data-priority="6">{{ __('messages.safe_scan.risk') }}</th>
                        <th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr>
                            <td><span class="badge text-bg-{{ $result->endpoint?->method_tone ?? 'secondary' }} me-1">{{ $result->method }}</span>{{ $result->endpoint?->name ?? $result->endpoint?->path ?? '—' }}</td>
                            <td><code class="aptoria-endpoint-path-cell">{{ $result->url }}</code></td>
                            <td><span class="badge badge-soft-{{ $result->status_tone }}">{{ $result->status_code ?? $result->status_label }}</span></td>
                            <td><small class="text-muted">{{ $result->response_time_ms ? $result->response_time_ms.' ms' : '—' }}</small></td>
                            <td><small class="text-muted text-truncate d-inline-block" style="max-width: 180px;">{{ $result->content_type ?: '—' }}</small></td>
                            <td><span class="badge badge-soft-{{ $result->risk_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $result->risk_label }}</span></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#scanResultModal{{ $result->id }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.projects.quick_preview') }}</button>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#scanFindingModal{{ $result->id }}"><i data-lucide="bug" class="me-2"></i>{{ __('messages.findings.create_from_result') }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">{{ __('messages.safe_scan.empty_results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.safe_scan.results_footer') }}</div>
</div>

@foreach ($results as $result)
    <div class="modal fade" id="scanResultModal{{ $result->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i data-lucide="certificate" class="me-2"></i>{{ __('messages.safe_scan.result_preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">{{ __('messages.safe_scan.url') }}</dt><dd class="col-sm-9"><code class="text-wrap">{{ $result->url }}</code></dd>
                        <dt class="col-sm-3">{{ __('messages.safe_scan.status_code') }}</dt><dd class="col-sm-9">{{ $result->status_code ?? $result->status_label }}</dd>
                        <dt class="col-sm-3">{{ __('messages.safe_scan.risk') }}</dt><dd class="col-sm-9">{{ $result->risk_label }} · {{ $result->risk_reason }}</dd>
                        <dt class="col-sm-3">{{ __('messages.safe_scan.error') }}</dt><dd class="col-sm-9">{{ $result->error_message ?: '—' }}</dd>
                        <dt class="col-sm-3">{{ __('messages.safe_scan.body_preview') }}</dt><dd class="col-sm-9"><pre class="bg-body-tertiary border rounded p-2 mb-0 text-wrap" style="max-height: 260px; white-space: pre-wrap;">{{ $result->body_preview ?: '—' }}</pre></dd>
                    </dl>
                </div>
                <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scanFindingModal{{ $result->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('projects.findings.store', $project) }}" data-aptoria-form-scope="finding" data-aptoria-form-plugin>
                    @csrf
                    <input type="hidden" name="source" value="scan">
                    <input type="hidden" name="status" value="open">
                    <input type="hidden" name="priority" value="normal">
                    <input type="hidden" name="endpoint_id" value="{{ $result->endpoint_id }}">
                    <input type="hidden" name="scan_result_id" value="{{ $result->id }}">
                    <input type="hidden" name="create_scan_evidence" value="1">
                    <div class="modal-header"><div><h5 class="modal-title"><i data-lucide="bug" class="me-2"></i>{{ __('messages.findings.create_from_result') }}</h5><p class="text-muted small mb-0">{{ $result->method }} {{ $result->url }}</p></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8"><label class="form-label">{{ __('messages.findings.title_field') }}</label><input type="text" name="title" class="form-control" value="{{ ($result->status === 'failed' ? 'Failed API evidence: ' : 'Review API evidence: ') . ($result->endpoint?->name ?? $result->endpoint?->path ?? $result->url) }}" required></div>
                            <div class="col-md-4"><label class="form-label">{{ __('messages.findings.severity') }}</label><select name="severity" class="form-select"><option value="medium" @selected($result->status === 'warning')>{{ __('messages.findings.severities.medium') }}</option><option value="high" @selected($result->status === 'failed')>{{ __('messages.findings.severities.high') }}</option><option value="critical">{{ __('messages.findings.severities.critical') }}</option><option value="low">{{ __('messages.findings.severities.low') }}</option></select></div>
                            <div class="col-12"><label class="form-label">{{ __('messages.findings.summary') }}</label><textarea name="summary" class="form-control" rows="3">{{ trim(($result->risk_reason ?: '') . "
" . ($result->error_message ?: '')) }}</textarea></div>
                            <div class="col-md-6"><label class="form-label">{{ __('messages.findings.expected_result') }}</label><textarea name="expected_result" class="form-control" rows="2">{{ $result->endpoint?->expected_status ? 'HTTP '.$result->endpoint->expected_status : '' }}</textarea></div>
                            <div class="col-md-6"><label class="form-label">{{ __('messages.findings.actual_result') }}</label><textarea name="actual_result" class="form-control" rows="2">{{ $result->status_code ? 'HTTP '.$result->status_code : $result->status_label }}</textarea></div>
                            <div class="col-md-6"><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="evidence_required" value="1" checked id="scanEvidenceRequired{{ $result->id }}"><label class="form-check-label" for="scanEvidenceRequired{{ $result->id }}">{{ __('messages.findings.evidence_required') }}</label></div></div>
                            <div class="col-md-6"><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="retest_required" value="1" checked id="scanRetestRequired{{ $result->id }}"><label class="form-check-label" for="scanRetestRequired{{ $result->id }}">{{ __('messages.findings.retest_required') }}</label></div></div>
                        </div>
                    </div>
                    <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.findings.create_finding') }}</button></div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
