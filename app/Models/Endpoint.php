<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Endpoint extends Model
{
    use HasFactory;

    public const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
    public const RISK_LEVELS = ['low', 'public', 'review', 'high', 'critical'];

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
        'is_active',
        'excluded_from_scan',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'auth_required' => 'boolean',
            'expected_status' => 'integer',
            'is_active' => 'boolean',
            'excluded_from_scan' => 'boolean',
        ];
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

    public function testRuns(): HasMany
    {
        return $this->hasMany(EndpointTestRun::class);
    }

    public function assertionRules(): HasMany
    {
        return $this->hasMany(EndpointAssertionRule::class);
    }

    public function latestTestRun(): HasOne
    {
        return $this->hasOne(EndpointTestRun::class)->latestOfMany('checked_at');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(FindingEvidence::class);
    }

    public function getMethodToneAttribute(): string
    {
        return match ($this->method) {
            'GET', 'HEAD' => 'success',
            'POST' => 'primary',
            'PUT', 'PATCH' => 'warning',
            'DELETE' => 'danger',
            default => 'secondary',
        };
    }

    public function getRiskToneAttribute(): string
    {
        return match ($this->risk_level) {
            'critical' => 'danger',
            'high' => 'warning',
            'review' => 'info',
            'public' => 'primary',
            default => 'success',
        };
    }

    public function getRiskLabelAttribute(): string
    {
        return __('messages.endpoints.risk_levels.'.$this->risk_level);
    }

    public function getScanStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return __('messages.endpoints.inactive');
        }

        if ($this->excluded_from_scan) {
            return __('messages.endpoints.excluded');
        }

        return in_array($this->method, ['GET', 'HEAD'], true)
            ? __('messages.endpoints.safe_scan_ready')
            : __('messages.endpoints.manual_review');
    }

    public function getScanStatusToneAttribute(): string
    {
        if (! $this->is_active) {
            return 'secondary';
        }

        if ($this->excluded_from_scan) {
            return 'warning';
        }

        return in_array($this->method, ['GET', 'HEAD'], true) ? 'success' : 'info';
    }
}
