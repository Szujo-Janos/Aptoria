<?php

namespace App\Services\Security;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Services\Settings\SettingService;
use Illuminate\Support\Str;

class SensitiveDataDetector
{
    /** @var array<int, string> */
    private const SENSITIVE_FIELD_NAMES = [
        'password',
        'passwd',
        'pwd',
        'token',
        'access_token',
        'refresh_token',
        'id_token',
        'api_key',
        'apikey',
        'secret',
        'client_secret',
        'authorization',
        'auth',
        'private_key',
        'session',
        'cookie',
        'set-cookie',
        'csrf',
        'xsrf',
    ];

    public function __construct(
        private readonly SensitiveValueMasker $masker,
        private readonly SettingService $settings,
    ) {
    }

    /**
     * @param array<string, array<int, string>|string> $headers
     * @return array{detected:bool,count:int,highest_severity:string,summary:string,matches:array<int,array<string,mixed>>}
     */
    public function inspectResponse(?string $body, array $headers = [], ?string $contentType = null): array
    {
        if (! $this->settings->boolean('security.sensitive_data_detector_enabled', true)) {
            return $this->emptyResult();
        }

        $matches = [];
        $body = (string) $body;

        $this->detectHeaderIssues($headers, $matches);
        $this->detectBodyIssues($body, $contentType, $matches);

        $matches = $this->deduplicate($matches);
        $matches = array_slice($matches, 0, max(1, $this->settings->integer('security.sensitive_data_max_matches', 30)));

        return [
            'detected' => $matches !== [],
            'count' => count($matches),
            'highest_severity' => $this->highestSeverity($matches),
            'summary' => $this->summary($matches),
            'matches' => $matches,
        ];
    }

    public function severityForFinding(array $analysis, bool $authRequired): string
    {
        $severity = (string) ($analysis['highest_severity'] ?? Finding::SEVERITY_MEDIUM);

        if (! $authRequired && in_array($severity, [Finding::SEVERITY_MEDIUM, Finding::SEVERITY_HIGH], true)) {
            return Finding::SEVERITY_HIGH;
        }

        return in_array($severity, Finding::SEVERITIES, true) ? $severity : Finding::SEVERITY_MEDIUM;
    }

    public function riskLevelForFinding(array $analysis, bool $authRequired): string
    {
        $severity = $this->severityForFinding($analysis, $authRequired);

        return match ($severity) {
            Finding::SEVERITY_CRITICAL => Endpoint::RISK_CRITICAL,
            Finding::SEVERITY_HIGH => Endpoint::RISK_HIGH,
            default => Endpoint::RISK_REVIEW,
        };
    }

    /** @return array{detected:bool,count:int,highest_severity:string,summary:string,matches:array<int,array<string,mixed>>} */
    private function emptyResult(): array
    {
        return [
            'detected' => false,
            'count' => 0,
            'highest_severity' => Finding::SEVERITY_LOW,
            'summary' => '',
            'matches' => [],
        ];
    }

    /** @param array<string, array<int, string>|string> $headers @param array<int,array<string,mixed>> $matches */
    private function detectHeaderIssues(array $headers, array &$matches): void
    {
        foreach ($headers as $name => $values) {
            $values = is_array($values) ? $values : [$values];
            $normalizedName = strtolower((string) $name);
            $valueText = implode('; ', array_map(fn (mixed $value): string => (string) $value, $values));

            if ($this->isSensitiveFieldName($normalizedName)) {
                $matches[] = $this->match('sensitive_header', Finding::SEVERITY_HIGH, 'header', $name, $valueText);
            }

            if (str_contains($normalizedName, 'set-cookie')) {
                $matches[] = $this->match('set_cookie', Finding::SEVERITY_HIGH, 'header', $name, $valueText);
            }

            $this->detectPatternIssues($valueText, 'header', (string) $name, $matches);
        }
    }

    /** @param array<int,array<string,mixed>> $matches */
    private function detectBodyIssues(string $body, ?string $contentType, array &$matches): void
    {
        if ($body === '') {
            return;
        }

        $lowerContentType = strtolower((string) $contentType);
        if (str_contains($lowerContentType, 'json') || $this->looksLikeJson($body)) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $this->walkJson($decoded, '$', $matches);
            }
        }

        $this->detectPatternIssues($body, 'body', 'response body', $matches);
    }

    /** @param array<mixed> $data @param array<int,array<string,mixed>> $matches */
    private function walkJson(array $data, string $path, array &$matches): void
    {
        foreach ($data as $key => $value) {
            $keyString = (string) $key;
            $currentPath = $path === '$' ? '$.'.$keyString : $path.'.'.$keyString;

            if ($this->isSensitiveFieldName($keyString)) {
                $matches[] = $this->match('sensitive_json_field', Finding::SEVERITY_CRITICAL, 'body', $currentPath, $this->scalarPreview($value));
            }

            if (is_array($value)) {
                $this->walkJson($value, $currentPath, $matches);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $this->detectPatternIssues((string) $value, 'body', $currentPath, $matches);
            }
        }
    }

    /** @param array<int,array<string,mixed>> $matches */
    private function detectPatternIssues(string $text, string $location, string $path, array &$matches): void
    {
        if ($text === '') {
            return;
        }

        $patternMap = [
            'private_key' => [Finding::SEVERITY_CRITICAL, '/-----BEGIN (?:RSA |EC |OPENSSH |DSA |PRIVATE )?PRIVATE KEY-----/i'],
            'jwt' => [Finding::SEVERITY_CRITICAL, '/\beyJ[A-Za-z0-9_\-]{8,}\.[A-Za-z0-9_\-]{8,}\.[A-Za-z0-9_\-]{8,}\b/'],
            'bearer_token' => [Finding::SEVERITY_HIGH, '/\bBearer\s+[A-Za-z0-9\-._~+\/]+=*/i'],
            'api_key_value' => [Finding::SEVERITY_HIGH, '/\b(?:api[_-]?key|access[_-]?token|refresh[_-]?token|client[_-]?secret|secret)\b\s*[:=]\s*["\']?[^\s,"\']{8,}/i'],
            'email' => [Finding::SEVERITY_MEDIUM, '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i'],
            'phone' => [Finding::SEVERITY_MEDIUM, '/(?<!\d)(?:\+\d{1,3}[\s.\-]?)?(?:\(?\d{2,4}\)?[\s.\-]?){2,4}\d{2,4}(?!\d)/'],
            'debug_trace' => [Finding::SEVERITY_HIGH, '/(?:stack trace|exception|traceback|SQLSTATE\[|Fatal error|Notice:|Warning:|at\s+[A-Za-z0-9_\\\\.]+\(|vendor\\\\laravel|node_modules\/)/i'],
        ];

        foreach ($patternMap as $type => [$severity, $pattern]) {
            if (preg_match($pattern, $text, $match) === 1) {
                $matches[] = $this->match((string) $type, (string) $severity, $location, $path, (string) ($match[0] ?? $text));
            }
        }
    }

    private function isSensitiveFieldName(string $key): bool
    {
        $normalized = strtolower(str_replace(['_', '-', ' '], '', $key));

        foreach (self::SENSITIVE_FIELD_NAMES as $field) {
            $field = strtolower(str_replace(['_', '-', ' '], '', $field));
            if ($field !== '' && str_contains($normalized, $field)) {
                return true;
            }
        }

        foreach ($this->settings->csv('security.custom_sensitive_json_fields') as $field) {
            $field = strtolower(str_replace(['_', '-', ' '], '', (string) $field));
            if ($field !== '' && str_contains($normalized, $field)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeJson(string $body): bool
    {
        $trimmed = ltrim($body);
        return str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[');
    }

    private function scalarPreview(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_array($value)) {
            return '[array]';
        }

        return (string) $value;
    }

    /** @return array<string,mixed> */
    private function match(string $type, string $severity, string $location, string $path, string $excerpt): array
    {
        return [
            'type' => $type,
            'label' => __('messages.sensitive_data.types.'.$type),
            'severity' => $severity,
            'severity_label' => __('messages.findings.severities.'.$severity),
            'location' => $location,
            'path' => Str::limit($path, 180, '...'),
            'excerpt' => Str::limit($this->maskedExcerpt($excerpt), 240, '...'),
        ];
    }


    private function maskedExcerpt(string $excerpt): string
    {
        $masked = $this->masker->maskForStorage($excerpt);
        $masked = preg_replace('/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i', '[email]', $masked) ?? $masked;
        $masked = preg_replace('/(?<!\d)(?:\+\d{1,3}[\s.\-]?)?(?:\(?\d{2,4}\)?[\s.\-]?){2,4}\d{2,4}(?!\d)/', '[phone]', $masked) ?? $masked;

        return $masked;
    }

    /** @param array<int,array<string,mixed>> $matches @return array<int,array<string,mixed>> */
    private function deduplicate(array $matches): array
    {
        $seen = [];
        $deduped = [];

        foreach ($matches as $match) {
            $key = implode('|', [
                (string) ($match['type'] ?? ''),
                (string) ($match['location'] ?? ''),
                (string) ($match['path'] ?? ''),
                (string) ($match['excerpt'] ?? ''),
            ]);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduped[] = $match;
        }

        return $deduped;
    }

    /** @param array<int,array<string,mixed>> $matches */
    private function highestSeverity(array $matches): string
    {
        $weights = [
            Finding::SEVERITY_LOW => 1,
            Finding::SEVERITY_MEDIUM => 2,
            Finding::SEVERITY_HIGH => 3,
            Finding::SEVERITY_CRITICAL => 4,
        ];
        $highest = Finding::SEVERITY_LOW;

        foreach ($matches as $match) {
            $severity = (string) ($match['severity'] ?? Finding::SEVERITY_LOW);
            if (($weights[$severity] ?? 0) > ($weights[$highest] ?? 0)) {
                $highest = $severity;
            }
        }

        return $highest;
    }

    /** @param array<int,array<string,mixed>> $matches */
    private function summary(array $matches): string
    {
        if ($matches === []) {
            return '';
        }

        $types = collect($matches)
            ->countBy(fn (array $match): string => (string) ($match['type'] ?? 'unknown'))
            ->map(fn (int $count, string $type): string => __('messages.sensitive_data.types.'.$type).' ('.$count.')')
            ->values()
            ->take(5)
            ->implode(', ');

        return $types;
    }
}
