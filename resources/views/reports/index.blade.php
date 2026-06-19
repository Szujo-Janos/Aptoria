@extends('layouts.app')
@section('title', __('messages.reports.title'))
@section('page_title', __('messages.reports.title'))
@section('page_actions')
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reportGenerateModal"><i data-lucide="file-plus" class="me-1"></i>{{ __('messages.reports.generate') }}</button>
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="report-analytics"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.reports.total') }}</p><h3 class="mb-0 fw-light">{{ $metrics['reports'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-success"><span class="avatar-title"><i data-lucide="badge-check"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.reports.approved') }}</p><h3 class="mb-0 fw-light">{{ $metrics['approved'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-info"><span class="avatar-title"><i data-lucide="file-check"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.reports.signed_off') }}</p><h3 class="mb-0 fw-light">{{ $metrics['signed_off'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-warning"><span class="avatar-title"><i data-lucide="bug"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.findings.open_findings') }}</p><h3 class="mb-0 fw-light">{{ $metrics['open_findings'] }}</h3></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.reports.versions') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.reports.copy') }}</p>
                </div>
                <span class="badge badge-soft-primary">{{ $reports->count() }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table data-tables="report-versions" data-aptoria-paging="true" data-aptoria-order-column="3" data-aptoria-order-dir="desc" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-report-versions-table">
                        <thead class="align-middle thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th data-priority="1">{{ __('messages.reports.report') }}</th>
                                <th data-priority="3">{{ __('messages.reports.type') }}</th>
                                <th data-priority="4">{{ __('messages.common.status') }}</th>
                                <th data-priority="5">{{ __('messages.reports.generated_at') }}</th>
                                <th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports as $report)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 min-w-0 ">
                                            <span class="avatar avatar-sm rounded text-bg-primary flex-shrink-0"><span class="avatar-title"><i data-lucide="git-fork"></i></span></span>
                                            <div class="min-w-0 w-100">
                                                <a href="{{ route('projects.reports.show', [$project, $report]) }}" class="fw-medium text-body d-block text-truncate aptoria-endpoint-name-cell">{{ $report->title }}</a>
                                                <small class="text-muted text-truncate d-block">{{ __('messages.reports.checksum') }}: {{ \Illuminate\Support\Str::limit($report->checksum, 16, '') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="aptoria-report-type-cell min-w-0">
                                            <span class="badge badge-soft-info badge-label">{{ $report->type_label }}</span>
                                            @if($report->release_decision_snapshot_id)
                                                <small class="text-muted d-block aptoria-report-source-cell">{{ __('messages.reports.source_release_decision') }} #{{ $report->release_decision_snapshot_id }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td><span class="badge badge-soft-{{ $report->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $report->status_label }}</span>@if($report->has_approval_signoff)<small class="text-muted d-block mt-1"><i data-lucide="file-check" class="me-1"></i>{{ __('messages.reports.signed_off') }}</small>@endif</td>
                                    <td><small class="text-muted text-nowrap">{{ $report->generated_at?->format('Y-m-d H:i') ?? $report->created_at?->format('Y-m-d H:i') }}</small></td>
                                    <td class="text-end aptoria-actions-cell">
                                        <div class="dropdown"><button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a href="{{ route('projects.reports.show', [$project, $report]) }}" class="dropdown-item"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.view') }}</a>
                                                <a href="{{ route('projects.reports.download', [$project, $report, 'md']) }}" class="dropdown-item"><i data-lucide="download" class="me-2"></i>{{ __('messages.reports.download_markdown') }}</a>
                                                <a href="{{ route('projects.reports.download', [$project, $report, 'html']) }}" class="dropdown-item"><i data-lucide="download" class="me-2"></i>{{ __('messages.reports.download_html') }}</a>
                                                <a href="{{ route('projects.reports.download', [$project, $report, 'pdf']) }}" class="dropdown-item"><i data-lucide="download" class="me-2"></i>{{ __('messages.reports.download_pdf') }}</a>
                                                <a href="{{ route('projects.reports.download', [$project, $report, 'json']) }}" class="dropdown-item"><i data-lucide="download" class="me-2"></i>{{ __('messages.reports.download_json') }}</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-5">{{ __('messages.reports.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.reports.footer') }}</div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.reports.package_context') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.latest_snapshot') }}</span><strong>{{ $latestReadiness?->score ? $latestReadiness->score.'%' : '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.blockers') }}</span><strong class="text-danger">{{ $latestReadiness?->blocker_count ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.warnings') }}</span><strong class="text-warning">{{ $latestReadiness?->warning_count ?? 0 }}</strong></div>
                    <div class="list-group-item"><small class="text-muted d-block mb-2">{{ __('messages.reports.package_hint') }}</small><button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#reportGenerateModal"><i data-lucide="file-plus" class="me-1"></i>{{ __('messages.reports.generate') }}</button></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.reports.package_footer') }}</div>
        </div>
    </div>
</div>

<div class="modal fade" id="reportGenerateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('projects.reports.store', $project) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.reports.confirm_title') }}" data-confirm-text="{{ __('messages.reports.confirm_text') }}" data-confirm-button="{{ __('messages.reports.generate') }}" data-aptoria-form-scope="reports" data-aptoria-form-plugin>
                @csrf
                <div class="modal-header"><h5 class="modal-title"><i data-lucide="file-plus" class="me-2"></i>{{ __('messages.reports.generate') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-2 align-items-start"><i data-lucide="info" class="mt-1"></i><div><strong>{{ __('messages.reports.snapshot_note') }}</strong><br><span class="small">{{ __('messages.reports.snapshot_note_copy') }}</span></div></div>
                    <div class="row g-3">
                        <div class="col-md-5"><label class="form-label">{{ __('messages.reports.type') }}</label><select name="type" class="form-select">@foreach(array_values(array_diff(\App\Models\ReportVersion::TYPES, ['release_decision'])) as $type)<option value="{{ $type }}">{{ __('messages.reports.types.'.$type) }}</option>@endforeach</select></div>
                        <div class="col-md-7"><label class="form-label">{{ __('messages.reports.title_field') }}</label><input type="text" name="title" class="form-control" placeholder="{{ __('messages.form_plugin.placeholders.reports.title') }}"></div>
                        <div class="col-12"><label class="form-label">{{ __('messages.reports.notes') }}</label><textarea name="notes" class="form-control" rows="4" placeholder="{{ __('messages.form_plugin.placeholders.reports.notes') }}"></textarea></div>
                        <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="confirm_report" value="1" required><span class="form-check-label">{{ __('messages.reports.confirm_checkbox') }}</span></label></div>
                    </div>
                </div>
                <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.reports.save_report') }}</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
