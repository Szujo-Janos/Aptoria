@extends('layouts.app')
@section('title', __('messages.help.developer.title'))
@section('page_title', __('messages.help.developer.title'))
@section('page_actions')
    <a href="{{ route('help.how_it_works') }}" class="btn btn-light btn-sm"><i data-lucide="sitemap" class="me-1"></i>{{ __('messages.nav.how_it_works') }}</a>
@endsection
@push('styles')
<style>
    .aptoria-doc-code { white-space: pre-wrap; font-size: .8125rem; line-height: 1.55; }
    .aptoria-help-anchor { scroll-margin-top: 90px; }
    .aptoria-doc-table td, .aptoria-doc-table th { vertical-align: top; }
</style>
@endpush
@section('content')
<div class="card aptoria-panel-card">
    <div class="card-body p-4">
        <span class="badge text-bg-primary mb-3"><i data-lucide="book-open" class="me-1"></i>{{ __('messages.help.developer.kicker') }}</span>
        <h1 class="h3 mb-2">{{ __('messages.help.developer.heading') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.help.developer.lead') }}</p>
    </div>
</div>

<div class="row g-3 align-items-start mt-1">
    <div class="col-xl-4">
        <div class="card aptoria-panel-card">
            <div class="card-header"><h2 class="h5 mb-1"><i data-lucide="list-checks" class="me-1"></i>{{ __('messages.help.developer.toc_title') }}</h2></div>
            <div class="list-group list-group-flush">
                @foreach (__('messages.help.developer.toc') as $item)
                    <a href="#{{ $item['id'] }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2"><i data-lucide="{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span></a>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card aptoria-panel-card aptoria-help-anchor" id="quickstart">
            <div class="card-header"><h2 class="h5 mb-1"><i data-lucide="play-circle" class="me-1"></i>{{ __('messages.help.developer.quickstart_title') }}</h2><p class="text-muted small mb-0">{{ __('messages.help.developer.quickstart_copy') }}</p></div>
            <div class="card-body p-4">
                <ol class="mb-0 text-muted">
                    @foreach (__('messages.help.developer.quickstart_steps') as $step)
                        <li class="mb-2">{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card aptoria-panel-card mt-3 aptoria-help-anchor" id="install">
    <div class="card-header"><h2 class="h5 mb-1"><i data-lucide="file-code-2" class="me-1"></i>{{ __('messages.help.developer.install_title') }}</h2><p class="text-muted small mb-0">{{ __('messages.help.developer.install_copy') }}</p></div>
    <div class="card-body p-0"><pre class="bg-dark text-white mb-0 p-3 aptoria-doc-code"><code>{{ __('messages.help.developer.install_code') }}</code></pre></div>
</div>

<div class="card aptoria-panel-card mt-3 aptoria-help-anchor" id="architecture">
    <div class="card-header"><h2 class="h5 mb-1"><i data-lucide="layout-grid" class="me-1"></i>{{ __('messages.help.developer.architecture_title') }}</h2><p class="text-muted small mb-0">{{ __('messages.help.developer.architecture_copy') }}</p></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-centered table-nowrap w-100 mb-0 aptoria-doc-table">
                <thead><tr><th>{{ __('messages.help.labels.layer') }}</th><th>{{ __('messages.help.labels.location') }}</th><th>{{ __('messages.help.labels.rule') }}</th></tr></thead>
                <tbody>
                @foreach (__('messages.help.developer.architecture_rows') as $row)
                    <tr><td class="fw-semibold">{{ $row['layer'] }}</td><td><code>{{ $row['location'] }}</code></td><td class="text-muted">{{ $row['rule'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card aptoria-panel-card mt-3 aptoria-help-anchor" id="modules">
    <div class="card-header"><h2 class="h5 mb-1"><i data-lucide="layout-grid" class="me-1"></i>{{ __('messages.help.developer.modules_title') }}</h2><p class="text-muted small mb-0">{{ __('messages.help.developer.modules_copy') }}</p></div>
    <div class="card-body p-4">
        <div class="row g-3 align-items-start">
            @foreach (__('messages.help.developer.modules') as $module)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3">
                        <h3 class="h6 mb-1"><i data-lucide="{{ $module['icon'] }}" class="me-1"></i>{{ $module['title'] }}</h3>
                        <p class="text-muted small mb-2">{{ $module['copy'] }}</p>
                        <small class="text-muted d-block"><strong>{{ __('messages.help.labels.watch') }}:</strong> {{ $module['watch'] }}</small>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 align-items-start mt-1 aptoria-help-anchor" id="samples">
    @foreach (__('messages.help.developer.samples') as $sample)
        <div class="col-xl-6">
            <div class="card aptoria-panel-card">
                <div class="card-header"><h2 class="h5 mb-1"><i data-lucide="{{ $sample['icon'] }}" class="me-1"></i>{{ $sample['title'] }}</h2><p class="text-muted small mb-0">{{ $sample['copy'] }}</p></div>
                <div class="card-body p-0"><pre class="bg-dark text-white mb-0 p-3 aptoria-doc-code"><code>{{ $sample['code'] }}</code></pre></div>
            </div>
        </div>
    @endforeach
</div>

<div class="card aptoria-panel-card mt-3 aptoria-help-anchor" id="extension">
    <div class="card-header"><h2 class="h5 mb-1"><i data-lucide="tool" class="me-1"></i>{{ __('messages.help.developer.extension_title') }}</h2><p class="text-muted small mb-0">{{ __('messages.help.developer.extension_copy') }}</p></div>
    <div class="card-body p-4">
        <div class="row g-3 align-items-start">
            @foreach (__('messages.help.developer.extension_steps') as $step)
                <div class="col-md-6 col-xl-3"><div class="border rounded p-3"><span class="badge text-bg-light mb-2">{{ $step['number'] }}</span><h3 class="h6 mb-1">{{ $step['title'] }}</h3><p class="text-muted small mb-0">{{ $step['copy'] }}</p></div></div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 align-items-start mt-1 aptoria-help-anchor" id="troubleshooting">
    <div class="col-xl-7">
        <div class="card aptoria-panel-card">
            <div class="card-header"><h2 class="h5 mb-1"><i data-lucide="clipboard-search" class="me-1"></i>{{ __('messages.help.developer.troubleshooting_title') }}</h2></div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped table-centered w-100 mb-0 aptoria-doc-table"><thead><tr><th>{{ __('messages.help.labels.symptom') }}</th><th>{{ __('messages.help.labels.fix') }}</th></tr></thead><tbody>@foreach (__('messages.help.developer.troubleshooting') as $row)<tr><td class="fw-semibold">{{ $row['symptom'] }}</td><td class="text-muted">{{ $row['fix'] }}</td></tr>@endforeach</tbody></table></div></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card aptoria-panel-card">
            <div class="card-header"><h2 class="h5 mb-1"><i data-lucide="shield-alert" class="me-1"></i>{{ __('messages.help.developer.security_title') }}</h2></div>
            <div class="card-body p-4"><ul class="mb-0 text-muted">@foreach (__('messages.help.developer.security_points') as $point)<li class="mb-2">{{ $point }}</li>@endforeach</ul></div>
        </div>
    </div>
</div>
@endsection
