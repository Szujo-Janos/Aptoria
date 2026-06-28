@php
    $tablerFontInstalled = file_exists(public_path('assets/aptoria-ui/assets/fonts/tabler/tabler-icons8aff.woff2'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $tablerFontInstalled ? 'aptoria-tabler-fonts-enabled' : 'aptoria-tabler-fonts-disabled' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aptoria evidence-first API QA and release decision platform.">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title', $appName)</title>
    <link rel="shortcut icon" href="{{ asset('assets/aptoria-ui/assets/images/favicon.ico') }}">
    <script src="{{ asset('assets/aptoria-ui/assets/js/config.js') }}"></script>
    <link href="{{ asset('assets/aptoria-ui/assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/aptoria-ui/assets/css/app.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/aptoria-ui/assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/aptoria-ui/assets/plugins/datatables/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css">
    @stack('styles')
    <link href="{{ asset('assets/aptoria-ui/assets/css/aptoria.css') }}?v={{ config('aptoria.version') }}" rel="stylesheet" type="text/css">
    @if ($tablerFontInstalled)
        <link href="{{ asset('assets/aptoria-ui/assets/css/aptoria-tabler-icons.css') }}" rel="stylesheet" type="text/css">
    @endif
    @include('partials.white-screen-head')
</head>
<body class="{{ $tablerFontInstalled ? 'aptoria-tabler-fonts-enabled' : 'aptoria-tabler-fonts-disabled' }}">
    @include('partials.white-screen-loader')

    <div class="toast-container position-fixed top-0 end-0 p-3 aptoria-toast-container">
        @if (session('status'))
            <div class="toast align-items-center text-bg-success border-0 aptoria-session-toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4500">
                <div class="d-flex">
                    <div class="toast-body"><i data-lucide="check-circle" class="me-1"></i>{{ session('status') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
            </div>
        @endif
    </div>

    <div class="wrapper">
        @include('partials.sidebar')
        @include('partials.topbar')

        <div class="content-page">
            <div class="container-fluid">
                @if (($currentWorkspaceMode ?? 'live') === 'sandbox')
                    <div class="aptoria-sandbox-safety-strip mt-3 d-flex align-items-start justify-content-between gap-3">
                        <div class="d-flex gap-2 align-items-start">
                            <i data-lucide="flask-conical" class="mt-1"></i>
                            <div>
                                <strong>{{ __('messages.workspace_mode.sandbox_banner_title') }}</strong>
                                <div class="small">{{ __('messages.workspace_mode.sandbox_banner_copy') }}</div>
                            </div>
                        </div>
                    </div>
                @elseif (config('aptoria.demo.mode'))
                    <div class="alert alert-info mt-3 d-flex align-items-start justify-content-between gap-3 aptoria-demo-mode-banner">
                        <div class="d-flex gap-2 align-items-start">
                            <i data-lucide="shield-check" class="mt-1"></i>
                            <div>
                                <strong>{{ __('messages.demo_guide.demo_mode_enabled') }}</strong>
                                <div class="small">{{ __('messages.demo_guide.demo_mode_copy') }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger mt-3">
                        <strong>{{ __('messages.common.validation_error') }}</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="page-title-head d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1 fw-semibold">@yield('page_title', __('messages.nav.dashboard'))</h4>
                        <p class="text-muted mb-0 small">{{ __('messages.product.positioning') }}</p>
                    </div>
                    @hasSection('page_actions')
                        <div class="d-flex align-items-center gap-2">
                            @yield('page_actions')
                        </div>
                    @endif
                </div>

                @yield('content')
            </div>

            <footer class="footer aptoria-app-footer">
                <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap gap-2 small text-muted">
                    <span>Copyright © 2026 Aptoria. All rights reserved.</span>
                    <span>v{{ $aptoriaVersion }} · Evidence-first API QA</span>
                </div>
            </footer>
        </div>
    </div>

    <script src="{{ asset('assets/aptoria-ui/assets/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/plugins/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/plugins/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/plugins/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/plugins/datatables/responsive.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/js/aptoria-icons.js') }}"></script>
    <script>window.AptoriaFormPlugin = @json(trans('messages.form_plugin'));</script>
    <script src="{{ asset('assets/aptoria-ui/assets/js/aptoria-forms.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/js/aptoria-tables.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/js/aptoria-app-guard.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.aptoria-session-toast').forEach(function (toastEl) {
                if (window.bootstrap && bootstrap.Toast) { new bootstrap.Toast(toastEl).show(); }
            });

            document.querySelectorAll('form[data-aptoria-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.confirmed === '1') { return; }
                    event.preventDefault();
                    var mode = form.dataset.aptoriaConfirm || 'warning';
                    var title = form.dataset.confirmTitle || '{{ __('messages.projects.delete_title') }}';
                    var text = form.dataset.confirmText || '{{ __('messages.projects.delete_text') }}';
                    var button = form.dataset.confirmButton || '{{ __('messages.projects.delete_confirm_button') }}';
                    var isDelete = mode === 'delete';
                    if (window.Swal) {
                        Swal.fire({
                            title: title,
                            text: text,
                            icon: isDelete ? 'warning' : 'question',
                            showCancelButton: true,
                            confirmButtonText: button,
                            cancelButtonText: '{{ __('messages.common.cancel') }}',
                            customClass: { confirmButton: 'btn ' + (isDelete ? 'btn-danger' : 'btn-primary') + ' me-2', cancelButton: 'btn btn-light' },
                            buttonsStyling: false
                        }).then(function (result) {
                            if (result.isConfirmed) { form.dataset.confirmed = '1'; form.submit(); }
                        });
                    } else if (confirm(text)) {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
