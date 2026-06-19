@extends('layouts.app')
@section('title', __('messages.snapshots.title') . ' · ' . $project->name)
@section('page_title', __('messages.snapshots.title'))
@section('page_actions')
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#snapshotCreateModal"><i data-lucide="camera" class="me-1"></i>{{ __('messages.snapshots.new') }}</button>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card aptoria-metric-card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar rounded text-bg-primary"><span class="avatar-title"><i data-lucide="camera"></i></span></span><div><div class="text-muted small">{{ __('messages.snapshots.total') }}</div><h3 class="fw-light mb-0">{{ $snapshots->count() }}</h3></div></div></div></div>
    <div class="col-md-3"><div class="card aptoria-metric-card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar rounded text-bg-success"><span class="avatar-title"><i data-lucide="check-circle"></i></span></span><div><div class="text-muted small">{{ __('messages.snapshots.latest_passed') }}</div><h3 class="fw-light mb-0">{{ $snapshots->first()?->passed ?? 0 }}</h3></div></div></div></div>
    <div class="col-md-3"><div class="card aptoria-metric-card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar rounded text-bg-danger"><span class="avatar-title"><i data-lucide="shield-x"></i></span></span><div><div class="text-muted small">{{ __('messages.snapshots.latest_failed') }}</div><h3 class="fw-light mb-0">{{ $snapshots->first()?->failed ?? 0 }}</h3></div></div></div></div>
    <div class="col-md-3"><div class="card aptoria-metric-card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar rounded text-bg-warning"><span class="avatar-title"><i data-lucide="arrows-diff"></i></span></span><div><div class="text-muted small">{{ __('messages.snapshots.compares') }}</div><h3 class="fw-light mb-0">{{ $compares->count() }}</h3></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="camera" class="me-2"></i>{{ __('messages.snapshots.registry') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.snapshots.copy') }}</p>
                </div>
                <span class="badge badge-soft-primary">{{ $snapshots->count() }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table data-tables="endpoint-snapshots" data-aptoria-paging="true" data-aptoria-order-column="3" data-aptoria-order-dir="desc" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                        <thead class="align-middle thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th data-priority="1">{{ __('messages.snapshots.snapshot') }}</th>
                                <th data-priority="2">{{ __('messages.common.status') }}</th>
                                <th data-priority="4">{{ __('messages.snapshots.totals') }}</th>
                                <th data-priority="3">{{ __('messages.snapshots.captured_at') }}</th>
                                <th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($snapshots as $snapshot)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <span class="avatar avatar-sm rounded text-bg-primary flex-shrink-0"><span class="avatar-title"><i data-lucide="camera"></i></span></span>
                                            <div class="min-w-0 w-100">
                                                <a href="{{ route('projects.snapshots.show', [$project, $snapshot]) }}" class="fw-medium text-body d-block text-truncate aptoria-endpoint-name-cell">{{ $snapshot->title }}</a>
                                                <small class="text-muted d-block text-truncate">{{ __('messages.snapshots.checksum') }}: {{ $snapshot->short_checksum }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-soft-{{ $snapshot->tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $snapshot->status_label }}</span></td>
                                    <td><small class="text-muted">{{ $snapshot->total }} · <span class="text-success">{{ $snapshot->passed }}</span>/<span class="text-warning">{{ $snapshot->warning }}</span>/<span class="text-danger">{{ $snapshot->failed }}</span>/<span class="text-muted">{{ $snapshot->skipped }}</span></small></td>
                                    <td><small class="text-muted text-nowrap">{{ $snapshot->captured_at?->format('Y-m-d H:i') ?? '—' }}</small></td>
                                    <td class="text-end aptoria-actions-cell">
                                        <div class="dropdown"><button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a href="{{ route('projects.snapshots.show', [$project, $snapshot]) }}" class="dropdown-item"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.view') }}</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-5">{{ __('messages.snapshots.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.snapshots.footer') }}</div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="arrows-diff" class="me-2"></i>{{ __('messages.snapshots.compare') }}</h5></div>
            <div class="card-body">
                <p class="text-muted small">{{ __('messages.snapshots.compare_copy') }}</p>
                <form method="POST" action="{{ route('projects.snapshots.compare', $project) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.snapshots.compare_confirm_title') }}" data-confirm-text="{{ __('messages.snapshots.compare_confirm_text') }}" data-confirm-button="{{ __('messages.snapshots.compare') }}" data-aptoria-form-plugin>
                    @csrf
                    <div class="mb-3"><label class="form-label">{{ __('messages.snapshots.baseline') }}</label><select name="baseline_snapshot_id" class="form-select" required><option value="">{{ __('messages.snapshots.choose_snapshot') }}</option>@foreach($snapshots as $snapshot)<option value="{{ $snapshot->id }}">#{{ $snapshot->id }} · {{ $snapshot->title }}</option>@endforeach</select><div class="form-text">{{ __('messages.snapshots.baseline_help') }}</div></div>
                    <div class="mb-3"><label class="form-label">{{ __('messages.snapshots.target') }}</label><select name="target_snapshot_id" class="form-select" required><option value="">{{ __('messages.snapshots.choose_snapshot') }}</option>@foreach($snapshots as $snapshot)<option value="{{ $snapshot->id }}">#{{ $snapshot->id }} · {{ $snapshot->title }}</option>@endforeach</select><div class="form-text">{{ __('messages.snapshots.target_help') }}</div></div>
                    <div class="mb-3"><label class="form-label">{{ __('messages.common.notes') }}</label><textarea name="notes" class="form-control" rows="3" placeholder="{{ __('messages.form_plugin.placeholders.snapshots.compare_notes') }}"></textarea></div>
                    <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="confirm_compare" value="1" required><span class="form-check-label">{{ __('messages.snapshots.confirm_compare') }}</span></label>
                    <button class="btn btn-primary w-100" type="submit" @disabled($snapshots->count() < 2)><i data-lucide="arrows-diff" class="me-1"></i>{{ __('messages.snapshots.compare') }}</button>
                </form>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.snapshots.compare_footer') }}</div>
        </div>

        <div class="card aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="git-compare" class="me-2"></i>{{ __('messages.snapshots.recent_compares') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($compares as $compare)
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-2" href="{{ route('projects.snapshot-compares.show', [$project, $compare]) }}"><span class="min-w-0"><span class="d-block text-truncate">#{{ $compare->baseline_snapshot_id }} → #{{ $compare->target_snapshot_id }}</span><small class="text-muted">{{ $compare->compared_at?->diffForHumans() }}</small></span><span class="badge badge-soft-{{ $compare->tone }}">{{ $compare->status_label }}</span></a>
                    @empty
                        <div class="list-group-item text-muted small">{{ __('messages.snapshots.no_compares') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="snapshotCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('projects.snapshots.store', $project) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.snapshots.create_confirm_title') }}" data-confirm-text="{{ __('messages.snapshots.create_confirm_text') }}" data-confirm-button="{{ __('messages.snapshots.create') }}" data-aptoria-form-scope="snapshots" data-aptoria-form-plugin>
                @csrf
                <div class="modal-header"><h5 class="modal-title"><i data-lucide="camera" class="me-2"></i>{{ __('messages.snapshots.new') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-2 align-items-start"><i data-lucide="info" class="mt-1"></i><div><strong>{{ __('messages.snapshots.form_help_title') }}</strong><br><span class="small">{{ __('messages.snapshots.form_help') }}</span></div></div>
                    <div class="row g-3">
                        <div class="col-md-5"><label class="form-label">{{ __('messages.snapshots.source_batch') }}</label><select name="endpoint_test_batch_id" class="form-select" required><option value="">{{ __('messages.snapshots.choose_batch') }}</option>@foreach($batches as $batch)<option value="{{ $batch->id }}">#{{ $batch->id }} · {{ $batch->state_label }} · {{ $batch->completed_at?->format('Y-m-d H:i') }}</option>@endforeach</select><div class="form-text">{{ __('messages.snapshots.source_batch_help') }}</div></div>
                        <div class="col-md-7"><label class="form-label">{{ __('messages.snapshots.title_field') }}</label><input type="text" name="title" class="form-control" placeholder="{{ __('messages.form_plugin.placeholders.snapshots.title') }}"><div class="form-text">{{ __('messages.snapshots.title_help') }}</div></div>
                        <div class="col-12"><label class="form-label">{{ __('messages.common.notes') }}</label><textarea name="notes" class="form-control" rows="4" placeholder="{{ __('messages.form_plugin.placeholders.snapshots.notes') }}"></textarea></div>
                        <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="confirm_snapshot" value="1" required><span class="form-check-label">{{ __('messages.snapshots.confirm_snapshot') }}</span></label></div>
                    </div>
                </div>
                <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.snapshots.create') }}</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
