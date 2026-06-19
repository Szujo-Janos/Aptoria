@extends('layouts.app')
@section('title', $report->title)
@section('page_title', __('messages.reports.snapshot_detail'))
@section('page_actions')
    <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.reports.back_to_reports') }}</a>
    @if ($report->releaseDecisionSnapshot)
        <a href="{{ route('projects.release-decisions.show', [$project, $report->releaseDecisionSnapshot]) }}" class="btn btn-light"><i data-lucide="clipboard-check" class="me-1"></i>{{ __('messages.release_decisions.view_snapshot') }}</a>
    @endif
    @if ($report->releaseGate)
        <a href="{{ route('projects.release-gates.show', [$project, $report->releaseGate]) }}" class="btn btn-light"><i data-lucide="workflow" class="me-1"></i>{{ __('messages.release_gates.gate') }}</a>
    @endif
    <div class="btn-group">
        <a href="{{ route('projects.reports.download', [$project, $report, 'pdf']) }}" class="btn btn-primary"><i data-lucide="file-type-pdf" class="me-1"></i>{{ __('messages.reports.download_pdf') }}</a>
        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">{{ __('messages.common.actions') }}</span></button>
        <div class="dropdown-menu dropdown-menu-end">
            <a href="{{ route('projects.reports.download', [$project, $report, 'md']) }}" class="dropdown-item"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.reports.download_markdown') }}</a>
            <a href="{{ route('projects.reports.download', [$project, $report, 'html']) }}" class="dropdown-item"><i data-lucide="file-code-2" class="me-2"></i>{{ __('messages.reports.download_html') }}</a>
            <a href="{{ route('projects.reports.download', [$project, $report, 'json']) }}" class="dropdown-item"><i data-lucide="file-json" class="me-2"></i>{{ __('messages.reports.download_json') }}</a>
        </div>
    </div>
@endsection
@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-start gap-3">
                <div class="min-w-0">
                    <span class="badge badge-soft-info mb-2">{{ $report->type_label }}</span>
                    <h4 class="card-title mb-1 text-truncate">{{ $report->title }}</h4>
                    <p class="text-muted mb-0 small">{{ __('messages.reports.snapshot_detail_copy') }}</p>
                </div>
                <span class="badge badge-soft-{{ $report->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $report->status_label }}</span>
            </div>
            <div class="card-body">
                @if ($report->type === 'release_decision')
                    <div class="alert alert-info d-flex gap-2 align-items-start"><i data-lucide="lock" class="mt-1"></i><div><strong>{{ __('messages.reports.versioned_release_decision') }}</strong><br><span class="small">{{ __('messages.reports.versioned_release_decision_copy') }}</span></div></div>
                @endif
                <div class="aptoria-report-preview border rounded p-3 bg-light-subtle">
                    {!! $report->content_html !!}
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex flex-wrap justify-content-between gap-2 text-muted">
                <span>{{ __('messages.reports.checksum') }}: <code>{{ $report->checksum }}</code></span>
                <span>{{ __('messages.reports.generated_at') }}: {{ $report->generated_at?->format('Y-m-d H:i') }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.reports.downloads') }}</h5></div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('projects.reports.download', [$project, $report, 'pdf']) }}" class="btn btn-light text-start"><i data-lucide="file-type-pdf" class="me-2"></i>{{ __('messages.reports.download_pdf') }}</a>
                <a href="{{ route('projects.reports.download', [$project, $report, 'md']) }}" class="btn btn-light text-start"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.reports.download_markdown') }}</a>
                <a href="{{ route('projects.reports.download', [$project, $report, 'html']) }}" class="btn btn-light text-start"><i data-lucide="file-code-2" class="me-2"></i>{{ __('messages.reports.download_html') }}</a>
                <a href="{{ route('projects.reports.download', [$project, $report, 'json']) }}" class="btn btn-light text-start"><i data-lucide="file-json" class="me-2"></i>{{ __('messages.reports.download_json') }}</a>
            </div>
        </div>

        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.reports.client_delivery') }}</h5></div>
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <span class="badge badge-soft-{{ $report->client_delivery_tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $report->client_delivery_state_label }}</span>
                        <p class="text-muted small mb-0">{{ __('messages.reports.client_delivery_copy') }}</p>
                    </div>
                    <div class="text-end small text-muted">
                        <div>{{ __('messages.reports.delivery_count') }}: <strong>{{ $report->client_delivery_count ?? 0 }}</strong></div>
                        <div>{{ __('messages.reports.download_count') }}: <strong>{{ $report->client_download_count ?? 0 }}</strong></div>
                    </div>
                </div>
                @if ($report->is_client_deliverable)
                    <form method="POST" action="{{ route('projects.reports.delivery-link', [$project, $report]) }}" class="row g-2" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.client_portal.delivery_confirm_title') }}" data-confirm-text="{{ __('messages.client_portal.delivery_confirm_text') }}" data-confirm-button="{{ __('messages.client_portal.create_delivery_link') }}" data-aptoria-form-scope="client_portal" data-aptoria-form-plugin>
                        @csrf
                        <div class="col-12"><label class="form-label">{{ __('messages.client_portal.access_name') }}</label><input class="form-control" name="name" placeholder="{{ __('messages.form_plugin.placeholders.client_portal.delivery_name') }}"></div>
                        <div class="col-sm-6"><label class="form-label">{{ __('messages.client_portal.expires_at') }}</label><input type="date" class="form-control" name="expires_at"></div>
                        <div class="col-sm-6 d-flex align-items-end"><input type="hidden" name="acknowledge_required" value="0"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="acknowledge_required" value="1" checked><span class="form-check-label">{{ __('messages.client_portal.acknowledge_required') }}</span></label></div>
                        <input type="hidden" name="confirm_delivery" value="1">
                        <div class="col-12"><button type="submit" class="btn btn-success w-100"><i data-lucide="door-open" class="me-1"></i>{{ __('messages.client_portal.create_delivery_link') }}</button></div>
                    </form>
                @else
                    <div class="alert alert-warning mb-0 d-flex gap-2 align-items-start"><i data-lucide="shield-alert" class="mt-1"></i><div><strong>{{ __('messages.client_portal.approved_only_title') }}</strong><br><span class="small">{{ __('messages.client_portal.approved_only_copy') }}</span></div></div>
                @endif
            </div>
            @if ($report->clientPortalAccesses->isNotEmpty())
                <div class="list-group list-group-flush">
                    @foreach ($report->clientPortalAccesses as $access)
                        <div class="list-group-item d-flex justify-content-between gap-3 align-items-start">
                            <div class="min-w-0"><div class="fw-medium text-truncate">{{ $access->name }}</div><small class="text-muted d-block">{{ $access->created_at?->format('Y-m-d H:i') }} · {{ $access->last_viewed_at?->diffForHumans() ?? __('messages.client_portal.not_viewed_yet') }}</small><span class="badge badge-soft-{{ $access->acknowledgement_state_tone }} mt-1">{{ $access->acknowledgement_state_label }}</span>@if ($access->acknowledgement_decision)<span class="badge badge-soft-{{ $access->acknowledgement_decision_tone }} mt-1">{{ $access->acknowledgement_decision_label }}</span>@endif</div>
                            <a href="{{ $access->public_url }}" target="_blank" class="btn btn-sm btn-light flex-shrink-0"><i data-lucide="external-link" class="me-1"></i>{{ __('messages.client_portal.open_public') }}</a>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.reports.client_delivery_footer') }}</div>
        </div>
        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="badge-check" class="me-1"></i>{{ __('messages.reports.status_workflow') }}</h5>
                    <p class="text-muted small mb-0">{{ __('messages.reports.status_workflow_copy') }}</p>
                </div>
            </div>
            <div class="card-body d-grid gap-3">
                <form method="POST" action="{{ route('projects.reports.status', [$project, $report]) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.reports.status_confirm_title') }}" data-confirm-text="{{ __('messages.reports.status_confirm_text') }}" data-confirm-button="{{ __('messages.reports.mark_reviewed') }}" data-aptoria-form-scope="report_review" data-aptoria-form-plugin>
                    @csrf
                    <input type="hidden" name="status" value="reviewed">
                    <input type="hidden" name="confirm_status" value="1">
                    <label class="form-label">{{ __('messages.reports.review_note') }}</label>
                    <textarea name="review_note" rows="3" class="form-control mb-2" placeholder="{{ __('messages.form_plugin.placeholders.reports.review_note') }}">{{ old('review_note', $report->review_note) }}</textarea>
                    <div class="form-text mb-2">{{ __('messages.reports.review_note_help') }}</div>
                    <button type="submit" class="btn btn-light text-start w-100"><i data-lucide="clipboard-search" class="me-2"></i>{{ __('messages.reports.mark_reviewed') }}</button>
                </form>

                <form method="POST" action="{{ route('projects.reports.status', [$project, $report]) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.reports.status_confirm_title') }}" data-confirm-text="{{ __('messages.reports.approval_confirm_text') }}" data-confirm-button="{{ __('messages.reports.mark_approved') }}" data-aptoria-form-scope="report_approval" data-aptoria-form-plugin>
                    @csrf
                    <input type="hidden" name="status" value="approved">
                    <input type="hidden" name="confirm_status" value="1">
                    <label class="form-label">{{ __('messages.reports.approval_note') }}</label>
                    <textarea name="approval_note" rows="3" class="form-control mb-2" placeholder="{{ __('messages.form_plugin.placeholders.reports.approval_note') }}">{{ old('approval_note', $report->approval_note) }}</textarea>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="form-label">{{ __('messages.reports.signoff_name') }}</label>
                            <input name="approval_signoff_name" class="form-control" value="{{ old('approval_signoff_name', $report->approval_signoff_name ?: auth()->user()?->name) }}" placeholder="{{ __('messages.form_plugin.placeholders.reports.signoff_name') }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">{{ __('messages.reports.signoff_role') }}</label>
                            <input name="approval_signoff_role" class="form-control" value="{{ old('approval_signoff_role', $report->approval_signoff_role ?: auth()->user()?->report_role_title) }}" placeholder="{{ __('messages.form_plugin.placeholders.reports.signoff_role') }}">
                        </div>
                    </div>
                    <label class="form-label mt-2">{{ __('messages.reports.signoff_statement') }}</label>
                    <textarea name="approval_signoff_statement" rows="3" class="form-control mb-2" placeholder="{{ __('messages.form_plugin.placeholders.reports.signoff_statement') }}" required>{{ old('approval_signoff_statement', $report->approval_signoff_statement ?: __('messages.reports.default_signoff_statement')) }}</textarea>
                    <div class="form-text mb-2">{{ __('messages.reports.signoff_help') }}</div>
                    <button type="submit" class="btn btn-success text-start w-100"><i data-lucide="badge-check" class="me-2"></i>{{ __('messages.reports.mark_approved') }}</button>
                </form>

                <form method="POST" action="{{ route('projects.reports.status', [$project, $report]) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.reports.status_confirm_title') }}" data-confirm-text="{{ __('messages.reports.status_confirm_text') }}" data-confirm-button="{{ __('messages.reports.mark_archived') }}" data-aptoria-form-scope="report_archive" data-aptoria-form-plugin>
                    @csrf
                    <input type="hidden" name="status" value="archived">
                    <input type="hidden" name="confirm_status" value="1">
                    <label class="form-label">{{ __('messages.reports.archive_note') }}</label>
                    <textarea name="archive_note" rows="2" class="form-control mb-2" placeholder="{{ __('messages.form_plugin.placeholders.reports.archive_note') }}">{{ old('archive_note', $report->archive_note) }}</textarea>
                    <button type="submit" class="btn btn-light text-start w-100"><i data-lucide="archive" class="me-2"></i>{{ __('messages.reports.mark_archived') }}</button>
                </form>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.reports.status_workflow_footer') }}</div>
        </div>

        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="file-check" class="me-1"></i>{{ __('messages.reports.approval_signoff') }}</h5>
                    <p class="text-muted small mb-0">{{ __('messages.reports.approval_signoff_copy') }}</p>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.review_note') }}</span><strong class="text-end">{{ $report->review_note ?: '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.approval_note') }}</span><strong class="text-end">{{ $report->approval_note ?: '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.signoff_name') }}</span><strong class="text-end">{{ $report->approval_signoff_display }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.approval_signed_at') }}</span><strong>{{ $report->approval_signed_at?->format('Y-m-d H:i') ?? '—' }}</strong></div>
                    <div class="list-group-item"><span class="text-muted d-block mb-1">{{ __('messages.reports.signoff_statement') }}</span><strong>{{ $report->approval_signoff_statement ?: '—' }}</strong></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.reports.approval_signoff_footer') }}</div>
        </div>
        <div class="card aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.reports.source_context') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.generated_by') }}</span><strong class="text-truncate">{{ $report->generatedBy?->name ?? '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.snapshot') }}</span><strong>{{ $report->release_readiness_run_id ? '#'.$report->release_readiness_run_id : '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.source_release_decision') }}</span><strong>{{ $report->release_decision_snapshot_id ? '#'.$report->release_decision_snapshot_id : '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.reviewed_by') }}</span><strong>{{ $report->reviewedBy?->name ?? '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.approved_by') }}</span><strong>{{ $report->approvedBy?->name ?? '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.archived_by') }}</span><strong>{{ $report->archivedBy?->name ?? '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.reports.metric_labels.open_findings') }}</span><strong>{{ data_get($data, 'metrics.open_findings', data_get($data, 'report_preview.counters.3.value', 0)) }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.evidence.total') }}</span><strong>{{ data_get($data, 'metrics.evidence', '—') }}</strong></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.reports.source_context_footer') }}</div>
        </div>
    </div>
</div>
@endsection
