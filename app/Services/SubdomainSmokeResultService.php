<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class SubdomainSmokeResultService
{
    private const FORMAT = 'aptoria-subdomain-smoke-v1';

    /** @return array<string,mixed> */
    public function dashboard(): array
    {
        $history = $this->history(8);
        $latest = $history[0] ?? null;
        $latestFull = $latest ? $this->find((string) $latest['id']) : null;

        return [
            'dashboard_format' => 'aptoria-subdomain-deployment-dashboard-v1',
            'generated_at' => now()->toIso8601String(),
            'version' => (string) config('aptoria.version'),
            'storage_path' => $this->storagePath(),
            'latest' => $latestFull,
            'history' => $history,
            'domains' => $latestFull ? $this->domainSummary($latestFull) : $this->emptyDomainSummary(),
            'summary' => $latestFull['summary'] ?? ['status' => 'missing', 'total' => 0, 'passed' => 0, 'failed' => 0, 'warnings' => 1],
            'freshness' => $this->freshness($latestFull),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function history(int $limit = 20): array
    {
        $index = $this->readIndex();
        $items = $index['results'] ?? [];
        if (! is_array($items)) {
            return [];
        }
        $items = array_values(array_filter($items, 'is_array'));

        usort($items, fn (array $a, array $b): int => strcmp((string) ($b['imported_at'] ?? ''), (string) ($a['imported_at'] ?? '')));

        return array_slice(array_values($items), 0, max(1, $limit));
    }

    /** @return array<string,mixed>|null */
    public function latest(): ?array
    {
        $history = $this->history(1);
        $id = $history[0]['id'] ?? null;

        return is_string($id) && $id !== '' ? $this->find($id) : null;
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        $id = $this->safeId($id);
        if ($id === '') {
            return null;
        }

        $path = $this->storagePath().DIRECTORY_SEPARATOR.$id.'.json';
        if (! File::exists($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /** @return array<string,mixed> */
    public function importFromUploadedFile(UploadedFile $file, string $source = 'upload'): array
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('The uploaded smoke result file is invalid.');
        }

        return $this->importFromString((string) file_get_contents($file->getRealPath()), $source, $file->getClientOriginalName());
    }

    /** @return array<string,mixed> */
    public function importFromPath(string $path, string $source = 'cli'): array
    {
        $resolved = $this->resolvePath($path);
        if (! File::exists($resolved)) {
            throw new InvalidArgumentException('Smoke result file does not exist: '.$path);
        }

        return $this->importFromString((string) File::get($resolved), $source, basename($resolved));
    }

    /** @return array<string,mixed> */
    public function importFromString(string $json, string $source = 'manual', ?string $sourceName = null): array
    {
        $json = trim($json);
        if ($json === '') {
            throw new InvalidArgumentException('Smoke result JSON is empty.');
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Smoke result JSON cannot be decoded: '.$exception->getMessage());
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Smoke result JSON must decode to an object.');
        }

        $payload = $this->normalize($decoded, $source, $sourceName);
        $this->persist($payload);

        return $payload;
    }

    /** @return array<string,mixed> */
    public function expectedMatrix(): array
    {
        return [
            'landing' => [
                'title' => 'aptoria.dev',
                'url' => (string) config('aptoria.domain.landing_url', 'https://aptoria.dev'),
                'expected' => ['root open', 'login blocked', 'setup blocked', 'demo API blocked'],
            ],
            'demo' => [
                'title' => 'demo.aptoria.dev',
                'url' => (string) config('aptoria.domain.demo_url', 'https://demo.aptoria.dev'),
                'expected' => ['demo guide open', 'demo API health open', 'setup blocked', 'program settings blocked'],
            ],
            'license' => [
                'title' => 'license.aptoria.dev',
                'url' => (string) config('aptoria.domain.license_url', 'https://license.aptoria.dev'),
                'expected' => ['authority status open', 'login blocked', 'non-JSON runtime lease rejected'],
            ],
            'admin' => [
                'title' => 'admin.aptoria.dev',
                'url' => (string) config('aptoria.domain.admin_url', 'https://admin.aptoria.dev'),
                'expected' => ['login open', 'license API blocked'],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function normalize(array $payload, string $source, ?string $sourceName): array
    {
        $format = (string) ($payload['smoke_result_format'] ?? $payload['format'] ?? '');
        if ($format !== '' && $format !== self::FORMAT) {
            throw new InvalidArgumentException('Unsupported smoke result format: '.$format);
        }

        $rawChecks = $payload['checks'] ?? $payload['results'] ?? [];
        if (! is_array($rawChecks) || count($rawChecks) === 0) {
            throw new InvalidArgumentException('Smoke result JSON must contain a non-empty checks/results array.');
        }

        $checks = [];
        foreach ($rawChecks as $index => $rawCheck) {
            if (! is_array($rawCheck)) {
                continue;
            }
            $checks[] = $this->normalizeCheck($rawCheck, $index);
        }

        if (count($checks) === 0) {
            throw new InvalidArgumentException('Smoke result JSON does not contain any valid check rows.');
        }

        $summary = $this->summary($checks);
        $importedAt = now()->toIso8601String();
        $generatedAt = (string) ($payload['generated_at'] ?? $payload['started_at'] ?? $importedAt);
        $id = $this->makeId($generatedAt);

        $targets = $payload['targets'] ?? [];
        if (! is_array($targets)) {
            $targets = [];
        }

        return [
            'smoke_result_format' => self::FORMAT,
            'id' => $id,
            'generated_at' => $generatedAt,
            'imported_at' => $importedAt,
            'source' => $source,
            'source_name' => $sourceName,
            'version' => (string) ($payload['version'] ?? config('aptoria.version')),
            'status' => $summary['failed'] > 0 ? 'failed' : 'passed',
            'summary' => $summary,
            'targets' => array_merge([
                'landing' => (string) config('aptoria.domain.landing_url', 'https://aptoria.dev'),
                'demo' => (string) config('aptoria.domain.demo_url', 'https://demo.aptoria.dev'),
                'admin' => (string) config('aptoria.domain.admin_url', 'https://admin.aptoria.dev'),
                'license' => (string) config('aptoria.domain.license_url', 'https://license.aptoria.dev'),
            ], $targets),
            'checks' => $checks,
        ];
    }

    /** @return array<string,mixed> */
    private function normalizeCheck(array $rawCheck, int $index): array
    {
        $statusCode = $rawCheck['status_code'] ?? $rawCheck['status'] ?? null;
        if (is_string($statusCode) && preg_match('/^\d+$/', $statusCode) === 1) {
            $statusCode = (int) $statusCode;
        }

        $expected = $rawCheck['expected'] ?? [];
        if (is_string($expected)) {
            $expected = array_values(array_filter(array_map('trim', explode(',', $expected))));
        }
        if (! is_array($expected)) {
            $expected = [];
        }
        $expected = array_values(array_map(fn ($value): int|string => is_numeric($value) ? (int) $value : (string) $value, $expected));

        $passed = (bool) ($rawCheck['passed'] ?? $rawCheck['ok'] ?? false);
        if (! array_key_exists('passed', $rawCheck) && $statusCode !== null && count($expected) > 0) {
            $passed = in_array($statusCode, $expected, true) || in_array((string) $statusCode, array_map('strval', $expected), true);
        }

        $name = trim((string) ($rawCheck['name'] ?? $rawCheck['check'] ?? 'check_'.$index));
        $domain = strtolower(trim((string) ($rawCheck['domain'] ?? $rawCheck['role'] ?? $this->domainFromName($name))));

        return [
            'id' => Str::slug($domain.'_'.$name, '_') ?: 'check_'.$index,
            'domain' => $domain !== '' ? $domain : 'unknown',
            'name' => $name,
            'method' => strtoupper((string) ($rawCheck['method'] ?? 'GET')),
            'url' => (string) ($rawCheck['url'] ?? ''),
            'expected' => $expected,
            'status_code' => $statusCode,
            'passed' => $passed,
            'status' => $passed ? 'pass' : 'fail',
            'error' => (string) ($rawCheck['error'] ?? ''),
            'duration_ms' => isset($rawCheck['duration_ms']) && is_numeric($rawCheck['duration_ms']) ? (int) $rawCheck['duration_ms'] : null,
        ];
    }

    /** @param list<array<string,mixed>> $checks @return array<string,mixed> */
    private function summary(array $checks): array
    {
        $passed = 0;
        $failed = 0;
        foreach ($checks as $check) {
            ((bool) ($check['passed'] ?? false)) ? $passed++ : $failed++;
        }

        return [
            'status' => $failed > 0 ? 'failed' : 'passed',
            'total' => count($checks),
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => 0,
        ];
    }

    /** @return array<string,mixed> */
    private function domainSummary(array $payload): array
    {
        $domains = $this->emptyDomainSummary();
        foreach (($payload['checks'] ?? []) as $check) {
            if (! is_array($check)) {
                continue;
            }
            $domain = (string) ($check['domain'] ?? 'unknown');
            if (! isset($domains[$domain])) {
                $domains[$domain] = ['title' => $domain, 'url' => '', 'total' => 0, 'passed' => 0, 'failed' => 0, 'status' => 'missing', 'checks' => []];
            }
            $domains[$domain]['total']++;
            if ((bool) ($check['passed'] ?? false)) {
                $domains[$domain]['passed']++;
            } else {
                $domains[$domain]['failed']++;
            }
            $domains[$domain]['checks'][] = $check;
        }

        foreach ($domains as $key => $domain) {
            $domains[$key]['status'] = ((int) ($domain['total'] ?? 0)) === 0 ? 'missing' : (((int) ($domain['failed'] ?? 0)) > 0 ? 'failed' : 'passed');
        }

        return $domains;
    }

    /** @return array<string,array<string,mixed>> */
    private function emptyDomainSummary(): array
    {
        $domains = [];
        foreach ($this->expectedMatrix() as $key => $meta) {
            $domains[$key] = [
                'title' => $meta['title'],
                'url' => $meta['url'],
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'status' => 'missing',
                'checks' => [],
                'expected' => $meta['expected'],
            ];
        }

        return $domains;
    }

    /** @return array<string,mixed> */
    public function freshness(?array $payload): array
    {
        $maxHours = max(1, (int) config('aptoria.deployment.smoke_result_max_age_hours', 24));
        if (! $payload) {
            return [
                'status' => 'missing',
                'age_hours' => null,
                'max_age_hours' => $maxHours,
                'message' => 'No imported subdomain smoke result was found.',
            ];
        }

        $timestamp = strtotime((string) ($payload['generated_at'] ?? $payload['imported_at'] ?? ''));
        if (! $timestamp) {
            return [
                'status' => 'warning',
                'age_hours' => null,
                'max_age_hours' => $maxHours,
                'message' => 'Imported smoke result has no readable generated_at timestamp.',
            ];
        }

        $ageHours = max(0, (int) floor((time() - $timestamp) / 3600));
        $status = $ageHours > $maxHours ? 'stale' : 'fresh';

        return [
            'status' => $status,
            'age_hours' => $ageHours,
            'max_age_hours' => $maxHours,
            'message' => $status === 'fresh'
                ? 'Latest smoke result is fresh enough for deployment review.'
                : 'Latest smoke result is older than the configured freshness window.',
        ];
    }

    private function persist(array $payload): void
    {
        File::ensureDirectoryExists($this->storagePath());
        $path = $this->storagePath().DIRECTORY_SEPARATOR.$payload['id'].'.json';
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $index = $this->readIndex();
        $items = array_values(array_filter(array_filter($index['results'] ?? [], 'is_array'), fn (array $item): bool => ($item['id'] ?? '') !== $payload['id']));
        array_unshift($items, $this->brief($payload));
        $index = [
            'index_format' => 'aptoria-subdomain-smoke-index-v1',
            'latest_id' => $payload['id'],
            'updated_at' => now()->toIso8601String(),
            'results' => array_slice($items, 0, max(1, (int) config('aptoria.deployment.smoke_result_history_limit', 20))),
        ];
        File::put($this->indexPath(), json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** @return array<string,mixed> */
    private function brief(array $payload): array
    {
        return [
            'id' => $payload['id'],
            'generated_at' => $payload['generated_at'],
            'imported_at' => $payload['imported_at'],
            'source' => $payload['source'],
            'source_name' => $payload['source_name'],
            'status' => $payload['status'],
            'summary' => $payload['summary'],
            'targets' => $payload['targets'],
        ];
    }

    /** @return array<string,mixed> */
    private function readIndex(): array
    {
        if (! File::exists($this->indexPath())) {
            return ['index_format' => 'aptoria-subdomain-smoke-index-v1', 'results' => []];
        }

        try {
            $decoded = json_decode((string) File::get($this->indexPath()), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ['index_format' => 'aptoria-subdomain-smoke-index-v1', 'results' => []];
        }

        return is_array($decoded) ? $decoded : ['index_format' => 'aptoria-subdomain-smoke-index-v1', 'results' => []];
    }

    private function storagePath(): string
    {
        return $this->resolvePath((string) config('aptoria.deployment.smoke_results_path', storage_path('app/aptoria-deployment/smoke-results')));
    }

    private function indexPath(): string
    {
        return $this->storagePath().DIRECTORY_SEPARATOR.'index.json';
    }

    private function resolvePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return storage_path('app/aptoria-deployment/smoke-results');
        }
        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, '/') || preg_match('~^[A-Za-z]:[\\/]~', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function makeId(string $generatedAt): string
    {
        $stamp = date('Ymd-His', strtotime($generatedAt) ?: time());

        return $stamp.'-'.Str::lower(Str::random(6));
    }

    private function safeId(string $id): string
    {
        return preg_match('/^[A-Za-z0-9_.-]+$/', $id) === 1 ? $id : '';
    }

    private function domainFromName(string $name): string
    {
        $lower = strtolower($name);
        foreach (['landing', 'demo', 'license', 'admin'] as $domain) {
            if (str_contains($lower, $domain)) {
                return $domain;
            }
        }

        return 'unknown';
    }
}
