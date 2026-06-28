<?php

return [
    'issuer' => 'license.aptoria.dev',
    'authority_url' => 'https://license.aptoria.dev',
    'lease_endpoint' => '/api/license/runtime-lease',
    'enabled' => true,

    // Host separation for shared admin/license deployments.
    // admin hosts expose only the internal issuer UI.
    // api hosts expose only the machine-readable license authority API.
    'admin_hosts' => ['admin.aptoria.dev'],
    'api_hosts' => ['license.aptoria.dev'],

    // Create a password hash with: php -r "echo password_hash('change-this-password', PASSWORD_DEFAULT), PHP_EOL;"
    'admin_password_hash' => '',

    'storage_path' => __DIR__.'/storage',
    'key_bits' => 4096,
    'openssl_config_path' => '',
    'lease_minutes' => 60,

    // License authority hardening defaults.
    // Keep enabled on public license.aptoria.dev deployments.
    'max_request_bytes' => 32768,
    'require_json_content_type' => true,
    'rate_limit_enabled' => true,
    'rate_limit_window_seconds' => 60,
    'rate_limit_max_requests' => 60,

    // Leave false unless the server is behind a trusted proxy/CDN that sets X-Forwarded-For correctly.
    'trust_proxy_headers' => false,
];
