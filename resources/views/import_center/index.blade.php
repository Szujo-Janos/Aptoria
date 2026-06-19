@extends('layouts.app')
@section('title', __('messages.import_center.title') . ' · ' . $project->name)
@section('page_title', __('messages.import_center.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <a href="{{ route('projects.import-center.create', $project) }}" class="btn btn-primary"><i data-lucide="brackets-contain" class="me-1"></i>{{ __('messages.import_center.new') }}</a>
@endsection

@section('content')
<div class="row g-3">
    @foreach ([
        ['item_count','file-check-2','primary',__('messages.import_center.items')],
        ['endpoint_count','plug-connected','info',__('messages.import_center.entity_types.endpoint')],
        ['finding_count','bug','danger',__('messages.import_center.entity_types.finding')],
        ['evidence_count','certificate','success',__('messages.import_center.entity_types.evidence')],
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

<div class="card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="brackets-contain" class="me-2"></i>{{ __('messages.import_center.adapter_layer_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.import_center.adapter_layer_copy') }}</p>
        </div>
        <a href="{{ route('projects.import-center.create', $project) }}" class="btn btn-light btn-sm"><i data-lucide="search-check" class="me-1"></i>{{ __('messages.import_center.create_preview') }}</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach ($sourceAdapters as $adapter)
                <div class="col-md-6 col-xl-4">
                    <div class="aptoria-form-section h-100 mb-0">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="avatar avatar-sm rounded text-bg-{{ $adapter['tone'] }}"><span class="avatar-title"><i data-lucide="{{ $adapter['icon'] }}"></i></span></span>
                            <div class="min-w-0">
                                <h6 class="mb-1">{{ __('messages.import_center.source_types.'.$adapter['key']) }}</h6>
                                <p class="text-muted small mb-2">{{ __('messages.import_center.source_type_descriptions.'.$adapter['key']) }}</p>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($adapter['outputs'] as $output)
                                        <span class="badge badge-soft-secondary">{{ __('messages.import_center.entity_types.'.$output) }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.import_center.adapter_layer_footer') }}</div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-4">
        <div class="card aptoria-panel-card">
            <div class="card-header border-light">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="file-search" class="me-2"></i>{{ __('messages.import_center.latest_summary') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.import_center.latest_copy') }}</p>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.source_type') }}</span><strong class="text-end fw-normal">{{ $summary['source_type_label'] ?? __('messages.import_center.none_imported') }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.common.status') }}</span><span class="badge badge-soft-{{ $summary['status_tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $summary['status_label'] ?? __('messages.import_center.statuses.missing') }}</span></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.entity_types.assertion') }}</span><strong>{{ $summary['assertion_count'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.blockers') }}</span><strong class="text-danger">{{ $summary['blocker_count'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.warnings') }}</span><strong class="text-warning">{{ $summary['warning_count'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.conflicts') }}</span><strong class="text-danger">{{ $summary['conflict_count'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.needs_review') }}</span><strong class="text-warning">{{ $summary['needs_review_count'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.import_center.previewed_at') }}</span><strong class="text-end fw-normal">{{ $summary['previewed_at'] ?? __('messages.common.not_available') }}</strong></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.import_center.latest_footer') }}</div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="file-clock" class="me-2"></i>{{ __('messages.import_center.runs_title') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.import_center.runs_copy') }}</p>
                </div>
                <span class="badge badge-soft-primary">{{ $runs->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table data-tables="external-import-runs" data-aptoria-actions="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                        <thead>
                            <tr>
                                <th data-priority="1">{{ __('messages.import_center.source') }}</th>
                                <th data-priority="2">{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.import_center.impact') }}</th>
                                <th>{{ __('messages.import_center.previewed_at') }}</th>
                                <th data-priority="3" class="text-end">{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($runs as $run)
                                <tr>
                                    <td><div class="d-flex align-items-start gap-2 min-w-0"><span class="avatar avatar-xs rounded text-bg-{{ $run->source_type_tone }}"><span class="avatar-title"><i data-lucide="{{ $run->source_type_icon }}"></i></span></span><div class="min-w-0"><span class="fw-medium d-block text-truncate">{{ $run->source_name ?: $run->source_type_label }}</span><small class="text-muted d-block text-truncate">{{ $run->source_type_label }} @if($run->source_version) · {{ $run->source_version }} @endif</small></div></div></td>
                                    <td><span class="badge badge-soft-{{ $run->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $run->status_label }}</span></td>
                                    <td><small class="text-muted d-block text-truncate">{{ __('messages.import_center.items') }}: {{ $run->item_count }} · {{ __('messages.import_center.entity_types.finding') }}: {{ $run->finding_count }} · {{ __('messages.release_readiness.blockers') }}: {{ $run->blocker_count }} · {{ __('messages.import_center.conflicts') }}: {{ $run->summary['conflict_count'] ?? 0 }}</small></td>
                                    <td><small class="text-muted">{{ $run->previewed_at?->format('Y-m-d H:i') ?? '—' }}</small></td>
                                    <td class="text-end aptoria-actions-cell">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="{{ route('projects.import-center.show', [$project, $run]) }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.view') }}</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.import_center.empty_runs') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.import_center.runs_footer') }}</div>
        </div>
    </div>
</div>

@endsection
