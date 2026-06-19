<div class="modal fade" id="environmentCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('projects.environments.store', $project) }}" data-aptoria-form-scope="environment" data-aptoria-form-plugin>
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ __('messages.environments.new') }}</h5>
                    <p class="text-muted small mb-0">{{ __('messages.environments.form_help') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
            <div class="modal-body">
                @include('environments.partials.form', ['environment' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                <button class="btn btn-primary" type="submit"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
            </div>
        </form>
    </div>
</div>

@foreach ($environments as $environment)
    <div class="modal fade" id="environmentEditModal{{ $environment->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('projects.environments.update', [$project, $environment]) }}" data-aptoria-form-scope="environment" data-aptoria-form-plugin>
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ __('messages.environments.edit') }} · {{ $environment->name }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.environments.form_help') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    @include('environments.partials.form', ['environment' => $environment])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                    <button class="btn btn-primary" type="submit"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="environmentPreviewModal{{ $environment->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $environment->name }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.environments.preview_copy') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.environments.type') }}</span><span class="badge badge-soft-{{ $environment->tone }}">{{ $environment->type_label }}</span></div>
                        <div class="list-group-item"><div class="text-muted small mb-1">{{ __('messages.environments.base_url') }}</div><div class="text-break">{{ $environment->base_url }}</div></div>
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.common.default') }}</span><strong>{{ $environment->is_default ? __('messages.common.yes') : __('messages.common.no') }}</strong></div>
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.environments.production') }}</span><strong>{{ $environment->is_production ? __('messages.common.yes') : __('messages.common.no') }}</strong></div>
                        <div class="list-group-item"><div class="text-muted small mb-1">{{ __('messages.common.notes') }}</div><div>{{ $environment->notes ?: '—' }}</div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button></div>
            </div>
        </div>
    </div>
@endforeach
