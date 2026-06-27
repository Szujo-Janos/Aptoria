@extends('layouts.app')
@section('title', 'Runtime diagnostics')
@section('page_title', 'Runtime diagnostics')
@section('page_actions')
    <a href="{{ route('runtime-diagnostics.json') }}" class="btn btn-light" target="_blank"><i data-lucide="file-json" class="me-1"></i>JSON</a>
    <a href="{{ route('deployment-readiness.index') }}" class="btn btn-light"><i data-lucide="rocket" class="me-1"></i>Deployment readiness</a>
    <a href="{{ route('program-settings.edit') }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
@endsection

@section('content')
@php
    $status = $diagnostics['status'] ?? 'warning';
    $tone = $status === 'ok' ? 'success' : ($status === 'error' ? 'danger' : 'warning');
@endphp
<div class="row g-3">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header border-light">
                <h5 class="card-title mb-0"><i data-lucide="stethoscope" class="me-1"></i>Hosting profile status</h5>
            </div>
            <div class="card-body">
                <span class="badge badge-soft-{{ $tone }} badge-label mb-3">{{ strtoupper($status) }}</span>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Version</dt><dd class="col-7">{{ $diagnostics['version'] ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Role</dt><dd class="col-7"><code>{{ $diagnostics['role'] ?? '—' }}</code></dd>
                    <dt class="col-5 text-muted">APP_ENV</dt><dd class="col-7"><code>{{ $diagnostics['app_env'] ?? '—' }}</code></dd>
                    <dt class="col-5 text-muted">APP_URL</dt><dd class="col-7 text-break"><code>{{ $diagnostics['app_url'] ?? '—' }}</code></dd>
                </dl>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted small">
                Use <code>php artisan aptoria:hosting-diagnostics --strict</code> before deployment.
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">Validation checks</h5>
                    <p class="text-muted mb-0 small">Errors block release; warnings should be reviewed before public hosting.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge badge-soft-danger badge-label">Errors: {{ $diagnostics['summary']['errors'] ?? 0 }}</span>
                    <span class="badge badge-soft-warning badge-label">Warnings: {{ $diagnostics['summary']['warnings'] ?? 0 }}</span>
                    <span class="badge badge-soft-success badge-label">Passed: {{ $diagnostics['summary']['passed'] ?? 0 }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead><tr><th>Check</th><th>Status</th><th>Actual</th><th>Fix</th></tr></thead>
                        <tbody>
                            @foreach (($diagnostics['checks'] ?? []) as $check)
                                @php
                                    $checkTone = ($check['passed'] ?? false) ? 'success' : (($check['severity'] ?? 'warning') === 'error' ? 'danger' : 'warning');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $check['message'] ?? $check['id'] }}</div>
                                        <code class="small">{{ $check['id'] ?? 'check' }}</code>
                                    </td>
                                    <td><span class="badge badge-soft-{{ $checkTone }} badge-label">{{ $check['status'] ?? 'unknown' }}</span></td>
                                    <td><code class="small text-break">{{ $check['actual'] ?? '—' }}</code></td>
                                    <td class="small text-muted">{{ $check['remediation'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
