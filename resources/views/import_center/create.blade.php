@extends('layouts.app')
@section('title', __('messages.import_center.create_title') . ' · ' . $project->name)
@section('page_title', __('messages.import_center.create_title'))
@section('page_actions')
    <a href="{{ route('projects.import-center.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.cancel') }}</a>
@endsection

@section('content')
<form method="POST" action="{{ route('projects.import-center.store', $project) }}" class="aptoria-form-shell" data-aptoria-form-plugin data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.import_center.confirm_preview_title') }}" data-confirm-text="{{ __('messages.import_center.confirm_preview_text') }}" data-confirm-button="{{ __('messages.import_center.confirm_preview_button') }}">
    @csrf
    <div class="card aptoria-panel-card">
        <div class="card-header border-light d-flex justify-content-between align-items-start gap-3">
            <div class="d-flex gap-3 align-items-start">
                <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="brackets-contain"></i></span></span>
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.import_center.create_title') }}</h5>
                    <p class="text-muted small mb-0">{{ __('messages.import_center.create_copy') }}</p>
                </div>
            </div>
            <span class="badge badge-soft-info"><i data-lucide="workflow" class="me-1"></i>{{ __('messages.import_center.normalized_pipeline') }}</span>
        </div>
        <div class="card-body">
            <div class="alert alert-light border d-flex gap-2 align-items-start mb-3">
                <i data-lucide="fingerprint" class="mt-1"></i>
                <div>
                    <strong>{{ __('messages.import_center.normalization_notice_title') }}</strong><br>
                    <span class="text-muted">{{ __('messages.import_center.normalization_notice_copy') }}</span>
                </div>
            </div>

            <div class="aptoria-form-section mb-3">
                <div class="aptoria-form-section-header">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="id-card"></i></span></span>
                        <div>
                            <h6 class="mb-1">{{ __('messages.import_center.sections.source') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.import_center.sections.source_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="source_type">{{ __('messages.import_center.source_type') }}</label>
                        <select id="source_type" name="source_type" class="form-select @error('source_type') is-invalid @enderror" required data-import-source-select>
                            @foreach ($sourceAdapters as $adapter)
                                <option value="{{ $adapter['key'] }}" data-sample="{{ $adapter['sample_key'] }}" @selected(old('source_type') === $adapter['key'])>{{ __('messages.import_center.source_types.'.$adapter['key']) }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('messages.import_center.source_type_help') }}</div>
                        @error('source_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="source_name">{{ __('messages.import_center.source_name') }}</label>
                        <input id="source_name" type="text" name="source_name" value="{{ old('source_name') }}" class="form-control @error('source_name') is-invalid @enderror" placeholder="{{ __('messages.import_center.placeholders.source_name') }}">
                        <div class="form-text">{{ __('messages.import_center.source_name_help') }}</div>
                        @error('source_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="source_version">{{ __('messages.import_center.source_version') }}</label>
                        <input id="source_version" type="text" name="source_version" value="{{ old('source_version') }}" class="form-control @error('source_version') is-invalid @enderror" placeholder="{{ __('messages.import_center.placeholders.source_version') }}">
                        <div class="form-text">{{ __('messages.import_center.source_version_help') }}</div>
                        @error('source_version')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="aptoria-form-section mb-3">
                <div class="aptoria-form-section-header">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="avatar avatar-xs rounded text-bg-info"><span class="avatar-title"><i data-lucide="workflow"></i></span></span>
                        <div>
                            <h6 class="mb-1">{{ __('messages.import_center.sections.adapters') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.import_center.sections.adapters_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach ($sourceAdapters as $adapter)
                        <div class="col-md-6 col-xl-4">
                            <label class="aptoria-adapter-option h-100">
                                <input class="form-check-input visually-hidden" type="radio" name="source_type_card" value="{{ $adapter['key'] }}" @checked(old('source_type', 'postman_collection') === $adapter['key']) data-import-source-card>
                                <span class="aptoria-form-section h-100 mb-0 d-block">
                                    <span class="d-flex gap-3 align-items-start">
                                        <span class="avatar avatar-sm rounded text-bg-{{ $adapter['tone'] }}"><span class="avatar-title"><i data-lucide="{{ $adapter['icon'] }}"></i></span></span>
                                        <span class="min-w-0 d-block">
                                            <span class="fw-medium d-block">{{ __('messages.import_center.source_types.'.$adapter['key']) }}</span>
                                            <span class="text-muted small d-block mb-2">{{ __('messages.import_center.source_type_descriptions.'.$adapter['key']) }}</span>
                                            <span class="d-flex flex-wrap gap-1">
                                                @foreach ($adapter['outputs'] as $output)
                                                    <span class="badge badge-soft-secondary">{{ __('messages.import_center.entity_types.'.$output) }}</span>
                                                @endforeach
                                            </span>
                                        </span>
                                    </span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="aptoria-form-section mb-3">
                <div class="aptoria-form-section-header d-flex justify-content-between align-items-start gap-3">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="avatar avatar-xs rounded text-bg-warning"><span class="avatar-title"><i data-lucide="file-code-2"></i></span></span>
                        <div>
                            <h6 class="mb-1">{{ __('messages.import_center.sections.content') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.import_center.sections.content_help') }}</p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-light btn-sm" data-fill-import-sample><i data-lucide="copy-check" class="me-1"></i>{{ __('messages.import_center.fill_sample') }}</button>
                </div>
                <label class="form-label" for="import_content">{{ __('messages.import_center.import_content') }}</label>
                <textarea id="import_content" name="import_content" class="form-control font-monospace @error('import_content') is-invalid @enderror" rows="18" required placeholder="{{ __('messages.import_center.placeholders.import_content') }}">{{ old('import_content') }}</textarea>
                <div class="form-text">{{ __('messages.import_center.import_content_help') }}</div>
                @error('import_content')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="aptoria-form-section">
                <div class="aptoria-form-section-header">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="avatar avatar-xs rounded text-bg-success"><span class="avatar-title"><i data-lucide="shield-check"></i></span></span>
                        <div>
                            <h6 class="mb-1">{{ __('messages.import_center.sections.review') }}</h6>
                            <p class="text-muted small mb-0">{{ __('messages.import_center.sections.review_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input @error('confirm_preview') is-invalid @enderror" type="checkbox" value="1" id="confirmPreview" name="confirm_preview" required @checked(old('confirm_preview'))>
                    <label class="form-check-label" for="confirmPreview">{{ __('messages.import_center.confirm_preview_checkbox') }}</label>
                    @error('confirm_preview')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-end gap-2 flex-wrap">
            <a href="{{ route('projects.import-center.index', $project) }}" class="btn btn-light"><i data-lucide="x" class="me-1"></i>{{ __('messages.common.cancel') }}</a>
            <button type="submit" class="btn btn-primary"><i data-lucide="search-check" class="me-1"></i>{{ __('messages.import_center.create_preview') }}</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.querySelector('[data-import-source-select]');
    var cards = Array.prototype.slice.call(document.querySelectorAll('[data-import-source-card]'));
    var textarea = document.getElementById('import_content');
    var samples = @json(__('messages.import_center.samples'));

    function syncCards(value) {
        cards.forEach(function (card) { card.checked = card.value === value; });
    }

    if (select) {
        syncCards(select.value);
        select.addEventListener('change', function () { syncCards(select.value); });
    }

    cards.forEach(function (card) {
        card.addEventListener('change', function () {
            if (! card.checked || ! select) { return; }
            select.value = card.value;
            select.dispatchEvent(new Event('change'));
        });
    });

    var sampleButton = document.querySelector('[data-fill-import-sample]');
    if (sampleButton && textarea && select) {
        sampleButton.addEventListener('click', function () {
            var option = select.options[select.selectedIndex];
            var key = option ? option.getAttribute('data-sample') : null;
            if (key && samples[key]) {
                textarea.value = samples[key];
                textarea.focus();
            }
        });
    }
});
</script>
@endpush
