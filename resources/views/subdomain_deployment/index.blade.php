@extends('layouts.app')
@section('title', 'Subdomain deployment')
@section('page_title', 'Subdomain deployment')
@section('page_actions')
    <a href="{{ route('subdomain-deployment.json') }}" class="btn btn-light" target="_blank"><i data-lucide="file-json" class="me-1"></i>JSON</a>
    <a href="{{ route('deployment-readiness.index') }}" class="btn btn-light"><i data-lucide="rocket" class="me-1"></i>Deployment readiness</a>
    <a href="{{ route('program-settings.edit') }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
@endsection

@section('content')
@php
    $latest = $dashboard['latest'] ?? null;
    $summary = $dashboard['summary'] ?? ['status' => 'missing', 'total' => 0, 'passed' => 0, 'failed' => 0];
    $status = $summary['status'] ?? 'missing';
    $tone = $status === 'passed' ? 'success' : ($status === 'failed' ? 'danger' : 'warning');
    $freshness = $dashboard['freshness'] ?? ['status' => 'missing', 'message' => 'No imported smoke result was found.'];
    $freshTone = ($freshness['status'] ?? '') === 'fresh' ? 'success' : (($freshness['status'] ?? '') === 'stale' ? 'warning' : 'secondary');
@endphp

@if (session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle-2" class="me-1"></i>{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger"><i data-lucide="triangle-alert" class="me-1"></i>{{ session('error') }}</div>
@endif

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light">
                <h5 class="card-title mb-0"><i data-lucide="network" class="me-1"></i>Deployment boundary status</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="avatar avatar-lg rounded text-bg-{{ $tone }}"><span class="avatar-title fs-4">{{ strtoupper(substr((string) $status, 0, 2)) }}</span></span>
                    <div>
                        <span class="badge badge-soft-{{ $tone }} badge-label mb-1">{{ strtoupper((string) $status) }}</span>
                        <div class="text-muted small">Latest smoke import: <code>{{ $latest['generated_at'] ?? 'not imported' }}</code></div>
                        <div class="text-muted small">Version: <code>{{ $latest['version'] ?? config('aptoria.version') }}</code></div>
                    </div>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted">Total checks</dt><dd class="col-6">{{ $summary['total'] ?? 0 }}</dd>
                    <dt class="col-6 text-muted">Passed</dt><dd class="col-6 text-success fw-semibold">{{ $summary['passed'] ?? 0 }}</dd>
                    <dt class="col-6 text-muted">Failed</dt><dd class="col-6 text-danger fw-semibold">{{ $summary['failed'] ?? 0 }}</dd>
                    <dt class="col-6 text-muted">Freshness</dt><dd class="col-6"><span class="badge badge-soft-{{ $freshTone }} badge-label">{{ $freshness['status'] ?? 'missing' }}</span></dd>
                </dl>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted small">
                {{ $freshness['message'] ?? 'Run smoke-subdomains and import the generated JSON before deploy approval.' }}
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="upload" class="me-1"></i>Import smoke result</h5>
                    <p class="text-muted mb-0 small">Use the JSON output from <code>scripts/smoke-subdomains.ps1</code> or <code>scripts/smoke-subdomains.sh</code>.</p>
                </div>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('subdomain-deployment.import') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-lg-6">
                        <label class="form-label">JSON file</label>
                        <input type="file" name="smoke_result_file" class="form-control" accept=".json,application/json,text/plain">
                        <div class="form-text">PowerShell: <code>.\scripts\smoke-subdomains.ps1 -JsonOutput smoke-result.json</code></div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Paste JSON</label>
                        <textarea name="smoke_result_json" class="form-control" rows="4" placeholder='{"smoke_result_format":"aptoria-subdomain-smoke-v1", ...}'>{{ old('smoke_result_json') }}</textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i data-lucide="file-up" class="me-1"></i>Import result</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    @foreach(($dashboard['domains'] ?? []) as $key => $domain)
        @php
            $domainStatus = $domain['status'] ?? 'missing';
            $domainTone = $domainStatus === 'passed' ? 'success' : ($domainStatus === 'failed' ? 'danger' : 'warning');
            $icon = match($key) {
                'landing' => 'monitor-smartphone',
                'demo' => 'flask-conical',
                'admin' => 'shield-check',
                'license' => 'key-round',
                default => 'network',
            };
        @endphp
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="{{ $icon }}"></i></span></span>
                        <span class="badge badge-soft-{{ $domainTone }} badge-label">{{ strtoupper($domainStatus) }}</span>
                    </div>
                    <h5 class="mb-1">{{ $domain['title'] ?? $key }}</h5>
                    <div class="small text-muted text-break mb-3"><code>{{ $domain['url'] ?? '—' }}</code></div>
                    <div class="d-flex gap-3 small">
                        <span class="text-success">{{ $domain['passed'] ?? 0 }} passed</span>
                        <span class="text-danger">{{ $domain['failed'] ?? 0 }} failed</span>
                    </div>
                    @if (($domain['total'] ?? 0) === 0)
                        <div class="small text-muted mt-2">Expected: {{ implode(', ', $domain['expected'] ?? []) }}</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mt-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">Latest smoke checks</h5>
            <p class="text-muted mb-0 small">These checks prove that each subdomain exposes only its intended surface.</p>
        </div>
        @if ($latest)
            <a href="{{ route('subdomain-deployment.result', $latest['id']) }}" target="_blank" class="btn btn-sm btn-light"><i data-lucide="file-json" class="me-1"></i>Latest raw JSON</a>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Domain</th><th>Check</th><th>Expected</th><th>Actual</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse(($latest['checks'] ?? []) as $check)
                        @php $checkTone = ($check['passed'] ?? false) ? 'success' : 'danger'; @endphp
                        <tr>
                            <td><code>{{ $check['domain'] ?? 'unknown' }}</code></td>
                            <td>
                                <div class="fw-semibold">{{ $check['name'] ?? 'check' }}</div>
                                <code class="small text-break">{{ $check['url'] ?? '' }}</code>
                            </td>
                            <td><code class="small">{{ implode(',', $check['expected'] ?? []) }}</code></td>
                            <td><code class="small">{{ $check['status_code'] ?? ($check['error'] ?? '—') }}</code></td>
                            <td><span class="badge badge-soft-{{ $checkTone }} badge-label">{{ $check['status'] ?? 'unknown' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No smoke result has been imported yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header border-light"><h5 class="card-title mb-0">Import history</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Imported</th><th>Generated</th><th>Source</th><th>Result</th><th>Checks</th><th></th></tr></thead>
                <tbody>
                    @forelse(($dashboard['history'] ?? []) as $item)
                        @php $itemTone = ($item['status'] ?? '') === 'passed' ? 'success' : 'danger'; @endphp
                        <tr>
                            <td><code>{{ $item['imported_at'] ?? '—' }}</code></td>
                            <td><code>{{ $item['generated_at'] ?? '—' }}</code></td>
                            <td>{{ $item['source_name'] ?? $item['source'] ?? '—' }}</td>
                            <td><span class="badge badge-soft-{{ $itemTone }} badge-label">{{ $item['status'] ?? 'unknown' }}</span></td>
                            <td>{{ $item['summary']['passed'] ?? 0 }} / {{ $item['summary']['total'] ?? 0 }} passed</td>
                            <td class="text-end"><a href="{{ route('subdomain-deployment.result', $item['id']) }}" target="_blank" class="btn btn-sm btn-light">JSON</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No imported smoke result history yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
