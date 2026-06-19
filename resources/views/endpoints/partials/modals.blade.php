<div class="modal fade" id="endpointCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('projects.endpoints.store', $project) }}" data-aptoria-form-scope="endpoint" data-aptoria-form-plugin>
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ __('messages.endpoints.new') }}</h5>
                    <p class="text-muted small mb-0">{{ __('messages.endpoints.form_help') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
            <div class="modal-body">
                @include('endpoints.partials.form', ['endpoint' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                <button class="btn btn-primary" type="submit"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
            </div>
        </form>
    </div>
</div>

@foreach ($endpoints as $endpoint)
    <div class="modal fade" id="endpointEditModal{{ $endpoint->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('projects.endpoints.update', [$project, $endpoint]) }}" data-aptoria-form-scope="endpoint" data-aptoria-form-plugin>
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ __('messages.endpoints.edit') }} · {{ $endpoint->method }} {{ $endpoint->path }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.endpoints.form_help') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    @include('endpoints.partials.form', ['endpoint' => $endpoint])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                    <button class="btn btn-primary" type="submit"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="endpointPreviewModal{{ $endpoint->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title"><span class="badge text-bg-{{ $endpoint->method_tone }} me-2">{{ $endpoint->method }}</span>{{ $endpoint->path }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.endpoints.preview_copy') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.endpoints.name') }}</span><strong>{{ $endpoint->name ?: __('messages.endpoints.unnamed') }}</strong></div>
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.nav.environments') }}</span><strong>{{ $endpoint->environment?->name ?? __('messages.endpoints.default_target') }}</strong></div>
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.nav.auth_profiles') }}</span><strong>{{ $endpoint->authProfile?->name ?? __('messages.auth_profiles.no_auth_preview') }}</strong></div>
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.endpoints.risk') }}</span><span class="badge badge-soft-{{ $endpoint->risk_tone }}">{{ $endpoint->risk_label }}</span></div>
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.endpoints.expected_status') }}</span><strong>{{ $endpoint->expected_status ?: '—' }}</strong></div>
                        <div class="list-group-item"><div class="text-muted small mb-1">{{ __('messages.endpoints.description') }}</div><div>{{ $endpoint->description ?: '—' }}</div></div>
                        <div class="list-group-item"><div class="text-muted small mb-1">{{ __('messages.common.notes') }}</div><div>{{ $endpoint->notes ?: '—' }}</div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button></div>
            </div>
        </div>
    </div>
@endforeach
