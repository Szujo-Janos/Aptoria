@extends('layouts.app')
@section('title', __('messages.contract_validation.detail_title') . ' · ' . $project->name)
@section('page_title', __('messages.contract_validation.detail_title'))
@section('page_actions')
    <a href="{{ route('projects.contract-validation.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.contract_validation.back_to_contracts') }}</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="min-w-0">
                        <span class="badge badge-soft-{{ $run->status_tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $run->status_label }}</span>
                        <h2 class="fw-normal mb-2">{{ $run->source_name ?: __('messages.contract_validation.unnamed_contract') }}</h2>
                        <p class="text-muted mb-0">{{ __('messages.contract_validation.detail_copy') }}</p>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.contract_validation.openapi_version') }}</div>
                        <strong class="fw-normal">{{ $run->openapi_version ?: __('messages.common.not_available') }}</strong>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex flex-wrap justify-content-between gap-2"><span>{{ __('messages.contract_validation.validated_by') }}: {{ $run->validatedBy?->name ?? '—' }}</span><span>{{ $run->validated_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="gauge" class="me-2"></i>{{ __('messages.contract_validation.validation_summary') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.matched_operations') }}</span><strong class="text-success">{{ $run->matched_operations }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.undocumented_inventory_operations') }}</span><strong class="text-warning">{{ $run->undocumented_inventory_operations }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.missing_inventory_operations') }}</span><strong class="text-info">{{ $run->missing_inventory_operations }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.blockers') }}</span><strong class="text-danger">{{ $run->blocker_count }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.warnings') }}</span><strong class="text-warning">{{ $run->warning_count }}</strong></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.contract_validation.summary_footer') }}</div>
        </div>
    </div>
</div>

@if (! empty($run->summary['source_warnings'] ?? []))
    <div class="alert alert-warning d-flex align-items-start gap-2 mt-3 mb-0">
        <i data-lucide="triangle-alert" class="mt-1"></i>
        <div>
            <strong>{{ __('messages.contract_validation.source_warnings_title') }}</strong>
            <p class="small mb-2">{{ __('messages.contract_validation.source_warnings_copy') }}</p>
            <ul class="mb-0 ps-3">
                @foreach (($run->summary['source_warnings'] ?? []) as $warning)
                    <li>{{ __('messages.contract_validation.source_warnings.'.(string) $warning) }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="card aptoria-table-card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="list-checks" class="me-2"></i>{{ __('messages.contract_validation.results_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.contract_validation.results_copy') }}</p>
        </div>
        <span class="badge badge-soft-primary">{{ $results->count() }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table data-tables="contract-validation-results" data-aptoria-actions="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead>
                    <tr>
                        <th data-priority="1">{{ __('messages.contract_validation.operation') }}</th>
                        <th>{{ __('messages.contract_validation.result_type') }}</th>
                        <th>{{ __('messages.contract_validation.severity') }}</th>
                        <th>{{ __('messages.contract_validation.summary') }}</th>
                        <th data-priority="2" class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr>
                            <td><span class="badge text-bg-{{ in_array($result->method, ['GET','HEAD'], true) ? 'success' : 'warning' }}">{{ $result->method }}</span><code class="ms-1">{{ $result->path }}</code>@if($result->operation_id)<small class="text-muted d-block mt-1">{{ $result->operation_id }}</small>@endif</td>
                            <td><span class="badge badge-soft-{{ $result->type_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $result->type_label }}</span></td>
                            <td><span class="badge badge-soft-{{ $result->severity_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $result->severity_label }}</span></td>
                            <td><span class="text-muted text-truncate d-block">{{ $result->summary }}</span></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        @if ($result->endpoint)
                                            <a class="dropdown-item" href="{{ route('projects.endpoints.index', $project) }}"><i data-lucide="plug-connected" class="me-2"></i>{{ __('messages.contract_validation.open_inventory') }}</a>
                                        @else
                                            <a class="dropdown-item" href="{{ route('projects.endpoints.index', $project) }}"><i data-lucide="plus" class="me-2"></i>{{ __('messages.contract_validation.create_inventory_item') }}</a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.contract_validation.empty_results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.contract_validation.results_footer') }}</div>
</div>

<div class="card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.contract_validation.copyable_evidence') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.contract_validation.copyable_evidence_copy') }}</p>
        </div>
        <button type="button" class="btn btn-light btn-sm" data-copy-target="#contractValidationMarkdown"><i data-lucide="copy" class="me-1"></i>{{ __('messages.common.copy') }}</button>
    </div>
    <div class="card-body"><textarea id="contractValidationMarkdown" class="form-control font-monospace" rows="12" readonly>{{ $markdownEvidence }}</textarea></div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.contract_validation.copyable_evidence_footer') }}</div>
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
            if (window.Swal) { Swal.fire({toast:true,position:'top-end',timer:1800,showConfirmButton:false,icon:'success',title:@json(__('messages.contract_validation.evidence_copied'))}); }
        });
    });
});
</script>
@endpush
