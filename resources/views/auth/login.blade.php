@extends('layouts.auth')

@section('title', __('messages.auth.login_title'))

@section('content')
<div class="login-container">
    <div class="row">
        <div class="col-md-12">
            <div class="text-center m-b-md">
                <img src="{{ asset('assets/aptoria/img/aptoria-logo-horizontal.png') }}" alt="Aptoria" class="aptoria-login-logo">
                <small class="aptoria-login-tagline">{{ __('messages.app.tagline') }}</small>
                <div class="m-t-sm">
                    <div class="btn-group btn-group-xs">
                        @foreach(config('aptoria.supported_locales') as $localeCode => $localeName)
                            <a href="{{ route('language.switch', $localeCode) }}" class="btn {{ app()->getLocale() === $localeCode ? 'btn-primary' : 'btn-default' }}">{{ $localeName }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="hpanel">
                <div class="panel-body">
                    @if($errors->any())
                        <div class="alert alert-danger aptoria-login-error" role="alert">
                            <strong>{{ __('messages.common.needs_fix') }}</strong>
                            <ul class="m-b-none">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('login.attempt') }}">
                        @csrf
                        <div class="form-group">
                            <label class="control-label" for="email">{{ __('messages.auth.email') }}</label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', 'admin@example.com') }}" required autofocus>
                        </div>
                        <div class="form-group">
                            <label class="control-label" for="password">{{ __('messages.auth.password') }}</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="{{ __('messages.auth.password_placeholder') }}" required>
                        </div>
                        <div class="checkbox">
                            <label><input type="checkbox" name="remember" value="1"> {{ __('messages.auth.remember') }}</label>
                        </div>
                        <button class="btn btn-success btn-block" type="submit">{{ __('messages.auth.login_button') }}</button>
                        <p class="m-t text-muted small">{{ __('messages.auth.default_password_warning') }}</p>
                    </form>
                </div>
            </div>
            <div class="text-center text-muted small">v{{ config('aptoria.version') }} · © 2026 János Szujó</div>
        </div>
    </div>
</div>
@endsection
