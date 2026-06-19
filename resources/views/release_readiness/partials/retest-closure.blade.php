@php
    $closure = $retestClosure ?? [];
    $rows = collect($closure['rows'] ?? []);
    $tone = $closure['tone'] ?? 'secondary';
    $rate = (int) ($closure['closure_rate'] ?? 100);
@endphp
<div class="card aptoria-table-card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div class="d-flex align-items-start gap-2 min-w-0">
            <span class="avatar avatar-sm rounded text-bg-{{ $tone }} mt-1"><span class="avatar-title"><i data-lucide="shield-check"></i></span></span>
            <div class="min-w-0">
                <h5 class="card-title mb-1 text-truncate">{{ __('messages.release_readiness.retest_closure.title') }}</h5>
                <p class="text-muted mb-0 small text-truncate">{{ __('messages.release_readiness.retest_closure.copy') }}</p>
            </div>
        </div>
        <span class="badge badge-soft-{{ $tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $closure['label'] ?? __('messages.release_readiness.retest_closure.statuses.closed') }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-stretch">
            <div class="col-lg-4">
                <div class="border rounded h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">{{ __('messages.release_readiness.retest_closure.closure_rate') }}</span>
                        <strong>{{ $rate }}%</strong>
                    </div>
                    <div class="progress progress-sm mb-3"><div class="progress-bar bg-{{ $tone }}" style="width: {{ $rate }}%;"></div></div>
                    <p class="text-muted small mb-0">{{ $closure['summary'] ?? __('messages.release_readiness.retest_closure.summary_closed') }}</p>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="row row-cols-2 row-cols-md-4 g-2">
                    @foreach ([
                        ['total', 'files', 'secondary', __('messages.release_readiness.retest_closure.total')],
                        ['passed', 'badge-check', 'success', __('messages.release_readiness.retest_closure.passed')],
                        ['pending', 'rotate-ccw', 'warning', __('messages.release_readiness.retest_closure.pending')],
                        ['failed', 'shield-x', 'danger', __('messages.release_readiness.retest_closure.failed')],
                        ['missing_evidence', 'certificate', 'warning', __('messages.release_readiness.retest_closure.missing_evidence')],
                        ['regression_open', 'bug', 'danger', __('messages.release_readiness.retest_closure.regression_open')],
                        ['regression_retest_open', 'git-pull-request-closed', 'danger', __('messages.release_readiness.retest_closure.regression_retest_open')],
                        ['stale_ready', 'clock-alert', 'warning', __('messages.release_readiness.retest_closure.stale_ready')],
                    ] as [$key, $icon, $counterTone, $label])
                        <div class="col">
                            <div class="border rounded p-2 h-100 d-flex align-items-center gap-2">
                                <span class="avatar avatar-xs rounded text-bg-{{ $counterTone }}"><span class="avatar-title"><i data-lucide="{{ $icon }}"></i></span></span>
                                <div class="min-w-0">
                                    <div class="fw-medium">{{ (int) ($closure[$key] ?? 0) }}</div>
                                    <small class="text-muted d-block text-truncate">{{ $label }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0 border-top">
        <div class="table-responsive">
            <table class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead class="thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.findings.finding') }}</th>
                        <th data-priority="2">{{ __('messages.findings.retest') }}</th>
                        <th data-priority="3">{{ __('messages.endpoints.endpoint') }}</th>
                        <th data-priority="4">{{ __('messages.evidence.title') }}</th>
                        <th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                <div class="min-w-0">
                                    <span class="fw-medium d-block text-truncate">{{ $row['title'] ?? '—' }}</span>
                                    <small class="text-muted d-block text-truncate">#{{ $row['id'] ?? '—' }} · {{ $row['severity_label'] ?? '' }}</small>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-{{ $row['retest_status_tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $row['retest_status_label'] ?? '—' }}</span></td>
                            <td><code class="small text-break">{{ $row['endpoint'] ?? '—' }}</code></td>
                            <td><span class="badge badge-soft-{{ ! empty($row['has_retest_evidence']) ? 'success' : 'warning' }} badge-label"><i class="ti ti-point-filled"></i>{{ ! empty($row['has_retest_evidence']) ? __('messages.release_readiness.retest_closure.evidence_attached') : __('messages.release_readiness.retest_closure.evidence_missing') }}</span></td>
                            <td class="text-end aptoria-actions-cell">
                                @if (! empty($row['id']))
                                    <a href="{{ route('projects.findings.show', [$project, $row['id']]) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="{{ __('messages.common.view') }}"><i data-lucide="eye"></i></a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.release_readiness.retest_closure.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex flex-wrap justify-content-between gap-2">
        <span>{{ __('messages.release_readiness.retest_closure.footer') }}</span>
        <span>{{ __('messages.release_readiness.retest_closure.locked_at') }}: {{ $closure['locked_at'] ?? '—' }}</span>
    </div>
</div>
