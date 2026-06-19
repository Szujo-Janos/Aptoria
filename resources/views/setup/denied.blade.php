@extends('layouts.auth')

@section('title', __('messages.setup.access_denied_title'))
@section('body_class', 'auth-bg aptoria-setup-page min-vh-100')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-xl-7 col-lg-9">
        <div class="card border-0 shadow-lg overflow-hidden aptoria-animate-in">
            <div class="card-header bg-warning-subtle border-0 py-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar-md rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center">
                        <i data-lucide="shield-alert"></i>
                    </span>
                    <div>
                        <h1 class="h4 mb-1">{{ __('messages.setup.access_denied_title') }}</h1>
                        <p class="text-muted mb-0">{{ __('messages.setup.access_denied_copy') }}</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4 p-lg-5">
                <div class="alert alert-warning d-flex gap-2" role="alert">
                    <i data-lucide="key-round" class="flex-shrink-0"></i>
                    <div>
                        <strong>{{ __('messages.setup.token_required') }}</strong><br>
                        <span>{{ __('messages.setup.token_location') }}:</span>
                        <code>{{ $access['token_path'] }}</code>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">{{ __('messages.setup.token_hint') }}</div>
                            <div class="fs-5">{{ $access['token_hint'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">{{ __('messages.setup.remote_mode') }}</div>
                            <div class="fs-5">{{ __('messages.setup.protected') }}</div>
                        </div>
                    </div>
                </div>
                <p class="text-muted mt-4 mb-0">{{ __('messages.setup.token_usage') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
