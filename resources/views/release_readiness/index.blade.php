@extends('layouts.app')
@section('title', __('messages.release_readiness.title'))
@section('page_title', __('messages.release_readiness.title'))
@section('page_actions')
    <a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-light"><i data-lucide="workflow" class="me-1"></i>{{ __('messages.nav.release_gates') }}</a>
    <a href="{{ route('projects.release-readiness.rules.index', $project) }}" class="btn btn-light"><i data-lucide="sliders-horizontal" class="me-1"></i>{{ __('messages.release_readiness.rule_builder') }}</a>
    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#releaseDecisionSnapshotModal"><i data-lucide="clipboard-check" class="me-1"></i>{{ __('messages.release_decisions.save_decision_snapshot') }}</button>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#releaseReadinessEvaluateModal"><i data-lucide="shield-chevron" class="me-1"></i>{{ __('messages.release_readiness.evaluate') }}</button>
@endsection
@section('content')
@php
    $status = $evaluation['status'];
    $score = $evaluation['score'];
    $metrics = $evaluation['metrics'];
    $checks = $evaluation['checks'];
    $retestClosure = $evaluation['retest_closure'] ?? [];
    $riskAcceptance = $evaluation['risk_acceptance'] ?? [];
    $contractValidation = $evaluation['contract_validation'] ?? [];
    $externalImport = $evaluation['external_import'] ?? [];
    $profileSummary = $evaluation['profile'] ?? [];
@endphp
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100 aptoria-panel-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="min-w-0">
                        <span class="badge badge-soft-{{ $status === 'ready' ? 'success' : ($status === 'warning' ? 'warning' : 'danger') }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.statuses.'.$status) }}</span>
                        <h2 class="fw-normal mb-2">{{ __('messages.release_readiness.heading') }}</h2>
                        <p class="text-muted mb-0">{{ $evaluation['summary']['headline'] }}</p>
                    </div>
                    <div class="aptoria-project-score text-center">
                        <div class="aptoria-score-ring" style="--aptoria-score: {{ $score }}%;">{{ $score }}%</div>
                        <small class="text-muted">{{ __('messages.release_readiness.score') }} · {{ $evaluation['grade'] }}</small>
                    </div>
                </div>
                <div class="progress progress-lg mt-4 mb-2"><div class="progress-bar bg-{{ $status === 'ready' ? 'success' : ($status === 'warning' ? 'warning' : 'danger') }}" style="width: {{ $score }}%;"></div></div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>{{ __('messages.release_readiness.foundation') }}</span>
                    <span>{{ __('messages.release_readiness.evidence') }}</span>
                    <span>{{ __('messages.release_readiness.risk') }}</span>
                    <span>{{ __('messages.release_readiness.decision') }}</span>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <span>{{ __('messages.release_readiness.decision') }}: {{ $evaluation['summary']['decision'] }}</span>
                    <span>{{ __('messages.release_readiness.latest_snapshot') }}: {{ $latestRun?->generated_at?->format('Y-m-d H:i') ?? __('messages.release_readiness.no_snapshot') }}</span>
                    <span>{{ __('messages.release_readiness.active_profile') }}: {{ $profileSummary['profile_label'] ?? __('messages.release_readiness.profiles.standard') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.release_readiness.gate_summary') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.blockers') }}</span><strong class="text-danger">{{ $evaluation['blocker_count'] }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.warnings') }}</span><strong class="text-warning">{{ $evaluation['warning_count'] }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.passed_checks') }}</span><strong>{{ $evaluation['passed_check_count'] }} / {{ $evaluation['check_count'] }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.findings.open_findings') }}</span><strong>{{ $metrics['open_findings'] }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.evidence.total') }}</span><strong>{{ $metrics['evidence_count'] }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.risk_acceptance.accepted_risk') }}</span><strong>{{ $metrics['risk_acceptance_active'] ?? 0 }}</strong></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-center text-muted">{{ __('messages.release_readiness.gate_summary_footer') }}</div>
        </div>
    </div>
</div>

<div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 mt-1">
    @foreach ([['endpoint_count','route','primary',__('messages.release_readiness.metric_endpoints')], ['scan_run_count','radar','info',__('messages.release_readiness.metric_scans')], ['finding_count','triangle-alert','danger',__('messages.release_readiness.metric_findings')], ['risk_acceptance_active','shield-check','warning',__('messages.risk_acceptance.accepted_risk')], ['evidence_count','folder-search','success',__('messages.release_readiness.metric_evidence')]] as [$key, $icon, $tone, $label])
        <div class="col"><div class="card card-h-100 aptoria-widget-card"><div class="card-body d-flex justify-content-between align-items-start"><div><h5 class="fw-normal text-uppercase mb-3">{{ $label }}</h5><h2 class="fw-light mb-0">{{ $metrics[$key] ?? 0 }}</h2></div><i data-lucide="{{ $icon }}" class="text-muted fs-42 svg-sw-10"></i></div><div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.release_readiness.metric_footer') }}</div></div></div>
    @endforeach
</div>

@include('release_readiness.partials.retest-closure', ['project' => $project, 'retestClosure' => $retestClosure])
@include('release_readiness.partials.risk-acceptance-expiry', ['project' => $project, 'riskAcceptance' => $riskAcceptance])
@include('release_readiness.partials.contract-validation', ['project' => $project, 'contractValidation' => $contractValidation])
@include('release_readiness.partials.external-import', ['project' => $project, 'externalImport' => $externalImport])

<div class="row g-3 mt-1">
    <div class="col-xl-5">
        <div class="card card-h-100 aptoria-panel-card border-{{ $decisionSummary['recommended_tone'] }}">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.release_decisions.foundation_title') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.release_decisions.foundation_copy') }}</p>
                </div>
                <span class="badge badge-soft-{{ $decisionSummary['recommended_tone'] }} badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.release_decisions.decisions.'.$decisionSummary['recommended_decision']) }}</span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                    <div>
                        <div class="text-muted small">{{ __('messages.release_decisions.recommended_decision') }}</div>
                        <h3 class="fw-normal mb-0">{{ __('messages.release_decisions.decisions.'.$decisionSummary['recommended_decision']) }}</h3>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.release_readiness.score') }}</div>
                        <h3 class="fw-light mb-0">{{ $decisionSummary['readiness_score'] }}%</h3>
                    </div>
                </div>
                <div class="progress progress-lg mb-3"><div class="progress-bar bg-{{ $decisionSummary['recommended_tone'] }}" style="width: {{ $decisionSummary['readiness_score'] }}%;"></div></div>
                <div class="alert alert-light border mb-0 d-flex gap-2 align-items-start"><i data-lucide="lock-keyhole" class="mt-1"></i><div><strong>{{ __('messages.release_decisions.locked_evidence') }}</strong><br><span class="small text-muted">{{ __('messages.release_decisions.locked_evidence_copy') }}</span></div></div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>{{ __('messages.release_decisions.latest_decision_snapshot') }}</span>
                <span>{{ $latestDecisionSnapshot?->decided_at?->format('Y-m-d H:i') ?? __('messages.release_decisions.no_decision_snapshot') }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card aptoria-panel-card card-h-100">
            <div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1">{{ __('messages.release_decisions.signal_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_decisions.signal_copy') }}</p></div><span class="badge badge-soft-primary">6</span></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @php($sourceState = $decisionSummary['source_state'])
                    @php($signals = $decisionSummary['evidence_summary']['signals'] ?? [])
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="min-w-0"><span class="d-flex align-items-center gap-2"><i data-lucide="test-tube"></i>{{ __('messages.release_decisions.quick_test_state') }}</span><small class="text-muted d-block text-truncate">{{ $sourceState['quick_tests']['passed'] }} / {{ $sourceState['quick_tests']['total'] }} {{ __('messages.release_decisions.passed_short') }} · {{ $sourceState['quick_tests']['failed'] }} {{ __('messages.release_decisions.failed_short') }} · {{ $sourceState['quick_tests']['warning'] }} {{ __('messages.release_decisions.warning_short') }}</small></div>
                        <span class="badge badge-soft-{{ $signals['quick_tests']['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $signals['quick_tests']['label'] ?? __('messages.release_decisions.signal_states.missing') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="min-w-0"><span class="d-flex align-items-center gap-2"><i data-lucide="checklist"></i>{{ __('messages.release_decisions.assertion_state') }}</span><small class="text-muted d-block text-truncate">{{ __('messages.safe_scan.run') }} #{{ $sourceState['assertions']['latest_scan_id'] ?? '—' }} · {{ $sourceState['assertions']['expectation_failures'] }} {{ __('messages.release_decisions.expectation_failures_short') }} · {{ $sourceState['assertions']['failed'] }} {{ __('messages.release_decisions.failed_short') }}</small></div>
                        <span class="badge badge-soft-{{ $signals['assertions']['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $signals['assertions']['label'] ?? __('messages.release_decisions.signal_states.missing') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="min-w-0"><span class="d-flex align-items-center gap-2"><i data-lucide="layers"></i>{{ __('messages.release_decisions.batch_state') }}</span><small class="text-muted d-block text-truncate">{{ __('messages.endpoints.batch_test_evidence') }} #{{ $sourceState['batch']['latest_batch_id'] ?? '—' }} · {{ $sourceState['batch']['passed'] }} / {{ $sourceState['batch']['total'] }} {{ __('messages.release_decisions.passed_short') }} · {{ $sourceState['batch']['failed'] }} {{ __('messages.release_decisions.failed_short') }}</small></div>
                        <span class="badge badge-soft-{{ $signals['batch']['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $signals['batch']['label'] ?? __('messages.release_decisions.signal_states.missing') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="min-w-0"><span class="d-flex align-items-center gap-2"><i data-lucide="bug"></i>{{ __('messages.release_decisions.risk_state') }}</span><small class="text-muted d-block text-truncate">{{ $sourceState['risk']['critical_findings'] }} critical · {{ $sourceState['risk']['high_findings'] }} high · {{ $sourceState['risk']['missing_evidence'] }} missing evidence · {{ $sourceState['risk']['retest_needed'] }} retest</small></div>
                        <span class="badge badge-soft-{{ $signals['risk']['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $signals['risk']['label'] ?? __('messages.release_decisions.signal_states.missing') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="min-w-0"><span class="d-flex align-items-center gap-2"><i data-lucide="calendar-clock"></i>{{ __('messages.release_decisions.risk_acceptance_expiry_state') }}</span><small class="text-muted d-block text-truncate">{{ $sourceState['risk']['expiring_soon_risk_acceptances'] ?? 0 }} {{ __('messages.risk_acceptance.statuses.expiring_soon') }} · {{ $sourceState['risk']['expired_risk_acceptances'] ?? 0 }} {{ __('messages.risk_acceptance.statuses.expired') }} · {{ $sourceState['risk']['next_risk_acceptance_expiry_at'] ?? '—' }}</small></div>
                        <span class="badge badge-soft-{{ $signals['risk_acceptance_expiry']['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $signals['risk_acceptance_expiry']['label'] ?? __('messages.release_decisions.signal_states.missing') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="min-w-0"><span class="d-flex align-items-center gap-2"><i data-lucide="file-check-2"></i>{{ __('messages.release_decisions.contract_validation_state') }}</span><small class="text-muted d-block text-truncate">{{ $sourceState['contract_validation']['matched_operations'] ?? 0 }} / {{ $sourceState['contract_validation']['inventory_operations'] ?? 0 }} {{ __('messages.contract_validation.matched_operations') }} · {{ $sourceState['contract_validation']['undocumented'] ?? 0 }} {{ __('messages.contract_validation.undocumented_inventory_operations') }}</small></div>
                        <span class="badge badge-soft-{{ $signals['contract_validation']['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ $signals['contract_validation']['label'] ?? __('messages.release_decisions.signal_states.missing') }}</span>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_decisions.signal_footer') }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-7">
        <div class="card aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div><h5 class="card-title mb-1">{{ __('messages.release_readiness.check_table') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_readiness.check_table_copy') }}</p></div>
                <span class="badge badge-soft-primary">{{ count($checks) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table data-tables="release-readiness-checks" data-aptoria-search="false" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-checks-table">
                        <thead class="thead-sm text-uppercase fs-xxs"><tr><th data-priority="1">{{ __('messages.release_readiness.check') }}</th><th data-priority="2">{{ __('messages.common.status') }}</th><th data-priority="3">{{ __('messages.release_readiness.impact') }}</th></tr></thead>
                        <tbody>
                            @foreach ($checks as $check)
                                <tr>
                                    <td><div class="d-flex align-items-center gap-2 min-w-0"><span class="avatar avatar-sm rounded text-bg-{{ $check['tone'] }}"><span class="avatar-title"><i data-lucide="{{ $check['icon'] }}"></i></span></span><div class="min-w-0"><span class="fw-medium d-block text-truncate">{{ $check['label'] }}</span><small class="text-muted d-block text-truncate">{{ $check['hint'] }}</small></div></div></td>
                                    <td><span class="badge badge-soft-{{ $check['tone'] }} badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.levels.'.$check['level']) }}</span></td>
                                    <td><span class="text-muted aptoria-impact-cell">{{ $check['passed'] ? __('messages.release_readiness.no_action') : __('messages.release_readiness.action_required') }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_readiness.check_table_footer') }}</div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1">{{ __('messages.release_readiness.snapshots') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_readiness.snapshots_copy') }}</p></div><span class="badge badge-soft-secondary">{{ $runs->count() }}</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-snapshots-table">
                        <colgroup>
                            <col class="aptoria-col-snapshot">
                            <col class="aptoria-col-score">
                            <col class="aptoria-col-actions">
                        </colgroup>
                        <thead class="thead-sm text-uppercase fs-xxs"><tr><th data-priority="1">{{ __('messages.release_readiness.snapshot') }}</th><th data-priority="2">{{ __('messages.release_readiness.score') }}</th><th data-priority="1" class="text-end aptoria-actions-cell no-sort">{{ __('messages.common.actions') }}</th></tr></thead>
                        <tbody>
                            @forelse ($runs as $run)
                                <tr>
                                    <td>
                                        <div class="aptoria-snapshot-cell min-w-0">
                                            <span class="badge badge-soft-{{ $run->status_tone }} badge-label mb-1"><i class="ti ti-point-filled"></i>{{ $run->status_label }}</span>
                                            <small class="text-muted d-block text-truncate">{{ $run->generated_at?->format('Y-m-d H:i') ?? $run->created_at?->format('Y-m-d H:i') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="aptoria-score-cell min-w-0">
                                            <div class="d-flex align-items-center gap-2 min-w-0">
                                                <strong class="flex-shrink-0">{{ $run->score }}%</strong>
                                                <div class="progress progress-sm flex-grow-1 min-w-0"><div class="progress-bar bg-{{ $run->status_tone }}" style="width: {{ $run->score }}%;"></div></div>
                                            </div>
                                            <small class="text-muted d-block text-truncate">{{ $run->grade }}</small>
                                        </div>
                                    </td>
                                    <td class="text-end aptoria-actions-cell"><a href="{{ route('projects.release-readiness.show', [$project, $run]) }}" class="btn btn-light btn-icon btn-sm rounded-circle"><i data-lucide="eye"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">{{ __('messages.release_readiness.empty_snapshots') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_readiness.snapshots_footer') }}</div>
        </div>
    </div>
</div>


<div class="card aptoria-table-card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div><h5 class="card-title mb-1">{{ __('messages.release_decisions.snapshots') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_decisions.snapshots_copy') }}</p></div>
        <span class="badge badge-soft-secondary">{{ $decisionSnapshots->count() }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-release-decision-table">
                <thead class="thead-sm text-uppercase fs-xxs"><tr><th>{{ __('messages.release_decisions.decision') }}</th><th>{{ __('messages.release_readiness.score') }}</th><th>{{ __('messages.release_decisions.evidence_summary') }}</th><th class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th></tr></thead>
                <tbody>
                    @forelse ($decisionSnapshots as $snapshot)
                        @php($snapshotSummary = $snapshot->evidence_summary)
                        @php($snapshotReportVersion = $snapshot->reportVersions->sortByDesc('id')->first())
                        <tr>
                            <td><div class="min-w-0"><span class="badge badge-soft-{{ $snapshot->decision_tone }} badge-label mb-1"><i class="ti ti-point-filled"></i>{{ $snapshot->decision_label }}</span><small class="text-muted d-block text-truncate">{{ $snapshot->decided_at?->format('Y-m-d H:i') ?? $snapshot->created_at?->format('Y-m-d H:i') }}</small></div></td>
                            <td><div class="d-flex align-items-center gap-2"><strong>{{ $snapshotSummary['readiness']['score'] ?? $snapshot->releaseReadinessRun?->score ?? 0 }}%</strong><div class="progress progress-sm flex-grow-1"><div class="progress-bar bg-{{ $snapshot->decision_tone }}" style="width: {{ $snapshotSummary['readiness']['score'] ?? $snapshot->releaseReadinessRun?->score ?? 0 }}%;"></div></div></div></td>
                            <td><small class="text-muted text-truncate d-block">{{ __('messages.release_readiness.blockers') }}: {{ $snapshotSummary['readiness']['blockers'] ?? 0 }} · {{ __('messages.release_readiness.warnings') }}: {{ $snapshotSummary['readiness']['warnings'] ?? 0 }}</small></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="{{ route('projects.release-decisions.show', [$project, $snapshot]) }}" class="dropdown-item"><i data-lucide="clipboard-check" class="me-2"></i>{{ __('messages.release_decisions.view_snapshot') }}</a>
                                        <a href="{{ route('projects.release-decisions.report-preview', [$project, $snapshot]) }}" class="dropdown-item"><i data-lucide="file-check" class="me-2"></i>{{ __('messages.release_decisions.report.open_preview') }}</a>
                                        @if ($snapshotReportVersion)
                                            <a href="{{ route('projects.reports.show', [$project, $snapshotReportVersion]) }}" class="dropdown-item"><i data-lucide="git-fork" class="me-2"></i>{{ __('messages.reports.open_latest_report_version') }}</a>
                                        @endif
                                        <form method="POST" action="{{ route('projects.release-decisions.report-version.store', [$project, $snapshot]) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.reports.release_decision_confirm_title') }}" data-confirm-text="{{ __('messages.reports.release_decision_confirm_text') }}" data-confirm-button="{{ __('messages.reports.create_report_version') }}">@csrf<input type="hidden" name="confirm_report_version" value="1"><button type="submit" class="dropdown-item text-start w-100"><i data-lucide="file-plus" class="me-2"></i>{{ __('messages.reports.create_report_version') }}</button></form>
                                        <div class="dropdown-divider"></div>
                                        <a href="{{ route('projects.release-decisions.download', [$project, $snapshot, 'pdf']) }}" class="dropdown-item"><i data-lucide="file-type-pdf" class="me-2"></i>{{ __('messages.release_decisions.report.export_pdf') }}</a>
                                        <a href="{{ route('projects.release-decisions.download', [$project, $snapshot, 'html']) }}" class="dropdown-item"><i data-lucide="file-code-2" class="me-2"></i>{{ __('messages.release_decisions.report.export_html') }}</a>
                                        <a href="{{ route('projects.release-decisions.download', [$project, $snapshot, 'md']) }}" class="dropdown-item"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.release_decisions.report.export_md') }}</a>
                                        @if ($snapshot->release_readiness_run_id)
                                            <a href="{{ route('projects.release-readiness.show', [$project, $snapshot->release_readiness_run_id]) }}" class="dropdown-item"><i data-lucide="shield-chevron" class="me-2"></i>{{ __('messages.release_decisions.view_readiness_snapshot') }}</a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('messages.release_decisions.empty_snapshots') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_decisions.snapshots_footer') }}</div>
</div>

<div class="modal fade" id="releaseDecisionSnapshotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('projects.release-decisions.store', $project) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.release_decisions.confirm_title') }}" data-confirm-text="{{ __('messages.release_decisions.confirm_text') }}" data-confirm-button="{{ __('messages.release_decisions.save_decision_snapshot') }}" data-aptoria-form-scope="release_decisions" data-aptoria-form-plugin>
                @csrf
                <div class="modal-header"><h5 class="modal-title"><i data-lucide="clipboard-check" class="me-2"></i>{{ __('messages.release_decisions.save_decision_snapshot') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-2 align-items-start"><i data-lucide="info" class="mt-1"></i><div><strong>{{ __('messages.release_decisions.snapshot_note') }}</strong><br><span class="small">{{ __('messages.release_decisions.snapshot_note_copy') }}</span></div></div>
                    <div class="mb-3"><label class="form-label">{{ __('messages.release_decisions.decision') }}</label><select name="decision" class="form-select" required><option value="ready" @selected($decisionSummary['recommended_decision'] === 'ready')>{{ __('messages.release_decisions.decisions.ready') }}</option><option value="needs_review" @selected($decisionSummary['recommended_decision'] === 'needs_review')>{{ __('messages.release_decisions.decisions.needs_review') }}</option><option value="blocked" @selected($decisionSummary['recommended_decision'] === 'blocked')>{{ __('messages.release_decisions.decisions.blocked') }}</option></select><div class="form-text">{{ __('messages.release_decisions.decision_help') }}</div></div>
                    <div class="mb-3"><label class="form-label">{{ __('messages.release_decisions.decision_note') }}</label><textarea name="decision_note" class="form-control" rows="4" placeholder="{{ __('messages.form_plugin.placeholders.release_decisions.decision_note') }}"></textarea></div>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="confirm_decision" value="1" required><span class="form-check-label">{{ __('messages.release_decisions.confirm_checkbox') }}</span></label>
                </div>
                <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.release_decisions.save_decision_snapshot') }}</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="releaseReadinessEvaluateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('projects.release-readiness.store', $project) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.release_readiness.confirm_title') }}" data-confirm-text="{{ __('messages.release_readiness.confirm_text') }}" data-confirm-button="{{ __('messages.release_readiness.evaluate') }}" data-aptoria-form-scope="release_readiness" data-aptoria-form-plugin>
                @csrf
                <div class="modal-header"><h5 class="modal-title"><i data-lucide="shield-chevron" class="me-2"></i>{{ __('messages.release_readiness.evaluate') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-2 align-items-start"><i data-lucide="info" class="mt-1"></i><div><strong>{{ __('messages.release_readiness.snapshot_note') }}</strong><br><span class="small">{{ __('messages.release_readiness.snapshot_note_copy') }}</span></div></div>
                    <div class="mb-3"><label class="form-label">{{ __('messages.release_readiness.decision_note') }}</label><textarea name="decision_note" class="form-control" rows="4" placeholder="{{ __('messages.form_plugin.placeholders.release_readiness.decision_note') }}"></textarea></div>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="confirm_evaluation" value="1" required><span class="form-check-label">{{ __('messages.release_readiness.confirm_checkbox') }}</span></label>
                </div>
                <div class="modal-footer aptoria-card-footer-subtle"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.release_readiness.save_snapshot') }}</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
