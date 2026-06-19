@extends('layouts.app')
@section('title', $pack->title)
@section('page_title', $pack->title)
@section('page_actions')
    <a href="{{ route('projects.evidence-packs.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <a href="{{ route('projects.evidence-packs.download', [$project, $pack, 'html']) }}" class="btn btn-outline-primary"><i data-lucide="code-xml" class="me-1"></i>HTML</a>
    <a href="{{ route('projects.evidence-packs.download', [$project, $pack, 'pdf']) }}" class="btn btn-outline-primary"><i data-lucide="file-badge" class="me-1"></i>PDF</a>
    <a href="{{ route('projects.evidence-packs.download', [$project, $pack, 'zip']) }}" class="btn btn-primary"><i data-lucide="archive" class="me-1"></i>ZIP</a>
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card h-100">
            <div class="card-body">
                <span class="badge badge-soft-primary badge-label mb-2"><i class="ti ti-point-filled"></i>{{ __('messages.evidence_packs.standard_badge') }}</span>
                <h2 class="fw-normal mb-2">{{ __('messages.evidence_packs.standardized_exports') }}</h2>
                <p class="text-muted mb-0">{{ __('messages.evidence_packs.standardized_exports_copy') }}</p>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-light" href="{{ route('projects.evidence-packs.download', [$project, $pack, 'html']) }}"><i data-lucide="code-xml" class="me-1"></i>{{ __('messages.evidence_packs.download_html') }}</a>
                <a class="btn btn-sm btn-light" href="{{ route('projects.evidence-packs.download', [$project, $pack, 'pdf']) }}"><i data-lucide="file-badge" class="me-1"></i>{{ __('messages.evidence_packs.download_pdf') }}</a>
                <a class="btn btn-sm btn-light" href="{{ route('projects.evidence-packs.download', [$project, $pack, 'zip']) }}"><i data-lucide="archive" class="me-1"></i>{{ __('messages.evidence_packs.download_zip') }}</a>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light">
                <h5 class="card-title mb-0"><i data-lucide="file-check-2" class="me-2"></i>{{ __('messages.evidence_packs.manifest') }}</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>{{ __('messages.evidence_packs.type') }}</dt>
                    <dd>{{ $pack->pack_type_label }}</dd>
                    <dt>{{ __('messages.evidence_packs.pack_status') }}</dt>
                    <dd><span class="badge badge-soft-{{ $pack->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.evidence_packs.statuses.'.($pack->status ?: 'generated')) }}</span></dd>
                    <dt>{{ __('messages.evidence_packs.checksum') }}</dt>
                    <dd><code>{{ $pack->checksum }}</code></dd>
                    <dt>{{ __('messages.evidence_packs.generated_at') }}</dt>
                    <dd>{{ $pack->generated_at?->format('Y-m-d H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light">
                <h5 class="card-title mb-0"><i data-lucide="list-checks" class="me-2"></i>{{ __('messages.evidence_packs.sections') }}</h5>
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                @foreach($pack->included_sections_json ?? [] as $section)
                    <span class="badge badge-soft-primary badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.evidence_packs.sections_list.'.$section) }}</span>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card aptoria-panel-card">
            <div class="card-header border-light">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="book-open" class="me-2"></i>{{ __('messages.evidence_packs.preview') }}</h5>
                    <p class="text-muted small mb-0">{{ __('messages.evidence_packs.table_copy') }}</p>
                </div>
            </div>
            <div class="card-body">
                <pre class="bg-light border rounded p-3 mb-0" style="white-space: pre-wrap;">{{ $pack->content_markdown }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection
