<div class="modal fade" id="authProfileCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('projects.auth-profiles.store', $project) }}" data-aptoria-form-scope="auth_profile" data-aptoria-form-plugin>
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ __('messages.auth_profiles.new') }}</h5>
                    <p class="text-muted small mb-0">{{ __('messages.auth_profiles.form_help') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
            <div class="modal-body">
                @include('auth_profiles.partials.form', ['profile' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                <button class="btn btn-primary" type="submit"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
            </div>
        </form>
    </div>
</div>

@foreach ($authProfiles as $profile)
    <div class="modal fade" id="authProfileEditModal{{ $profile->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('projects.auth-profiles.update', [$project, $profile]) }}" data-aptoria-form-scope="auth_profile" data-aptoria-form-plugin>
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ __('messages.auth_profiles.edit') }} · {{ $profile->name }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.auth_profiles.edit_help') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    @include('auth_profiles.partials.form', ['profile' => $profile])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                    <button class="btn btn-primary" type="submit"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="authProfilePreviewModal{{ $profile->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $profile->name }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.auth_profiles.preview_copy') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.auth_profiles.type') }}</span><span class="badge badge-soft-{{ $profile->tone }}">{{ $profile->type_label }}</span></div>
                        <div class="list-group-item"><div class="text-muted small mb-1">{{ __('messages.auth_profiles.masked_preview') }}</div><code>{{ $profile->masked_preview }}</code></div>
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.common.default') }}</span><strong>{{ $profile->is_default ? __('messages.common.yes') : __('messages.common.no') }}</strong></div>
                        <div class="list-group-item"><div class="text-muted small mb-1">{{ __('messages.common.notes') }}</div><div>{{ $profile->notes ?: '—' }}</div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button></div>
            </div>
        </div>
    </div>
@endforeach
