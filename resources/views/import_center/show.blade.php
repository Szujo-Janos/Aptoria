@extends('layouts.app')
@section('title', __('messages.import_center.detail_title') . ' · ' . $project->name)
@section('page_title', __('messages.import_center.detail_title'))
@section('page_actions')
    <a href="{{ route('projects.import-center.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.import_center.back_to_imports') }}</a>
    @if ($run->status !== 'applied' && (int) ($run->summary['conflict_count'] ?? 0) === 0)
        <form method="POST" action="{{ route('projects.import-center.apply', [$project, $run]) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.import_center.confirm_apply_title') }}" data-confirm-text="{{ __('messages.import_center.confirm_apply_text') }}" data-confirm-button="{{ __('messages.import_center.confirm_apply_button') }}" class="d-inline">
            @csrf
            <input type="hidden" name="confirm_apply" value="1">
            <button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.import_center.apply_import') }}</button>
        </form>
    @elseif ($run->status !== 'applied' && $run->status !== 'reverted')
        <button type="button" class="btn btn-warning" disabled><i data-lucide="shield-alert" class="me-1"></i>{{ __('messages.import_center.conflicts') }}</button>
    @endif
    @if ($run->status === 'applied')
        <form method="POST" action="{{ route('projects.import-center.undo', [$project, $run]) }}" data-aptoria-confirm="danger" data-confirm-title="{{ __('messages.import_center.confirm_undo_title') }}" data-confirm-text="{{ __('messages.import_center.confirm_undo_text') }}" data-confirm-button="{{ __('messages.import_center.confirm_undo_button') }}" class="d-inline">
            @csrf
            <input type="hidden" name="confirm_undo" value="1">
            <button type="submit" class="btn btn-outline-danger"><i data-lucide="undo-2" class="me-1"></i>{{ __('messages.import_center.undo_import') }}</button>
        </form>
    @endif
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="min-w-0">
                        <span class="badge badge-soft-{{ $run->status_tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $run->status_label }}</span>
                        <h2 class="fw-normal mb-2"><i data-lucide="{{ $run->source_type_icon }}" class="me-2"></i>{{ $run->source_name ?: $run->source_type_label }}</h2>
                        <p class="text-muted mb-0">{{ __('messages.import_center.detail_copy') }}</p>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.import_center.source_type') }}</div>
                        <strong class="fw-normal"><i data-lucide="{{ $run->source_type_icon }}" class="me-1"></i>{{ $run->source_type_label }}</strong>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex flex-wrap justify-content-between gap-2">
                <span>{{ __('messages.import_center.created_by') }}: {{ $run->createdBy?->name ?? '—' }}</span>
                <span>{{ $run->previewed_at?->format('Y-m-d H:i') ?? '—' }}</span>@if($run->reverted_at)<span>{{ __('messages.import_center.reverted_at') }}: {{ $run->reverted_at?->format('Y-m-d H:i') }}</span>@endif
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="gauge" class="me-2"></i>{{ __('messages.import_center.validation_summary') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.items') }}</span><strong>{{ $run->item_count }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.entity_types.endpoint') }}</span><strong class="text-primary">{{ $run->endpoint_count }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.entity_types.assertion') }}</span><strong class="text-warning">{{ $run->assertion_count }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.entity_types.finding') }}</span><strong class="text-danger">{{ $run->finding_count }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.entity_types.evidence') }}</span><strong class="text-success">{{ $run->evidence_count }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.blockers') }}</span><strong class="text-danger">{{ $run->blocker_count }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.updates') }}</span><strong class="text-info">{{ $run->summary['update_count'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.duplicates') }}</span><strong class="text-secondary">{{ $run->summary['duplicate_count'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.conflicts') }}</span><strong class="text-danger">{{ $run->summary['conflict_count'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.needs_review') }}</span><strong class="text-warning">{{ $run->summary['needs_review_count'] ?? 0 }}</strong></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.import_center.summary_footer') }}</div>
        </div>
    </div>
</div>

<div class="card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="route" class="me-2"></i>{{ __('messages.import_center.traceability_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.import_center.traceability_copy') }}</p>
        </div>
        <span class="badge badge-soft-secondary">{{ __('messages.import_center.undo_safe') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.created_records') }}</span><strong>{{ $run->trace_summary['created_records'] ?? ($run->summary['traceability']['created_records'] ?? 0) }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.updated_records') }}</span><strong>{{ $run->trace_summary['updated_records'] ?? ($run->summary['traceability']['updated_records'] ?? 0) }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.skipped_items') }}</span><strong>{{ $run->trace_summary['skipped_items'] ?? ($run->summary['traceability']['skipped_items'] ?? 0) }}</strong></div>
            @if($run->status === 'reverted')
                <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.deleted_records') }}</span><strong class="text-danger">{{ $run->revert_summary['deleted'] ?? 0 }}</strong></div>
                <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.restored_records') }}</span><strong class="text-info">{{ $run->revert_summary['restored'] ?? 0 }}</strong></div>
                <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.reverted_by') }}</span><strong class="fw-normal">{{ $run->revertedBy?->name ?? '—' }}</strong></div>
            @endif
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.import_center.traceability_footer') }}</div>
</div>

<div class="card aptoria-table-card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="list-checks" class="me-2"></i>{{ __('messages.import_center.preview_items') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.import_center.preview_items_copy') }}</p>
        </div>
        <span class="badge badge-soft-primary">{{ $items->count() }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table data-tables="external-import-items" data-aptoria-actions="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead>
                    <tr>
                        <th data-priority="1">{{ __('messages.import_center.item') }}</th>
                        <th>{{ __('messages.import_center.entity') }}</th>
                        <th>{{ __('messages.import_center.action') }}</th>
                        <th>{{ __('messages.import_center.match_status') }}</th>
                        <th>{{ __('messages.import_center.severity') }}</th>
                        <th>{{ __('messages.import_center.summary') }}</th>
                        <th>{{ __('messages.import_center.trace_target') }}</th>
                        <th>{{ __('messages.import_center.revert_status') }}</th>
                        <th data-priority="2" class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><div class="min-w-0"><span class="fw-medium d-block text-truncate">{{ $item->title }}</span>@if($item->method || $item->path)<small class="text-muted d-block text-truncate"><span class="badge text-bg-light">{{ $item->method }}</span> <code>{{ $item->path }}</code></small>@endif</div></td>
                            <td><span class="badge badge-soft-{{ $item->entity_tone }} badge-label"><i data-lucide="{{ $item->entity_icon }}" class="me-1"></i>{{ $item->entity_type_label }}</span></td>
                            <td><span class="badge badge-soft-secondary badge-label">{{ $item->action_label }}</span></td>
                            <td><span class="badge badge-soft-{{ $item->match_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $item->match_status_label }}</span>@if($item->conflict_reason)<small class="text-danger d-block text-truncate">{{ $item->conflict_reason }}</small>@endif</td>
                            <td><span class="badge badge-soft-{{ $item->severity_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $item->severity_label }}</span></td>
                            <td><span class="text-muted text-truncate d-block">{{ $item->summary }}</span>@if($item->trace_note)<small class="text-muted d-block text-truncate">{{ $item->trace_note }}</small>@endif</td>
                            <td><span class="text-muted small">{{ $item->trace_target_label }}</span></td>
                            <td><span class="badge badge-soft-{{ $item->revert_status ? 'secondary' : 'light' }} badge-label">{{ $item->revert_status_label }}</span></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        @if ($item->endpoint)
                                            <a class="dropdown-item" href="{{ route('projects.endpoints.index', $project) }}"><i data-lucide="plug-connected" class="me-2"></i>{{ __('messages.import_center.open_endpoint') }}</a>
                                        @endif
                                        @if ($item->finding)
                                            <a class="dropdown-item" href="{{ route('projects.findings.show', [$project, $item->finding]) }}"><i data-lucide="bug" class="me-2"></i>{{ __('messages.import_center.open_finding') }}</a>
                                        @endif
                                        <span class="dropdown-item-text text-muted small">{{ __('messages.common.status') }}: {{ __('messages.import_center.item_statuses.'.($item->status ?: 'previewed')) }}</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">{{ __('messages.import_center.empty_items') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.import_center.preview_items_footer') }}</div>
</div>

<div class="card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.import_center.copyable_evidence') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.import_center.copyable_evidence_copy') }}</p>
        </div>
        <button type="button" class="btn btn-light btn-sm" data-copy-target="#externalImportMarkdown"><i data-lucide="copy" class="me-1"></i>{{ __('messages.common.copy') }}</button>
    </div>
    <div class="card-body"><textarea id="externalImportMarkdown" class="form-control font-monospace" rows="12" readonly>{{ $markdownEvidence }}</textarea></div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.import_center.copyable_evidence_footer') }}</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-copy-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector(button.dataset.copyTarget);
            if (! target) { return; }
            target.select();
            document.execCommand('copy');
            if (window.Swal) { Swal.fire({toast:true,position:'top-end',timer:1800,showConfirmButton:false,icon:'success',title:@json(__('messages.import_center.evidence_copied'))}); }
        });
    });
});
</script>
@endpush
