@php($modalId = $modalId ?? 'createSuiteModal')
@php($uid = str_replace([':', '.', '#'], '-', $modalId))
<div class="modal fade aptoria-scrollable-form-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $uid }}-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable aptoria-scrollable-form-dialog">
        <form method="POST" action="{{ route('projects.tests.suites.store', $project) }}" class="modal-content aptoria-form-shell" data-aptoria-form-plugin>
            @csrf
            <input type="hidden" name="_native_test_modal" value="{{ $modalId }}">
            <div class="modal-header aptoria-scrollable-form-header">
                <div class="d-flex gap-3 align-items-start">
                    <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="flask-conical"></i></span></span>
                    <div>
                        <h5 class="modal-title mb-1" id="{{ $uid }}-label">{{ __('messages.native_tests.create_suite_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.native_tests.create_suite_copy') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.cancel') }}"></button>
            </div>
            <div class="modal-body aptoria-scrollable-form-body">
                <div class="aptoria-form-section mb-3">
                    <div class="aptoria-form-section-header">
                        <div class="d-flex gap-2 align-items-start"><span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="id-card"></i></span></span><div><h6 class="mb-1">{{ __('messages.native_tests.sections.suite_identity') }}</h6><p class="text-muted small mb-0">{{ __('messages.native_tests.sections.suite_identity_help') }}</p></div></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="{{ $uid }}-name">{{ __('messages.native_tests.name') }}</label><input id="{{ $uid }}-name" name="name" value="{{ old('_native_test_modal') === $modalId ? old('name') : '' }}" class="form-control @error('name') is-invalid @enderror" required placeholder="{{ __('messages.native_tests.placeholders.suite_name') }}"><div class="form-text">{{ __('messages.native_tests.help.suite_name') }}</div>@error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label" for="{{ $uid }}-owner_name">{{ __('messages.native_tests.owner_name') }}</label><input id="{{ $uid }}-owner_name" name="owner_name" value="{{ old('_native_test_modal') === $modalId ? old('owner_name') : '' }}" class="form-control @error('owner_name') is-invalid @enderror" placeholder="{{ __('messages.native_tests.placeholders.owner_name') }}"><div class="form-text">{{ __('messages.native_tests.help.owner_name') }}</div>@error('owner_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label" for="{{ $uid }}-status">{{ __('messages.common.status') }}</label><select id="{{ $uid }}-status" name="status" class="form-select @error('status') is-invalid @enderror">@foreach(\App\Models\TestSuite::STATUSES as $status)<option value="{{ $status }}" @selected((old('_native_test_modal') === $modalId ? old('status') : 'active')===$status)>{{ __('messages.native_tests.statuses.'.$status) }}</option>@endforeach</select><div class="form-text">{{ __('messages.native_tests.help.suite_status') }}</div>@error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label" for="{{ $uid }}-priority">{{ __('messages.native_tests.priority') }}</label><select id="{{ $uid }}-priority" name="priority" class="form-select @error('priority') is-invalid @enderror">@foreach(\App\Models\TestSuite::PRIORITIES as $priority)<option value="{{ $priority }}" @selected((old('_native_test_modal') === $modalId ? old('priority') : 'normal')===$priority)>{{ __('messages.native_tests.priorities.'.$priority) }}</option>@endforeach</select><div class="form-text">{{ __('messages.native_tests.help.priority') }}</div>@error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label" for="{{ $uid }}-description">{{ __('messages.native_tests.description') }}</label><textarea id="{{ $uid }}-description" name="description" rows="5" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('messages.native_tests.placeholders.suite_description') }}">{{ old('_native_test_modal') === $modalId ? old('description') : '' }}</textarea><div class="form-text">{{ __('messages.native_tests.help.suite_description') }}</div>@error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer aptoria-scrollable-form-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button></div>
        </form>
    </div>
</div>
