<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ route('projects.release-gates.report-version.store', [$project, $gate]) }}" class="modal-content aptoria-scrollable-modal-form">
            @csrf
            <div class="modal-header">
                <div class="d-flex gap-3 align-items-center">
                    <span class="avatar avatar-sm rounded text-bg-success"><span class="avatar-title"><i data-lucide="package-check"></i></span></span>
                    <div>
                        <h5 class="modal-title" id="{{ $modalId }}Label">{{ __('messages.release_gates.package.create_title') }}</h5>
                        <p class="text-muted mb-0 small">{{ __('messages.release_gates.package.create_copy') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="aptoria-form-section mb-3">
                    <div class="aptoria-form-section-heading">
                        <i data-lucide="file-check-2"></i>
                        <div>
                            <h6>{{ __('messages.release_gates.package.sections.identity') }}</h6>
                            <p>{{ __('messages.release_gates.package.sections.identity_help') }}</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.release_gates.gate') }}</label>
                            <input type="text" class="form-control" value="{{ $gate->title }}" readonly>
                            <div class="form-text">{{ __('messages.release_gates.package.help.gate') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.release_gates.final_decision') }}</label>
                            <input type="text" class="form-control" value="{{ $gate->final_decision_label }}" readonly>
                            <div class="form-text">{{ __('messages.release_gates.package.help.decision') }}</div>
                        </div>
                    </div>
                </div>

                <div class="aptoria-form-section mb-3">
                    <div class="aptoria-form-section-heading">
                        <i data-lucide="clipboard-search"></i>
                        <div>
                            <h6>{{ __('messages.release_gates.package.sections.review') }}</h6>
                            <p>{{ __('messages.release_gates.package.sections.review_help') }}</p>
                        </div>
                    </div>
                    <label for="decision_package_notes" class="form-label">{{ __('messages.common.notes') }}</label>
                    <textarea id="decision_package_notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4" placeholder="{{ __('messages.release_gates.package.placeholders.notes') }}">{{ old('notes') }}</textarea>
                    <div class="form-text">{{ __('messages.release_gates.package.help.notes') }}</div>
                    @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input @error('confirm_decision_package') is-invalid @enderror" id="confirm_decision_package" name="confirm_decision_package" value="1" {{ old('confirm_decision_package') ? 'checked' : '' }}>
                    <label for="confirm_decision_package" class="form-check-label">{{ __('messages.release_gates.package.confirm') }}</label>
                    <div class="form-text">{{ __('messages.release_gates.package.help.confirm') }}</div>
                    @error('confirm_decision_package')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                <button type="submit" class="btn btn-success"><i data-lucide="file-check-2" class="me-1"></i>{{ __('messages.release_gates.package.create_report') }}</button>
            </div>
        </form>
    </div>
</div>
@if($errors->has('confirm_decision_package') || $errors->has('notes'))
    <script>window.aptoriaOpenModal = '{{ $modalId }}';</script>
@endif
