@php
    $risk = $riskAcceptance ?? [];
    $rows = collect($risk['rows'] ?? []);
    $expired = (int) ($risk['expired'] ?? 0);
    $expiringSoon = (int) ($risk['expiring_soon'] ?? 0);
    $tone = $expired > 0 ? 'danger' : ($expiringSoon > 0 ? 'warning' : 'success');
@endphp
<div class="card aptoria-table-card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div class="d-flex align-items-start gap-2 min-w-0">
            <span class="avatar avatar-sm rounded text-bg-{{ $tone }} mt-1"><span class="avatar-title"><i data-lucide="calendar-clock"></i></span></span>
            <div class="min-w-0">
                <h5 class="card-title mb-1 text-truncate">{{ __('messages.release_readiness.risk_acceptance_expiry.title') }}</h5>
                <p class="text-muted mb-0 small text-truncate">{{ __('messages.release_readiness.risk_acceptance_expiry.copy', ['days' => $risk['watch_window_days'] ?? 7]) }}</p>
            </div>
        </div>
        <span class="badge badge-soft-{{ $tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $expired > 0 ? __('messages.release_readiness.risk_acceptance_expiry.statuses.blocked') : ($expiringSoon > 0 ? __('messages.release_readiness.risk_acceptance_expiry.statuses.review') : __('messages.release_readiness.risk_acceptance_expiry.statuses.clean')) }}</span>
    </div>
    <div class="card-body">
        <div class="row row-cols-2 row-cols-md-4 g-2">
            @foreach ([
                ['active', 'shield-check', 'success', __('messages.risk_acceptance.statuses.active')],
                ['expiring_soon', 'calendar-clock', 'warning', __('messages.risk_acceptance.statuses.expiring_soon')],
                ['expired', 'shield-alert', 'danger', __('messages.risk_acceptance.statuses.expired')],
                ['renewed', 'refresh-cw', 'info', __('messages.risk_acceptance.statuses.renewed')],
            ] as [$key, $icon, $counterTone, $label])
                <div class="col">
                    <div class="border rounded p-2 h-100 d-flex align-items-center gap-2">
                        <span class="avatar avatar-xs rounded text-bg-{{ $counterTone }}"><span class="avatar-title"><i data-lucide="{{ $icon }}"></i></span></span>
                        <div class="min-w-0">
                            <div class="fw-medium">{{ (int) ($risk[$key] ?? 0) }}</div>
                            <small class="text-muted d-block text-truncate">{{ $label }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-body p-0 border-top">
        <div class="table-responsive">
            <table data-tables="risk-acceptance-expiry" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead class="thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.findings.finding') }}</th>
                        <th data-priority="2">{{ __('messages.common.status') }}</th>
                        <th data-priority="3">{{ __('messages.risk_acceptance.accepted_until') }}</th>
                        <th data-priority="4">{{ __('messages.risk_acceptance.release_scope') }}</th>
                        <th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                <div class="min-w-0">
                                    <span class="fw-medium d-block text-truncate">{{ $row['finding_title'] ?? '—' }}</span>
                                    <small class="text-muted d-block text-truncate">#{{ $row['finding_id'] ?? '—' }} · {{ $row['finding_severity'] ?? '—' }}</small>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-{{ $row['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $row['status_label'] ?? '—' }}</span></td>
                            <td><small class="text-muted">{{ $row['accepted_until'] ?? '—' }} @if(($row['days_until_expiry'] ?? null) !== null) · {{ __('messages.risk_acceptance.days_left', ['days' => $row['days_until_expiry']]) }} @endif</small></td>
                            <td><span class="text-muted small text-break">{{ $row['release_scope'] ?? __('messages.risk_acceptance.default_scope') }}</span></td>
                            <td class="text-end aptoria-actions-cell">
                                @if (! empty($row['finding_id']))
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('messages.common.actions') }}"><i data-lucide="more-horizontal"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="{{ route('projects.findings.show', [$project, $row['finding_id']]) }}" class="dropdown-item"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.view') }}</a>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.release_readiness.risk_acceptance_expiry.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex flex-wrap justify-content-between gap-2">
        <span>{{ __('messages.release_readiness.risk_acceptance_expiry.footer') }}</span>
        <span>{{ __('messages.release_readiness.risk_acceptance_expiry.next_expiry') }}: {{ $risk['next_expiry_at'] ?? '—' }}</span>
    </div>
</div>
