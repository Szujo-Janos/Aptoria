@extends('layouts.auth')

@section('title', 'Aptoria')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card border-0 shadow-lg overflow-hidden">
            <div class="row g-0">
                <div class="col-lg-6 p-5 bg-primary text-white">
                    <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo aptoria-auth-logo aptoria-brand-logo-on-dark mb-4">
                    <h1 class="display-6 fw-semibold mb-3">{{ __('messages.product.headline') }}</h1>
                    <p class="lead opacity-75">{{ __('messages.product.landing_copy') }}</p>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <span class="badge text-bg-light text-primary">{{ __('messages.product.landing_badge_evidence') }}</span>
                        <span class="badge text-bg-light text-primary">{{ __('messages.product.landing_badge_safe_scan') }}</span>
                        <span class="badge text-bg-light text-primary">{{ __('messages.product.landing_badge_release_gate') }}</span>
                        <span class="badge text-bg-light text-primary">{{ __('messages.product.landing_badge_audit_trail') }}</span>
                    </div>
                </div>
                <div class="col-lg-6 p-5">
                    <h2 class="h4 mb-3">{{ __('messages.product.not_a_clone') }}</h2>
                    <p class="text-muted">{{ __('messages.product.not_a_clone_copy') }}</p>
                    <ul class="list-unstyled vstack gap-3 my-4">
                        <li class="d-flex gap-2"><i data-lucide="check-circle" class="text-success"></i><span>{{ __('messages.product.value_1') }}</span></li>
                        <li class="d-flex gap-2"><i data-lucide="check-circle" class="text-success"></i><span>{{ __('messages.product.value_2') }}</span></li>
                        <li class="d-flex gap-2"><i data-lucide="check-circle" class="text-success"></i><span>{{ __('messages.product.value_3') }}</span></li>
                    </ul>
                    <a href="{{ route('login') }}" class="btn btn-primary">{{ __('messages.auth.sign_in') }}</a>
                    <a href="{{ route('setup.index') }}" class="btn btn-light ms-2">{{ __('messages.setup.open_setup') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
