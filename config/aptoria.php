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
    'default_admin' => [
        'name' => 'Aptoria Admin',
        'email' => 'admin@example.com',
        'password' => 'change-me-now',
    ],
];
