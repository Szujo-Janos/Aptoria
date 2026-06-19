<div id="aptoria-white-screen" aria-hidden="true">
    <div class="aptoria-loader-card">
        <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-loader-logo">
        <div class="aptoria-loader-bars"><span></span><span></span><span></span><span></span><span></span></div>
        <div class="aptoria-loader-title">Aptoria</div>
        <div class="aptoria-loader-subtitle">{{ __('messages.setup.loader') }}</div>
    </div>
</div>

<div id="aptoria-desktop-only-screen" role="alert" aria-live="assertive">
    <div class="aptoria-desktop-only-card">
        <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-desktop-only-logo">
        <div class="aptoria-desktop-only-icon" aria-hidden="true"><i data-lucide="monitor-check"></i></div>
        <div class="aptoria-desktop-only-title">{{ __('messages.desktop_only.title') }}</div>
        <p class="aptoria-desktop-only-text">{{ __('messages.desktop_only.message') }}</p>
        <div class="aptoria-desktop-only-minimum">{{ __('messages.desktop_only.minimum') }}</div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        window.setTimeout(function () {
            document.body.classList.add('aptoria-ready');
            document.documentElement.classList.remove('aptoria-page-loading');
        }, 220);
    });
</script>
