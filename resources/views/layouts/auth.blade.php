@php
    $tablerFontInstalled = file_exists(public_path('assets/aptoria-ui/assets/fonts/tabler/tabler-icons8aff.woff2'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $tablerFontInstalled ? 'aptoria-tabler-fonts-enabled' : 'aptoria-tabler-fonts-disabled' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $appName)</title>
    <link rel="shortcut icon" href="{{ asset('assets/aptoria-ui/assets/images/favicon.ico') }}">
    <script src="{{ asset('assets/aptoria-ui/assets/js/config.js') }}"></script>
    <link href="{{ asset('assets/aptoria-ui/assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/aptoria-ui/assets/css/app.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/aptoria-ui/assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/aptoria-ui/assets/css/aptoria.css') }}" rel="stylesheet" type="text/css">
    @if ($tablerFontInstalled)
        <link href="{{ asset('assets/aptoria-ui/assets/css/aptoria-tabler-icons.css') }}" rel="stylesheet" type="text/css">
    @endif
    @include('partials.white-screen-head')
</head>
<body class="@yield('body_class', 'auth-bg d-flex align-items-center min-vh-100') {{ $tablerFontInstalled ? 'aptoria-tabler-fonts-enabled' : 'aptoria-tabler-fonts-disabled' }}">
    @include('partials.white-screen-loader')
    <div class="container">
        @yield('content')
    </div>
    <script src="{{ asset('assets/aptoria-ui/assets/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/js/aptoria-icons.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>window.AptoriaFormPlugin = @json(trans('messages.form_plugin'));</script>
    <script src="{{ asset('assets/aptoria-ui/assets/js/aptoria-forms.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/js/aptoria-app-guard.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
