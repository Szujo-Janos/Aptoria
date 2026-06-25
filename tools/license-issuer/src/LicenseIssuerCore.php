<?php

declare(strict_types=1);

/**
 * Shared private-side Aptoria license issuer logic.
 *
 * This file has no Laravel dependencies so the issuer folder can later be moved
 * into a separate private repository/tool without rewriting the signing logic.
 */
class AptoriaLicenseIssuerCore
{
    public function generateKeypair(string $out, string $name = 'aptoria-license', int $bits = 2048, bool $force = false): array
    {
        $out = $this->absolutePath($out);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'aptoria-license';
        $bits = max(2048, $bits);
        $privatePath = $out.DIRECTORY_SEPARATOR.$name.'-private.pem';
        $publicPath = $out.DIRECTORY_SEPARATOR.$name.'-public.pem';

        if (! is_dir($out) && ! mkdir($out, 0700, true) && ! is_dir($out)) {
            throw new RuntimeException('Unable to create key output directory: '.$out);
        }

        if ((is_file($privatePath) || is_file($publicPath)) && ! $force) {
            throw new RuntimeException('Key files already exist. Enable overwrite to replace them.');
        }

        $diagnostics = [];
        $generated = $this->generateKeypairWithPhpOpenSsl($bits, $diagnostics);

        if ($generated === null) {
            $generated = $this->generateKeypairWithOpenSslBinary($privatePath, $publicPath, $bits, $diagnostics);
        }

        if ($generated === null) {
            $message = 'Unable to generate RSA keypair. ';
            $message .= 'PHP OpenSSL or an openssl binary is required. ';
            $message .= 'On Windows/XAMPP, set OPENSSL_CONF or APTORIA_OPENSSL_CONF to an openssl.cnf file, for example C:\xampp\apache\conf\openssl.cnf. ';
            if ($diagnostics !== []) {
                $message .= 'Diagnostics: '.implode(' | ', array_slice($diagnostics, 0, 8));
            }

            throw new RuntimeException($message);
        }

        file_put_contents($privatePath, $generated['private_key']);
        file_put_contents($publicPath, $generated['public_key']);
        @chmod($privatePath, 0600);
        @chmod($publicPath, 0644);

        return [
            'private_path' => $privatePath,
            'public_path' => $publicPath,
            'public_key' => $generated['public_key'],
            'bits' => $bits,
            'openssl_source' => $generated['source'] ?? 'php-openssl',
            'openssl_config' => $generated['config'] ?? null,
        ];
    }

    public function issue(array $request, string $privateKey, array $options = []): array
    {
        $this->validateRequest($request);

        if (trim($privateKey) === '') {
            throw new RuntimeException('Private key is empty.');
        }

        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if ($privateKeyResource === false) {
            throw new RuntimeException('Private key is not a readable RSA private key.');
        }

        $bindingMode = (string) ($options['binding'] ?? 'machine_or_usb');
        if (! in_array($bindingMode, ['none', 'machine', 'usb', 'machine_or_usb'], true)) {
            throw new RuntimeException('Invalid binding mode.');
        }

        $features = $this->parseFeatures($options['features'] ?? ['portable_usb', 'evidence_repository', 'import_adapter', 'native_test_evidence', 'release_gate', 'client_portal']);
        $licenseId = trim((string) ($options['license_id'] ?? '')) ?: $this->generatedLicenseId();
        $subject = trim((string) ($options['subject'] ?? '')) ?: 'Aptoria Customer';
        $issuedTo = trim((string) ($options['issued_to'] ?? ''));
        $edition = trim((string) ($options['edition'] ?? 'portable')) ?: 'portable';
        $issuer = trim((string) ($options['issuer'] ?? 'Aptoria License Issuer')) ?: 'Aptoria License Issuer';
        $expiresAt = $this->normalizeExpiry((string) ($options['expires'] ?? '+1 year'));
        $issuedAt = gmdate('Y-m-d\\TH:i:s\\Z');
        $fingerprints = $this->fingerprintsForMode($request, $bindingMode);
        $maxUsers = isset($options['max_users']) && trim((string) $options['max_users']) !== '' ? max(1, (int) $options['max_users']) : null;
        $notes = trim((string) ($options['notes'] ?? ''));

        $payload = array_filter([
            'license_id' => $licenseId,
            'product' => 'aptoria',
            'edition' => $edition,
            'subject' => $subject,
            'issued_to' => $issuedTo !== '' ? $issuedTo : null,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'max_users' => $maxUsers,
            'features' => array_values($features),
            'fingerprint_binding' => [
                'mode' => $bindingMode,
                'fingerprints' => array_values($fingerprints),
            ],
            'issuer' => $issuer,
            'source_request' => [
                'request_id' => $request['request_id'] ?? null,
                'request_format' => $request['request_format'] ?? null,
                'version' => $request['version'] ?? null,
                'generated_at' => $request['generated_at'] ?? null,
            ],
            'notes' => $notes !== '' ? $notes : null,
        ], static fn ($value): bool => $value !== null && $value !== '');

        $signature = '';
        if (! openssl_sign($this->canonicalPayload($payload), $signature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign payload with the supplied private key.');
        }

        $document = [
            'payload' => $payload,
            'signature' => base64_encode($signature),
        ];

        return [
            'document' => $document,
            'payload' => $payload,
            'json' => json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
        ];
    }

    public function verify(array $document, string $publicKey, ?array $request = null): array
    {
        $payload = $document['payload'] ?? null;
        $signature = $document['signature'] ?? null;
        if (! is_array($payload) || ! is_string($signature) || trim($signature) === '') {
            throw new RuntimeException('License must contain payload object and base64 signature.');
        }

        if (($payload['product'] ?? null) !== 'aptoria') {
            throw new RuntimeException('License product mismatch.');
        }

        foreach (['license_id', 'edition', 'issued_at', 'expires_at'] as $required) {
            if (empty($payload[$required])) {
                throw new RuntimeException('License payload is missing required field: '.$required.'.');
            }
        }

        $expiry = strtotime((string) $payload['expires_at']);
        if ($expiry === false) {
            throw new RuntimeException('License expiry date is invalid.');
        }
        if ($expiry < time()) {
            throw new RuntimeException('License is expired.');
        }

        if (trim($publicKey) === '') {
            throw new RuntimeException('Public key is empty.');
        }

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            throw new RuntimeException('License signature is not valid base64.');
        }

        $result = openssl_verify($this->canonicalPayload($payload), $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($result !== 1) {
            throw new RuntimeException('License signature verification failed.');
        }

        $bindingResult = 'not_checked';
        if ($request !== null) {
            $bindingResult = $this->verifyBinding($payload, $request);
        }

        return [
            'ok' => true,
            'license_id' => $payload['license_id'] ?? null,
            'subject' => $payload['subject'] ?? null,
            'edition' => $payload['edition'] ?? null,
            'expires_at' => $payload['expires_at'] ?? null,
            'binding_mode' => $payload['fingerprint_binding']['mode'] ?? 'none',
            'binding_result' => $bindingResult,
        ];
    }

    public function decodeJson(string $raw, string $label): array
    {
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid '.$label.' JSON: '.$exception->getMessage());
        }

        if (! is_array($decoded)) {
            throw new RuntimeException($label.' must decode to a JSON object.');
        }

        return $decoded;
    }

    public function canonicalPayload(array $payload): string
    {
        return json_encode($this->sortRecursive($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) ?: '{}';
    }

    private function validateRequest(array $request): void
    {
        if (($request['product'] ?? null) !== 'aptoria') {
            throw new RuntimeException('License request product must be aptoria.');
        }

        if (! is_array($request['fingerprints'] ?? null)) {
            throw new RuntimeException('License request is missing fingerprints.');
        }
    }

    private function parseFeatures(array|string $features): array
    {
        $items = is_array($features) ? $features : explode(',', $features);
        $items = array_filter(array_map(static fn ($item): string => trim((string) $item), $items));

        return $items === [] ? ['portable_usb'] : array_values(array_unique($items));
    }

    private function generatedLicenseId(): string
    {
        return 'APT-'.gmdate('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
    }

    private function normalizeExpiry(string $value): string
    {
        $value = trim($value) ?: '+1 year';
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new RuntimeException('Invalid expiry value. Use YYYY-MM-DD, ISO-8601 or a relative value such as +1 year.');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return gmdate('Y-m-d\\T23:59:59\\Z', $timestamp);
        }

        return gmdate('Y-m-d\\TH:i:s\\Z', $timestamp);
    }

    private function fingerprintsForMode(array $request, string $mode): array
    {
        if ($mode === 'none') {
            return [];
        }

        $machine = $this->normalizeFingerprint((string) ($request['fingerprints']['machine'] ?? ''));
        $usb = $this->normalizeFingerprint((string) ($request['fingerprints']['usb'] ?? ''));
        $fingerprints = [];

        if (($mode === 'machine' || $mode === 'machine_or_usb') && $machine !== null) {
            $fingerprints[] = $machine;
        }

        if (($mode === 'usb' || $mode === 'machine_or_usb') && $usb !== null) {
            $fingerprints[] = $usb;
        }

        if ($fingerprints === []) {
            throw new RuntimeException("Binding mode {$mode} requires a matching fingerprint in the license request.");
        }

        return array_values(array_unique($fingerprints));
    }

    private function verifyBinding(array $payload, array $request): string
    {
        $binding = $payload['fingerprint_binding'] ?? ['mode' => 'none'];
        if (! is_array($binding)) {
            throw new RuntimeException('License fingerprint binding is malformed.');
        }

        $mode = (string) ($binding['mode'] ?? 'none');
        if ($mode === 'none') {
            return 'none';
        }

        $allowed = array_map([$this, 'normalizeFingerprint'], $binding['fingerprints'] ?? []);
        $allowed = array_values(array_filter($allowed));
        $machine = $this->normalizeFingerprint((string) ($request['fingerprints']['machine'] ?? ''));
        $usb = $this->normalizeFingerprint((string) ($request['fingerprints']['usb'] ?? ''));

        $ok = match ($mode) {
            'machine' => $machine !== null && in_array($machine, $allowed, true),
            'usb' => $usb !== null && in_array($usb, $allowed, true),
            'machine_or_usb' => ($machine !== null && in_array($machine, $allowed, true)) || ($usb !== null && in_array($usb, $allowed, true)),
            default => false,
        };

        if (! $ok) {
            throw new RuntimeException('License fingerprint binding does not match the supplied request.');
        }

        if ($machine !== null && in_array($machine, $allowed, true)) {
            return 'machine';
        }

        if ($usb !== null && in_array($usb, $allowed, true)) {
            return 'usb';
        }

        return 'matched';
    }

    private function normalizeFingerprint(string $fingerprint): ?string
    {
        $fingerprint = trim(strtolower($fingerprint));
        if ($fingerprint === '') {
            return null;
        }

        return str_starts_with($fingerprint, 'sha256:') ? $fingerprint : 'sha256:'.$fingerprint;
    }

    private function sortRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        ksort($value);

        return $value;
    }


    /**
     * Generate an RSA keypair through PHP's OpenSSL extension.
     *
     * Windows/XAMPP often has the extension enabled but no default openssl.cnf.
     * The method therefore retries with known config locations before falling
     * back to the openssl CLI binary.
     */
    private function generateKeypairWithPhpOpenSsl(int $bits, array &$diagnostics): ?array
    {
        if (! extension_loaded('openssl')) {
            $diagnostics[] = 'PHP OpenSSL extension is not loaded.';

            return null;
        }

        foreach ($this->openSslConfigCandidates() as $config) {
            $options = [
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => $bits,
                'digest_alg' => 'sha256',
            ];

            if ($config !== null) {
                $options['config'] = $config;
            }

            $this->drainOpenSslErrors();
            $key = @openssl_pkey_new($options);
            $newErrors = $this->drainOpenSslErrors();
            $label = $config === null ? 'default OpenSSL config' : 'config '.$config;

            if ($key === false) {
                $diagnostics[] = 'openssl_pkey_new failed with '.$label.($newErrors !== [] ? ': '.implode('; ', $newErrors) : '.');
                continue;
            }

            $privateKey = '';
            $exportOptions = $config !== null ? ['config' => $config] : null;
            $this->drainOpenSslErrors();
            $exportOk = $exportOptions === null
                ? @openssl_pkey_export($key, $privateKey)
                : @openssl_pkey_export($key, $privateKey, null, $exportOptions);
            $exportErrors = $this->drainOpenSslErrors();

            if (! $exportOk || trim($privateKey) === '') {
                $diagnostics[] = 'openssl_pkey_export failed with '.$label.($exportErrors !== [] ? ': '.implode('; ', $exportErrors) : '.');
                continue;
            }

            $details = @openssl_pkey_get_details($key);
            $publicKey = is_array($details) ? ($details['key'] ?? null) : null;
            if (! is_string($publicKey) || trim($publicKey) === '') {
                $diagnostics[] = 'openssl_pkey_get_details did not return a public key with '.$label.'.';
                continue;
            }

            return [
                'private_key' => $privateKey,
                'public_key' => $publicKey,
                'source' => 'php-openssl',
                'config' => $config,
            ];
        }

        return null;
    }

    /**
     * Last-resort fallback for XAMPP/Windows installations where PHP's
     * OpenSSL extension cannot locate openssl.cnf, but openssl.exe is present.
     */
    private function generateKeypairWithOpenSslBinary(string $privatePath, string $publicPath, int $bits, array &$diagnostics): ?array
    {
        foreach ($this->openSslBinaryCandidates() as $binary) {
            $tempPrivate = $privatePath.'.tmp-'.bin2hex(random_bytes(4));
            $tempPublic = $publicPath.'.tmp-'.bin2hex(random_bytes(4));

            $genResult = $this->runCommand([
                $binary,
                'genrsa',
                '-out',
                $tempPrivate,
                (string) $bits,
            ]);

            if ($genResult['exit_code'] !== 0 || ! is_file($tempPrivate)) {
                @unlink($tempPrivate);
                @unlink($tempPublic);
                $diagnostics[] = 'openssl binary genrsa failed for '.$binary.': '.trim($genResult['stderr'] ?: $genResult['stdout']);
                continue;
            }

            $pubResult = $this->runCommand([
                $binary,
                'rsa',
                '-in',
                $tempPrivate,
                '-pubout',
                '-out',
                $tempPublic,
            ]);

            if ($pubResult['exit_code'] !== 0 || ! is_file($tempPublic)) {
                @unlink($tempPrivate);
                @unlink($tempPublic);
                $diagnostics[] = 'openssl binary public key export failed for '.$binary.': '.trim($pubResult['stderr'] ?: $pubResult['stdout']);
                continue;
            }

            $privateKey = (string) file_get_contents($tempPrivate);
            $publicKey = (string) file_get_contents($tempPublic);
            @unlink($tempPrivate);
            @unlink($tempPublic);

            if (str_contains($privateKey, 'PRIVATE KEY') && str_contains($publicKey, 'PUBLIC KEY')) {
                return [
                    'private_key' => $privateKey,
                    'public_key' => $publicKey,
                    'source' => 'openssl-binary:'.$binary,
                    'config' => null,
                ];
            }

            $diagnostics[] = 'openssl binary returned invalid PEM content for '.$binary.'.';
        }

        return null;
    }

    /**
     * @return array<int, string|null>
     */
    private function openSslConfigCandidates(): array
    {
        $candidates = [null];

        foreach (['APTORIA_OPENSSL_CONF', 'OPENSSL_CONF'] as $envName) {
            $value = $this->environmentValue($envName);
            if (is_string($value) && trim($value) !== '') {
                $candidates[] = trim($value);
            }
        }

        $phpBinary = defined('PHP_BINARY') ? (string) PHP_BINARY : '';
        $phpDir = $phpBinary !== '' ? dirname($phpBinary) : '';
        $xamppRoot = $phpDir !== '' ? dirname($phpDir) : '';

        foreach ([
            $xamppRoot.DIRECTORY_SEPARATOR.'apache'.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'openssl.cnf',
            $xamppRoot.DIRECTORY_SEPARATOR.'apache'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'openssl.cnf',
            $phpDir.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            $phpDir.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'openssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            'C:\xampp\apache\conf\openssl.cnf',
            'C:\xampp\apache\bin\openssl.cnf',
            'C:\xampp\php\extras\ssl\openssl.cnf',
            '/etc/ssl/openssl.cnf',
            '/usr/lib/ssl/openssl.cnf',
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $candidates[] = $candidate;
            }
        }

        $seen = [];
        $valid = [];
        foreach ($candidates as $candidate) {
            $key = $candidate === null ? '__default__' : strtolower(str_replace('\\', '/', $candidate));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            if ($candidate === null || is_file($candidate)) {
                $valid[] = $candidate;
            }
        }

        return $valid;
    }

    /**
     * @return array<int, string>
     */
    private function openSslBinaryCandidates(): array
    {
        $candidates = [];
        $envBinary = $this->environmentValue('APTORIA_OPENSSL_BINARY');
        if (is_string($envBinary) && trim($envBinary) !== '') {
            $candidates[] = trim($envBinary);
        }

        $phpBinary = defined('PHP_BINARY') ? (string) PHP_BINARY : '';
        $phpDir = $phpBinary !== '' ? dirname($phpBinary) : '';
        $xamppRoot = $phpDir !== '' ? dirname($phpDir) : '';

        foreach ([
            $xamppRoot.DIRECTORY_SEPARATOR.'apache'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'openssl.exe',
            $phpDir.DIRECTORY_SEPARATOR.'openssl.exe',
            'C:\xampp\apache\bin\openssl.exe',
            'C:\xampp\php\openssl.exe',
            'openssl',
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $candidates[] = $candidate;
            }
        }

        $seen = [];
        $valid = [];
        foreach ($candidates as $candidate) {
            $key = strtolower(str_replace('\\', '/', $candidate));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $valid[] = $candidate;
        }

        return $valid;
    }


    private function environmentValue(string $key): ?string
    {
        $value = getenv($key);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        foreach ([$_ENV[$key] ?? null, $_SERVER[$key] ?? null] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $parts
     * @return array{exit_code:int, stdout:string, stderr:string}
     */
    private function runCommand(array $parts): array
    {
        $command = implode(' ', array_map('escapeshellarg', $parts));
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptorSpec, $pipes);
        if (! is_resource($process)) {
            return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'proc_open failed'];
        }

        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'exit_code' => is_int($exitCode) ? $exitCode : 1,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function drainOpenSslErrors(): array
    {
        $errors = [];
        while (($message = openssl_error_string()) !== false) {
            $errors[] = $message;
        }

        return $errors;
    }

    private function absolutePath(string $path): string
    {
        $path = trim($path);
        if (preg_match('/^[A-Z]:[\\\\\/]/i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return getcwd().DIRECTORY_SEPARATOR.$path;
    }
}
