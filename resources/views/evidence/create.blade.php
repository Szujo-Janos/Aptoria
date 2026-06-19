@extends('layouts.app')
@section('title', __('messages.evidence.create_title'))
@section('page_title', __('messages.evidence.create_title'))
@section('page_actions')
    <a href="{{ route('projects.evidence.index', $project) }}" class="btn btn-light">
        <i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.cancel') }}
    </a>
@endsection
@section('content')
<form method="POST" action="{{ route('projects.evidence.store', $project) }}" data-aptoria-form-scope="evidence" data-aptoria-form-plugin class="aptoria-evidence-intake-form">
    @csrf
    <input type="hidden" name="scan_result_id" value="{{ old('scan_result_id', request('scan_result_id')) }}">
    <div class="card aptoria-panel-card aptoria-form-shell">
        <div class="card-header border-light d-flex justify-content-between align-items-start gap-3">
            <div class="d-flex gap-3 align-items-start">
                <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="file-plus"></i></span></span>
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.evidence.create_title') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.evidence.create_copy') }}</p>
                </div>
            </div>
            <span class="badge badge-soft-success"><i data-lucide="fingerprint" class="me-1"></i>{{ __('messages.evidence.lifecycle_audited') }}</span>
        </div>
        <div class="card-body">
            <div class="alert alert-light border d-flex gap-2 align-items-start mb-3">
                <i data-lucide="fingerprint" class="mt-1"></i>
                <div>
                    <strong>{{ __('messages.evidence.checksum_notice_title') }}</strong><br>
                    <span class="text-muted">{{ __('messages.evidence.checksum_notice_copy') }}</span>
                </div>
            </div>

            <div class="aptoria-form-section mb-3">
                <div class="aptoria-form-section-header">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="id-card"></i></span></span>
                        <div>
                            <h6 class="mb-1">{{ __('messages.evidence.sections.identity') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.evidence.sections.identity_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="evidence_type">{{ __('messages.evidence.type') }}</label>
                        <select id="evidence_type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                            @foreach(\App\Models\FindingEvidence::TYPES as $type)
                                <option value="{{ $type }}" @selected(old('type', 'note') === $type)>{{ __('messages.evidence.types.'.$type) }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('messages.evidence.type_help') }}</div>
                        @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="evidence_title">{{ __('messages.evidence.title_field') }}</label>
                        <input id="evidence_title" type="text" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="{{ __('messages.evidence.title_placeholder') }}" value="{{ old('title') }}" required>
                        <div class="form-text">{{ __('messages.evidence.title_help') }}</div>
                        @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="aptoria-form-section mb-3">
                <div class="aptoria-form-section-header">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="avatar avatar-xs rounded text-bg-info"><span class="avatar-title"><i data-lucide="git-fork"></i></span></span>
                        <div>
                            <h6 class="mb-1">{{ __('messages.evidence.sections.links') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.evidence.sections.links_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="evidence_finding_id">{{ __('messages.findings.finding') }}</label>
                        <select id="evidence_finding_id" name="finding_id" class="form-select @error('finding_id') is-invalid @enderror">
                            <option value="">{{ __('messages.evidence.no_finding_option') }}</option>
                            @foreach($findings as $finding)
                                <option value="{{ $finding->id }}" @selected((string) old('finding_id', request('finding_id')) === (string) $finding->id)>{{ $finding->title }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('messages.evidence.finding_help') }}</div>
                        @error('finding_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="evidence_endpoint_id">{{ __('messages.endpoints.endpoint') }}</label>
                        <select id="evidence_endpoint_id" name="endpoint_id" class="form-select @error('endpoint_id') is-invalid @enderror">
                            <option value="">{{ __('messages.evidence.no_endpoint_option') }}</option>
                            @foreach($endpoints as $endpoint)
                                <option value="{{ $endpoint->id }}" @selected((string) old('endpoint_id', request('endpoint_id')) === (string) $endpoint->id)>{{ $endpoint->method }} {{ $endpoint->path }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('messages.evidence.endpoint_help') }}</div>
                        @error('endpoint_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="evidence_source_label">{{ __('messages.evidence.source_label') }}</label>
                        <input id="evidence_source_label" type="text" name="source_label" class="form-control @error('source_label') is-invalid @enderror" placeholder="{{ __('messages.evidence.source_placeholder') }}" value="{{ old('source_label') }}">
                        <div class="form-text">{{ __('messages.evidence.source_help') }}</div>
                        @error('source_label')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="evidence_url">{{ __('messages.common.url') }}</label>
                        <input id="evidence_url" type="url" name="url" class="form-control @error('url') is-invalid @enderror" placeholder="{{ __('messages.evidence.url_placeholder') }}" value="{{ old('url') }}">
                        <div class="form-text">{{ __('messages.evidence.url_help') }}</div>
                        @error('url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="aptoria-form-section mb-3">
                <div class="aptoria-form-section-header">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="avatar avatar-xs rounded text-bg-warning"><span class="avatar-title"><i data-lucide="file-text"></i></span></span>
                        <div>
                            <h6 class="mb-1">{{ __('messages.evidence.sections.proof') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.evidence.sections.proof_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="evidence_content">{{ __('messages.evidence.content') }}</label>
                        <textarea id="evidence_content" name="content" class="form-control @error('content') is-invalid @enderror" rows="5" placeholder="{{ __('messages.evidence.content_placeholder') }}">{{ old('content') }}</textarea>
                        <div class="form-text">{{ __('messages.evidence.content_help') }}</div>
                        @error('content')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="evidence_request_excerpt">{{ __('messages.evidence.request_excerpt') }}</label>
                        <textarea id="evidence_request_excerpt" name="request_excerpt" class="form-control @error('request_excerpt') is-invalid @enderror" rows="4" placeholder="{{ __('messages.evidence.request_placeholder') }}">{{ old('request_excerpt') }}</textarea>
                        <div class="form-text">{{ __('messages.evidence.request_help') }}</div>
                        @error('request_excerpt')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="evidence_response_excerpt">{{ __('messages.evidence.response_excerpt') }}</label>
                        <textarea id="evidence_response_excerpt" name="response_excerpt" class="form-control @error('response_excerpt') is-invalid @enderror" rows="4" placeholder="{{ __('messages.evidence.response_placeholder') }}">{{ old('response_excerpt') }}</textarea>
                        <div class="form-text">{{ __('messages.evidence.response_help') }}</div>
                        @error('response_excerpt')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="aptoria-form-section">
                <div class="aptoria-form-section-header">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="avatar avatar-xs rounded text-bg-success"><span class="avatar-title"><i data-lucide="clipboard-check"></i></span></span>
                        <div>
                            <h6 class="mb-1">{{ __('messages.evidence.sections.repository') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.evidence.sections.repository_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="evidence_repository_notes">{{ __('messages.evidence.repository_notes') }}</label>
                        <textarea id="evidence_repository_notes" name="repository_notes" class="form-control @error('repository_notes') is-invalid @enderror" rows="3" placeholder="{{ __('messages.evidence.notes_placeholder') }}">{{ old('repository_notes') }}</textarea>
                        <div class="form-text">{{ __('messages.evidence.repository_notes_help') }}</div>
                        @error('repository_notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-end gap-2 flex-wrap">
            <a href="{{ route('projects.evidence.index', $project) }}" class="btn btn-light"><i data-lucide="x" class="me-1"></i>{{ __('messages.common.cancel') }}</a>
            <button class="btn btn-primary" type="submit"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
        </div>
    </div>
</form>
@endsection
