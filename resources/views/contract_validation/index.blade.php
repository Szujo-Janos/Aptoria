@extends('layouts.app')
@section('title', __('messages.contract_validation.title') . ' · ' . $project->name)
@section('page_title', __('messages.contract_validation.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contractValidationModal"><i data-lucide="file-check-2" class="me-1"></i>{{ __('messages.contract_validation.new') }}</button>
@endsection

@section('content')
<div class="row g-3">
    @foreach ([
        ['documented_operations','file-code-2','primary',__('messages.contract_validation.documented_operations')],
        ['inventory_operations','plug-connected','info',__('messages.contract_validation.inventory_operations')],
        ['matched_operations','badge-check','success',__('messages.contract_validation.matched_operations')],
        ['blocker_count','octagon-alert','danger',__('messages.release_readiness.blockers')],
    ] as [$key,$icon,$tone,$label])
        <div class="col-md-3">
            <div class="card aptoria-panel-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar rounded text-bg-{{ $tone }}"><span class="avatar-title"><i data-lucide="{{ $icon }}"></i></span></span>
                    <div>
                        <div class="text-muted small">{{ $label }}</div>
                        <h3 class="mb-0 fw-light">{{ $summary[$key] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-4">
        <div class="card aptoria-panel-card">
            <div class="card-header border-light">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="file-search" class="me-2"></i>{{ __('messages.contract_validation.latest_summary') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.contract_validation.copy') }}</p>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.common.status') }}</span><span class="badge badge-soft-{{ $summary['latest_status_tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $summary['latest_status_label'] ?? __('messages.contract_validation.statuses.missing') }}</span></div>
                    @if (($summary['source_warning_count'] ?? 0) > 0)
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.source_warning_count') }}</span><strong class="text-warning">{{ $summary['source_warning_count'] }}</strong></div>
                    @endif
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.undocumented_inventory_operations') }}</span><strong>{{ $summary['undocumented_inventory_operations'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.missing_inventory_operations') }}</span><strong>{{ $summary['missing_inventory_operations'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.warnings') }}</span><strong class="text-warning">{{ $summary['warning_count'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.contract_validation.validated_at') }}</span><strong class="text-end">{{ $summary['validated_at'] ?? __('messages.common.not_available') }}</strong></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.contract_validation.latest_footer') }}</div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="file-check-2" class="me-2"></i>{{ __('messages.contract_validation.runs_title') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.contract_validation.runs_copy') }}</p>
                </div>
                <span class="badge badge-soft-primary">{{ $runs->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table data-tables="contract-validation-runs" data-aptoria-actions="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                        <thead>
                            <tr>
                                <th data-priority="1">{{ __('messages.contract_validation.source') }}</th>
                                <th data-priority="2">{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.contract_validation.coverage') }}</th>
                                <th>{{ __('messages.contract_validation.validated_at') }}</th>
                                <th data-priority="3" class="text-end">{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($runs as $run)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="file-code-2"></i></span></span>
                                            <div class="min-w-0">
                                                <div class="fw-normal text-truncate">{{ $run->source_name ?: __('messages.contract_validation.unnamed_contract') }}</div>
                                                <small class="text-muted d-block text-truncate">{{ $run->source_version ?: $run->openapi_version ?: __('messages.common.not_available') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-soft-{{ $run->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $run->status_label }}</span></td>
                                    <td><small class="text-muted d-block text-truncate">{{ __('messages.contract_validation.matched_operations') }}: {{ $run->matched_operations }} / {{ $run->inventory_operations }} · {{ __('messages.release_readiness.blockers') }}: {{ $run->blocker_count }} · {{ __('messages.release_readiness.warnings') }}: {{ $run->warning_count }}</small></td>
                                    <td><span class="text-muted">{{ $run->validated_at?->format('Y-m-d H:i') ?? '—' }}</span></td>
                                    <td class="text-end aptoria-actions-cell">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="{{ route('projects.contract-validation.show', [$project, $run]) }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.view') }}</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.contract_validation.empty_runs') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.contract_validation.runs_footer') }}</div>
        </div>
    </div>
</div>

<div class="modal fade" id="contractValidationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('projects.contract-validation.store', $project) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.contract_validation.confirm_title') }}" data-confirm-text="{{ __('messages.contract_validation.confirm_text') }}" data-confirm-button="{{ __('messages.contract_validation.validate') }}" data-aptoria-form-scope="contract_validation" data-aptoria-form-plugin>
                @csrf
                <div class="modal-header"><h5 class="modal-title"><i data-lucide="file-check-2" class="me-2"></i>{{ __('messages.contract_validation.new') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger d-flex align-items-start gap-2"><i data-lucide="triangle-alert" class="mt-1"></i><div><strong>{{ __('messages.common.validation_error') }}</strong><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
                    @endif
                    <div class="alert alert-info d-flex align-items-start gap-2"><i data-lucide="info" class="mt-1"></i><div><strong>{{ __('messages.contract_validation.form_help_title') }}</strong><br><span class="small">{{ __('messages.contract_validation.form_help') }}</span></div></div>
                    <div class="aptoria-form-section mb-3">
                        <div class="aptoria-form-section-header"><div><h6 class="mb-1">{{ __('messages.contract_validation.sections.source') }}</h6><p class="text-muted small mb-0">{{ __('messages.contract_validation.sections.source_help') }}</p></div></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">{{ __('messages.contract_validation.source_name') }}</label><input type="text" name="source_name" value="{{ old('source_name') }}" class="form-control @error('source_name') is-invalid @enderror" placeholder="{{ __('messages.form_plugin.placeholders.contract_validation.source_name') }}">@error('source_name')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">{{ __('messages.contract_validation.source_name_help') }}</div></div>
                            <div class="col-md-6"><label class="form-label">{{ __('messages.contract_validation.source_version') }}</label><input type="text" name="source_version" value="{{ old('source_version') }}" class="form-control @error('source_version') is-invalid @enderror" placeholder="{{ __('messages.form_plugin.placeholders.contract_validation.source_version') }}">@error('source_version')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">{{ __('messages.contract_validation.source_version_help') }}</div></div>
                        </div>
                    </div>
                    <div class="aptoria-form-section mb-3">
                        <div class="aptoria-form-section-header"><div><h6 class="mb-1">{{ __('messages.contract_validation.sections.contract') }}</h6><p class="text-muted small mb-0">{{ __('messages.contract_validation.sections.contract_help') }}</p></div></div>
                        <label class="form-label">{{ __('messages.contract_validation.contract_content') }}</label>
                        <textarea name="contract_content" class="form-control font-monospace @error('contract_content') is-invalid @enderror" rows="16" required placeholder="{{ __('messages.form_plugin.placeholders.contract_validation.contract_content') }}">{{ old('contract_content') }}</textarea>
                        @error('contract_content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('messages.contract_validation.contract_content_help') }}</div>
                    </div>
                    <label class="form-check mb-0"><input class="form-check-input @error('confirm_validation') is-invalid @enderror" type="checkbox" name="confirm_validation" value="1" {{ old('confirm_validation') ? 'checked' : '' }} required><span class="form-check-label">{{ __('messages.contract_validation.confirm_checkbox') }}</span>@error('confirm_validation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</label>
                </div>
                <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="file-check-2" class="me-1"></i>{{ __('messages.contract_validation.validate') }}</button></div>
            </form>
        </div>
    </div>
</div>
@endsection


@push('scripts')
@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('contractValidationModal');
    if (! modal || ! window.bootstrap) { return; }
    window.bootstrap.Modal.getOrCreateInstance(modal).show();
});
</script>
@endif
@endpush
