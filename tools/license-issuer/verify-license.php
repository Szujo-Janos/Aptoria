#!/usr/bin/env php
<?php

declare(strict_types=1);

function fail(string $message, int $code = 1): never
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit($code);
}

function info(string $message): void
{
    fwrite(STDOUT, $message."\n");
}

function usage(): void
{
    info(<<<'TXT'
Aptoria License Verifier

Usage:
  php verify-license.php --license=aptoria-license.json --public-key=public.pem [--request=license-request.json]

Checks JSON structure, product, expiry, RSA/SHA-256 signature and optionally the fingerprint binding against a request file.
TXT);
}

$options = getopt('', ['license:', 'public-key:', 'request::', 'help']);
if (isset($options['help'])) {
    usage();
    exit(0);
}

foreach (['license', 'public-key'] as $required) {
    if (! isset($options[$required]) || trim((string) $options[$required]) === '') {
        usage();
        fail("Missing required --{$required} option.");
    }
}

$licensePath = absolutePath((string) $options['license']);
$publicKeyPath = absolutePath((string) $options['public-key']);
$requestPath = isset($options['request']) && trim((string) $options['request']) !== '' ? absolutePath((string) $options['request']) : null;

if (! is_file($licensePath)) {
    fail("License file not found: {$licensePath}");
}
if (! is_file($publicKeyPath)) {
    fail("Public key file not found: {$publicKeyPath}");
}

$document = decodeJsonFile($licensePath, 'license');
$payload = $document['payload'] ?? null;
$signature = $document['signature'] ?? null;
if (! is_array($payload) || ! is_string($signature) || trim($signature) === '') {
    fail('License must contain payload object and base64 signature.');
}

if (($payload['product'] ?? null) !== 'aptoria') {
    fail('License product mismatch.');
}

if (empty($payload['license_id']) || empty($payload['edition']) || empty($payload['issued_at']) || empty($payload['expires_at'])) {
    fail('License payload is missing required fields.');
}

$expiry = strtotime((string) $payload['expires_at']);
if ($expiry === false) {
    fail('License expiry date is invalid.');
}
if ($expiry < time()) {
    fail('License is expired.');
}

$publicKey = file_get_contents($publicKeyPath);
if (! is_string($publicKey) || trim($publicKey) === '') {
    fail('Public key file is empty.');
}

$decodedSignature = base64_decode($signature, true);
if ($decodedSignature === false) {
    fail('License signature is not valid base64.');
}

$result = openssl_verify(canonicalPayload($payload), $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
if ($result !== 1) {
    fail('License signature verification failed.');
}

if ($requestPath !== null) {
    if (! is_file($requestPath)) {
        fail("Request file not found: {$requestPath}");
    }
    $request = decodeJsonFile($requestPath, 'license request');
    verifyBinding($payload, $request);
}

info('License verification OK.');
info('License ID: '.($payload['license_id'] ?? 'n/a'));
info('Subject: '.($payload['subject'] ?? 'n/a'));
info('Edition: '.($payload['edition'] ?? 'n/a'));
info('Expires at: '.($payload['expires_at'] ?? 'n/a'));
info('Binding mode: '.($payload['fingerprint_binding']['mode'] ?? 'none'));
exit(0);

function absolutePath(string $path): string
{
    $path = trim($path);
    if (preg_match('/^[A-Z]:[\\\\\/]/i', $path) || str_starts_with($path, '/')) {
        return $path;
    }

    return getcwd().DIRECTORY_SEPARATOR.$path;
}

function decodeJsonFile(string $path, string $label): array
{
    $raw = file_get_contents($path);
    if (! is_string($raw)) {
        fail("Unable to read {$label}: {$path}");
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail("Invalid {$label} JSON: ".$exception->getMessage());
    }

    if (! is_array($decoded)) {
        fail("{$label} must decode to a JSON object.");
    }

    return $decoded;
}

function verifyBinding(array $payload, array $request): void
{
    $binding = $payload['fingerprint_binding'] ?? ['mode' => 'none'];
    if (! is_array($binding)) {
        fail('License fingerprint binding is malformed.');
    }

    $mode = (string) ($binding['mode'] ?? 'none');
    if ($mode === 'none') {
        return;
    }

    $allowed = array_map('normalizeFingerprint', $binding['fingerprints'] ?? []);
    $machine = normalizeFingerprint((string) ($request['fingerprints']['machine'] ?? ''));
    $usb = normalizeFingerprint((string) ($request['fingerprints']['usb'] ?? ''));

    $ok = match ($mode) {
        'machine' => $machine !== null && in_array($machine, $allowed, true),
        'usb' => $usb !== null && in_array($usb, $allowed, true),
        'machine_or_usb' => ($machine !== null && in_array($machine, $allowed, true)) || ($usb !== null && in_array($usb, $allowed, true)),
        default => false,
    };

    if (! $ok) {
        fail('License fingerprint binding does not match the supplied request.');
    }
}

function normalizeFingerprint(string $fingerprint): ?string
{
    $fingerprint = trim(strtolower($fingerprint));
    if ($fingerprint === '') {
        return null;
    }

    return str_starts_with($fingerprint, 'sha256:') ? $fingerprint : 'sha256:'.$fingerprint;
}

function canonicalPayload(array $payload): string
{
    return json_encode(sortRecursive($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) ?: '{}';
}

function sortRecursive(array $value): array
{
    foreach ($value as $key => $item) {
        if (is_array($item)) {
            $value[$key] = sortRecursive($item);
        }
    }

    ksort($value);

    return $value;
}
