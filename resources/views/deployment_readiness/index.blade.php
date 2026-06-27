@extends('layouts.app')
@section('title', 'Deployment readiness')
@section('page_title', 'Deployment readiness')
@section('page_actions')
    <a href="{{ route('deployment-readiness.json') }}" class="btn btn-light" target="_blank"><i data-lucide="file-json" class="me-1"></i>JSON</a>
    <a href="{{ route('runtime-diagnostics.index') }}" class="btn btn-light"><i data-lucide="stethoscope" class="me-1"></i>Runtime diagnostics</a>
    <a href="{{ route('program-settings.edit') }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
@endsection

@section('content')
@php
    $status = $readiness['status'] ?? 'warning';
    $tone = $status === 'ok' ? 'success' : ($status === 'error' ? 'danger' : 'warning');
    $score = (int) ($readiness['score'] ?? 0);
@endphp
<div class="row g-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light">
                <h5 class="card-title mb-0"><i data-lucide="rocket" class="me-1"></i>Deployment readiness</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="avatar avatar-lg rounded text-bg-{{ $tone }}"><span class="avatar-title fs-3">{{ $score }}</span></span>
                    <div>
                        <span class="badge badge-soft-{{ $tone }} badge-label mb-1">{{ strtoupper($status) }}</span>
                        <div class="text-muted small">Role: <code>{{ $readiness['role'] ?? '—' }}</code></div>
                        <div class="text-muted small text-break">APP_URL: <code>{{ $readiness['app_url'] ?? '—' }}</code></div>
                    </div>
                </div>
                <div class="progress mb-3" style="height: 10px;"><div class="progress-bar" style="width: {{ $score }}%"></div></div>
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted">Errors</dt><dd class="col-6">{{ $readiness['summary']['errors'] ?? 0 }}</dd>
                    <dt class="col-6 text-muted">Warnings</dt><dd class="col-6">{{ $readiness['summary']['warnings'] ?? 0 }}</dd>
                    <dt class="col-6 text-muted">Passed</dt><dd class="col-6">{{ $readiness['summary']['passed'] ?? 0 }}</dd>
                    <dt class="col-6 text-muted">Release blocked</dt><dd class="col-6">{{ ($readiness['release_blocked'] ?? false) ? 'yes' : 'no' }}</dd>
                </dl>
            </div>
            <div class="card-footer aptoria-card-footer-subtle small text-muted">
                CLI: <code>php artisan aptoria:deployment-preflight --strict</code>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">Preflight decision</h5>
                    <p class="text-muted mb-0 small">Use this before publishing a ZIP or exposing aptoria.dev/demo/admin/license subdomains.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge badge-soft-danger badge-label">Errors: {{ $readiness['summary']['errors'] ?? 0 }}</span>
                    <span class="badge badge-soft-warning badge-label">Warnings: {{ $readiness['summary']['warnings'] ?? 0 }}</span>
                    <span class="badge badge-soft-success badge-label">Passed: {{ $readiness['summary']['passed'] ?? 0 }}</span>
                </div>
            </div>
            <div class="card-body">
                @if(! empty($readiness['blocking_checks']))
                    <div class="alert alert-danger">
                        <strong>Release is blocked.</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($readiness['blocking_checks'] as $check)
                                <li><code>{{ $check['id'] }}</code> — {{ $check['remediation'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @elseif(! empty($readiness['warning_checks']))
                    <div class="alert alert-warning">
                        <strong>Release is possible, but warnings need review.</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($readiness['warning_checks'] as $check)
                                <li><code>{{ $check['id'] }}</code> — {{ $check['remediation'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="alert alert-success"><strong>Ready.</strong> No error or warning level deployment blockers were found.</div>
                @endif

                <div class="row g-2">
                    @foreach(($readiness['next_steps'] ?? []) as $nextStep)
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 d-flex gap-2 align-items-start">
                                <span class="avatar avatar-xs rounded text-bg-light"><span class="avatar-title"><i data-lucide="check-circle-2"></i></span></span>
                                <span class="small text-muted">{{ $nextStep }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header border-light">
        <h5 class="card-title mb-0">Readiness stages</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Stage</th><th>Status</th><th>Passed</th><th>Warnings</th><th>Errors</th></tr></thead>
                <tbody>
                    @foreach(($readiness['stages'] ?? []) as $stage)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $stage['title'] }}</div>
                                <code class="small">{{ $stage['id'] }}</code>
                            </td>
                            <td><span class="badge badge-soft-{{ $stage['tone'] ?? 'secondary' }} badge-label">{{ $stage['status'] ?? 'unknown' }}</span></td>
                            <td>{{ $stage['summary']['passed'] ?? 0 }}</td>
                            <td>{{ $stage['summary']['warnings'] ?? 0 }}</td>
                            <td>{{ $stage['summary']['errors'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">All checks</h5>
            <p class="text-muted mb-0 small">Machine-readable form is available from the JSON action.</p>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Check</th><th>Status</th><th>Actual</th><th>Fix</th></tr></thead>
                <tbody>
                    @foreach(($readiness['checks'] ?? []) as $check)
                        @php
                            $checkTone = ($check['status'] ?? '') === 'pass' || ($check['status'] ?? '') === 'info' ? 'success' : (($check['status'] ?? '') === 'error' ? 'danger' : 'warning');
                        @endphp
                        <tr>
                            <td><div class="fw-semibold">{{ $check['message'] }}</div><code class="small">{{ $check['id'] }}</code></td>
                            <td><span class="badge badge-soft-{{ $checkTone }} badge-label">{{ $check['status'] }}</span></td>
                            <td><code class="small text-break">{{ $check['actual'] }}</code></td>
                            <td class="small text-muted">{{ $check['remediation'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
