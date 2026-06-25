@extends('layouts.auth')
@section('title', __('messages.client_portal.public_title') . ' · ' . $project->name)
@section('body_class', 'auth-bg d-flex align-items-start min-vh-100 py-5')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-11">
        @if (session('status'))
            <div class="alert alert-success d-flex gap-2 align-items-center"><i data-lucide="check-circle"></i><span>{{ session('status') }}</span></div>
        @endif
        <div class="card border-0 shadow-lg overflow-hidden mb-3">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo aptoria-client-portal-logo">
                        <div class="min-w-0">
                            <span class="badge badge-soft-primary badge-label mb-2"><i class="ti ti-point-filled"></i>{{ __('messages.client_portal.external_review') }}</span>
                            <h1 class="h3 mb-1">{{ $project->name }}</h1>
                            <p class="text-muted mb-0">{{ __('messages.client_portal.public_copy') }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.client_portal.access_name') }}</div>
                        <h5 class="mb-1">{{ $access->name }}</h5>
                        <span class="badge badge-soft-{{ $access->status_tone }}">{{ $access->status_label }}</span>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-between flex-wrap gap-2 text-muted">
                <span>{{ __('messages.client_portal.role') }}: {{ $access->role_label }}</span>
                <span>{{ __('messages.client_portal.expires_at') }}: {{ $access->expires_at?->format('Y-m-d') ?? __('messages.client_portal.never_expires') }}</span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                @if ($access->allows('decision_package'))
                    <div class="card aptoria-panel-card mb-3">
                        <div class="card-header border-light justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1"><i data-lucide="package-check" class="me-2 text-primary"></i>{{ __('messages.client_portal.decision_packages') }}</h5>
                                <p class="text-muted mb-0 small">{{ __('messages.client_portal.decision_packages_copy') }}</p>
                            </div>
                            <span class="badge badge-soft-primary">{{ $decisionPackages->count() }}</span>
                        </div>
                        <div class="card-body">
                            @forelse ($decisionPackages as $package)
                                @php($summary = $handoffService->summary($package))
                                <div class="border rounded-3 p-3 mb-3 aptoria-workflow-section">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                        <div class="min-w-0">
                                            <span class="badge badge-soft-success badge-label mb-2"><i class="ti ti-point-filled"></i>{{ __('messages.client_portal.approved_decision_package') }}</span>
                                            <h5 class="mb-1 text-truncate">{{ $summary['gate_title'] ?: $package->title }}</h5>
                                            <p class="text-muted small mb-0">{{ __('messages.client_portal.decision_package_checksum') }} <code>{{ $summary['checksum'] ? \Illuminate\Support\Str::limit($summary['checksum'], 16, '') : '—' }}</code></p>
                                        </div>
                                        <span class="badge badge-soft-{{ ($summary['final_decision'] ?? '') === 'no_go' ? 'danger' : (($summary['final_decision'] ?? '') === 'conditional_go' ? 'warning' : 'success') }} fs-6">{{ $summary['final_decision_label'] ?: __('messages.common.not_available') }}</span>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6 col-lg-3"><div class="border rounded p-2 h-100"><small class="text-muted d-block">{{ __('messages.release_readiness.score') }}</small><strong>{{ $summary['score'] ?? '—' }}/100</strong><span class="text-muted small ms-1">{{ $summary['grade'] ?: '' }}</span></div></div>
                                        <div class="col-6 col-lg-3"><div class="border rounded p-2 h-100"><small class="text-muted d-block">{{ __('messages.release_gates.metrics.blockers') }}</small><strong>{{ $summary['blockers'] }}</strong></div></div>
                                        <div class="col-6 col-lg-3"><div class="border rounded p-2 h-100"><small class="text-muted d-block">{{ __('messages.evidence.verified_metric') }}</small><strong>{{ $summary['verified_evidence'] }}</strong></div></div>
                                        <div class="col-6 col-lg-3"><div class="border rounded p-2 h-100"><small class="text-muted d-block">{{ __('messages.native_tests.runs') }}</small><strong>{{ $summary['test_runs'] }}</strong></div></div>
                                    </div>
                                    @if (!empty($summary['decision_note']))
                                        <div class="alert alert-light border small mb-3"><i data-lucide="file-text" class="me-1"></i>{{ $summary['decision_note'] }}</div>
                                    @endif
                                    <div class="btn-group btn-group-sm flex-wrap">
                                        <a class="btn btn-light" href="{{ route('client-portal.reports.download', [$access->token, $package, 'html']) }}"><i data-lucide="file-code-2" class="me-1"></i>HTML</a>
                                        <a class="btn btn-light" href="{{ route('client-portal.reports.download', [$access->token, $package, 'pdf']) }}"><i data-lucide="file-type-pdf" class="me-1"></i>PDF</a>
                                        <a class="btn btn-light" href="{{ route('client-portal.reports.download', [$access->token, $package, 'json']) }}"><i data-lucide="braces" class="me-1"></i>JSON</a>
                                        <a class="btn btn-primary" href="{{ route('client-portal.reports.download', [$access->token, $package, 'zip']) }}"><i data-lucide="archive" class="me-1"></i>ZIP</a>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">{{ __('messages.client_portal.no_decision_packages') }}</div>
                            @endforelse
                        </div>
                        <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.client_portal.decision_packages_footer') }}</div>
                    </div>
                @endif

                @if ($access->allows('reports'))
                    <div class="card aptoria-panel-card mb-3">
                        <div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1">{{ __('messages.client_portal.shared_reports') }}</h5><p class="text-muted mb-0 small">{{ __('messages.client_portal.shared_reports_copy') }}</p></div><span class="badge badge-soft-primary">{{ $reports->count() }}</span></div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse ($reports as $report)
                                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div class="min-w-0">
                                            <div class="fw-medium text-truncate">{{ $report->title }}</div>
                                            <span class="badge badge-soft-success badge-label mt-1"><i class="ti ti-point-filled"></i>{{ __('messages.reports.statuses.approved') }}</span>
                                            <small class="text-muted d-block mt-1">{{ $report->type_label }} · {{ $report->generated_at?->format('Y-m-d H:i') ?? $report->created_at?->format('Y-m-d H:i') }} · {{ __('messages.reports.checksum') }} {{ $report->checksum ? \Illuminate\Support\Str::limit($report->checksum, 10, '') : '—' }}</small>
                                        </div>
                                        <div class="btn-group btn-group-sm flex-shrink-0">
                                            <a class="btn btn-light" href="{{ route('client-portal.reports.download', [$access->token, $report, 'html']) }}"><i data-lucide="file-code-2" class="me-1"></i>HTML</a>
                                            <a class="btn btn-light" href="{{ route('client-portal.reports.download', [$access->token, $report, 'md']) }}"><i data-lucide="markdown" class="me-1"></i>MD</a>
                                            <a class="btn btn-light" href="{{ route('client-portal.reports.download', [$access->token, $report, 'pdf']) }}"><i data-lucide="file-type-pdf" class="me-1"></i>PDF</a>
                                            <a class="btn btn-light" href="{{ route('client-portal.reports.download', [$access->token, $report, 'json']) }}"><i data-lucide="file-json" class="me-1"></i>JSON</a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-4">{{ __('messages.client_portal.no_reports') }}</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.client_portal.public_reports_footer') }} {{ __('messages.client_portal.public_approved_only_footer') }}</div>
                    </div>
                @endif

                @if ($access->allows('findings'))
                    <div class="card aptoria-panel-card mb-3">
                        <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.client_portal.shared_findings') }}</h5></div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse ($findings as $finding)
                                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3"><div class="min-w-0"><div class="fw-medium text-truncate">{{ $finding->title }}</div><small class="text-muted">{{ $finding->severity_label }} · {{ $finding->status_label }}</small></div><span class="badge badge-soft-{{ $finding->severity_tone ?? 'warning' }}">{{ $finding->severity_label }}</span></div>
                                @empty
                                    <div class="text-center text-muted py-4">{{ __('messages.client_portal.no_findings') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-xl-4">
                @if ($access->allows('readiness'))
                    <div class="card aptoria-panel-card mb-3">
                        <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.release_readiness.title') }}</h5></div>
                        <div class="card-body text-center">
                            <div class="display-5 fw-light mb-1">{{ $latestReadiness?->score ?? 0 }}%</div>
                            <span class="badge badge-soft-{{ $latestReadiness?->status_tone ?? 'secondary' }}">{{ $latestReadiness?->status_label ?? __('messages.common.not_available') }}</span>
                            <p class="text-muted small mt-3 mb-0">{{ __('messages.client_portal.readiness_public_hint') }}</p>
                        </div>
                    </div>
                @endif

                @if ($access->allows('evidence'))
                    <div class="card aptoria-panel-card mb-3">
                        <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.client_portal.shared_evidence') }}</h5></div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse ($evidence as $item)
                                    <div class="list-group-item"><div class="fw-medium text-truncate">{{ $item->title }}</div><small class="text-muted">{{ $item->type_label }} · {{ $item->captured_at?->format('Y-m-d') ?? $item->created_at?->format('Y-m-d') }}</small></div>
                                @empty
                                    <div class="text-center text-muted py-4">{{ __('messages.client_portal.no_evidence') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                @if ($access->acknowledge_required)
                    <div class="card aptoria-panel-card mb-3">
                        <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="badge-check" class="me-1"></i>{{ __('messages.client_portal.acknowledgement') }}</h5></div>
                        <div class="card-body">
                            @if ($access->acknowledged_at)
                                <div class="alert alert-success mb-3"><i data-lucide="check-circle" class="me-1"></i>{{ __('messages.client_portal.already_acknowledged', ['name' => $access->acknowledged_by_name]) }}</div>
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                    <span class="text-muted small">{{ __('messages.client_portal.ack_decision') }}</span>
                                    <span class="badge badge-soft-{{ $access->acknowledgement_decision_tone }}">{{ $access->acknowledgement_decision_label }}</span>
                                </div>
                                @if ($access->acknowledgement_comment)
                                    <div class="alert alert-light border mb-0">{{ $access->acknowledgement_comment }}</div>
                                @endif
                            @else
                                <form method="POST" action="{{ route('client-portal.acknowledge', $access->token) }}" data-aptoria-form-scope="client_portal" data-aptoria-form-plugin>
                                    @csrf
                                    <div class="mb-3"><label class="form-label">{{ __('messages.client_portal.ack_name') }}</label><input class="form-control" name="acknowledged_by_name" required placeholder="{{ __('messages.form_plugin.placeholders.client_portal.ack_name') }}"><div class="form-text">{{ __('messages.client_portal.ack_name_help') }}</div></div>
                                    <div class="mb-3"><label class="form-label">{{ __('messages.client_portal.ack_email') }}</label><input class="form-control" type="email" name="acknowledged_by_email" placeholder="{{ __('messages.form_plugin.placeholders.client_portal.ack_email') }}"><div class="form-text">{{ __('messages.client_portal.ack_email_help') }}</div></div>
                                    <div class="mb-3"><label class="form-label">{{ __('messages.client_portal.ack_decision') }}</label><select class="form-select" name="decision_status" required>@foreach(\App\Models\ClientPortalAcknowledgement::DECISIONS as $decision)<option value="{{ $decision }}">{{ __('messages.client_portal.ack_decisions.'.$decision) }}</option>@endforeach</select><div class="form-text">{{ __('messages.client_portal.ack_decision_help') }}</div></div>
                                    <div class="mb-3"><label class="form-label">{{ __('messages.client_portal.ack_comment') }}</label><textarea class="form-control" name="comment" rows="3" placeholder="{{ __('messages.form_plugin.placeholders.client_portal.ack_comment') }}"></textarea><div class="form-text">{{ __('messages.client_portal.ack_comment_help') }}</div></div>
                                    <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="acknowledge_terms" value="1" required><span class="form-check-label">{{ __('messages.client_portal.ack_terms') }}</span></label>
                                    <button class="btn btn-primary w-100" type="submit"><i data-lucide="badge-check" class="me-1"></i>{{ __('messages.client_portal.acknowledge') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="card aptoria-panel-card">
                        <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.client_portal.acknowledgement_history') }}</h5></div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse ($access->acknowledgements as $acknowledgement)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="min-w-0"><div class="fw-medium text-truncate">{{ $acknowledgement->acknowledged_by_name }}</div><small class="text-muted">{{ $acknowledgement->acknowledged_at?->format('Y-m-d H:i') }}</small></div>
                                            <span class="badge badge-soft-{{ $acknowledgement->decision_tone }}">{{ $acknowledgement->decision_label }}</span>
                                        </div>
                                        @if ($acknowledgement->comment)
                                            <small class="text-muted d-block mt-2">{{ $acknowledgement->comment }}</small>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-4">{{ __('messages.client_portal.no_acknowledgements') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
