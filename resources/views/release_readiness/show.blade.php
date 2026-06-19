@extends('layouts.app')
@section('title', __('messages.release_readiness.snapshot_detail'))
@section('page_title', __('messages.release_readiness.snapshot_detail'))
@section('page_actions')
    <a href="{{ route('projects.release-readiness.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.release_readiness.back_to_readiness') }}</a>
@endsection
@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100 aptoria-panel-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <span class="badge badge-soft-{{ $run->status_tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $run->status_label }}</span>
                        <h2 class="fw-normal mb-2">{{ __('messages.release_readiness.snapshot') }} #{{ $run->id }}</h2>
                        <p class="text-muted mb-0">{{ $summary['headline'] ?? __('messages.release_readiness.snapshot_detail_copy') }}</p>
                    </div>
                    <div class="aptoria-project-score text-center"><div class="aptoria-score-ring" style="--aptoria-score: {{ $run->score }}%;">{{ $run->score }}%</div><small class="text-muted">{{ __('messages.release_readiness.score') }} · {{ $run->grade }}</small></div>
                </div>
                @if ($run->decision_note)
                    <div class="alert alert-light border mt-4 mb-0"><strong>{{ __('messages.release_readiness.decision_note') }}</strong><br>{{ $run->decision_note }}</div>
                @endif
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex flex-wrap justify-content-between gap-2"><span>{{ __('messages.release_readiness.generated_by') }}: {{ $run->generatedBy?->name ?? '—' }}</span><span>{{ $run->generated_at?->format('Y-m-d H:i') ?? $run->created_at?->format('Y-m-d H:i') }}</span></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card"><div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.release_readiness.gate_summary') }}</h5></div><div class="card-body p-0"><div class="list-group list-group-flush"><div class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('messages.release_readiness.blockers') }}</span><strong class="text-danger">{{ $run->blocker_count }}</strong></div><div class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('messages.release_readiness.warnings') }}</span><strong class="text-warning">{{ $run->warning_count }}</strong></div><div class="list-group-item d-flex justify-content-between"><span class="text-muted">{{ __('messages.release_readiness.passed_checks') }}</span><strong>{{ $run->passed_check_count }} / {{ $run->check_count }}</strong></div></div></div><div class="card-footer aptoria-card-footer-subtle text-center text-muted">{{ __('messages.release_readiness.snapshot_locked') }}</div></div>
    </div>
</div>
@include('release_readiness.partials.retest-closure', ['project' => $project, 'retestClosure' => $run->retest_closure])
@include('release_readiness.partials.risk-acceptance-expiry', ['project' => $project, 'riskAcceptance' => $riskAcceptance ?? $run->risk_acceptance])
@include('release_readiness.partials.contract-validation', ['project' => $project, 'contractValidation' => $contractValidation ?? $run->contract_validation])
@php
    $externalImportSnapshot = [];
    if (($run->metrics['external_import_has_run'] ?? false)) {
        $externalImportSnapshot = [
            'has_run' => true,
            'latest_run_id' => $run->metrics['external_import_latest_run_id'] ?? null,
            'status' => $run->metrics['external_import_status'] ?? 'missing',
            'status_label' => __('messages.import_center.statuses.'.($run->metrics['external_import_status'] ?? 'missing')),
            'status_tone' => ($run->metrics['external_import_status'] ?? '') === 'applied' ? 'success' : 'warning',
            'item_count' => $run->metrics['external_import_items'] ?? 0,
            'endpoint_count' => $run->metrics['external_import_endpoints'] ?? 0,
            'assertion_count' => $run->metrics['external_import_assertions'] ?? 0,
            'finding_count' => $run->metrics['external_import_findings'] ?? 0,
            'evidence_count' => $run->metrics['external_import_evidence'] ?? 0,
            'blocker_count' => $run->metrics['external_import_blockers'] ?? 0,
        ];
    }
@endphp
@include('release_readiness.partials.external-import', ['project' => $project, 'externalImport' => $externalImportSnapshot])

<div class="card aptoria-table-card aptoria-panel-card mt-3">
    <div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1">{{ __('messages.release_readiness.check_table') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_readiness.snapshot_detail_copy') }}</p></div><span class="badge badge-soft-primary">{{ count($checks) }}</span></div>
    <div class="card-body p-0"><div class="table-responsive"><table data-tables="release-readiness-snapshot-checks" data-aptoria-search="false" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-checks-table"><thead class="thead-sm text-uppercase fs-xxs"><tr><th data-priority="1">{{ __('messages.release_readiness.check') }}</th><th data-priority="2">{{ __('messages.common.status') }}</th><th data-priority="3">{{ __('messages.release_readiness.impact') }}</th></tr></thead><tbody>@foreach($checks as $check)<tr><td><div class="d-flex align-items-center gap-2 min-w-0"><span class="avatar avatar-sm rounded text-bg-{{ $check['tone'] ?? 'secondary' }}"><span class="avatar-title"><i data-lucide="{{ $check['icon'] ?? 'check-circle' }}"></i></span></span><div class="min-w-0"><span class="fw-medium d-block text-truncate">{{ $check['label'] ?? '—' }}</span><small class="text-muted d-block text-truncate">{{ $check['hint'] ?? '' }}</small></div></div></td><td><span class="badge badge-soft-{{ $check['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.levels.'.($check['level'] ?? 'warning')) }}</span></td><td><span class="text-muted aptoria-impact-cell">{{ ($check['passed'] ?? false) ? __('messages.release_readiness.no_action') : __('messages.release_readiness.action_required') }}</span></td></tr>@endforeach</tbody></table></div></div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_readiness.check_table_footer') }}</div>
</div>
@endsection
