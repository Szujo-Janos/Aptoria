@extends('layouts.auth')

@section('title', __('messages.auth.sign_in'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5 col-xl-4">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4">
                <div class="aptoria-auth-brand mb-4">
                    <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo aptoria-auth-logo mx-auto">
                    <p class="text-muted mt-3 mb-0">{{ __('messages.product.tagline') }}</p>
                </div>
                @if (config('aptoria.demo.mode'))
                    <div class="alert alert-info d-flex gap-2 align-items-start">
                        <i data-lucide="server-cog" class="mt-1"></i>
                        <div>
                            <strong>{{ __('messages.demo_mode.login_title') }}</strong>
                            <div class="small mt-1">{{ __('messages.demo_mode.login_copy') }}</div>
                            <div class="small mt-2"><code>{{ config('aptoria.demo.demo_user_email') }}</code> / <code>{{ config('aptoria.demo.demo_user_password') }}</code></div>
                        </div>
                    </div>
                @endif
                @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
                @if (session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
                @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('login.store') }}" class="vstack gap-3" data-aptoria-form-scope="login" data-aptoria-form-plugin>
                    @csrf
                    <div>
                        <label class="form-label">{{ __('messages.auth.email') }}</label>
                        <input class="form-control" name="email" type="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div>
                        <label class="form-label">{{ __('messages.auth.password') }}</label>
                        <input class="form-control" name="password" type="password" required>
                    </div>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="remember"> <span class="form-check-label">{{ __('messages.auth.remember') }}</span></label>
                    <button class="btn btn-primary w-100" type="submit">{{ __('messages.auth.sign_in') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
