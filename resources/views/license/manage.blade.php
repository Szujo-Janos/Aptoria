@extends('layouts.app')
@section('title', __('messages.license.manage_title'))
@section('page_title', __('messages.license.manage_title'))

@section('page_actions')
    <a href="{{ route('program-settings.edit') }}" class="btn btn-light">
        <i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}
    </a>
@endsection

@section('content')
@php
    $features = $licenseStatus['features'] ?? [];
    $payload = $licenseStatus['payload'] ?? [];
    $isValid = (bool) ($licenseStatus['valid'] ?? false);
    $isEnforced = (bool) ($licenseStatus['enforced'] ?? false);
    $publicKeyConfigured = (bool) ($licenseStatus['public_key_configured'] ?? false);
    $onlineAuthority = $licenseStatus['online_authority'] ?? null;
    $licenseMode = $licenseStatus['license_mode'] ?? 'local_package';
    $onlineAuthorityEnabled = in_array($licenseMode, ['online_authority', 'hybrid'], true);
    $licenseModeLabel = match ($licenseMode) {
        'online_authority' => __('messages.license.license_mode_online_authority'),
        'hybrid' => __('messages.license.license_mode_hybrid'),
        default => __('messages.license.license_mode_local_package'),
    };
@endphp

@if (session('status'))
    <div class="alert alert-success">
        <i data-lucide="check-circle" class="me-1"></i>{{ session('status') }}
    </div>
@endif
@if (session('warning'))
    <div class="alert alert-warning">
        <i data-lucide="alert-triangle" class="me-1"></i>{{ session('warning') }}
    </div>
@endif
@if (session('license_online_status'))
    <div class="alert alert-{{ session('license_online_tone') === 'danger' ? 'danger' : (session('license_online_tone') === 'success' ? 'success' : 'warning') }}">
        <i data-lucide="cloud-check" class="me-1"></i>{{ session('license_online_status') }}
    </div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-license-management-hero">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">
                        <i data-lucide="{{ $isValid ? 'shield-check' : 'shield-alert' }}" class="me-1"></i>{{ __('messages.license.simple_management_title') }}
                    </h5>
                    <p class="text-muted mb-0 small">{{ __('messages.license.simple_management_copy') }}</p>
                </div>
                <span class="badge badge-soft-{{ $licenseStatus['tone'] ?? 'secondary' }} badge-label">
                    <i data-lucide="fingerprint" class="me-1"></i>{{ $licenseStatus['label'] ?? __('messages.common.not_available') }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="aptoria-license-mini-stat">
                            <small>{{ __('messages.license.state') }}</small>
                            <strong>{{ $licenseStatus['label'] ?? '—' }}</strong>
                            <span>{{ $licenseStatus['message'] ?? __('messages.license.status_unknown') }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="aptoria-license-mini-stat">
                            <small>{{ __('messages.license.enforcement') }}</small>
                            <strong>{{ $isEnforced ? __('messages.license.enforced') : __('messages.license.not_enforced') }}</strong>
                            <span>APTORIA_LICENSE_REQUIRED</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="aptoria-license-mini-stat">
                            <small>{{ __('messages.license.expires_at') }}</small>
                            <strong>{{ $licenseStatus['expires_at'] ?? '—' }}</strong>
                            <span>{{ __('messages.license.days_remaining') }}: {{ $licenseStatus['days_remaining'] ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="aptoria-license-mini-stat">
                            <small>{{ __('messages.license.public_key') }}</small>
                            <strong>{{ $publicKeyConfigured ? __('messages.common.yes') : __('messages.common.no') }}</strong>
                            <span>{{ __('messages.license.binding_mode') }}: {{ $licenseStatus['binding_mode'] ?? 'none' }}</span>
                        </div>
                    </div>
                    @if ($onlineAuthority)
                        <div class="col-md-3">
                            <div class="aptoria-license-mini-stat">
                                <small>{{ __('messages.license.authority_public_key') }}</small>
                                <strong>{{ ($onlineAuthority['authority_public_key_configured'] ?? false) ? __('messages.common.yes') : __('messages.common.no') }}</strong>
                                <span>{{ __('messages.license.license_mode') }}: {{ $licenseMode }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="aptoria-license-mini-stat">
                                <small>{{ __('messages.license.runtime_lease') }}</small>
                                <strong>{{ $onlineAuthority['label'] ?? '—' }}</strong>
                                <span>{{ ! empty($onlineAuthority['valid_until'] ?? null) ? $onlineAuthority['valid_until'] : __('messages.license.runtime_lease_missing') }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="aptoria-form-section mt-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-start gap-3">
                                <span class="avatar rounded {{ $onlineAuthorityEnabled ? 'text-bg-info' : 'text-bg-success' }}">
                                    <span class="avatar-title"><i data-lucide="{{ $onlineAuthorityEnabled ? 'cloud-check' : 'package-check' }}"></i></span>
                                </span>
                                <div>
                                    <div class="fw-semibold">
                                        {{ $onlineAuthorityEnabled ? __('messages.license.online_authority_title') : __('messages.license.activation_mode_title') }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $onlineAuthorityEnabled ? __('messages.license.online_authority_copy') : __('messages.license.activation_mode_local_copy') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <span class="badge badge-soft-{{ $onlineAuthorityEnabled ? ($onlineAuthority['tone'] ?? 'secondary') : 'success' }} badge-label">
                                {{ $onlineAuthorityEnabled ? ($onlineAuthority['label'] ?? $licenseModeLabel) : $licenseModeLabel }}
                            </span>
                        </div>
                    </div>
                    @if ($onlineAuthorityEnabled && $onlineAuthority)
                        <div class="small text-muted mt-2">
                            {{ $onlineAuthority['message'] ?? '' }}
                            @if (! empty($onlineAuthority['valid_until']))
                                <span class="ms-2">{{ __('messages.license.runtime_lease_valid_until') }}: <code>{{ $onlineAuthority['valid_until'] }}</code></span>
                            @endif
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('messages.license.authority_url') }}</div>
                                <code class="small text-break d-block">{{ $onlineAuthority['authority_url'] ?? '—' }}</code>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('messages.license.authority_public_key_path') }}</div>
                                <code class="small text-break d-block">{{ $onlineAuthority['authority_public_key_path'] ?? '—' }}</code>
                            </div>
                            @if (! empty($onlineAuthority['lease_id']))
                                <div class="col-md-6">
                                    <div class="text-muted small">Lease ID</div>
                                    <code class="small text-break d-block">{{ $onlineAuthority['lease_id'] }}</code>
                                </div>
                            @endif
                            @if (! empty($onlineAuthority['grace_until']))
                                <div class="col-md-6">
                                    <div class="text-muted small">Offline grace</div>
                                    <code class="small text-break d-block">{{ $onlineAuthority['grace_until'] }}</code>
                                </div>
                            @endif
                        </div>
                        @if (($onlineAuthority['enabled'] ?? false) && $isValid)
                            <form method="POST" action="{{ route('program-settings.license.authority.refresh') }}" class="mt-3" data-aptoria-form-plugin>
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light">
                                    <i data-lucide="refresh-cw" class="me-1"></i>{{ __('messages.license.runtime_lease_refresh') }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>


                <div class="aptoria-form-section mt-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-start gap-3">
                                <span class="avatar rounded text-bg-primary">
                                    <span class="avatar-title"><i data-lucide="download"></i></span>
                                </span>
                                <div>
                                    <div class="fw-semibold">{{ __('messages.license.simple_request_title') }}</div>
                                    <div class="text-muted small">{{ __('messages.license.simple_request_copy') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <a href="{{ route('license.request.download') }}" class="btn btn-primary">
                                <i data-lucide="download" class="me-1"></i>{{ __('messages.license.download_request') }}
                            </a>
                        </div>
                    </div>
                </div>

                <details class="aptoria-license-details mt-3">
                    <summary class="text-muted small">{{ __('messages.license.simple_details_summary') }}</summary>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <div class="small text-muted text-uppercase mb-1">{{ __('messages.license.license_id') }}</div>
                            <div class="fw-semibold text-break">{{ $licenseStatus['license_id'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted text-uppercase mb-1">{{ __('messages.license.subject') }}</div>
                            <div class="fw-semibold text-break">{{ $licenseStatus['subject'] ?? $licenseStatus['issued_to'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted text-uppercase mb-1">{{ __('messages.license.edition') }}</div>
                            <div class="fw-semibold text-break">{{ $licenseStatus['edition'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted text-uppercase mb-1">{{ __('messages.license.matched_binding') }}</div>
                            <div class="fw-semibold text-break">{{ $licenseStatus['matched_binding'] ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">{{ __('messages.license.machine_fingerprint') }}</div>
                            <code class="small text-break d-block">{{ $licenseStatus['fingerprints']['machine']['value'] ?? '—' }}</code>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">{{ __('messages.license.usb_fingerprint') }}</div>
                            <code class="small text-break d-block">{{ $licenseStatus['fingerprints']['usb']['value'] ?? '—' }}</code>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">{{ __('messages.license.license_file') }}</div>
                            <code class="small text-break d-block">{{ $licenseStatus['file_path'] ?? 'storage/app/aptoria-license.json' }}</code>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">{{ __('messages.license.public_key_path') }}</div>
                            <code class="small text-break d-block">{{ $licenseStatus['public_key_path'] ?? 'storage/app/license-public.pem' }}</code>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">{{ __('messages.license.license_mode') }}</div>
                            <code class="small text-break d-block">{{ $licenseModeLabel }}</code>
                        </div>
                        @if ($onlineAuthority)
                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('messages.license.authority_url') }}</div>
                                <code class="small text-break d-block">{{ $onlineAuthority['authority_url'] ?? '—' }}</code>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">{{ __('messages.license.runtime_lease_cache') }}</div>
                                <code class="small text-break d-block">{{ $onlineAuthority['lease_cache_path'] ?? '—' }}</code>
                            </div>
                        @endif
                    </div>

                    <div class="aptoria-form-section mt-3">
                        <div class="aptoria-form-section-title"><i data-lucide="sparkles" class="me-1"></i>{{ __('messages.license.features') }}</div>
                        @if ($features)
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($features as $feature)
                                    <span class="badge badge-soft-primary badge-label">{{ $feature }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0 small">{{ __('messages.license.no_features') }}</p>
                        @endif
                    </div>

                    <div class="aptoria-form-section mt-3">
                        <div class="aptoria-form-section-title"><i data-lucide="file-json" class="me-1"></i>{{ __('messages.license.request_title') }}</div>
                        <div class="aptoria-code-panel">
                            <pre class="mb-0 small text-break"><code>{{ json_encode($licenseRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    </div>
                </details>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.license.simple_management_footer') }}</div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header border-light">
                <h5 class="card-title mb-1">
                    <i data-lucide="package-open" class="me-1"></i>{{ __('messages.license.simple_package_title') }}
                </h5>
                <p class="text-muted small mb-0">{{ __('messages.license.simple_management_upload_copy') }}</p>
            </div>
            <form method="POST" action="{{ route('program-settings.license.package') }}" enctype="multipart/form-data" data-aptoria-form-plugin>
                @csrf
                <div class="card-body">
                    <div class="aptoria-simple-activation-drop">
                        <label class="form-label fw-semibold">{{ __('messages.license.simple_package_title') }}</label>
                        <input type="file" name="activation_package" class="form-control @error('activation_package') is-invalid @enderror" accept="application/json,.json,application/zip,.zip">
                        <div class="form-text">{{ __('messages.license.simple_package_help') }}</div>
                        @error('activation_package')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <strong>{{ __('messages.license.simple_issuer_title') }}</strong>
                        <div class="small mt-1">{{ __('messages.license.simple_issuer_copy') }}</div>
                    </div>
                </div>
                <div class="card-footer aptoria-card-footer-subtle d-grid">
                    <button type="submit" class="btn btn-success">
                        <i data-lucide="shield-check" class="me-1"></i>{{ __('messages.license.simple_management_upload_button') }}
                    </button>
                </div>
            </form>
        </div>

        <details class="card mt-3">
            <summary class="card-header border-light d-flex align-items-center justify-content-between aptoria-card-summary">
                <span class="card-title mb-0"><i data-lucide="settings-2" class="me-1"></i>{{ __('messages.license.advanced_title') }}</span>
                <span class="text-muted small">{{ __('messages.common.optional') }}</span>
            </summary>
            <div class="card-body">
                <p class="text-muted small">{{ __('messages.license.advanced_copy') }}</p>

                <form method="POST" action="{{ route('program-settings.license.upload') }}" enctype="multipart/form-data" data-aptoria-form-plugin class="mb-3">
                    @csrf
                    <label class="form-label">{{ __('messages.license.license_file') }}</label>
                    <input type="file" name="license_file" class="form-control @error('license_file') is-invalid @enderror" accept="application/json,.json">
                    <div class="form-text">{{ __('messages.license.upload_help') }}</div>
                    @error('license_file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-sm btn-light mt-2">{{ __('messages.license.upload_button') }}</button>
                </form>

                <form method="POST" action="{{ route('program-settings.license.public-key') }}" data-aptoria-form-plugin>
                    @csrf
                    <label class="form-label">{{ __('messages.license.public_key') }}</label>
                    <textarea name="public_key" rows="6" class="form-control font-monospace @error('public_key') is-invalid @enderror" placeholder="-----BEGIN PUBLIC KEY-----&#10;...&#10;-----END PUBLIC KEY-----">{{ old('public_key') }}</textarea>
                    <div class="form-text">{{ __('messages.license.public_key_help') }}</div>
                    @error('public_key')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-sm btn-light mt-2">{{ __('messages.license.save_public_key') }}</button>
                </form>
            </div>
        </details>
    </div>
</div>
@endsection
