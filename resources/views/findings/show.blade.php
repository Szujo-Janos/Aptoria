@extends('layouts.app')
@section('title', $finding->title)
@section('page_title', __('messages.findings.detail_title'))
@section('page_actions')
    <a href="{{ route('projects.findings.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    @if($finding->active_risk_acceptance || ($finding->latest_risk_acceptance && $finding->latest_risk_acceptance->display_status === 'expired'))
        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#riskRenewalModal"><i data-lucide="refresh-cw" class="me-1"></i>{{ __('messages.risk_acceptance.renew') }}</button>
    @else
        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#riskAcceptanceModal"><i data-lucide="shield-check" class="me-1"></i>{{ __('messages.risk_acceptance.accept_risk') }}</button>
    @endif
    <a href="{{ route('projects.evidence.create', ['project' => $project, 'finding_id' => $finding->id, 'endpoint_id' => $finding->endpoint_id, 'scan_result_id' => $finding->scan_result_id]) }}" class="btn btn-primary"><i data-lucide="file-plus" class="me-1"></i>{{ __('messages.evidence.add') }}</a>
@endsection

@section('content')
@php
    $activeRiskAcceptance = $finding->active_risk_acceptance;
    $latestRiskAcceptance = $finding->latest_risk_acceptance;
@endphp
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light justify-content-between align-items-start">
                <div>
                    <h5 class="card-title mb-1">{{ $finding->title }}</h5>
                    <p class="text-muted mb-0 small">{{ $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : __('messages.findings.no_endpoint') }}</p>
                </div>
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <span class="badge badge-soft-{{ $finding->severity_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $finding->severity_label }}</span>
                    <span class="badge badge-soft-{{ $finding->status_tone }}">{{ $finding->status_label }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-4"><div class="border rounded p-3"><span class="text-muted small">{{ __('messages.findings.source') }}</span><h6 class="mb-0">{{ $finding->source_label }}</h6></div></div>
                    <div class="col-sm-4"><div class="border rounded p-3"><span class="text-muted small">{{ __('messages.findings.priority') }}</span><h6 class="mb-0">{{ $finding->priority_label }}</h6></div></div>
                    <div class="col-sm-4"><div class="border rounded p-3"><span class="text-muted small">{{ __('messages.findings.owner') }}</span><h6 class="mb-0">{{ $finding->owner_name ?: '—' }}</h6></div></div>
                </div>
                <h6>{{ __('messages.findings.summary') }}</h6>
                <p class="text-muted">{{ $finding->summary ?: '—' }}</p>
                <div class="row g-3">
                    <div class="col-md-6"><h6>{{ __('messages.findings.expected_result') }}</h6><p class="text-muted small text-break">{{ $finding->expected_result ?: '—' }}</p></div>
                    <div class="col-md-6"><h6>{{ __('messages.findings.actual_result') }}</h6><p class="text-muted small text-break">{{ $finding->actual_result ?: '—' }}</p></div>
                    <div class="col-md-6"><h6>{{ __('messages.findings.reproduction_steps') }}</h6><p class="text-muted small text-break">{{ $finding->reproduction_steps ?: '—' }}</p></div>
                    <div class="col-md-6"><h6>{{ __('messages.findings.recommendation') }}</h6><p class="text-muted small text-break">{{ $finding->recommendation ?: '—' }}</p></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-between flex-wrap gap-2 text-muted"><span>{{ __('messages.common.updated') }}: {{ $finding->updated_at?->format('Y-m-d H:i') }}</span><span>{{ __('messages.nav.evidence') }}: {{ $evidenceItems->count() }}</span></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i data-lucide="rotate-ccw" class="me-2"></i>{{ __('messages.findings.retest_workflow') }}</h5>
                <span class="badge badge-soft-{{ $finding->retest_status_tone }}">{{ $finding->retest_status_label }}</span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2"><span>{{ __('messages.findings.retest_required') }}</span><span class="badge badge-soft-{{ $finding->retest_required ? 'warning' : 'success' }}">{{ $finding->retest_required ? __('messages.common.yes') : __('messages.common.no') }}</span></div>
                <div class="d-flex align-items-center justify-content-between mb-2"><span>{{ __('messages.findings.ready_for_retest_at') }}</span><small class="text-muted">{{ $finding->ready_for_retest_at?->format('Y-m-d H:i') ?? '—' }}</small></div>
                <div class="d-flex align-items-center justify-content-between mb-2"><span>{{ __('messages.findings.retested_at') }}</span><small class="text-muted">{{ $finding->retested_at?->format('Y-m-d H:i') ?? '—' }}</small></div>
                <div class="progress mt-3" style="height: 8px;"><div class="progress-bar bg-{{ $finding->retest_status_tone }}" style="width: {{ $finding->retest_status === 'passed' ? 100 : ($finding->retest_status === 'failed' ? 85 : ($finding->retest_status === 'ready_for_retest' ? 65 : ($finding->retest_status === 'required' ? 35 : 10))) }}%"></div></div>
                @if($finding->retest_note)
                    <p class="text-muted small mt-3 mb-0 text-break">{{ $finding->retest_note }}</p>
                @endif
                <div class="d-grid gap-2 mt-3">
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#requestRetestModal"><i data-lucide="rotate-ccw" class="me-1"></i>{{ __('messages.findings.request_retest') }}</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#readyForRetestModal"><i data-lucide="clipboard-check" class="me-1"></i>{{ __('messages.findings.mark_ready_for_retest') }}</button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#recordRetestModal"><i data-lucide="test-tube" class="me-1"></i>{{ __('messages.findings.record_retest') }}</button>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted small">{{ __('messages.findings.retest_workflow_footer') }}</div>
        </div>
        <div class="card mt-3 aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i data-lucide="shield-check" class="me-2"></i>{{ __('messages.risk_acceptance.title') }}</h5>
                <span class="badge badge-soft-{{ $finding->risk_acceptance_tone }}">{{ $finding->risk_acceptance_state_label }}</span>
            </div>
            <div class="card-body">
                @if($activeRiskAcceptance)
                    @if($activeRiskAcceptance->is_expiring_soon)
                        <div class="alert alert-warning d-flex gap-2 align-items-start"><i data-lucide="calendar-clock" class="mt-1"></i><div><strong>{{ __('messages.risk_acceptance.expiring_soon_title') }}</strong><br><span class="small">{{ __('messages.risk_acceptance.expiring_soon_copy', ['days' => $activeRiskAcceptance->days_until_expiry]) }}</span></div></div>
                    @endif
                    <div class="d-flex align-items-center justify-content-between mb-2"><span>{{ __('messages.risk_acceptance.accepted_until') }}</span><small class="text-muted">{{ $activeRiskAcceptance->accepted_until?->format('Y-m-d') ?? '—' }}</small></div>
                    <div class="d-flex align-items-center justify-content-between mb-2"><span>{{ __('messages.risk_acceptance.accepted_by') }}</span><small class="text-muted">{{ $activeRiskAcceptance->acceptedBy?->name ?? '—' }}</small></div>
                    <div class="d-flex align-items-center justify-content-between mb-2"><span>{{ __('messages.risk_acceptance.days_until_expiry') }}</span><span class="badge badge-soft-{{ $activeRiskAcceptance->status_tone }}">{{ $activeRiskAcceptance->days_until_expiry ?? '—' }}</span></div>
                    <p class="text-muted small mb-3 text-break">{{ $activeRiskAcceptance->reason }}</p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#riskRenewalModal"><i data-lucide="refresh-cw" class="me-1"></i>{{ __('messages.risk_acceptance.renew') }}</button>
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#riskCloseFindingModal"><i data-lucide="badge-check" class="me-1"></i>{{ __('messages.risk_acceptance.close_finding') }}</button>
                        <form method="POST" action="{{ route('projects.findings.risk-acceptance.revoke', [$project, $finding]) }}" data-aptoria-confirm="{{ __('messages.risk_acceptance.revoke_confirm') }}">
                            @csrf
                            <input type="hidden" name="revocation_note" value="{{ __('messages.risk_acceptance.revoked_from_panel') }}">
                            <button type="submit" class="btn btn-light w-100 text-danger"><i data-lucide="shield-x" class="me-1"></i>{{ __('messages.risk_acceptance.revoke') }}</button>
                        </form>
                    </div>
                @elseif($latestRiskAcceptance && $latestRiskAcceptance->display_status === 'expired')
                    <div class="alert alert-danger d-flex gap-2 align-items-start"><i data-lucide="shield-alert" class="mt-1"></i><div><strong>{{ __('messages.risk_acceptance.expired_title') }}</strong><br><span class="small">{{ __('messages.risk_acceptance.expired_copy') }}</span></div></div>
                    <div class="d-flex align-items-center justify-content-between mb-2"><span>{{ __('messages.risk_acceptance.accepted_until') }}</span><small class="text-muted">{{ $latestRiskAcceptance->accepted_until?->format('Y-m-d') ?? '—' }}</small></div>
                    <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#riskRenewalModal"><i data-lucide="refresh-cw" class="me-1"></i>{{ __('messages.risk_acceptance.renew') }}</button>
                @else
                    <p class="text-muted small mb-3">{{ __('messages.risk_acceptance.not_accepted_help') }}</p>
                    <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#riskAcceptanceModal"><i data-lucide="shield-check" class="me-1"></i>{{ __('messages.risk_acceptance.accept_risk') }}</button>
                @endif
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted small">{{ __('messages.risk_acceptance.footer') }}</div>
        </div>
        <div class="card mt-3 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="certificate" class="me-2"></i>{{ __('messages.findings.evidence_status') }}</h5></div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2"><span>{{ __('messages.findings.evidence_required') }}</span><span class="badge badge-soft-{{ $finding->evidence_required ? 'warning' : 'success' }}">{{ $finding->evidence_required ? __('messages.common.yes') : __('messages.common.no') }}</span></div>
                <div class="d-flex align-items-center justify-content-between mb-2"><span>{{ __('messages.nav.evidence') }}</span><span class="badge text-bg-light">{{ $evidenceItems->count() }}</span></div>
                <div class="progress mt-3" style="height: 8px;"><div class="progress-bar" style="width: {{ $evidenceItems->count() > 0 ? 100 : 15 }}%"></div></div>
                <p class="text-muted small mb-0 mt-2">{{ $evidenceItems->count() > 0 ? __('messages.evidence.linked_help') : __('messages.evidence.missing_help') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1">{{ __('messages.evidence.table_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.evidence.copy') }}</p></div><span class="badge badge-soft-primary">{{ $evidenceItems->count() }}</span></div>
    <div class="card-body"><div class="table-responsive"><table data-tables="finding-evidence" data-aptoria-paging="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table"><thead class="thead-sm text-uppercase fs-xxs"><tr><th data-priority="1">{{ __('messages.evidence.evidence') }}</th><th data-priority="2">{{ __('messages.evidence.type') }}</th><th data-priority="3">{{ __('messages.evidence.source_label') }}</th><th data-priority="4">{{ __('messages.evidence.captured_at') }}</th><th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th></tr></thead><tbody>@forelse($evidenceItems as $item)<tr><td><div class="fw-medium text-truncate aptoria-endpoint-name-cell">{{ $item->title }}</div><small class="text-muted text-truncate d-block">{{ $item->content ?: $item->url }}</small></td><td><span class="badge badge-soft-{{ $item->type_tone }}">{{ $item->type_label }}</span></td><td>{{ $item->source_label ?: '—' }}</td><td><small class="text-muted">{{ $item->captured_at?->format('Y-m-d H:i') ?? $item->created_at?->format('Y-m-d H:i') }}</small></td><td class="text-end aptoria-actions-cell"><form method="POST" action="{{ route('projects.evidence.destroy', [$project, $item]) }}" data-aptoria-confirm="{{ __('messages.evidence.confirm_delete') }}">@csrf @method('DELETE')<button class="btn btn-light btn-icon btn-sm rounded-circle text-danger" type="submit"><i data-lucide="trash-2"></i></button></form></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-5">{{ __('messages.evidence.empty') }}</td></tr>@endforelse</tbody></table></div></div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.evidence.footer') }}</div>
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1"><i data-lucide="shield-check" class="me-2"></i>{{ __('messages.risk_acceptance.history') }}</h5><p class="text-muted mb-0 small">{{ __('messages.risk_acceptance.history_copy') }}</p></div><span class="badge badge-soft-primary">{{ $riskAcceptances->count() }}</span></div>
    <div class="card-body"><div class="table-responsive"><table data-tables="risk-acceptance-history" data-aptoria-search="false" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table"><thead class="thead-sm text-uppercase fs-xxs"><tr><th data-priority="1">{{ __('messages.risk_acceptance.acceptance') }}</th><th data-priority="2">{{ __('messages.common.status') }}</th><th data-priority="3">{{ __('messages.risk_acceptance.accepted_until') }}</th><th data-priority="4">{{ __('messages.risk_acceptance.days_until_expiry') }}</th><th data-priority="5">{{ __('messages.risk_acceptance.accepted_by') }}</th></tr></thead><tbody>@forelse($riskAcceptances as $acceptance)<tr><td><div class="fw-medium text-truncate aptoria-endpoint-name-cell">{{ $acceptance->release_scope ?: __('messages.risk_acceptance.default_scope') }}</div><small class="text-muted text-truncate d-block">{{ $acceptance->reason }}</small></td><td><span class="badge badge-soft-{{ $acceptance->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $acceptance->status_label }}</span></td><td><small class="text-muted">{{ $acceptance->accepted_until?->format('Y-m-d') ?? '—' }}</small></td><td><span class="badge badge-soft-{{ $acceptance->status_tone }}">{{ $acceptance->days_until_expiry ?? '—' }}</span></td><td><small class="text-muted">{{ $acceptance->acceptedBy?->name ?? '—' }}</small></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-5">{{ __('messages.risk_acceptance.empty') }}</td></tr>@endforelse</tbody></table></div></div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.risk_acceptance.history_footer') }}</div>
</div>

@include('findings.partials.risk-acceptance-modal', ['modalId' => 'riskAcceptanceModal', 'action' => route('projects.findings.risk-acceptance.store', [$project, $finding]), 'title' => __('messages.risk_acceptance.accept_risk'), 'submitLabel' => __('messages.risk_acceptance.accept_risk'), 'submitIcon' => 'shield-check', 'finding' => $finding, 'acceptance' => null])
@include('findings.partials.risk-acceptance-modal', ['modalId' => 'riskRenewalModal', 'action' => route('projects.findings.risk-acceptance.renew', [$project, $finding]), 'title' => __('messages.risk_acceptance.renew'), 'submitLabel' => __('messages.risk_acceptance.renew'), 'submitIcon' => 'refresh-cw', 'finding' => $finding, 'acceptance' => $activeRiskAcceptance ?? $latestRiskAcceptance])

<div class="modal fade" id="riskCloseFindingModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('projects.findings.risk-acceptance.close-finding', [$project, $finding]) }}" data-aptoria-form-scope="risk-acceptance-close" data-aptoria-form-plugin>@csrf<div class="modal-header"><h5 class="modal-title"><i data-lucide="badge-check" class="me-2"></i>{{ __('messages.risk_acceptance.close_finding') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-light border d-flex gap-2 align-items-start"><i data-lucide="circle-alert" class="mt-1"></i><div><strong>{{ __('messages.risk_acceptance.close_finding_warning_title') }}</strong><br><span class="small text-muted">{{ __('messages.risk_acceptance.close_finding_warning_copy') }}</span></div></div><label class="form-label">{{ __('messages.risk_acceptance.closure_note') }}</label><textarea name="closure_note" class="form-control" rows="4" required placeholder="{{ __('messages.risk_acceptance.closure_note_placeholder') }}"></textarea><div class="form-text">{{ __('messages.risk_acceptance.closure_note_help') }}</div></div><div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-success"><i data-lucide="badge-check" class="me-1"></i>{{ __('messages.risk_acceptance.close_finding') }}</button></div></form></div></div></div>

<div class="modal fade" id="requestRetestModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('projects.findings.request-retest', [$project, $finding]) }}" data-aptoria-form-scope="finding-retest" data-aptoria-form-plugin>@csrf<div class="modal-header"><h5 class="modal-title"><i data-lucide="rotate-ccw" class="me-2"></i>{{ __('messages.findings.request_retest') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">{{ __('messages.findings.retest_note') }}</label><textarea name="retest_note" class="form-control" rows="4" placeholder="{{ __('messages.findings.retest_note_placeholder') }}">{{ $finding->retest_note }}</textarea><div class="form-text">{{ __('messages.findings.request_retest_help') }}</div></div><div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-warning"><i data-lucide="rotate-ccw" class="me-1"></i>{{ __('messages.findings.request_retest') }}</button></div></form></div></div></div>
<div class="modal fade" id="readyForRetestModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('projects.findings.ready-for-retest', [$project, $finding]) }}" data-aptoria-form-scope="finding-retest" data-aptoria-form-plugin>@csrf<div class="modal-header"><h5 class="modal-title"><i data-lucide="clipboard-check" class="me-2"></i>{{ __('messages.findings.mark_ready_for_retest') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">{{ __('messages.findings.retest_note') }}</label><textarea name="retest_note" class="form-control" rows="4" placeholder="{{ __('messages.findings.ready_for_retest_placeholder') }}">{{ $finding->retest_note }}</textarea><div class="form-text">{{ __('messages.findings.ready_for_retest_help') }}</div></div><div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="clipboard-check" class="me-1"></i>{{ __('messages.findings.mark_ready_for_retest') }}</button></div></form></div></div></div>
<div class="modal fade" id="recordRetestModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('projects.findings.record-retest', [$project, $finding]) }}" data-aptoria-form-scope="finding-retest" data-aptoria-form-plugin>@csrf<div class="modal-header"><h5 class="modal-title"><i data-lucide="test-tube" class="me-2"></i>{{ __('messages.findings.record_retest') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">{{ __('messages.findings.retest_result') }}</label><select name="result" class="form-select"><option value="passed">{{ __('messages.findings.retest_statuses.passed') }}</option><option value="failed">{{ __('messages.findings.retest_statuses.failed') }}</option></select><div class="form-text">{{ __('messages.findings.record_retest_help') }}</div></div><label class="form-label">{{ __('messages.findings.retest_note') }}</label><textarea name="retest_note" class="form-control" rows="4" placeholder="{{ __('messages.findings.record_retest_placeholder') }}"></textarea></div><div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-success"><i data-lucide="save" class="me-1"></i>{{ __('messages.findings.record_retest') }}</button></div></form></div></div></div>

@endsection
