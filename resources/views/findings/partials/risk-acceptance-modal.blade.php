<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ $action }}" data-aptoria-form-scope="risk-acceptance" data-aptoria-form-plugin>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i data-lucide="{{ $submitIcon ?? 'shield-check' }}" class="me-2"></i>{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-flex gap-2 align-items-start">
                        <i data-lucide="shield-alert" class="mt-1"></i>
                        <div>
                            <strong>{{ __('messages.risk_acceptance.warning_title') }}</strong><br>
                            <span class="small">{{ __('messages.risk_acceptance.warning_copy') }}</span>
                        </div>
                    </div>
                    @if(! empty($acceptance))
                        <div class="alert alert-light border d-flex gap-2 align-items-start">
                            <i data-lucide="refresh-cw" class="mt-1"></i>
                            <div>
                                <strong>{{ __('messages.risk_acceptance.renewal_context') }}</strong><br>
                                <span class="small text-muted">{{ __('messages.risk_acceptance.renewal_context_copy', ['date' => $acceptance->accepted_until?->format('Y-m-d') ?? '—']) }}</span>
                            </div>
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.risk_acceptance.accepted_until') }}</label>
                            <input type="date" name="accepted_until" class="form-control" required>
                            <div class="form-text">{{ __('messages.risk_acceptance.accepted_until_help') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.risk_acceptance.release_scope') }}</label>
                            <input type="text" name="release_scope" class="form-control" value="{{ old('release_scope', $acceptance?->release_scope) }}" placeholder="{{ __('messages.form_plugin.placeholders.risk_acceptance.release_scope') }}">
                            <div class="form-text">{{ __('messages.risk_acceptance.release_scope_help') }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('messages.risk_acceptance.reason') }}</label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="{{ __('messages.form_plugin.placeholders.risk_acceptance.reason') }}">{{ old('reason') }}</textarea>
                            <div class="form-text">{{ __('messages.risk_acceptance.reason_help') }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('messages.risk_acceptance.business_justification') }}</label>
                            <textarea name="business_justification" class="form-control" rows="3" required placeholder="{{ __('messages.form_plugin.placeholders.risk_acceptance.business_justification') }}">{{ old('business_justification') }}</textarea>
                            <div class="form-text">{{ __('messages.risk_acceptance.business_justification_help') }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('messages.risk_acceptance.mitigation_note') }}</label>
                            <textarea name="mitigation_note" class="form-control" rows="3" placeholder="{{ __('messages.form_plugin.placeholders.risk_acceptance.mitigation_note') }}">{{ old('mitigation_note', $acceptance?->mitigation_note) }}</textarea>
                            <div class="form-text">{{ __('messages.risk_acceptance.mitigation_note_help') }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer aptoria-card-footer-subtle">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                    <button type="submit" class="btn btn-warning"><i data-lucide="{{ $submitIcon ?? 'shield-check' }}" class="me-1"></i>{{ $submitLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
