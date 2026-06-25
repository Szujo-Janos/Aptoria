@extends('layouts.app')
@section('title', __('messages.license_issuer.title'))
@section('page_title', __('messages.license_issuer.title'))
@section('page_actions')
    <a href="{{ route('program-settings.license') }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    @if ($publicKeyExists)
        <a href="{{ route('program-settings.license-issuer.public-key.download') }}" class="btn btn-primary"><i data-lucide="download" class="me-1"></i>{{ __('messages.license_issuer.download_public_key') }}</a>
    @endif
@endsection

@section('content')
@php
    $result = session('issuer_result');
@endphp

@if ($errors->any())
    <div class="alert alert-danger d-flex gap-2 align-items-start">
        <i data-lucide="triangle-alert" class="mt-1"></i>
        <div>
            <strong>{{ __('messages.common.validation_error') }}</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if ($result)
    <div class="alert alert-{{ $result['tone'] ?? 'info' }} d-flex gap-2 align-items-start">
        <i data-lucide="{{ ($result['type'] ?? '') === 'verify' ? 'shield-check' : 'certificate' }}" class="mt-1"></i>
        <div>
            <strong>{{ $result['message'] ?? __('messages.common.ok') }}</strong>
            @if (($result['type'] ?? '') === 'keypair')
                <div class="small mt-1 text-break">{{ __('messages.license_issuer.private_key_path') }}: <code>{{ $result['private_path'] ?? '' }}</code></div>
                <div class="small text-break">{{ __('messages.license_issuer.public_key_path') }}: <code>{{ $result['public_path'] ?? '' }}</code></div>
            @elseif (($result['type'] ?? '') === 'verify')
                <div class="small mt-1 text-break">{{ __('messages.license.license_id') }}: <code>{{ $result['license_id'] ?? '—' }}</code></div>
                <div class="small text-break">{{ __('messages.license.subject') }}: {{ $result['subject'] ?? '—' }}</div>
                <div class="small text-break">{{ __('messages.license.binding_mode') }}: {{ $result['binding_mode'] ?? '—' }} / {{ __('messages.license.matched_binding') }}: {{ $result['binding_result'] ?? '—' }}</div>
            @endif
        </div>
    </div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header border-light justify-content-between align-items-start">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="certificate" class="me-1"></i>{{ __('messages.license_issuer.tool_panel_title') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.license_issuer.tool_panel_copy') }}</p>
                </div>
                <span class="badge badge-soft-warning badge-label"><i data-lucide="lock" class="me-1"></i>{{ __('messages.license_issuer.private_tool_badge') }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted text-uppercase mb-1">{{ __('messages.license_issuer.tool_folder') }}</div>
                            <div class="fw-semibold text-break"><code>tools/license-issuer</code></div>
                            <small class="text-muted">{{ __('messages.license_issuer.tool_folder_help') }}</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted text-uppercase mb-1">{{ __('messages.license_issuer.private_key') }}</div>
                            <div class="fw-semibold">{{ $privateKeyExists ? __('messages.common.yes') : __('messages.common.no') }}</div>
                            <small class="text-muted text-break"><code>{{ $privateKeyPath }}</code></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted text-uppercase mb-1">{{ __('messages.license_issuer.public_key') }}</div>
                            <div class="fw-semibold">{{ $publicKeyExists ? __('messages.common.yes') : __('messages.common.no') }}</div>
                            <small class="text-muted text-break"><code>{{ $publicKeyPath }}</code></small>
                        </div>
                    </div>
                </div>

                <div class="aptoria-form-section mt-3">
                    <div class="aptoria-form-section-title"><i data-lucide="shield-alert" class="me-1"></i>{{ __('messages.license_issuer.safety_title') }}</div>
                    <p class="text-muted mb-0 small">{{ __('messages.license_issuer.safety_copy') }}</p>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex flex-wrap gap-2 justify-content-end">
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#issuerKeypairModal"><i data-lucide="key-round" class="me-1"></i>{{ __('messages.license_issuer.generate_keypair') }}</button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issuerIssueModal"><i data-lucide="certificate" class="me-1"></i>{{ __('messages.license_issuer.issue_license') }}</button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#issuerVerifyModal"><i data-lucide="shield-check" class="me-1"></i>{{ __('messages.license_issuer.verify_license') }}</button>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header border-light">
                <h5 class="card-title mb-1"><i data-lucide="file-json" class="me-1"></i>{{ __('messages.license_issuer.current_request_title') }}</h5>
                <p class="text-muted mb-0 small">{{ __('messages.license_issuer.current_request_copy') }}</p>
            </div>
            <div class="card-body">
                <div class="aptoria-code-panel">
                    <pre class="mb-0 small text-break"><code>{{ json_encode($licenseRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header border-light">
                <h5 class="card-title mb-0"><i data-lucide="workflow" class="me-1"></i>{{ __('messages.license_issuer.workflow_title') }}</h5>
            </div>
            <div class="card-body">
                <ol class="small text-muted mb-0 ps-3">
                    <li>{{ __('messages.license_issuer.workflow_1') }}</li>
                    <li>{{ __('messages.license_issuer.workflow_2') }}</li>
                    <li>{{ __('messages.license_issuer.workflow_3') }}</li>
                    <li>{{ __('messages.license_issuer.workflow_4') }}</li>
                    <li>{{ __('messages.license_issuer.workflow_5') }}</li>
                </ol>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header border-light">
                <h5 class="card-title mb-0"><i data-lucide="folder-check" class="me-1"></i>{{ __('messages.license_issuer.path_title') }}</h5>
            </div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">{{ __('messages.license_issuer.keys_path') }}:</span><br><code class="text-break">{{ $keysPath }}</code></div>
                <div><span class="text-muted">{{ __('messages.license_issuer.out_path') }}:</span><br><code class="text-break">{{ $outPath }}</code></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade aptoria-scrollable-form-modal" id="issuerKeypairModal" tabindex="-1" aria-labelledby="issuerKeypairModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable aptoria-scrollable-form-dialog">
        <form method="POST" action="{{ route('program-settings.license-issuer.keypair') }}" class="modal-content aptoria-form-shell" data-aptoria-form-plugin>
            @csrf
            <div class="modal-header aptoria-scrollable-form-header">
                <div class="d-flex gap-3 align-items-start"><span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="key-round"></i></span></span><div><h5 class="modal-title mb-1" id="issuerKeypairModalLabel">{{ __('messages.license_issuer.generate_keypair') }}</h5><p class="text-muted small mb-0">{{ __('messages.license_issuer.generate_keypair_copy') }}</p></div></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.cancel') }}"></button>
            </div>
            <div class="modal-body aptoria-scrollable-form-body">
                <div class="aptoria-form-section">
                    <div class="aptoria-form-section-title"><i data-lucide="certificate" class="me-1"></i>{{ __('messages.license_issuer.key_identity') }}</div>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label">{{ __('messages.license_issuer.key_name') }}</label><input type="text" name="key_name" value="{{ old('key_name', 'aptoria-license') }}" class="form-control" placeholder="aptoria-license"><div class="form-text">{{ __('messages.license_issuer.key_name_help') }}</div></div>
                        <div class="col-md-4"><label class="form-label">{{ __('messages.license_issuer.key_bits') }}</label><select name="bits" class="form-select"><option value="2048" @selected(old('bits', '2048') === '2048')>2048</option><option value="3072" @selected(old('bits') === '3072')>3072</option><option value="4096" @selected(old('bits') === '4096')>4096</option></select><div class="form-text">{{ __('messages.license_issuer.key_bits_help') }}</div></div>
                        <div class="col-12"><label class="border rounded p-2 d-flex gap-2 align-items-start"><input type="checkbox" name="force" value="1" class="form-check-input mt-1"><span><strong>{{ __('messages.license_issuer.force_overwrite') }}</strong><br><small class="text-muted">{{ __('messages.license_issuer.force_overwrite_help') }}</small></span></label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer aptoria-scrollable-form-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button class="btn btn-primary"><i data-lucide="key-round" class="me-1"></i>{{ __('messages.license_issuer.generate_keypair') }}</button></div>
        </form>
    </div>
</div>

<div class="modal fade aptoria-scrollable-form-modal" id="issuerIssueModal" tabindex="-1" aria-labelledby="issuerIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable aptoria-scrollable-form-dialog">
        <form method="POST" action="{{ route('program-settings.license-issuer.issue') }}" enctype="multipart/form-data" class="modal-content aptoria-form-shell" data-aptoria-form-plugin>
            @csrf
            <div class="modal-header aptoria-scrollable-form-header">
                <div class="d-flex gap-3 align-items-start"><span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="certificate"></i></span></span><div><h5 class="modal-title mb-1" id="issuerIssueModalLabel">{{ __('messages.license_issuer.issue_license') }}</h5><p class="text-muted small mb-0">{{ __('messages.license_issuer.issue_license_copy') }}</p></div></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.cancel') }}"></button>
            </div>
            <div class="modal-body aptoria-scrollable-form-body">
                <div class="aptoria-form-section">
                    <div class="aptoria-form-section-title"><i data-lucide="file-json" class="me-1"></i>{{ __('messages.license_issuer.request_input') }}</div>
                    <div class="row g-3">
                        <div class="col-md-5"><label class="form-label">{{ __('messages.license_issuer.request_file') }}</label><input type="file" name="request_file" class="form-control" accept="application/json,.json"><div class="form-text">{{ __('messages.license_issuer.request_file_help') }}</div></div>
                        <div class="col-md-7"><label class="form-label">{{ __('messages.license_issuer.request_json') }}</label><textarea name="request_json" rows="5" class="form-control font-monospace" placeholder='{"request_format":"aptoria-license-request-v1",...}'>{{ old('request_json') }}</textarea><div class="form-text">{{ __('messages.license_issuer.request_json_help') }}</div></div>
                    </div>
                </div>
                <div class="aptoria-form-section mt-3">
                    <div class="aptoria-form-section-title"><i data-lucide="key-round" class="me-1"></i>{{ __('messages.license_issuer.private_key_input') }}</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">{{ __('messages.license_issuer.private_key_path') }}</label><input type="text" name="private_key_path" value="{{ old('private_key_path', 'tools/license-issuer/keys/aptoria-license-private.pem') }}" class="form-control" placeholder="tools/license-issuer/keys/aptoria-license-private.pem"><div class="form-text">{{ __('messages.license_issuer.private_key_path_help') }}</div></div>
                        <div class="col-md-6"><label class="form-label">{{ __('messages.license_issuer.private_key_file') }}</label><input type="file" name="private_key_file" class="form-control" accept=".pem,.key,text/plain"><div class="form-text">{{ __('messages.license_issuer.private_key_file_help') }}</div></div>
                        <div class="col-12"><label class="form-label">{{ __('messages.license_issuer.private_key_pem') }}</label><textarea name="private_key_pem" rows="4" class="form-control font-monospace" placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----">{{ old('private_key_pem') }}</textarea><div class="form-text">{{ __('messages.license_issuer.private_key_pem_help') }}</div></div>
                    </div>
                </div>
                <div class="aptoria-form-section mt-3">
                    <div class="aptoria-form-section-title"><i data-lucide="id-card" class="me-1"></i>{{ __('messages.license_issuer.license_identity') }}</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">{{ __('messages.license.license_id') }}</label><input type="text" name="license_id" value="{{ old('license_id') }}" class="form-control" placeholder="APT-20260620-ABC123"><div class="form-text">{{ __('messages.license_issuer.license_id_help') }}</div></div>
                        <div class="col-md-4"><label class="form-label">{{ __('messages.license.subject') }}</label><input type="text" name="subject" value="{{ old('subject', 'Customer Demo') }}" class="form-control" placeholder="Customer Demo"><div class="form-text">{{ __('messages.license_issuer.subject_help') }}</div></div>
                        <div class="col-md-4"><label class="form-label">{{ __('messages.license_issuer.issued_to') }}</label><input type="text" name="issued_to" value="{{ old('issued_to') }}" class="form-control" placeholder="customer@example.com"><div class="form-text">{{ __('messages.license_issuer.issued_to_help') }}</div></div>
                        <div class="col-md-3"><label class="form-label">{{ __('messages.license.edition') }}</label><select name="edition" class="form-select"><option value="portable">portable</option><option value="server">server</option><option value="trial">trial</option><option value="internal">internal</option></select><div class="form-text">{{ __('messages.license_issuer.edition_help') }}</div></div>
                        <div class="col-md-3"><label class="form-label">{{ __('messages.license.expires_at') }}</label><input type="text" name="expires" value="{{ old('expires', now()->addYear()->format('Y-m-d')) }}" class="form-control" placeholder="2027-06-20"><div class="form-text">{{ __('messages.license_issuer.expires_help') }}</div></div>
                        <div class="col-md-3"><label class="form-label">{{ __('messages.license.binding_mode') }}</label><select name="binding" class="form-select"><option value="machine_or_usb">machine_or_usb</option><option value="machine">machine</option><option value="usb">usb</option><option value="none">none</option></select><div class="form-text">{{ __('messages.license_issuer.binding_help') }}</div></div>
                        <div class="col-md-3"><label class="form-label">{{ __('messages.license_issuer.max_users') }}</label><input type="number" name="max_users" value="{{ old('max_users') }}" min="1" class="form-control" placeholder="5"><div class="form-text">{{ __('messages.license_issuer.max_users_help') }}</div></div>
                        <div class="col-md-6"><label class="form-label">{{ __('messages.license_issuer.issuer') }}</label><input type="text" name="issuer" value="{{ old('issuer', 'Aptoria License Issuer') }}" class="form-control" placeholder="Aptoria License Issuer"><div class="form-text">{{ __('messages.license_issuer.issuer_help') }}</div></div>
                        <div class="col-md-6"><label class="form-label">{{ __('messages.common.notes') }}</label><input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Portable customer handoff license"><div class="form-text">{{ __('messages.license_issuer.notes_help') }}</div></div>
                    </div>
                </div>
                <div class="aptoria-form-section mt-3">
                    <div class="aptoria-form-section-title"><i data-lucide="package-check" class="me-1"></i>{{ __('messages.license.features') }}</div>
                    <div class="row g-2">
                        @foreach ($defaultFeatures as $feature)
                            <div class="col-md-4"><label class="border rounded p-2 d-flex gap-2 align-items-center"><input type="checkbox" name="features[]" value="{{ $feature }}" class="form-check-input" checked><span class="small">{{ $feature }}</span></label></div>
                        @endforeach
                    </div>
                    <div class="form-text">{{ __('messages.license_issuer.features_help') }}</div>
                </div>
            </div>
            <div class="modal-footer aptoria-scrollable-form-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button class="btn btn-primary"><i data-lucide="certificate" class="me-1"></i>{{ __('messages.license_issuer.issue_and_download') }}</button></div>
        </form>
    </div>
</div>

<div class="modal fade aptoria-scrollable-form-modal" id="issuerVerifyModal" tabindex="-1" aria-labelledby="issuerVerifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable aptoria-scrollable-form-dialog">
        <form method="POST" action="{{ route('program-settings.license-issuer.verify') }}" enctype="multipart/form-data" class="modal-content aptoria-form-shell" data-aptoria-form-plugin>
            @csrf
            <div class="modal-header aptoria-scrollable-form-header">
                <div class="d-flex gap-3 align-items-start"><span class="avatar avatar-sm rounded text-bg-success"><span class="avatar-title"><i data-lucide="shield-check"></i></span></span><div><h5 class="modal-title mb-1" id="issuerVerifyModalLabel">{{ __('messages.license_issuer.verify_license') }}</h5><p class="text-muted small mb-0">{{ __('messages.license_issuer.verify_license_copy') }}</p></div></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.cancel') }}"></button>
            </div>
            <div class="modal-body aptoria-scrollable-form-body">
                <div class="aptoria-form-section">
                    <div class="aptoria-form-section-title"><i data-lucide="file-check" class="me-1"></i>{{ __('messages.license_issuer.license_input') }}</div>
                    <div class="row g-3">
                        <div class="col-md-5"><label class="form-label">{{ __('messages.license.license_file') }}</label><input type="file" name="license_file" class="form-control" accept="application/json,.json"><div class="form-text">{{ __('messages.license_issuer.license_file_help') }}</div></div>
                        <div class="col-md-7"><label class="form-label">{{ __('messages.license_issuer.license_json') }}</label><textarea name="license_json" rows="5" class="form-control font-monospace" placeholder='{"payload":{...},"signature":"..."}'>{{ old('license_json') }}</textarea><div class="form-text">{{ __('messages.license_issuer.license_json_help') }}</div></div>
                    </div>
                </div>
                <div class="aptoria-form-section mt-3">
                    <div class="aptoria-form-section-title"><i data-lucide="key-round" class="me-1"></i>{{ __('messages.license_issuer.public_key_input') }}</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">{{ __('messages.license_issuer.public_key_path') }}</label><input type="text" name="public_key_path" value="{{ old('public_key_path', 'tools/license-issuer/keys/aptoria-license-public.pem') }}" class="form-control" placeholder="tools/license-issuer/keys/aptoria-license-public.pem"><div class="form-text">{{ __('messages.license_issuer.public_key_path_help') }}</div></div>
                        <div class="col-md-6"><label class="form-label">{{ __('messages.license_issuer.public_key_file') }}</label><input type="file" name="public_key_file" class="form-control" accept=".pem,.key,text/plain"><div class="form-text">{{ __('messages.license_issuer.public_key_file_help') }}</div></div>
                        <div class="col-12"><label class="form-label">{{ __('messages.license_issuer.public_key_pem') }}</label><textarea name="public_key_pem" rows="4" class="form-control font-monospace" placeholder="-----BEGIN PUBLIC KEY-----&#10;...&#10;-----END PUBLIC KEY-----">{{ old('public_key_pem') }}</textarea><div class="form-text">{{ __('messages.license_issuer.public_key_pem_help') }}</div></div>
                    </div>
                </div>
                <div class="aptoria-form-section mt-3">
                    <div class="aptoria-form-section-title"><i data-lucide="fingerprint" class="me-1"></i>{{ __('messages.license_issuer.optional_request_input') }}</div>
                    <div class="row g-3">
                        <div class="col-md-5"><label class="form-label">{{ __('messages.license_issuer.request_file') }}</label><input type="file" name="request_file" class="form-control" accept="application/json,.json"><div class="form-text">{{ __('messages.license_issuer.verify_request_file_help') }}</div></div>
                        <div class="col-md-7"><label class="form-label">{{ __('messages.license_issuer.request_json') }}</label><textarea name="request_json" rows="4" class="form-control font-monospace" placeholder='{"request_format":"aptoria-license-request-v1",...}'>{{ old('request_json') }}</textarea><div class="form-text">{{ __('messages.license_issuer.verify_request_json_help') }}</div></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer aptoria-scrollable-form-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button class="btn btn-success"><i data-lucide="shield-check" class="me-1"></i>{{ __('messages.license_issuer.verify_license') }}</button></div>
        </form>
    </div>
</div>
@endsection
