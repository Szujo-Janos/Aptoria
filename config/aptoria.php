<?php

$aptoriaVersionFile = base_path('VERSION');
$aptoriaVersion = is_file($aptoriaVersionFile) ? trim((string) file_get_contents($aptoriaVersionFile)) : '1.0.74';

return [
    'version' => $aptoriaVersion !== '' ? $aptoriaVersion : '1.0.74',
    'product_name' => 'Aptoria',
    'positioning' => 'Self-hosted API QA workflow, evidence and release gate platform.',
    'default_locale' => env('APTORIA_DEFAULT_LOCALE', 'en'),
    'default_admin' => [
        'email' => env('APTORIA_DEFAULT_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('APTORIA_DEFAULT_ADMIN_PASSWORD', 'change-me-now'),
    ],
    'supported_locales' => [
        'en' => 'English',
        'hu' => 'Magyar',
    ],
    'safe_mode_default' => true,
    'default_timeout_seconds' => 10,
    'default_connect_timeout_seconds' => 5,
    'max_redirects' => 3,
    'slow_response_threshold_ms' => 3000,
    'max_response_preview_bytes' => 32768,
    'private_network_scan_default' => false,
    'destructive_methods_enabled' => false,
    'setup_token_min_length' => (int) env('APTORIA_SETUP_TOKEN_MIN_LENGTH', 32),
    'setup_token_placeholder_values' => [
        'change-this-long-random-setup-token',
        'replace-with-64-character-random-token',
        'your-setup-token',
        'changeme',
    ],
    'security_headers' => [
        'hsts_max_age' => (int) env('APTORIA_HSTS_MAX_AGE', 31536000),
        'hsts_include_subdomains' => (bool) env('APTORIA_HSTS_INCLUDE_SUBDOMAINS', true),
        'hsts_preload' => (bool) env('APTORIA_HSTS_PRELOAD', false),
        'enable_cache_control_for_sensitive_pages' => (bool) env('APTORIA_SENSITIVE_PAGE_NO_STORE', true),
        'content_security_policy_report_only' => (bool) env('APTORIA_CSP_REPORT_ONLY', true),
    ],
];
