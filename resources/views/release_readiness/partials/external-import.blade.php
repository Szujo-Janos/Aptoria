@php
    $externalImport = $externalImport ?? [];
    $hasRun = (bool) ($externalImport['has_run'] ?? false);
    $tone = $externalImport['status_tone'] ?? 'secondary';
    $label = $externalImport['status_label'] ?? __('messages.import_center.statuses.missing');
@endphp
<div class="card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="brackets-contain" class="me-2"></i>{{ __('messages.release_readiness.external_import.title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.release_readiness.external_import.copy') }}</p>
        </div>
        <span class="badge badge-soft-{{ $tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $label }}</span>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.items') }}</span><strong>{{ $externalImport['item_count'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.entity_types.endpoint') }}</span><strong>{{ $externalImport['endpoint_count'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.entity_types.assertion') }}</span><strong>{{ $externalImport['assertion_count'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.entity_types.finding') }}</span><strong class="text-danger">{{ $externalImport['finding_count'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.entity_types.evidence') }}</span><strong class="text-success">{{ $externalImport['evidence_count'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.conflicts') }}</span><strong class="text-danger">{{ $externalImport['conflict_count'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.needs_review') }}</span><strong class="text-warning">{{ $externalImport['needs_review_count'] ?? 0 }}</strong></div>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle d-flex flex-wrap justify-content-between gap-2 text-muted">
        <span>{{ __('messages.release_readiness.external_import.footer') }}</span>
        @if ($hasRun && ($externalImport['latest_run_id'] ?? null))
            <a href="{{ route('projects.import-center.show', [$project, $externalImport['latest_run_id']]) }}" class="link-secondary"><i data-lucide="eye" class="me-1"></i>{{ __('messages.import_center.view_latest') }}</a>
        @else
            <a href="{{ route('projects.import-center.create', $project) }}" class="link-secondary"><i data-lucide="brackets-contain" class="me-1"></i>{{ __('messages.import_center.new') }}</a>
        @endif
    </div>
</div>
