#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'LicenseIssuerCore.php';

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
Aptoria License Keypair Generator

Usage:
  php generate-keypair.php --out=keys [--name=aptoria-license] [--bits=2048] [--force]

Creates:
  <out>/<name>-private.pem
  <out>/<name>-public.pem

Windows/XAMPP note:
  If PHP OpenSSL cannot find openssl.cnf, set one of these before running:
    set OPENSSL_CONF=C:\xampp\apache\conf\openssl.cnf
    set APTORIA_OPENSSL_CONF=C:\xampp\apache\conf\openssl.cnf

Keep the private key offline/private. Only the public key may be installed into Aptoria runtimes.
TXT);
}

$options = getopt('', ['out:', 'name::', 'bits::', 'force', 'help']);
if (isset($options['help'])) {
    usage();
    exit(0);
}

if (! isset($options['out']) || trim((string) $options['out']) === '') {
    usage();
    fail('Missing required --out option.');
}

$core = new AptoriaLicenseIssuerCore();

try {
    $result = $core->generateKeypair(
        (string) $options['out'],
        (string) ($options['name'] ?? 'aptoria-license'),
        max(2048, (int) ($options['bits'] ?? 2048)),
        isset($options['force']),
    );
} catch (RuntimeException $exception) {
    fail($exception->getMessage());
}

info('Private key: '.$result['private_path']);
info('Public key: '.$result['public_path']);
info('Bits: '.$result['bits']);
info('Source: '.($result['openssl_source'] ?? 'php-openssl'));
if (! empty($result['openssl_config'])) {
    info('OpenSSL config: '.$result['openssl_config']);
}
info('Keep the private key offline and never commit it.');
