<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php($aptoriaAuthSettings = app(\App\Services\Settings\SettingService::class))
    @php($aptoriaEnableSweetalert = $aptoriaAuthSettings->boolean('ui.enable_sweetalert', true))
    <title>@yield('title', __('messages.auth.login_title')) | Aptoria</title>
    <link rel="icon" href="{{ asset('assets/aptoria/img/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/aptoria/img/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/aptoria/img/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/fontawesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/animate/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/toastr/css/toastr.min.css') }}">
    @if($aptoriaEnableSweetalert)
        <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/sweetalert/css/sweet-alert.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/iCheck/skins/square/green.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/css/static_custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria/css/app.css') }}?v={{ config('aptoria.version') }}">
</head>
<body class="blank aptoria-auth-layout aptoria-pro-auth">
    @if(session('success'))
        <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.success_title') }}" data-message="{{ session('success') }}" data-type="success"></div>
    @elseif(session('warning'))
        <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.needs_fix') }}" data-message="{{ session('warning') }}" data-type="warning"></div>
    @elseif(session('info'))
        <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.info_title') }}" data-message="{{ session('info') }}" data-type="info"></div>
    @elseif(session('error'))
        <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.needs_fix') }}" data-message="{{ session('error') }}" data-type="error"></div>
    @elseif($errors->any())
        <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.needs_fix') }}" data-message="{{ implode(' ', $errors->all()) }}" data-type="error"></div>
    @endif
    @yield('content')
    <script src="{{ asset('assets/aptoria-ui/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/vendor/iCheck/icheck.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/vendor/toastr/js/toastr.min.js') }}"></script>
    @if($aptoriaEnableSweetalert)
        <script src="{{ asset('assets/aptoria-ui/vendor/sweetalert/js/sweet-alert.min.js') }}"></script>
    @endif
    <script src="{{ asset('assets/aptoria-ui/js/aptoria-ui.js') }}"></script>
    <script src="{{ asset('assets/aptoria/js/app.js') }}?v={{ config('aptoria.version') }}"></script>
    @stack('scripts')
</body>
</html>
