@extends('layouts.app')
@section('title', __('messages.client_portal.title') . ' · ' . $project->name)
@section('page_title', __('messages.client_portal.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clientPortalCreateModal"><i data-lucide="door-open" class="me-1"></i>{{ __('messages.client_portal.new') }}</button>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="link"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.client_portal.total_links') }}</p><h3 class="mb-0 fw-light">{{ $metrics['links'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-success"><span class="avatar-title"><i data-lucide="unlock-keyhole"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.client_portal.active_links') }}</p><h3 class="mb-0 fw-light">{{ $metrics['active'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-warning"><span class="avatar-title"><i data-lucide="clock-alert"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.client_portal.pending_acknowledgements') }}</p><h3 class="mb-0 fw-light">{{ $metrics['ack_pending'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-info"><span class="avatar-title"><i data-lucide="badge-check"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.client_portal.acknowledged_links') }}</p><h3 class="mb-0 fw-light">{{ $metrics['acknowledged'] }}</h3></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-table-card aptoria-panel-card mb-3">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.client_portal.links') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.client_portal.copy') }}</p>
                </div>
                <div class="card-action">
                    <span class="badge badge-soft-primary badge-label">{{ $accesses->count() }}</span>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#clientPortalCreateModal"><i data-lucide="plus" class="me-1"></i>{{ __('messages.client_portal.new') }}</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table data-tables="client-portal" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-client-portal-table">
                        <thead class="thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th data-priority="1">{{ __('messages.client_portal.access_name') }}</th>
                                <th data-priority="5">{{ __('messages.client_portal.role') }}</th>
                                <th data-priority="4">{{ __('messages.common.status') }}</th>
                                <th data-priority="2">{{ __('messages.client_portal.acknowledgement') }}</th>
                                <th data-priority="6">{{ __('messages.client_portal.expires_at') }}</th>
                                <th data-priority="7">{{ __('messages.client_portal.last_viewed_at') }}</th>
                                <th data-priority="3" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($accesses as $access)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <span class="avatar avatar-sm rounded text-bg-{{ $access->status_tone }}"><span class="avatar-title"><i data-lucide="door-open"></i></span></span>
                                            <div class="min-w-0">
                                                <span class="fw-medium d-block text-truncate aptoria-endpoint-name-cell">{{ $access->name }}</span>
                                                <small class="text-muted d-block text-truncate aptoria-url-cell">{{ $access->public_url }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-soft-info">{{ $access->role_label }}</span></td>
                                    <td><span class="badge badge-soft-{{ $access->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $access->status_label }}</span></td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge badge-soft-{{ $access->acknowledgement_state_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $access->acknowledgement_state_label }}</span>
                                            @if ($access->acknowledgement_decision)
                                                <small class="text-muted">{{ $access->acknowledgement_decision_label }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td><small class="text-muted">{{ $access->expires_at?->format('Y-m-d') ?? __('messages.client_portal.never_expires') }}</small></td>
                                    <td><small class="text-muted">{{ $access->last_viewed_at?->diffForHumans() ?? '—' }}</small></td>
                                    <td class="text-end aptoria-actions-cell">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i data-lucide="more-horizontal"></i></button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a href="{{ $access->public_url }}" target="_blank" class="dropdown-item"><i data-lucide="external-link" class="me-2"></i>{{ __('messages.client_portal.open_public') }}</a>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#clientPortalPreview{{ $access->id }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.projects.quick_preview') }}</button>
                                                <form method="POST" action="{{ route('projects.client-portal.toggle', [$project, $access]) }}">@csrf<button type="submit" class="dropdown-item"><i data-lucide="power" class="me-2"></i>{{ $access->is_active ? __('messages.client_portal.deactivate') : __('messages.client_portal.activate') }}</button></form>
                                                <div class="dropdown-divider"></div>
                                                <form method="POST" action="{{ route('projects.client-portal.destroy', [$project, $access]) }}" data-aptoria-confirm="delete" data-confirm-title="{{ __('messages.client_portal.delete_title') }}" data-confirm-text="{{ __('messages.client_portal.delete_text') }}" data-confirm-button="{{ __('messages.common.delete') }}">
                                                    @csrf @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit"><i data-lucide="trash-2" class="me-2"></i>{{ __('messages.common.delete') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5">{{ __('messages.client_portal.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.client_portal.footer') }}</div>
        </div>

        <div class="card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.client_portal.acknowledgement_history') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.client_portal.acknowledgement_history_copy') }}</p>
                </div>
                <span class="badge badge-soft-primary">{{ $acknowledgements->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse ($acknowledgements as $acknowledgement)
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                            <div class="min-w-0">
                                <div class="fw-medium text-truncate">{{ $acknowledgement->acknowledged_by_name }}</div>
                                <small class="text-muted d-block text-truncate">{{ $acknowledgement->access?->name ?? '—' }} · {{ $acknowledgement->reportVersion?->title ?? __('messages.client_portal.all_reports') }}</small>
                                @if ($acknowledgement->comment)
                                    <small class="text-muted d-block mt-1">{{ \Illuminate\Support\Str::limit($acknowledgement->comment, 120) }}</small>
                                @endif
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge badge-soft-{{ $acknowledgement->decision_tone }}">{{ $acknowledgement->decision_label }}</span>
                                <small class="text-muted d-block mt-1">{{ $acknowledgement->acknowledged_at?->format('Y-m-d H:i') }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">{{ __('messages.client_portal.no_acknowledgements') }}</div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.client_portal.acknowledgement_history_footer') }}</div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.client_portal.share_context') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.client_portal.deliverable_reports') }}</span><strong>{{ $metrics['deliverable_reports'] }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.latest_snapshot') }}</span><strong>{{ $latestReadiness?->score ? $latestReadiness->score.'%' : '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.client_portal.pending_acknowledgements') }}</span><strong>{{ $metrics['ack_pending'] }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.client_portal.approved_acknowledgements') }}</span><strong>{{ $metrics['approved_acknowledgements'] }}</strong></div>
                    <div class="list-group-item"><small class="text-muted d-block mb-2">{{ __('messages.client_portal.context_hint') }}</small><button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#clientPortalCreateModal"><i data-lucide="door-open" class="me-1"></i>{{ __('messages.client_portal.new') }}</button></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.client_portal.share_context_footer') }}</div>
        </div>

        <div class="card aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.client_portal.acknowledgement_policy') }}</h5></div>
            <div class="card-body">
                <div class="alert alert-info d-flex gap-2 align-items-start mb-3"><i data-lucide="badge-check" class="mt-1"></i><div><strong>{{ __('messages.client_portal.acknowledgement_policy_title') }}</strong><br><span class="small">{{ __('messages.client_portal.acknowledgement_policy_copy') }}</span></div></div>
                <ul class="text-muted small mb-0 ps-3">
                    <li>{{ __('messages.client_portal.ack_policy_approved_only') }}</li>
                    <li>{{ __('messages.client_portal.ack_policy_history') }}</li>
                    <li>{{ __('messages.client_portal.ack_policy_audit') }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="clientPortalCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('projects.client-portal.store', $project) }}" data-aptoria-form-scope="client_portal" data-aptoria-form-plugin>
                @csrf
                <div class="modal-header"><h5 class="modal-title"><i data-lucide="door-open" class="me-2"></i>{{ __('messages.client_portal.new') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-2 align-items-start"><i data-lucide="info" class="mt-1"></i><div><strong>{{ __('messages.client_portal.security_note') }}</strong><br><span class="small">{{ __('messages.client_portal.security_note_copy') }}</span></div></div>
                    <div class="alert alert-success d-flex gap-2 align-items-start"><i data-lucide="badge-check" class="mt-1"></i><div><strong>{{ __('messages.client_portal.approved_only_title') }}</strong><br><span class="small">{{ __('messages.client_portal.portal_approved_only_copy') }}</span></div></div>
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label">{{ __('messages.client_portal.access_name') }}</label><input class="form-control" name="name" required placeholder="{{ __('messages.form_plugin.placeholders.client_portal.name') }}"><div class="form-text">{{ __('messages.client_portal.access_name_help') }}</div></div>
                        <div class="col-md-5"><label class="form-label">{{ __('messages.client_portal.role') }}</label><select class="form-select" name="role">@foreach(\App\Models\ClientPortalAccess::ROLES as $role)<option value="{{ $role }}">{{ __('messages.client_portal.roles.'.$role) }}</option>@endforeach</select><div class="form-text">{{ __('messages.client_portal.role_help') }}</div></div>
                        <div class="col-md-7"><label class="form-label">{{ __('messages.client_portal.report_scope') }}</label><select class="form-select" name="report_version_id"><option value="">{{ __('messages.client_portal.all_reports') }}</option>@foreach($reports as $report)<option value="{{ $report->id }}">{{ $report->title }} · {{ $report->checksum ? \Illuminate\Support\Str::limit($report->checksum, 10, '') : __('messages.common.not_available') }}</option>@endforeach</select><div class="form-text">{{ __('messages.client_portal.report_scope_help') }}</div></div>
                        <div class="col-md-5"><label class="form-label">{{ __('messages.client_portal.expires_at') }}</label><input type="date" class="form-control" name="expires_at"><div class="form-text">{{ __('messages.client_portal.expires_at_help') }}</div></div>
                        <div class="col-12"><label class="form-label">{{ __('messages.client_portal.permissions_title') }}</label><div class="row g-2">@foreach(\App\Models\ClientPortalAccess::PERMISSIONS as $permission)<div class="col-sm-6"><label class="form-check border rounded p-2"><input class="form-check-input ms-0 me-2" type="checkbox" name="permissions[]" value="{{ $permission }}" {{ in_array($permission, ['reports','readiness'], true) ? 'checked' : '' }}><span class="form-check-label">{{ __('messages.client_portal.permissions.'.$permission) }}</span></label></div>@endforeach</div><div class="form-text">{{ __('messages.client_portal.permissions_help') }}</div></div>
                        <div class="col-12"><label class="form-check border rounded p-3"><input class="form-check-input" type="checkbox" name="acknowledge_required" value="1" checked><span class="form-check-label fw-medium">{{ __('messages.client_portal.acknowledge_required') }}</span><small class="text-muted d-block ms-4">{{ __('messages.client_portal.acknowledge_required_help') }}</small></label></div>
                    </div>
                </div>
                <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.client_portal.create_link') }}</button></div>
            </form>
        </div>
    </div>
</div>

@foreach ($accesses as $access)
<div class="modal fade" id="clientPortalPreview{{ $access->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ $access->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div>
            <div class="modal-body">
                <div class="input-group mb-3"><span class="input-group-text"><i data-lucide="link"></i></span><input class="form-control" readonly value="{{ $access->public_url }}"><a class="btn btn-primary" target="_blank" href="{{ $access->public_url }}">{{ __('messages.client_portal.open_public') }}</a></div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6"><div class="border rounded p-3"><small class="text-muted d-block">{{ __('messages.client_portal.permissions_title') }}</small><div class="d-flex flex-wrap gap-1 mt-2">@foreach($access->permissions as $permission)<span class="badge badge-soft-primary">{{ __('messages.client_portal.permissions.'.$permission) }}</span>@endforeach</div></div></div>
                    <div class="col-md-6"><div class="border rounded p-3"><small class="text-muted d-block">{{ __('messages.client_portal.report_scope') }}</small><div class="mt-2">{{ $access->reportVersion?->title ?? __('messages.client_portal.all_reports') }}</div></div></div>
                </div>
                <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                        <div><small class="text-muted d-block">{{ __('messages.client_portal.acknowledgement') }}</small><strong>{{ $access->acknowledgement_state_label }}</strong></div>
                        @if ($access->acknowledgement_decision)<span class="badge badge-soft-{{ $access->acknowledgement_decision_tone }}">{{ $access->acknowledgement_decision_label }}</span>@endif
                    </div>
                    @if ($access->acknowledged_at)
                        <p class="text-muted small mb-2">{{ __('messages.client_portal.acknowledged_by_line', ['name' => $access->acknowledged_by_name, 'date' => $access->acknowledged_at->format('Y-m-d H:i')]) }}</p>
                    @else
                        <p class="text-muted small mb-0">{{ __('messages.client_portal.ack_pending_hint') }}</p>
                    @endif
                    @if ($access->acknowledgement_comment)
                        <div class="alert alert-light border mb-0 mt-2">{{ $access->acknowledgement_comment }}</div>
                    @endif
                </div>
            </div>
            <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.close') }}</button></div>
        </div>
    </div>
</div>
@endforeach
@endsection
