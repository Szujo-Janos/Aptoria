@php($uid = str_replace([':', '.', '#'], '-', $modalId))
<div class="modal fade aptoria-scrollable-form-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $uid }}-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable aptoria-scrollable-form-dialog">
        <form method="POST" action="{{ route('projects.release-gates.items.update', [$project, $gate, $item]) }}" class="modal-content aptoria-form-shell" data-aptoria-form-plugin>
            @csrf @method('PUT')
            <input type="hidden" name="_release_gate_modal" value="{{ $modalId }}">
            <div class="modal-header aptoria-scrollable-form-header"><div class="d-flex gap-3 align-items-start"><span class="avatar avatar-sm rounded text-bg-{{ $item->effective_state_tone }}"><span class="avatar-title"><i data-lucide="{{ $item->icon }}"></i></span></span><div><h5 class="modal-title mb-1" id="{{ $uid }}-label">{{ __('messages.release_gates.review_item') }}</h5><p class="text-muted small mb-0">{{ $item->label }}</p></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.cancel') }}"></button></div>
            <div class="modal-body aptoria-scrollable-form-body">
                <div class="aptoria-form-section mb-3"><div class="aptoria-form-section-header"><div class="d-flex gap-2 align-items-start"><span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="clipboard-search"></i></span></span><div><h6 class="mb-1">{{ __('messages.release_gates.sections.item_review') }}</h6><p class="text-muted small mb-0">{{ __('messages.release_gates.sections.item_review_help') }}</p></div></div></div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="{{ $uid }}-manual_state">{{ __('messages.release_gates.form.manual_state') }}</label><select id="{{ $uid }}-manual_state" name="manual_state" class="form-select @error('manual_state') is-invalid @enderror"><option value="">{{ __('messages.release_gates.keep_automated_state') }}</option>@foreach(\App\Models\ReleaseGateItem::STATES as $state)<option value="{{ $state }}" @selected((old('_release_gate_modal') === $modalId ? old('manual_state') : $item->manual_state) === $state)>{{ __('messages.release_gates.item_states.'.$state) }}</option>@endforeach</select><div class="form-text">{{ __('messages.release_gates.help.manual_state') }}</div>@error('manual_state')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-8"><label class="form-label" for="{{ $uid }}-reviewer_note">{{ __('messages.release_gates.form.reviewer_note') }}</label><textarea id="{{ $uid }}-reviewer_note" name="reviewer_note" rows="5" class="form-control @error('reviewer_note') is-invalid @enderror" placeholder="{{ __('messages.release_gates.placeholders.reviewer_note') }}">{{ old('_release_gate_modal') === $modalId ? old('reviewer_note') : $item->reviewer_note }}</textarea><div class="form-text">{{ __('messages.release_gates.help.reviewer_note') }}</div>@error('reviewer_note')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    </div>
                </div>
                <div class="alert alert-light border mb-0"><i data-lucide="{{ $item->icon }}" class="me-1"></i><strong>{{ __('messages.release_gates.required_action') }}:</strong> {{ $item->required_action ?: __('messages.release_gates.no_required_action') }}</div>
            </div>
            <div class="modal-footer aptoria-scrollable-form-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button></div>
        </form>
    </div>
</div>
