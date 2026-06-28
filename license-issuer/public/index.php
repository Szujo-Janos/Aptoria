<?php

declare(strict_types=1);

$config = require __DIR__.'/../config.example.php';
if (is_file(__DIR__.'/../config.php')) {
    $local = require __DIR__.'/../config.php';
    if (is_array($local)) {
        $config = array_replace($config, $local);
    }
}

$storagePath = rtrim((string) ($config['storage_path'] ?? (__DIR__.'/../storage')), '/\\');
if ($storagePath === '') {
    $storagePath = __DIR__.'/../storage';
}
if (! is_dir($storagePath)) {
    @mkdir($storagePath, 0775, true);
}

$privateKeyPath = $storagePath.'/license-authority-private.pem';
$publicKeyPath = $storagePath.'/license-authority-public.pem';
$registryPath = $storagePath.'/license-authority-registry.json';
$uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if (! str_starts_with($uri, '/api/license/')) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    exit;
}

function request_id(): string
{
    $incoming = (string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? '');
    $clean = preg_replace('/[^A-Za-z0-9._:-]/', '', $incoming) ?: '';

    return $clean !== '' && strlen($clean) <= 80 ? $clean : 'req_'.bin2hex(random_bytes(12));
}

function client_ip(array $config): string
{
    if ((bool) ($config['trust_proxy_headers'] ?? false)) {
        $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwarded !== '') {
            $parts = array_map('trim', explode(',', $forwarded));
            if (($parts[0] ?? '') !== '') {
                return $parts[0];
            }
        }
    }

    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function append_json_log(string $storagePath, string $filename, array $entry): void
{
    $dir = rtrim($storagePath, '/\\').'/logs';
    if (! is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $entry['logged_at'] = gmdate('c');
    @file_put_contents(
        $dir.'/'.$filename,
        json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
        FILE_APPEND | LOCK_EX
    );
}

function authority_error(string $code, int $status, string $storagePath, array $context = []): never
{
    $rid = request_id();
    append_json_log($storagePath, 'authority-abuse.jsonl', array_replace([
        'request_id' => $rid,
        'event' => 'api_rejected',
        'code' => $code,
        'http_status' => $status,
        'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
        'uri' => parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/',
    ], $context));

    json_response([
        'error' => $code,
        'request_id' => $rid,
    ], $status);
}

function request_content_length(): int
{
    $length = (string) ($_SERVER['CONTENT_LENGTH'] ?? '0');

    return ctype_digit($length) ? (int) $length : 0;
}

function enforce_api_request_basics(array $config, string $storagePath, string $uri, string $method): void
{
    if (! str_starts_with($uri, '/api/license/')) {
        return;
    }

    $maxBytes = max(1024, (int) ($config['max_request_bytes'] ?? 32768));
    if (request_content_length() > $maxBytes) {
        authority_error('request_too_large', 413, $storagePath, ['content_length' => request_content_length()]);
    }

    if ($method === 'POST' && (bool) ($config['require_json_content_type'] ?? true)) {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
        if (! str_contains($contentType, 'application/json')) {
            authority_error('unsupported_media_type', 415, $storagePath, ['content_type' => $contentType]);
        }
    }
}

function rate_limit_key(array $config, string $uri): string
{
    return hash('sha256', client_ip($config).'|'.$uri);
}

function enforce_rate_limit(array $config, string $storagePath, string $uri): void
{
    if (! str_starts_with($uri, '/api/license/')) {
        return;
    }
    if (! (bool) ($config['rate_limit_enabled'] ?? true)) {
        return;
    }

    $window = max(10, (int) ($config['rate_limit_window_seconds'] ?? 60));
    $limit = max(1, (int) ($config['rate_limit_max_requests'] ?? 60));
    $dir = rtrim($storagePath, '/\\').'/rate-limit';
    if (! is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $file = $dir.'/'.rate_limit_key($config, $uri).'.json';
    $now = time();
    $state = ['window_started_at' => $now, 'count' => 0];
    if (is_file($file)) {
        $decoded = json_decode((string) @file_get_contents($file), true);
        if (is_array($decoded)) {
            $state = array_replace($state, $decoded);
        }
    }

    if (($now - (int) ($state['window_started_at'] ?? $now)) >= $window) {
        $state = ['window_started_at' => $now, 'count' => 0];
    }

    $state['count'] = (int) ($state['count'] ?? 0) + 1;
    @file_put_contents($file, json_encode($state, JSON_UNESCAPED_SLASHES), LOCK_EX);

    if ($state['count'] > $limit) {
        authority_error('rate_limited', 429, $storagePath, [
            'client' => hash('sha256', client_ip($config)),
            'window_seconds' => $window,
            'limit' => $limit,
        ]);
    }
}

function decode_json_body(string $storagePath): array
{
    $raw = (string) file_get_contents('php://input');
    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        authority_error('invalid_json', 422, $storagePath);
    }

    if (! is_array($decoded)) {
        authority_error('invalid_request', 422, $storagePath);
    }

    return $decoded;
}

function validate_runtime_lease_request(array $request, string $storagePath): void
{
    if (($request['request_format'] ?? '') !== 'aptoria-runtime-lease-request-v1') {
        authority_error('invalid_request_format', 422, $storagePath);
    }
    if (($request['product'] ?? '') !== 'aptoria') {
        authority_error('invalid_product', 422, $storagePath);
    }

    $licenseId = trim((string) ($request['license']['license_id'] ?? ''));
    if ($licenseId === '' || strlen($licenseId) > 80 || ! preg_match('/^[A-Za-z0-9._:-]+$/', $licenseId)) {
        authority_error('invalid_license_id', 422, $storagePath);
    }

    $installId = trim((string) ($request['install_id'] ?? ''));
    if ($installId !== '' && strlen($installId) > 160) {
        authority_error('invalid_install_id', 422, $storagePath);
    }

    $fingerprints = $request['fingerprints'] ?? [];
    if (! is_array($fingerprints)) {
        authority_error('invalid_fingerprints', 422, $storagePath);
    }

    foreach (['machine', 'usb'] as $key) {
        $value = $fingerprints[$key] ?? null;
        if ($value !== null && (! is_string($value) || strlen($value) > 160)) {
            authority_error('invalid_fingerprints', 422, $storagePath, ['field' => $key]);
        }
    }
}

function request_summary(array $request): array
{
    return [
        'request_format' => (string) ($request['request_format'] ?? ''),
        'product' => (string) ($request['product'] ?? ''),
        'app_version' => (string) ($request['app_version'] ?? ''),
        'license_id' => (string) ($request['license']['license_id'] ?? ''),
        'install_id_hash' => 'sha256:'.hash('sha256', (string) ($request['install_id'] ?? '')),
        'machine_fingerprint_hash' => 'sha256:'.hash('sha256', normalize_fingerprint((string) ($request['fingerprints']['machine'] ?? ''))),
        'usb_fingerprint_hash' => 'sha256:'.hash('sha256', normalize_fingerprint((string) ($request['fingerprints']['usb'] ?? ''))),
    ];
}

function api_status_code_for_decision(string $decision): int
{
    return match ($decision) {
        'valid' => 200,
        'license_expired', 'license_revoked', 'license_disabled', 'license_suspended', 'fingerprint_mismatch' => 200,
        default => 200,
    };
}

function request_host(): string
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

    return preg_replace('/:\d+$/', '', $host) ?: $host;
}

function configured_hosts(array $config, string $key): array
{
    $value = $config[$key] ?? [];
    if (is_string($value)) {
        $value = explode(',', $value);
    }
    if (! is_array($value)) {
        return [];
    }

    return array_values(array_filter(array_map(
        fn ($host) => strtolower(trim((string) $host)),
        $value
    )));
}

function host_matches(array $hosts): bool
{
    $host = request_host();

    return $host !== '' && in_array($host, $hosts, true);
}

function enforce_host_boundary(array $config, string $uri): void
{
    $adminHosts = configured_hosts($config, 'admin_hosts');
    $apiHosts = configured_hosts($config, 'api_hosts');
    $isApiHost = host_matches($apiHosts);
    $isAdminHost = host_matches($adminHosts);

    if ($isApiHost && ! str_starts_with($uri, '/api/license/')) {
        json_response(['error' => 'not_found'], 404);
    }

    if ($isAdminHost && str_starts_with($uri, '/api/license/')) {
        json_response(['error' => 'not_found'], 404);
    }
}

function redirect_to(string $path): never
{
    header('Location: '.$path);
    exit;
}

function admin_configured(array $config): bool
{
    return trim((string) ($config['admin_password_hash'] ?? '')) !== '';
}

function admin_logged_in(): bool
{
    return ! empty($_SESSION['aptoria_license_issuer_admin']);
}

function require_admin(array $config): void
{
    if (! admin_configured($config) || ! admin_logged_in()) {
        redirect_to('/login');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }

    return (string) $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = (string) ($_POST['_token'] ?? '');
    if ($token === '' || ! hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        echo 'Invalid CSRF token.';
        exit;
    }
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function registry(string $path): array
{
    if (! is_file($path)) {
        return ['registry_format' => 'aptoria-license-authority-registry-v1', 'updated_at' => gmdate('c'), 'records' => []];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (! is_array($decoded)) {
        return ['registry_format' => 'aptoria-license-authority-registry-v1', 'updated_at' => gmdate('c'), 'records' => []];
    }

    $decoded['records'] = is_array($decoded['records'] ?? null) ? $decoded['records'] : [];

    return $decoded;
}

function save_registry(string $path, array $registry): void
{
    $registry['registry_format'] = 'aptoria-license-authority-registry-v1';
    $registry['updated_at'] = gmdate('c');
    if (! is_dir(dirname($path))) {
        @mkdir(dirname($path), 0775, true);
    }
    file_put_contents($path, json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n", LOCK_EX);
}

function normalize_fingerprint(string $value): string
{
    $value = strtolower(trim($value));
    if ($value === '') {
        return '';
    }

    return str_starts_with($value, 'sha256:') ? $value : 'sha256:'.$value;
}

function canonical_payload(array $payload): string
{
    $sort = function (array $value) use (&$sort): array {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $sort($item);
            }
        }
        ksort($value);
        return $value;
    };

    return json_encode($sort($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) ?: '{}';
}

function sign_document(array $payload, string $privateKey): array
{
    $signature = '';
    $ok = @openssl_sign(canonical_payload($payload), $rawSignature, $privateKey, OPENSSL_ALGO_SHA256);
    if ($ok) {
        $signature = base64_encode($rawSignature);
    }
    if ($signature === '') {
        throw new RuntimeException('Could not sign the document with the configured private key.');
    }

    return ['payload' => $payload, 'signature' => $signature];
}

function key_options(array $config): array
{
    $options = [
        'private_key_bits' => max(2048, (int) ($config['key_bits'] ?? 4096)),
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];
    $opensslConfig = trim((string) ($config['openssl_config_path'] ?? ''));
    if ($opensslConfig !== '' && is_file($opensslConfig)) {
        $options['config'] = $opensslConfig;
    }

    return $options;
}

function find_record(array $registry, string $licenseId): ?array
{
    foreach (($registry['records'] ?? []) as $record) {
        if ((string) ($record['license_id'] ?? '') === $licenseId) {
            return is_array($record) ? $record : null;
        }
    }

    return null;
}

function upsert_record(string $path, array $record): void
{
    $registry = registry($path);
    $records = [];
    $found = false;
    foreach (($registry['records'] ?? []) as $existing) {
        if ((string) ($existing['license_id'] ?? '') === (string) $record['license_id']) {
            $records[] = $record;
            $found = true;
        } else {
            $records[] = $existing;
        }
    }
    if (! $found) {
        $records[] = $record;
    }
    $registry['records'] = array_values($records);
    save_registry($path, $registry);
}

function delete_record(string $path, string $licenseId): void
{
    $registry = registry($path);
    $registry['records'] = array_values(array_filter($registry['records'] ?? [], fn ($record) => (string) ($record['license_id'] ?? '') !== $licenseId));
    save_registry($path, $registry);
}

function public_key_fingerprint(string $publicKeyPath): string
{
    if (! is_file($publicKeyPath)) {
        return '—';
    }

    return 'sha256:'.hash('sha256', (string) file_get_contents($publicKeyPath));
}

function imported_request_defaults(): array
{
    $request = $_SESSION['imported_license_request'] ?? null;
    if (! is_array($request)) {
        return [];
    }

    $machine = normalize_fingerprint((string) ($request['fingerprints']['machine'] ?? ''));
    $usb = normalize_fingerprint((string) ($request['fingerprints']['usb'] ?? ''));
    $fingerprints = array_values(array_filter([$machine, $usb]));

    return [
        'license_id' => 'APT-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) ($request['request_id'] ?? uniqid('', true))), 0, 12)),
        'subject' => '',
        'edition' => (string) ($request['edition_request'] ?? 'portable'),
        'status' => 'active',
        'expires_at' => gmdate('Y-m-d', strtotime('+1 year')),
        'binding_mode' => $fingerprints === [] ? 'none' : 'machine_or_usb',
        'fingerprints' => implode("\n", $fingerprints),
        'notes' => 'Imported request ID: '.(string) ($request['request_id'] ?? ''),
    ];
}

function build_license_payload(array $record, array $config): array
{
    $issuedAt = gmdate('c');
    $expiresAt = trim((string) ($record['expires_at'] ?? ''));
    if ($expiresAt !== '') {
        $expiresAt = gmdate('c', strtotime($expiresAt));
    } else {
        $expiresAt = gmdate('c', strtotime('+1 year'));
    }

    return [
        'license_id' => (string) ($record['license_id'] ?? ''),
        'product' => 'aptoria',
        'edition' => (string) ($record['edition'] ?? 'portable'),
        'subject' => (string) ($record['subject'] ?? ''),
        'issued_to' => (string) ($record['subject'] ?? ''),
        'issued_at' => $issuedAt,
        'expires_at' => $expiresAt,
        'features' => ['portable_usb', 'local_package_activation', 'online_authority_runtime_lease'],
        'fingerprint_binding' => [
            'mode' => (string) ($record['fingerprint_binding']['mode'] ?? 'none'),
            'fingerprints' => array_values($record['fingerprint_binding']['fingerprints'] ?? []),
        ],
        'authority' => [
            'issuer' => (string) ($config['issuer'] ?? 'license.aptoria.dev'),
            'url' => rtrim((string) ($config['authority_url'] ?? ''), '/'),
            'lease_endpoint' => (string) ($config['lease_endpoint'] ?? '/api/license/runtime-lease'),
        ],
    ];
}

function build_activation_zip(array $record, array $config, string $privateKeyPath, string $publicKeyPath): string
{
    if (! class_exists(ZipArchive::class)) {
        throw new RuntimeException('PHP ZipArchive extension is missing.');
    }
    if (! is_file($privateKeyPath) || ! is_file($publicKeyPath)) {
        throw new RuntimeException('Signing keys are missing. Generate the authority key pair first.');
    }

    $privateKey = (string) file_get_contents($privateKeyPath);
    $publicKey = rtrim((string) file_get_contents($publicKeyPath))."\n";
    $license = sign_document(build_license_payload($record, $config), $privateKey);
    $licenseJson = json_encode($license, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    $manifest = [
        'package_format' => 'aptoria-activation-package-v1',
        'generated_at' => gmdate('c'),
        'product' => 'aptoria',
        'license_id' => (string) ($record['license_id'] ?? ''),
        'subject' => (string) ($record['subject'] ?? ''),
        'edition' => (string) ($record['edition'] ?? ''),
        'files' => [
            'aptoria-license.json' => 'sha256:'.hash('sha256', $licenseJson),
            'license-public.pem' => 'sha256:'.hash('sha256', $publicKey),
            'license-authority-public.pem' => 'sha256:'.hash('sha256', $publicKey),
        ],
        'install_flow' => 'Upload this one ZIP package in Aptoria License Management.',
    ];

    $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', strtolower((string) ($record['license_id'] ?? 'aptoria-license')));
    $zipPath = sys_get_temp_dir().'/aptoria-activation-'.$safe.'-'.bin2hex(random_bytes(4)).'.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Could not create activation package.');
    }
    $zip->addFromString('aptoria-license.json', $licenseJson);
    $zip->addFromString('license-public.pem', $publicKey);
    $zip->addFromString('license-authority-public.pem', $publicKey);
    $zip->addFromString('activation-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    $zip->close();

    return $zipPath;
}

function render_header(string $title): void
{
    $flash = flash();
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).'</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f6f8fb;margin:0;color:#1f2937}.wrap{max-width:1180px;margin:0 auto;padding:24px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin-bottom:16px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}.btn{display:inline-block;border:0;border-radius:8px;padding:9px 12px;background:#2563eb;color:white;text-decoration:none;cursor:pointer}.btn.secondary{background:#e5e7eb;color:#111827}.btn.danger{background:#dc2626}.btn.warn{background:#d97706}.muted{color:#6b7280;font-size:13px}.badge{display:inline-block;border-radius:999px;padding:4px 9px;background:#eef2ff;color:#3730a3;font-size:12px}input,select,textarea{width:100%;box-sizing:border-box;border:1px solid #d1d5db;border-radius:8px;padding:9px;background:#fff}label{display:block;font-size:13px;color:#374151;margin-bottom:5px}.row{margin-bottom:10px}table{width:100%;border-collapse:collapse}td,th{border-top:1px solid #e5e7eb;padding:9px;text-align:left;vertical-align:top}code{background:#f3f4f6;padding:2px 4px;border-radius:4px}.flash{padding:10px 12px;border-radius:10px;margin-bottom:14px}.success{background:#dcfce7;color:#166534}.error{background:#fee2e2;color:#991b1b}.top{display:flex;justify-content:space-between;gap:12px;align-items:center}</style></head><body><div class="wrap">';
    if ($flash) {
        $class = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success';
        echo '<div class="flash '.$class.'">'.h((string) ($flash['message'] ?? '')).'</div>';
    }
}

function render_footer(): void
{
    echo '</div></body></html>';
}

function runtime_decision(array $record, array $request): array
{
    $status = strtolower(trim((string) ($record['status'] ?? 'active')));
    if ($status !== 'active') {
        $decision = match ($status) {
            'revoked' => 'license_revoked',
            'disabled' => 'license_disabled',
            'suspended' => 'license_suspended',
            default => 'license_inactive',
        };

        return [$decision, 'License is not active.'];
    }

    $expires = trim((string) ($record['expires_at'] ?? ''));
    if ($expires !== '' && strtotime($expires) !== false && strtotime($expires) < time()) {
        return ['license_expired', 'License is expired.'];
    }

    $binding = is_array($record['fingerprint_binding'] ?? null) ? $record['fingerprint_binding'] : ['mode' => 'none', 'fingerprints' => []];
    $mode = (string) ($binding['mode'] ?? 'none');
    $allowed = array_values(array_filter(array_map('normalize_fingerprint', $binding['fingerprints'] ?? [])));
    if ($mode !== 'none') {
        $machine = normalize_fingerprint((string) ($request['fingerprints']['machine'] ?? ''));
        $usb = normalize_fingerprint((string) ($request['fingerprints']['usb'] ?? ''));
        $ok = match ($mode) {
            'machine' => $machine !== '' && in_array($machine, $allowed, true),
            'usb' => $usb !== '' && in_array($usb, $allowed, true),
            'machine_or_usb' => ($machine !== '' && in_array($machine, $allowed, true)) || ($usb !== '' && in_array($usb, $allowed, true)),
            default => false,
        };
        if (! $ok) {
            return ['fingerprint_mismatch', 'Runtime fingerprint is not allowed for this license.'];
        }
    }

    return ['valid', 'License is valid.'];
}


function issuer_diagnostic_checks(array $config, string $storagePath, string $privateKeyPath, string $publicKeyPath, string $registryPath): array
{
    $checks = [];
    $add = function (string $id, string $severity, bool $passed, string $message, string $actual, string $remediation) use (&$checks): void {
        $checks[] = [
            'id' => $id,
            'severity' => $severity,
            'passed' => $passed,
            'status' => $passed ? 'pass' : $severity,
            'message' => $message,
            'actual' => $actual,
            'remediation' => $remediation,
        ];
    };

    $adminHosts = configured_hosts($config, 'admin_hosts');
    $apiHosts = configured_hosts($config, 'api_hosts');
    $authorityUrl = rtrim((string) ($config['authority_url'] ?? ''), '/');
    $leaseEndpoint = (string) ($config['lease_endpoint'] ?? '');

    $add('issuer_enabled', 'error', (bool) ($config['enabled'] ?? true), 'License authority is enabled.', ((bool) ($config['enabled'] ?? true)) ? 'true' : 'false', 'Set enabled=true when the authority should issue runtime leases.');
    $add('admin_hosts_configured', 'error', $adminHosts !== [], 'Admin host allowlist is configured.', implode(',', $adminHosts) ?: '(empty)', 'Set admin_hosts to admin.aptoria.dev.');
    $add('api_hosts_configured', 'error', $apiHosts !== [], 'API host allowlist is configured.', implode(',', $apiHosts) ?: '(empty)', 'Set api_hosts to license.aptoria.dev.');
    $add('host_boundary_no_overlap', 'error', array_intersect($adminHosts, $apiHosts) === [], 'Admin and API host allowlists do not overlap.', 'admin='.implode(',', $adminHosts).' api='.implode(',', $apiHosts), 'Keep admin.aptoria.dev and license.aptoria.dev separated.');
    $add('authority_url_https', 'warning', str_starts_with($authorityUrl, 'https://'), 'Authority URL uses HTTPS.', $authorityUrl === '' ? '(empty)' : $authorityUrl, 'Use https://license.aptoria.dev.');
    $add('lease_endpoint_expected', 'warning', $leaseEndpoint === '/api/license/runtime-lease', 'Runtime lease endpoint uses the expected path.', $leaseEndpoint, 'Use /api/license/runtime-lease.');
    $add('admin_password_hash_configured', 'error', trim((string) ($config['admin_password_hash'] ?? '')) !== '', 'Issuer admin password hash is configured.', trim((string) ($config['admin_password_hash'] ?? '')) === '' ? '(empty)' : 'configured', 'Create license-issuer/config.php and set admin_password_hash.');
    $add('storage_path_writable', 'error', is_dir($storagePath) && is_writable($storagePath), 'Issuer storage path is writable.', $storagePath, 'Create the storage directory outside public web root and make it writable.');
    $add('private_key_present', 'warning', is_file($privateKeyPath), 'Private signing key is present.', is_file($privateKeyPath) ? 'present' : 'missing', 'Generate the authority key pair from admin.aptoria.dev before issuing packages.');
    $add('public_key_present', 'warning', is_file($publicKeyPath), 'Public signing key is present.', is_file($publicKeyPath) ? public_key_fingerprint($publicKeyPath) : 'missing', 'Generate the authority key pair from admin.aptoria.dev.');
    $add('registry_present', 'warning', is_file($registryPath), 'License registry exists.', is_file($registryPath) ? 'present' : 'missing', 'Create at least one license record or import a license request.');
    $add('rate_limit_enabled', 'warning', (bool) ($config['rate_limit_enabled'] ?? true), 'Authority API rate limiting is enabled.', ((bool) ($config['rate_limit_enabled'] ?? true)) ? 'true' : 'false', 'Keep rate_limit_enabled=true for public API hosts.');
    $add('json_content_type_required', 'warning', (bool) ($config['require_json_content_type'] ?? true), 'Runtime lease POST requires JSON content type.', ((bool) ($config['require_json_content_type'] ?? true)) ? 'true' : 'false', 'Keep require_json_content_type=true.');

    return $checks;
}

function issuer_diagnostics(array $config, string $storagePath, string $privateKeyPath, string $publicKeyPath, string $registryPath): array
{
    $checks = issuer_diagnostic_checks($config, $storagePath, $privateKeyPath, $publicKeyPath, $registryPath);
    $summary = ['passed' => 0, 'warnings' => 0, 'errors' => 0, 'total' => count($checks)];
    foreach ($checks as $check) {
        if ((bool) ($check['passed'] ?? false)) {
            $summary['passed']++;
        } elseif (($check['severity'] ?? 'warning') === 'error') {
            $summary['errors']++;
        } else {
            $summary['warnings']++;
        }
    }

    return [
        'diagnostics_format' => 'aptoria-issuer-diagnostics-v1',
        'authority_version' => '1.0.2',
        'generated_at' => gmdate('c'),
        'status' => $summary['errors'] > 0 ? 'error' : ($summary['warnings'] > 0 ? 'warning' : 'ok'),
        'host' => request_host(),
        'summary' => $summary,
        'checks' => $checks,
    ];
}

enforce_host_boundary($config, $uri);
enforce_api_request_basics($config, $storagePath, $uri, $method);
enforce_rate_limit($config, $storagePath, $uri);

if ($uri === '/api/license/authority/status' && $method === 'GET') {
    json_response([
        'authority_format' => 'aptoria-license-authority-status-v1',
        'authority_version' => '1.0.2',
        'request_id' => request_id(),
        'enabled' => (bool) ($config['enabled'] ?? true),
        'issuer' => (string) ($config['issuer'] ?? 'license.aptoria.dev'),
        'api_only' => host_matches(configured_hosts($config, 'api_hosts')),
        'public_key_configured' => is_file($publicKeyPath),
        'public_key_fingerprint' => is_file($publicKeyPath) ? public_key_fingerprint($publicKeyPath) : null,
        'registry_configured' => is_file($registryPath),
        'rate_limit_enabled' => (bool) ($config['rate_limit_enabled'] ?? true),
        'max_request_bytes' => max(1024, (int) ($config['max_request_bytes'] ?? 32768)),
    ]);
}

if ($uri === '/api/license/runtime-lease' && $method === 'POST') {
    if (! (bool) ($config['enabled'] ?? true)) {
        authority_error('authority_unavailable', 503, $storagePath, ['reason' => 'disabled']);
    }
    if (! is_file($privateKeyPath)) {
        authority_error('authority_unavailable', 503, $storagePath, ['reason' => 'signing_key_missing']);
    }

    $request = decode_json_body($storagePath);
    validate_runtime_lease_request($request, $storagePath);

    $licenseId = trim((string) ($request['license']['license_id'] ?? ''));
    $record = find_record(registry($registryPath), $licenseId);
    if ($record === null) {
        authority_error('unknown_license', 404, $storagePath, [
            'license_id_hash' => 'sha256:'.hash('sha256', $licenseId),
        ]);
    }

    [$decision, $reason] = runtime_decision($record, $request);
    $now = time();
    $leaseMinutes = max(5, (int) ($config['lease_minutes'] ?? 60));
    $requestHash = 'sha256:'.hash('sha256', canonical_payload($request));
    $payload = [
        'lease_format' => 'aptoria-runtime-lease-v1',
        'lease_id' => 'lease_'.bin2hex(random_bytes(16)),
        'request_id' => request_id(),
        'issuer' => (string) ($config['issuer'] ?? 'license.aptoria.dev'),
        'license_id' => $licenseId,
        'product' => 'aptoria',
        'status' => $decision,
        'reason' => $reason,
        'issued_at' => gmdate('c', $now),
        'valid_until' => gmdate('c', $now + ($leaseMinutes * 60)),
        'request_hash' => $requestHash,
        'policy' => [
            'lease_minutes' => $leaseMinutes,
        ],
    ];

    append_json_log($storagePath, 'authority-requests.jsonl', [
        'request_id' => $payload['request_id'],
        'event' => 'runtime_lease_decision',
        'decision' => $decision,
        'http_status' => api_status_code_for_decision($decision),
        'client' => hash('sha256', client_ip($config)),
        'summary' => request_summary($request),
    ]);

    json_response(sign_document($payload, (string) file_get_contents($privateKeyPath)), api_status_code_for_decision($decision));
}

if ($uri === '/api/license/runtime-lease') {
    authority_error('method_not_allowed', 405, $storagePath, ['allowed_methods' => ['POST']]);
}

if (str_starts_with($uri, '/api/license/')) {
    authority_error('not_found', 404, $storagePath);
}


if ($uri === '/login' && $method === 'GET') {
    render_header('Aptoria License Issuer Login');
    echo '<div class="card"><h1>Aptoria License Issuer</h1>';
    if (! admin_configured($config)) {
        echo '<p class="error flash">Create <code>license-issuer/config.php</code> and set <code>admin_password_hash</code> before using the issuer admin.</p>';
    }
    echo '<form method="post" action="/login"><input type="hidden" name="_token" value="'.h(csrf_token()).'"><div class="row"><label>Password</label><input type="password" name="password" autofocus></div><button class="btn">Login</button></form></div>';
    render_footer();
    exit;
}

if ($uri === '/login' && $method === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    $hash = (string) ($config['admin_password_hash'] ?? '');
    if ($hash !== '' && password_verify($password, $hash)) {
        $_SESSION['aptoria_license_issuer_admin'] = true;
        session_regenerate_id(true);
        append_json_log($storagePath, 'issuer-admin-audit.jsonl', ['event' => 'admin_login', 'client' => hash('sha256', client_ip($config))]);
        redirect_to('/');
    }
    flash('Invalid password or issuer admin is not configured.', 'error');
    redirect_to('/login');
}

if ($uri === '/logout' && $method === 'POST') {
    verify_csrf();
    append_json_log($storagePath, 'issuer-admin-audit.jsonl', ['event' => 'admin_logout', 'client' => hash('sha256', client_ip($config))]);
    unset($_SESSION['aptoria_license_issuer_admin']);
    redirect_to('/login');
}

require_admin($config);


if ($uri === '/diagnostics.json' && $method === 'GET') {
    json_response(issuer_diagnostics($config, $storagePath, $privateKeyPath, $publicKeyPath, $registryPath));
}

if ($uri === '/diagnostics' && $method === 'GET') {
    $diagnostics = issuer_diagnostics($config, $storagePath, $privateKeyPath, $publicKeyPath, $registryPath);
    render_header('Aptoria License Issuer Diagnostics');
    echo '<div class="top"><div><h1>Issuer diagnostics</h1><p class="muted">Runtime validation for admin.aptoria.dev and license.aptoria.dev hosting profiles.</p></div><p><a class="btn secondary" href="/">Back</a> <a class="btn secondary" href="/diagnostics.json">JSON</a></p></div>';
    echo '<div class="card"><h3>Status: <span class="badge">'.h(strtoupper((string) $diagnostics['status'])).'</span></h3><p class="muted">Errors: '.h((string) $diagnostics['summary']['errors']).' · Warnings: '.h((string) $diagnostics['summary']['warnings']).' · Passed: '.h((string) $diagnostics['summary']['passed']).'</p></div>';
    echo '<div class="card"><table><thead><tr><th>Check</th><th>Status</th><th>Actual</th><th>Fix</th></tr></thead><tbody>';
    foreach ($diagnostics['checks'] as $check) {
        echo '<tr><td><strong>'.h((string) $check['message']).'</strong><br><code>'.h((string) $check['id']).'</code></td><td><span class="badge">'.h((string) $check['status']).'</span></td><td><code>'.h((string) $check['actual']).'</code></td><td class="muted">'.h((string) $check['remediation']).'</td></tr>';
    }
    echo '</tbody></table></div>';
    render_footer();
    exit;
}

if ($uri === '/keys/generate' && $method === 'POST') {
    verify_csrf();
    try {
        $res = openssl_pkey_new(key_options($config));
        if ($res === false) {
            throw new RuntimeException('OpenSSL could not generate a key pair.');
        }
        $private = '';
        if (! openssl_pkey_export($res, $private, null, key_options($config))) {
            throw new RuntimeException('OpenSSL could not export the private key.');
        }
        $details = openssl_pkey_get_details($res);
        $public = is_array($details) ? (string) ($details['key'] ?? '') : '';
        if ($private === '' || $public === '') {
            throw new RuntimeException('OpenSSL generated an incomplete key pair.');
        }
        file_put_contents($privateKeyPath, rtrim($private)."\n", LOCK_EX);
        file_put_contents($publicKeyPath, rtrim($public)."\n", LOCK_EX);
        append_json_log($storagePath, 'issuer-admin-audit.jsonl', ['event' => 'signing_key_generated', 'client' => hash('sha256', client_ip($config))]);
        flash('Signing key pair generated.');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'error');
    }
    redirect_to('/');
}

if ($uri === '/keys/public.pem' && $method === 'GET') {
    if (! is_file($publicKeyPath)) {
        flash('Public key is missing.', 'error');
        redirect_to('/');
    }
    header('Content-Type: application/x-pem-file');
    header('Content-Disposition: attachment; filename="license-authority-public.pem"');
    readfile($publicKeyPath);
    exit;
}

if ($uri === '/request/import' && $method === 'POST') {
    verify_csrf();
    $tmp = $_FILES['license_request']['tmp_name'] ?? '';
    $decoded = is_string($tmp) && is_uploaded_file($tmp) ? json_decode((string) file_get_contents($tmp), true) : null;
    if (! is_array($decoded) || ($decoded['request_format'] ?? '') !== 'aptoria-license-request-v1') {
        flash('Invalid Aptoria license request JSON.', 'error');
    } else {
        $_SESSION['imported_license_request'] = $decoded;
        flash('License request imported. The new record form was prefilled.');
    }
    redirect_to('/');
}

if ($uri === '/registry/save' && $method === 'POST') {
    verify_csrf();
    $fingerprints = array_values(array_filter(array_map('normalize_fingerprint', preg_split('/\R+/', (string) ($_POST['fingerprints'] ?? '')) ?: [])));
    $mode = (string) ($_POST['binding_mode'] ?? 'none');
    if (! in_array($mode, ['none', 'machine', 'usb', 'machine_or_usb'], true)) {
        $mode = 'none';
    }
    $record = [
        'license_id' => trim((string) ($_POST['license_id'] ?? '')),
        'subject' => trim((string) ($_POST['subject'] ?? '')),
        'edition' => trim((string) ($_POST['edition'] ?? 'portable')) ?: 'portable',
        'status' => in_array((string) ($_POST['status'] ?? 'active'), ['active', 'revoked', 'disabled', 'suspended'], true) ? (string) $_POST['status'] : 'active',
        'expires_at' => trim((string) ($_POST['expires_at'] ?? '')),
        'fingerprint_binding' => ['mode' => $mode, 'fingerprints' => $mode === 'none' ? [] : $fingerprints],
        'notes' => trim((string) ($_POST['notes'] ?? '')),
        'updated_at' => gmdate('c'),
    ];
    if ($record['license_id'] === '') {
        flash('License ID is required.', 'error');
    } else {
        upsert_record($registryPath, $record);
        unset($_SESSION['imported_license_request']);
        append_json_log($storagePath, 'issuer-admin-audit.jsonl', ['event' => 'registry_record_saved', 'license_id_hash' => 'sha256:'.hash('sha256', (string) $record['license_id']), 'client' => hash('sha256', client_ip($config))]);
        flash('License registry record saved.');
    }
    redirect_to('/');
}

if ($uri === '/registry/status' && $method === 'POST') {
    verify_csrf();
    $licenseId = (string) ($_POST['license_id'] ?? '');
    $status = (string) ($_POST['status'] ?? 'active');
    $record = find_record(registry($registryPath), $licenseId);
    if ($record !== null && in_array($status, ['active', 'revoked', 'disabled', 'suspended'], true)) {
        $record['status'] = $status;
        $record['updated_at'] = gmdate('c');
        upsert_record($registryPath, $record);
        append_json_log($storagePath, 'issuer-admin-audit.jsonl', ['event' => 'registry_status_updated', 'license_id_hash' => 'sha256:'.hash('sha256', $licenseId), 'status' => $status, 'client' => hash('sha256', client_ip($config))]);
        flash('License status updated.');
    }
    redirect_to('/');
}

if ($uri === '/registry/delete' && $method === 'POST') {
    verify_csrf();
    $deletedLicenseId = (string) ($_POST['license_id'] ?? '');
    delete_record($registryPath, $deletedLicenseId);
    append_json_log($storagePath, 'issuer-admin-audit.jsonl', ['event' => 'registry_record_deleted', 'license_id_hash' => 'sha256:'.hash('sha256', $deletedLicenseId), 'client' => hash('sha256', client_ip($config))]);
    flash('License record deleted.');
    redirect_to('/');
}

if ($uri === '/registry/export' && $method === 'GET') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="license-authority-registry.json"');
    echo json_encode(registry($registryPath), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    exit;
}

if ($uri === '/activation-package' && $method === 'GET') {
    $licenseId = (string) ($_GET['license_id'] ?? '');
    $record = find_record(registry($registryPath), $licenseId);
    if ($record === null) {
        flash('License record not found.', 'error');
        redirect_to('/');
    }
    if ((string) ($record['status'] ?? '') !== 'active') {
        flash('Activation package can only be generated for active records.', 'error');
        redirect_to('/');
    }
    try {
        append_json_log($storagePath, 'issuer-admin-audit.jsonl', ['event' => 'activation_package_generated', 'license_id_hash' => 'sha256:'.hash('sha256', $licenseId), 'client' => hash('sha256', client_ip($config))]);
        $zipPath = build_activation_zip($record, $config, $privateKeyPath, $publicKeyPath);
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', strtolower($licenseId));
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="aptoria-activation-'.$safe.'.zip"');
        header('Content-Length: '.filesize($zipPath));
        readfile($zipPath);
        @unlink($zipPath);
        exit;
    } catch (Throwable $e) {
        flash($e->getMessage(), 'error');
        redirect_to('/');
    }
}

$defaults = imported_request_defaults();
$records = registry($registryPath)['records'] ?? [];
render_header('Aptoria License Issuer');
echo '<div class="top"><div><h1>Aptoria License Issuer</h1><p class="muted">Separate authority/issuer app for admin.aptoria.dev and license.aptoria.dev.</p></div><div><a class="btn secondary" href="/diagnostics">Diagnostics</a> <form style="display:inline" method="post" action="/logout"><input type="hidden" name="_token" value="'.h(csrf_token()).'"><button class="btn secondary">Logout</button></form></div></div>';

echo '<div class="grid"><div class="card"><h3>Signing keys</h3><p class="muted">Private key: '.(is_file($privateKeyPath) ? 'present' : 'missing').'</p><p class="muted">Public key: '.(is_file($publicKeyPath) ? h(public_key_fingerprint($publicKeyPath)) : 'missing').'</p><form method="post" action="/keys/generate"><input type="hidden" name="_token" value="'.h(csrf_token()).'"><button class="btn warn">Generate / replace key pair</button></form><p><a class="btn secondary" href="/keys/public.pem">Download public key</a></p></div>';

echo '<div class="card"><h3>Import license request</h3><form method="post" action="/request/import" enctype="multipart/form-data"><input type="hidden" name="_token" value="'.h(csrf_token()).'"><div class="row"><label>license-request.json from customer Aptoria</label><input type="file" name="license_request" accept="application/json,.json"></div><button class="btn">Import request</button></form></div>';

echo '<div class="card"><h3>Authority API</h3><p class="muted"><code>GET /api/license/authority/status</code></p><p class="muted"><code>POST /api/license/runtime-lease</code></p><p><span class="badge">'.h((string) ($config['issuer'] ?? 'license.aptoria.dev')).'</span></p><p class="muted">Rate limit: '.((bool) ($config['rate_limit_enabled'] ?? true) ? 'enabled' : 'disabled').'</p><p class="muted">Max request: '.h((string) max(1024, (int) ($config['max_request_bytes'] ?? 32768))).' bytes</p></div></div>';

echo '<div class="card"><h3>Create / update license record</h3><form method="post" action="/registry/save"><input type="hidden" name="_token" value="'.h(csrf_token()).'"><div class="grid"><div class="row"><label>License ID</label><input name="license_id" value="'.h((string) ($defaults['license_id'] ?? '')).'" required></div><div class="row"><label>Customer / subject</label><input name="subject" value="'.h((string) ($defaults['subject'] ?? '')).'"></div><div class="row"><label>Edition</label><input name="edition" value="'.h((string) ($defaults['edition'] ?? 'portable')).'"></div><div class="row"><label>Status</label><select name="status"><option>active</option><option>revoked</option><option>disabled</option><option>suspended</option></select></div><div class="row"><label>Expires at</label><input type="date" name="expires_at" value="'.h((string) ($defaults['expires_at'] ?? '')).'"></div><div class="row"><label>Binding mode</label><select name="binding_mode"><option value="none">none</option><option value="machine">machine</option><option value="usb">usb</option><option value="machine_or_usb" selected>machine_or_usb</option></select></div></div><div class="row"><label>Allowed fingerprints, one per line</label><textarea name="fingerprints" rows="4">'.h((string) ($defaults['fingerprints'] ?? '')).'</textarea></div><div class="row"><label>Notes</label><textarea name="notes" rows="3">'.h((string) ($defaults['notes'] ?? '')).'</textarea></div><button class="btn">Save record</button></form></div>';

echo '<div class="card"><div class="top"><h3>Registry</h3><a class="btn secondary" href="/registry/export">Export JSON</a></div><table><thead><tr><th>License</th><th>Subject</th><th>Status</th><th>Binding</th><th>Expires</th><th>Actions</th></tr></thead><tbody>';
foreach ($records as $record) {
    $licenseId = (string) ($record['license_id'] ?? '');
    $status = (string) ($record['status'] ?? 'active');
    echo '<tr><td><code>'.h($licenseId).'</code></td><td>'.h((string) ($record['subject'] ?? '')).'</td><td><span class="badge">'.h($status).'</span></td><td>'.h((string) ($record['fingerprint_binding']['mode'] ?? 'none')).'</td><td>'.h((string) ($record['expires_at'] ?? '')).'</td><td>';
    if ($status === 'active') {
        echo '<a class="btn" href="/activation-package?license_id='.rawurlencode($licenseId).'">Generate package</a> ';
    }
    echo '<form style="display:inline" method="post" action="/registry/status"><input type="hidden" name="_token" value="'.h(csrf_token()).'"><input type="hidden" name="license_id" value="'.h($licenseId).'"><input type="hidden" name="status" value="revoked"><button class="btn danger">Revoke</button></form> ';
    echo '<form style="display:inline" method="post" action="/registry/delete"><input type="hidden" name="_token" value="'.h(csrf_token()).'"><input type="hidden" name="license_id" value="'.h($licenseId).'"><button class="btn secondary">Delete</button></form>';
    echo '</td></tr>';
}
if ($records === []) {
    echo '<tr><td colspan="6" class="muted">No license records yet.</td></tr>';
}
echo '</tbody></table></div>';
render_footer();
