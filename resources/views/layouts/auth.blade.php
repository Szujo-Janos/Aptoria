@php
    $tablerFontInstalled = file_exists(public_path('assets/aptoria-ui/assets/fonts/tabler/tabler-icons8aff.woff2'));
    $defaultTitle = $appName ?? config('app.name', 'Aptoria');
    $seoTitle = trim($__env->yieldContent('title', $defaultTitle));
    $seoDescription = trim($__env->yieldContent('meta_description', 'Aptoria is a self-hosted QA workspace for API evidence, Postman and Newman imports, finding triage, release gates and audit-ready reports.'));
    $seoRobots = trim($__env->yieldContent('robots', 'noindex,nofollow,noarchive'));
    $seoCanonical = trim($__env->yieldContent('canonical_url', url()->current()));
    $seoOgType = trim($__env->yieldContent('og_type', 'website'));
    $seoOgTitle = trim($__env->yieldContent('og_title', $seoTitle));
    $seoOgDescription = trim($__env->yieldContent('og_description', $seoDescription));
    $seoOgUrl = trim($__env->yieldContent('og_url', $seoCanonical));
    $seoOgImage = trim($__env->yieldContent('og_image', asset('assets/aptoria-ui/assets/images/og-aptoria.png')));
    $seoTwitterTitle = trim($__env->yieldContent('twitter_title', $seoOgTitle));
    $seoTwitterDescription = trim($__env->yieldContent('twitter_description', $seoOgDescription));
    $seoTwitterImage = trim($__env->yieldContent('twitter_image', $seoOgImage));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $tablerFontInstalled ? 'aptoria-tabler-fonts-enabled' : 'aptoria-tabler-fonts-disabled' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:type" content="{{ $seoOgType }}">
    <meta property="og:site_name" content="Aptoria">
    <meta property="og:title" content="{{ $seoOgTitle }}">
    <meta property="og:description" content="{{ $seoOgDescription }}">
    <meta property="og:url" content="{{ $seoOgUrl }}">
    <meta property="og:image" content="{{ $seoOgImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Aptoria API QA Evidence and Release Readiness">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTwitterTitle }}">
    <meta name="twitter:description" content="{{ $seoTwitterDescription }}">
    <meta name="twitter:image" content="{{ $seoTwitterImage }}">

    @stack('head')
    <link rel="shortcut icon" href="{{ asset('assets/aptoria-ui/assets/images/favicon.ico') }}">
    <script src="{{ asset('assets/aptoria-ui/assets/js/config.js') }}"></script>
    <link href="{{ asset('assets/aptoria-ui/assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/aptoria-ui/assets/css/app.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/aptoria-ui/assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/aptoria-ui/assets/css/aptoria.css') }}?v={{ config('aptoria.version') }}" rel="stylesheet" type="text/css">
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
    @include('partials.cookie-consent')
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
