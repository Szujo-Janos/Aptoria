<style>
    html.aptoria-page-loading, html.aptoria-page-loading body { background: #ffffff; }
    #aptoria-white-screen {
        position: fixed;
        inset: 0;
        z-index: 2147483000;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        color: #1f2937;
        opacity: 1;
        visibility: visible;
        transition: opacity .28s ease, visibility .28s ease;
    }
    body.aptoria-ready #aptoria-white-screen {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    .aptoria-loader-card {
        width: min(360px, calc(100vw - 48px));
        text-align: center;
        transform: translateY(0);
        animation: aptoria-loader-rise .42s ease-out both;
    }
    .aptoria-loader-logo {
        height: 46px;
        object-fit: contain;
        margin-bottom: 18px;
    }
    .aptoria-loader-bars {
        display: inline-flex;
        align-items: end;
        gap: 5px;
        height: 34px;
        margin-bottom: 16px;
    }
    .aptoria-loader-bars span {
        display: block;
        width: 6px;
        border-radius: 999px;
        background: #334155;
        animation: aptoria-loader-wave .82s ease-in-out infinite;
    }
    .aptoria-loader-bars span:nth-child(1) { height: 16px; animation-delay: 0s; }
    .aptoria-loader-bars span:nth-child(2) { height: 24px; animation-delay: .08s; }
    .aptoria-loader-bars span:nth-child(3) { height: 31px; animation-delay: .16s; }
    .aptoria-loader-bars span:nth-child(4) { height: 22px; animation-delay: .24s; }
    .aptoria-loader-bars span:nth-child(5) { height: 14px; animation-delay: .32s; }
    .aptoria-loader-title { font-size: 16px; margin-bottom: 4px; }
    .aptoria-loader-subtitle { font-size: 12px; color: #64748b; }
    @keyframes aptoria-loader-wave { 0%, 100% { transform: scaleY(.62); opacity: .55; } 50% { transform: scaleY(1); opacity: 1; } }
    @keyframes aptoria-loader-rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    #aptoria-desktop-only-screen {
        position: fixed;
        inset: 0;
        z-index: 2147483001;
        display: none;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        color: #1f2937;
        padding: 24px;
        text-align: center;
    }
    .aptoria-desktop-only-card {
        width: min(430px, calc(100vw - 48px));
        animation: aptoria-loader-rise .42s ease-out both;
    }
    .aptoria-desktop-only-logo {
        height: 48px;
        object-fit: contain;
        margin-bottom: 18px;
    }
    .aptoria-desktop-only-icon {
        width: 44px;
        height: 44px;
        margin: 0 auto 16px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #0f172a;
    }
    .aptoria-desktop-only-title {
        font-size: 18px;
        font-weight: 500;
        color: #0f172a;
        margin-bottom: 8px;
    }
    .aptoria-desktop-only-text {
        font-size: 13px;
        color: #64748b;
        line-height: 1.55;
        margin: 0 auto;
        max-width: 360px;
    }
    .aptoria-desktop-only-minimum {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 16px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: 12px;
    }
    @media (max-width: 1399.98px) {
        html, body { overflow: hidden !important; }
        #aptoria-desktop-only-screen { display: flex; }
    }

</style>
<script>document.documentElement.classList.add('aptoria-page-loading');</script>
