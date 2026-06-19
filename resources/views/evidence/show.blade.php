@extends('layouts.app')
@section('title', $evidence->title)
@section('page_title', __('messages.evidence.detail_title'))
@section('page_actions')
    <a href="{{ route('projects.evidence.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    @if($evidence->repository_status !== \App\Models\FindingEvidence::STATUS_ARCHIVED)
        <form method="POST" action="{{ route('projects.evidence.verify', [$project, $evidence]) }}" class="d-inline">@csrf<button class="btn btn-success" type="submit"><i data-lucide="badge-check" class="me-1"></i>{{ __('messages.evidence.verify') }}</button></form>
        <form method="POST" action="{{ route('projects.evidence.archive', [$project, $evidence]) }}" class="d-inline" data-aptoria-confirm="{{ __('messages.evidence.confirm_archive') }}">@csrf<button class="btn btn-warning" type="submit"><i data-lucide="archive" class="me-1"></i>{{ __('messages.evidence.archive') }}</button></form>
    @else
        <form method="POST" action="{{ route('projects.evidence.restore', [$project, $evidence]) }}" class="d-inline">@csrf<button class="btn btn-primary" type="submit"><i data-lucide="archive-restore" class="me-1"></i>{{ __('messages.evidence.restore') }}</button></form>
    @endif
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-{{ $evidence->repository_status_tone }}"><span class="avatar-title"><i data-lucide="{{ $evidence->repository_status_icon }}"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.evidence.repository_status') }}</p><h5 class="mb-0 fw-normal">{{ $evidence->repository_status_label }}</h5></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-{{ $evidence->integrity_status_tone }}"><span class="avatar-title"><i data-lucide="fingerprint"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.evidence.integrity_status') }}</p><h5 class="mb-0 fw-normal">{{ $evidence->integrity_status_label }}</h5></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-info"><span class="avatar-title"><i data-lucide="{{ $evidence->type_icon }}"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.evidence.type') }}</p><h5 class="mb-0 fw-normal">{{ $evidence->type_label }}</h5></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-secondary"><span class="avatar-title"><i data-lucide="user-check"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.evidence.captured_by') }}</p><h5 class="mb-0 fw-normal text-truncate">{{ $evidence->capturedBy?->name ?? '—' }}</h5></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light d-flex gap-3 align-items-center">
                <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="folder-check"></i></span></span>
                <div><h5 class="card-title mb-1">{{ $evidence->title }}</h5><p class="text-muted mb-0 small">{{ $evidence->source_label ?: __('messages.evidence.no_source') }}</p></div>
            </div>
            <div class="card-body">
                @if($evidence->url)
                    <div class="mb-3"><span class="badge badge-soft-primary"><i data-lucide="external-link" class="me-1"></i>{{ $evidence->url }}</span></div>
                @endif
                <div class="mb-3">
                    <h6 class="text-uppercase text-muted fs-xxs">{{ __('messages.evidence.content') }}</h6>
                    <div class="border rounded p-3 bg-body-tertiary" style="white-space: pre-wrap;">{{ $evidence->content ?: '—' }}</div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-uppercase text-muted fs-xxs">{{ __('messages.evidence.request_excerpt') }}</h6>
                        <pre class="border rounded p-3 bg-body-tertiary mb-0"><code>{{ $evidence->request_excerpt ?: '—' }}</code></pre>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-uppercase text-muted fs-xxs">{{ __('messages.evidence.response_excerpt') }}</h6>
                        <pre class="border rounded p-3 bg-body-tertiary mb-0"><code>{{ $evidence->response_excerpt ?: '—' }}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="card aptoria-panel-card">
            <div class="card-header border-light d-flex gap-3 align-items-center">
                <span class="avatar avatar-sm rounded text-bg-info"><span class="avatar-title"><i data-lucide="file-delta"></i></span></span>
                <div><h5 class="card-title mb-1">{{ __('messages.evidence.lifecycle_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.evidence.lifecycle_copy') }}</p></div>
            </div>
            <div class="card-body">
                <div class="vstack gap-3">
                    @forelse($evidence->lifecycleEvents as $event)
                        <div class="d-flex gap-3">
                            <span class="avatar avatar-xs rounded text-bg-light"><span class="avatar-title"><i data-lucide="{{ match($event->action) { 'created' => 'file-plus', 'verified' => 'badge-check', 'archived' => 'archive', 'restored' => 'archive-restore', default => 'file-delta' } }}"></i></span></span>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between gap-3"><strong>{{ __('messages.evidence.lifecycle.'.$event->action) }}</strong><small class="text-muted">{{ $event->occurred_at?->format('Y-m-d H:i') }}</small></div>
                                <p class="text-muted mb-0 small">{{ $event->summary }} · {{ $event->user?->name ?? __('messages.common.system') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted text-center py-4">{{ __('messages.evidence.lifecycle_empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light d-flex gap-3 align-items-center">
                <span class="avatar avatar-sm rounded text-bg-success"><span class="avatar-title"><i data-lucide="fingerprint"></i></span></span>
                <div><h5 class="card-title mb-1">{{ __('messages.evidence.integrity_panel') }}</h5><p class="text-muted mb-0 small">{{ __('messages.evidence.integrity_panel_copy') }}</p></div>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5">{{ __('messages.evidence.sha256') }}</dt><dd class="col-7"><code class="text-break">{{ $evidence->sha256 ?: '—' }}</code></dd>
                    <dt class="col-5">{{ __('messages.evidence.algorithm') }}</dt><dd class="col-7">{{ $evidence->checksum_algorithm ?: \App\Models\FindingEvidence::CHECKSUM_ALGORITHM }}</dd>
                    <dt class="col-5">{{ __('messages.evidence.reviewed_by') }}</dt><dd class="col-7">{{ $evidence->reviewedBy?->name ?? '—' }}</dd>
                    <dt class="col-5">{{ __('messages.evidence.reviewed_at') }}</dt><dd class="col-7">{{ $evidence->reviewed_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    <dt class="col-5">{{ __('messages.evidence.archived_at') }}</dt><dd class="col-7">{{ $evidence->archived_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light d-flex gap-3 align-items-center">
                <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="git-fork"></i></span></span>
                <div><h5 class="card-title mb-1">{{ __('messages.evidence.linked_objects') }}</h5><p class="text-muted mb-0 small">{{ __('messages.evidence.linked_objects_copy') }}</p></div>
            </div>
            <div class="card-body vstack gap-2">
                @if($evidence->finding)
                    <a href="{{ route('projects.findings.show', [$project, $evidence->finding]) }}" class="btn btn-light justify-content-start"><i data-lucide="bug" class="me-2"></i>{{ $evidence->finding->title }}</a>
                @endif
                @if($evidence->endpoint)
                    <span class="btn btn-light justify-content-start disabled"><i data-lucide="route" class="me-2"></i>{{ $evidence->endpoint->method }} {{ $evidence->endpoint->path }}</span>
                @endif
                @if($evidence->scanResult)
                    <span class="btn btn-light justify-content-start disabled"><i data-lucide="scan-eye" class="me-2"></i>{{ __('messages.evidence.scan_result_evidence') }}</span>
                @endif
                @if($evidence->testCase)
                    <a href="{{ route('projects.tests.cases.show', [$project, $evidence->testCase]) }}" class="btn btn-light justify-content-start"><i data-lucide="clipboard-list" class="me-2"></i>{{ $evidence->testCase->title }}</a>
                @endif
                @if($evidence->testRun)
                    <span class="btn btn-light justify-content-start disabled"><i data-lucide="play" class="me-2"></i>{{ __('messages.native_tests.record_run') }} #{{ $evidence->testRun->id }}</span>
                @endif
                @unless($evidence->finding || $evidence->endpoint || $evidence->scanResult || $evidence->testCase || $evidence->testRun)
                    <p class="text-muted mb-0">{{ __('messages.evidence.no_links') }}</p>
                @endunless
            </div>
        </div>

        <div class="card aptoria-panel-card">
            <div class="card-header border-light d-flex gap-3 align-items-center">
                <span class="avatar avatar-sm rounded text-bg-secondary"><span class="avatar-title"><i data-lucide="clipboard-search"></i></span></span>
                <div><h5 class="card-title mb-1">{{ __('messages.evidence.repository_notes') }}</h5><p class="text-muted mb-0 small">{{ __('messages.evidence.repository_notes_copy') }}</p></div>
            </div>
            <div class="card-body"><p class="mb-0" style="white-space: pre-wrap;">{{ $evidence->repository_notes ?: '—' }}</p></div>
        </div>
    </div>
</div>
@endsection
