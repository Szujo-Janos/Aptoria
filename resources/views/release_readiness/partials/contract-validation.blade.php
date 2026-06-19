@php
    $contractValidation = $contractValidation ?? [];
    $hasRun = (bool) ($contractValidation['has_run'] ?? false);
    $tone = $contractValidation['latest_status_tone'] ?? 'secondary';
    $label = $contractValidation['latest_status_label'] ?? __('messages.contract_validation.statuses.missing');
@endphp
<div class="card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="file-check-2" class="me-2"></i>{{ __('messages.release_readiness.contract_validation.title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.release_readiness.contract_validation.copy') }}</p>
        </div>
        <span class="badge badge-soft-{{ $tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $label }}</span>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.documented_operations') }}</span><strong>{{ $contractValidation['documented_operations'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.inventory_operations') }}</span><strong>{{ $contractValidation['inventory_operations'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.undocumented_inventory_operations') }}</span><strong class="text-warning">{{ $contractValidation['undocumented_inventory_operations'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.missing_inventory_operations') }}</span><strong class="text-info">{{ $contractValidation['missing_inventory_operations'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.blockers') }}</span><strong class="text-danger">{{ $contractValidation['blocker_count'] ?? 0 }}</strong></div>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle d-flex flex-wrap justify-content-between gap-2 text-muted">
        <span>{{ __('messages.release_readiness.contract_validation.footer') }}</span>
        @if ($hasRun && ($contractValidation['latest_run_id'] ?? null))
            <a href="{{ route('projects.contract-validation.show', [$project, $contractValidation['latest_run_id']]) }}" class="link-secondary"><i data-lucide="eye" class="me-1"></i>{{ __('messages.contract_validation.view_latest') }}</a>
        @else
            <a href="{{ route('projects.contract-validation.index', $project) }}" class="link-secondary"><i data-lucide="file-plus" class="me-1"></i>{{ __('messages.contract_validation.new') }}</a>
        @endif
    </div>
</div>
