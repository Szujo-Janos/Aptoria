<?php

return [
    'version' => trim((string) @file_get_contents(base_path('VERSION'))) ?: '0.0.6',
    'product_direction' => 'Evidence-first API QA and release decision platform.',
    'installed_lock_path' => storage_path('app/installed.lock'),
    'setup_token_path' => storage_path('app/setup-token.txt'),
    'default_locale' => 'en',
    'supported_locales' => ['en', 'hu'],
    'supported_locale_names' => [
        'en' => 'English',
        'hu' => 'Magyar',
    ],
    'ui_asset_path' => 'assets/aptoria-ui/assets',
    'security' => [
        'session_timeout_minutes' => (int) env('APTORIA_SESSION_TIMEOUT_MINUTES', 120),
        'csp_report_only' => (bool) env('APTORIA_CSP_REPORT_ONLY', true),
    ],
    'license' => [
        'required' => filter_var(env('APTORIA_LICENSE_REQUIRED', false), FILTER_VALIDATE_BOOL),
        'mode' => env('APTORIA_LICENSE_MODE', 'local_package'),
        'file_path' => env('APTORIA_LICENSE_FILE', storage_path('app/aptoria-license.json')),
        'public_key' => env('APTORIA_LICENSE_PUBLIC_KEY'),
        'public_key_path' => env('APTORIA_LICENSE_PUBLIC_KEY_PATH', storage_path('app/license-public.pem')),
        'authority' => [
            'url' => env('APTORIA_LICENSE_AUTHORITY_URL', ''),
            'lease_endpoint' => env('APTORIA_LICENSE_AUTHORITY_LEASE_ENDPOINT', '/api/license/runtime-lease'),
            'timeout_seconds' => (int) env('APTORIA_LICENSE_AUTHORITY_TIMEOUT_SECONDS', 8),
            'offline_grace_hours' => (int) env('APTORIA_LICENSE_OFFLINE_GRACE_HOURS', 72),
            'public_key' => env('APTORIA_LICENSE_AUTHORITY_PUBLIC_KEY'),
            'public_key_path' => env('APTORIA_LICENSE_AUTHORITY_PUBLIC_KEY_PATH', storage_path('app/license-authority-public.pem')),
            'lease_cache_path' => env('APTORIA_LICENSE_RUNTIME_LEASE_FILE', storage_path('app/license-runtime-lease.json')),
        ],
    ],
    'demo' => [
        'mode' => filter_var(env('APTORIA_DEMO_MODE', false), FILTER_VALIDATE_BOOL),
        'api_base_url' => rtrim((string) env('APTORIA_DEMO_API_BASE_URL', rtrim((string) env('APP_URL', 'http://127.0.0.1:8000'), '/').'/demo-api'), '/'),
        'auth_token' => env('APTORIA_DEMO_API_TOKEN', 'aptoria-demo-token'),
        'allowed_targets' => array_values(array_filter(array_map('trim', explode(',', (string) env('APTORIA_DEMO_ALLOWED_TARGETS', ''))))),
        'demo_user_email' => env('APTORIA_DEMO_USER_EMAIL', 'demo@aptoria.dev'),
        'demo_user_password' => env('APTORIA_DEMO_USER_PASSWORD', 'aptoria-demo-2026'),
    ],
    'default_admin' => [
        'name' => 'Aptoria Admin',
        'email' => 'admin@example.com',
        'password' => 'change-me-now',
    ],
];
