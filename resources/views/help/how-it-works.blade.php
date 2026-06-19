@extends('layouts.app')
@section('title', __('messages.help.how.title'))
@section('page_title', __('messages.help.how.title'))
@section('page_actions')
    <a href="{{ route('help.index') }}" class="btn btn-primary btn-sm"><i data-lucide="help-circle" class="me-1"></i>{{ __('messages.help.how.open_help') }}</a>
@endsection
@push('styles')
<style>
    .aptoria-doc-code { white-space: pre-wrap; font-size: .8125rem; line-height: 1.55; }
    .aptoria-doc-step { border-left: 3px solid rgba(13,110,253,.28); }
    .aptoria-doc-table td, .aptoria-doc-table th { vertical-align: top; }
</style>
@endpush
@section('content')
<div class="row g-3 align-items-start">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card">
            <div class="card-body p-4">
                <span class="badge text-bg-primary mb-3"><i data-lucide="sitemap" class="me-1"></i>{{ __('messages.help.how.kicker') }}</span>
                <h1 class="h3 mb-2">{{ __('messages.help.how.heading') }}</h1>
                <p class="text-muted mb-0">{{ __('messages.help.how.lead') }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card">
            <div class="card-body p-4">
                <h2 class="h5 mb-2"><i data-lucide="badge-check" class="me-1"></i>{{ __('messages.help.how.decision_layer_title') }}</h2>
                <p class="text-muted mb-3">{{ __('messages.help.how.decision_layer_copy') }}</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach (__('messages.help.how.badges') as $badge)
                        <span class="badge text-bg-light">{{ $badge }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card aptoria-panel-card mt-3">
    <div class="card-header d-flex align-items-start justify-content-between gap-3">
        <div>
            <h2 class="h5 mb-1"><i data-lucide="git-fork" class="me-1"></i>{{ __('messages.help.how.workflow_title') }}</h2>
            <p class="text-muted mb-0 small">{{ __('messages.help.how.workflow_copy') }}</p>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 align-items-start">
            @foreach (__('messages.help.how.workflow') as $step)
                <div class="col-lg-6 col-xl-4">
                    <div class="border rounded p-3 aptoria-doc-step">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="{{ $step['icon'] }}"></i></span></span>
                            <div>
                                <h3 class="h6 mb-1">{{ $step['title'] }}</h3>
                                <p class="text-muted small mb-0">{{ $step['copy'] }}</p>
                            </div>
                        </div>
                        <dl class="mb-0 small">
                            <dt>{{ __('messages.help.labels.input') }}</dt>
                            <dd class="text-muted mb-2">{{ $step['input'] }}</dd>
                            <dt>{{ __('messages.help.labels.output') }}</dt>
                            <dd class="text-muted mb-0">{{ $step['output'] }}</dd>
                        </dl>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 align-items-start mt-1">
    <div class="col-xl-6">
        <div class="card aptoria-panel-card">
            <div class="card-header">
                <h2 class="h5 mb-1"><i data-lucide="database" class="me-1"></i>{{ __('messages.help.how.domain_title') }}</h2>
                <p class="text-muted small mb-0">{{ __('messages.help.how.domain_copy') }}</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-centered table-nowrap w-100 mb-0 aptoria-doc-table">
                        <thead><tr><th>{{ __('messages.help.labels.object') }}</th><th>{{ __('messages.help.labels.purpose') }}</th><th>{{ __('messages.help.labels.used_by') }}</th></tr></thead>
                        <tbody>
                        @foreach (__('messages.help.how.domain_rows') as $row)
                            <tr><td class="fw-semibold">{{ $row['object'] }}</td><td class="text-muted">{{ $row['purpose'] }}</td><td class="text-muted">{{ $row['used_by'] }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card aptoria-panel-card">
            <div class="card-header">
                <h2 class="h5 mb-1"><i data-lucide="shield-check" class="me-1"></i>{{ __('messages.help.how.safety_title') }}</h2>
                <p class="text-muted small mb-0">{{ __('messages.help.how.safety_copy') }}</p>
            </div>
            <div class="card-body p-4">
                <ul class="mb-0 text-muted">
                    @foreach (__('messages.help.how.safety_points') as $point)
                        <li class="mb-2">{{ $point }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 align-items-start mt-1">
    @foreach (__('messages.help.how.code_samples') as $sample)
        <div class="col-xl-6">
            <div class="card aptoria-panel-card">
                <div class="card-header">
                    <h2 class="h5 mb-1"><i data-lucide="{{ $sample['icon'] }}" class="me-1"></i>{{ $sample['title'] }}</h2>
                    <p class="text-muted small mb-0">{{ $sample['copy'] }}</p>
                </div>
                <div class="card-body p-0">
                    <pre class="bg-dark text-white rounded-0 mb-0 p-3 aptoria-doc-code"><code>{{ $sample['code'] }}</code></pre>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card aptoria-panel-card mt-3">
    <div class="card-header">
        <h2 class="h5 mb-1"><i data-lucide="shield-chevron" class="me-1"></i>{{ __('messages.help.how.release_logic_title') }}</h2>
        <p class="text-muted small mb-0">{{ __('messages.help.how.release_logic_copy') }}</p>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 align-items-start">
            @foreach (__('messages.help.how.release_checks') as $check)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3">
                        <span class="badge {{ $check['badge'] }} mb-2">{{ $check['level'] }}</span>
                        <h3 class="h6 mb-1">{{ $check['title'] }}</h3>
                        <p class="text-muted small mb-0">{{ $check['copy'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
