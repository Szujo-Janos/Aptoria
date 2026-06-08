<?php

namespace App\Models;

use App\Services\Risk\RiskAnalyzer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Endpoint extends Model
{
    use HasFactory;

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_OPTIONS = 'OPTIONS';

    public const METHODS = [
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_PATCH,
        self::METHOD_DELETE,
        self::METHOD_HEAD,
        self::METHOD_OPTIONS,
    ];

    public const RISK_CRITICAL = 'critical';
    public const RISK_HIGH = 'high';
    public const RISK_REVIEW = 'review';
    public const RISK_PUBLIC = 'public';
    public const RISK_LOW = 'low';

    public const RISKS = [
        self::RISK_CRITICAL,
        self::RISK_HIGH,
        self::RISK_REVIEW,
        self::RISK_PUBLIC,
        self::RISK_LOW,
    ];

    protected $fillable = [
        'project_id',
        'environment_id',
        'auth_profile_id',
        'method',
        'path',
        'name',
        'description',
        'tags',
        'auth_required',
        'expected_status',
        'expected_content_type',
        'risk_level',
        'risk_reason',
        'qa_notes',
        'is_active',
        'excluded_from_scan',
    ];

    protected function casts(): array
    {
        return [
            'auth_required' => 'boolean',
            'is_active' => 'boolean',
            'excluded_from_scan' => 'boolean',
            'expected_status' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Endpoint $endpoint): void {
            $endpoint->method = strtoupper((string) $endpoint->method);
            $endpoint->path = self::normalizePath((string) $endpoint->path);

            if (! $endpoint->risk_level) {
                $endpoint->risk_level = self::RISK_REVIEW;
            }

            if (! $endpoint->risk_reason) {
                $endpoint->risk_reason = $endpoint->buildRiskExplanation();
            }
        });
    }

    public static function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $parts = parse_url($path);
            $path = $parts['path'] ?? '/';
            if (! empty($parts['query'])) {
                $path .= '?'.$parts['query'];
            }
        }

        if (! Str::startsWith($path, '/')) {
            $path = '/'.$path;
        }

        return $path;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function authProfile(): BelongsTo
    {
        return $this->belongsTo(AuthProfile::class);
    }

    public function scanResults(): HasMany
    {
        return $this->hasMany(ScanResult::class);
    }

    public function assertionRules(): HasMany
    {
        return $this->hasMany(EndpointAssertionRule::class);
    }

    public function pathParameters(): HasMany
    {
        return $this->hasMany(EndpointPathParameter::class);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function contractValidationResults(): HasMany
    {
        return $this->hasMany(ContractValidationResult::class);
    }

    public function latestScanResult(): HasOne
    {
        return $this->hasOne(ScanResult::class)->latestOfMany();
    }

    public function latestContractValidationResult(): HasOne
    {
        return $this->hasOne(ContractValidationResult::class)->latestOfMany();
    }

    public function isProbeable(): bool
    {
        return in_array(strtoupper($this->method), [self::METHOD_GET, self::METHOD_HEAD], true)
            && $this->is_active
            && ! $this->excluded_from_scan;
    }

    public function getRiskLabelAttribute(): string
    {
        return __('messages.endpoints.risks.'.$this->risk_level);
    }

    public function getRiskCssAttribute(): string
    {
        return match ($this->risk_level) {
            self::RISK_CRITICAL => 'danger',
            self::RISK_HIGH => 'warning',
            self::RISK_PUBLIC => 'info',
            self::RISK_LOW => 'success',
            default => 'default',
        };
    }

    public function getFullUrlAttribute(): string
    {
        try {
            if ($this->project instanceof Project) {
                return app(\App\Services\Endpoints\PathParameterResolver::class)->buildUrl($this->project, $this);
            }
        } catch (\Throwable) {
            // Fall back to the raw URL below when the container or DB is unavailable.
        }

        $base = $this->environment?->base_url ?: $this->project?->base_url;
        if (! $base) {
            return $this->path;
        }

        return rtrim($base, '/').'/'.ltrim($this->path, '/');
    }

    public function getTagListAttribute(): array
    {
        if (! $this->tags) {
            return [];
        }

        return collect(explode(',', $this->tags))
            ->map(fn (string $tag): string => trim($tag))
            ->filter()
            ->values()
            ->all();
    }

    public function buildRiskExplanation(): string
    {
        $path = strtolower($this->path);
        $method = strtoupper($this->method);

        if ($this->risk_level === self::RISK_CRITICAL) {
            return __('messages.endpoints.risk_reasons.critical_manual');
        }

        if (str_contains($path, 'admin') || str_contains($path, 'debug') || str_contains($path, 'internal')) {
            return __('messages.endpoints.risk_reasons.admin_debug_internal');
        }

        if (! $this->auth_required && preg_match('/(user|users|order|orders|payment|payments|invoice|invoices|customer|customers|profile|account)/', $path)) {
            return __('messages.endpoints.risk_reasons.public_sensitive_name');
        }

        if (in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_PATCH, self::METHOD_DELETE], true)) {
            return __('messages.endpoints.risk_reasons.destructive_method_review');
        }

        if (! $this->auth_required && $this->risk_level === self::RISK_PUBLIC) {
            return __('messages.endpoints.risk_reasons.documented_public');
        }

        return __('messages.endpoints.risk_reasons.manual_review');
    }

    public function getDeveloperFixSnippetAttribute(): string
    {
        return app(RiskAnalyzer::class)->buildDeveloperReviewSnippet($this, $this->latestScanResult);
    }

    public function getQaBugDraftAttribute(): string
    {
        return app(RiskAnalyzer::class)->buildQaBugReport($this, $this->latestScanResult);
    }
}
