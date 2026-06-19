<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanResult extends Model
{
    use HasFactory;

    public const STATUSES = ['passed', 'warning', 'failed', 'skipped'];

    protected $fillable = [
        'scan_run_id',
        'project_id',
        'endpoint_id',
        'environment_id',
        'auth_profile_id',
        'method',
        'url',
        'status',
        'status_code',
        'response_time_ms',
        'content_type',
        'response_size',
        'headers_json',
        'body_preview',
        'error_message',
        'expected_status_matched',
        'expected_content_type_matched',
        'risk_level',
        'risk_reason',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'response_time_ms' => 'integer',
            'response_size' => 'integer',
            'headers_json' => 'array',
            'expected_status_matched' => 'boolean',
            'expected_content_type_matched' => 'boolean',
        ];
    }

    public function scanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function authProfile(): BelongsTo
    {
        return $this->belongsTo(AuthProfile::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(FindingEvidence::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.labels.scan_statuses.'.($this->status ?: 'skipped'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'passed' => 'success',
            'warning' => 'warning',
            'failed' => 'danger',
            'skipped' => 'secondary',
            default => 'secondary',
        };
    }

    public function getRiskLabelAttribute(): string
    {
        return __('messages.endpoints.risk_levels.'.($this->risk_level ?: 'low'));
    }

    public function getRiskToneAttribute(): string
    {
        return match ($this->risk_level) {
            'critical' => 'danger',
            'high' => 'warning',
            'review' => 'info',
            default => 'success',
        };
    }
}
