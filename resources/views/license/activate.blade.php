@extends('layouts.auth')

@section('title', __('messages.license.activation_title'))
@section('body_class', 'auth-bg d-flex align-items-center min-vh-100')

@php
    $fingerprints = $licenseStatus['fingerprints'] ?? [];
    $licenseValid = (bool) ($licenseStatus['valid'] ?? false);
    $publicKeyConfigured = (bool) ($licenseStatus['public_key_configured'] ?? false);
@endphp

@section('content')
<div class="row justify-content-center w-100">
    <div class="col-lg-8 col-xl-7">
        <div class="card border-0 shadow-lg overflow-hidden">
            <div class="card-body p-4 p-xl-5">
                <div class="d-flex align-items-start gap-3 mb-4">
                    <span class="avatar avatar-lg rounded text-bg-{{ $licenseValid ? 'success' : 'warning' }}">
                        <span class="avatar-title"><i data-lucide="{{ $licenseValid ? 'shield-check' : 'key-round' }}"></i></span>
                    </span>
                    <div class="min-w-0">
                        <span class="badge badge-soft-{{ $licenseStatus['tone'] ?? 'warning' }} badge-label mb-2">
                            <i data-lucide="shield" class="me-1"></i>{{ $licenseStatus['label'] ?? __('messages.common.not_available') }}
                        </span>
                        <h1 class="h3 mb-2">{{ __('messages.license.activation_title') }}</h1>
                        <p class="text-muted mb-0">{{ __('messages.license.simple_activation_copy') }}</p>
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">
                        <i data-lucide="check-circle" class="me-1"></i>{{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('license.activate.package') }}" enctype="multipart/form-data" data-aptoria-form-plugin>
                    @csrf
                    <div class="aptoria-simple-activation-drop mb-3">
                        <div class="d-flex align-items-start gap-3">
                            <span class="avatar rounded text-bg-primary">
                                <span class="avatar-title"><i data-lucide="package-open"></i></span>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <label class="form-label fw-semibold mb-1">{{ __('messages.license.simple_package_title') }}</label>
                                <p class="text-muted small mb-3">{{ __('messages.license.simple_package_help') }}</p>
                                <input type="file" name="activation_package" class="form-control form-control-lg @error('activation_package') is-invalid @enderror" accept="application/json,.json,application/zip,.zip">
                                @error('activation_package')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-grid d-sm-flex gap-2 align-items-center mb-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i data-lucide="shield-check" class="me-1"></i>{{ __('messages.license.simple_activate_button') }}
                        </button>
                        <a href="{{ route('license.request.download') }}" class="btn btn-light btn-lg">
                            <i data-lucide="download" class="me-1"></i>{{ __('messages.license.simple_request_button') }}
                        </a>
                    </div>
                </form>

                <div class="border rounded-3 p-3 mb-3 bg-light">
                    <div class="d-flex align-items-start gap-2">
                        <i data-lucide="info" class="text-primary mt-1"></i>
                        <div>
                            <div class="fw-semibold">{{ __('messages.license.simple_issuer_title') }}</div>
                            <div class="text-muted small">{{ __('messages.license.simple_issuer_copy') }}</div>
                        </div>
                    </div>
                </div>

                <details class="aptoria-license-details">
                    <summary class="text-muted small">{{ __('messages.license.simple_details_summary') }}</summary>
                    <div class="mt-3 vstack gap-3">
                        <div>
                            <div class="text-muted small">{{ __('messages.license.state') }}</div>
                            <div class="fw-semibold">{{ $licenseStatus['label'] ?? 'Invalid' }}</div>
                            <div class="text-muted small">{{ $licenseStatus['message'] ?? __('messages.license.status_unknown') }}</div>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('messages.license.public_key') }}</div>
                            <div class="fw-semibold">{{ $publicKeyConfigured ? __('messages.common.yes') : __('messages.common.no') }}</div>
                            <code class="small text-break d-block">{{ $licenseStatus['public_key_path'] ?? 'storage/app/license-public.pem' }}</code>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('messages.license.machine_fingerprint') }}</div>
                            <code class="small text-break d-block">{{ $fingerprints['machine']['value'] ?? '—' }}</code>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('messages.license.usb_fingerprint') }}</div>
                            <code class="small text-break d-block">{{ $fingerprints['usb']['value'] ?? '—' }}</code>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('license.status') }}" class="btn btn-sm btn-light">
                                <i data-lucide="file-json" class="me-1"></i>{{ __('messages.license.status_json') }}
                            </a>
                            <a href="{{ route('landing') }}" class="btn btn-sm btn-outline-secondary">
                                <i data-lucide="home" class="me-1"></i>{{ __('messages.setup.go_landing') }}
                            </a>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>
@endsection
