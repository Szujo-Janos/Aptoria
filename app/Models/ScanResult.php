<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanResult extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'scan_run_id',
        'endpoint_id',
        'auth_profile_id',
        'auth_applied',
        'auth_summary',
        'method',
        'url',
        'status',
        'status_code',
        'response_time_ms',
        'content_type',
        'response_size',
        'headers_json',
        'body_preview',
        'response_schema_json',
        'sensitive_data_detected',
        'sensitive_data_count',
        'sensitive_data_summary_json',
        'broken_auth_detected',
        'broken_auth_summary_json',
        'schema_drift_detected',
        'schema_drift_count',
        'schema_drift_summary_json',
        'error_message',
        'risk_level',
        'risk_reason',
        'expected_status_matched',
        'expected_content_type_matched',
    ];

    protected function casts(): array
    {
        return [
            'headers_json' => 'array',
            'sensitive_data_summary_json' => 'array',
            'response_schema_json' => 'array',
            'schema_drift_summary_json' => 'array',
            'schema_drift_detected' => 'boolean',
            'schema_drift_count' => 'integer',
            'sensitive_data_detected' => 'boolean',
            'sensitive_data_count' => 'integer',
            'broken_auth_detected' => 'boolean',
            'broken_auth_summary_json' => 'array',
            'auth_applied' => 'boolean',
            'expected_status_matched' => 'boolean',
            'expected_content_type_matched' => 'boolean',
        ];
    }

    public function scanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function authProfile(): BelongsTo
    {
        return $this->belongsTo(AuthProfile::class);
    }

    public function testCaseResults(): HasMany
    {
        return $this->hasMany(TestCaseResult::class);
    }

    public function contractValidationResults(): HasMany
    {
        return $this->hasMany(ContractValidationResult::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }


    public function getSchemaDriftSummaryLabelAttribute(): string
    {
        if (! is_array($this->schema_drift_summary_json)) {
            return __('messages.schema_drift.not_checked');
        }

        return (string) ($this->schema_drift_summary_json['summary'] ?? __('messages.common.not_available'));
    }


    public function getBrokenAuthSummaryLabelAttribute(): string
    {
        if (! is_array($this->broken_auth_summary_json)) {
            return __('messages.broken_auth.not_checked');
        }

        return (string) ($this->broken_auth_summary_json['summary'] ?? __('messages.common.not_available'));
    }

    public function getSensitiveDataSummaryLabelAttribute(): string
    {
        if (! $this->sensitive_data_detected || ! is_array($this->sensitive_data_summary_json)) {
            return __('messages.common.not_available');
        }

        return (string) ($this->sensitive_data_summary_json['summary'] ?? __('messages.common.not_available'));
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_SKIPPED => 'default',
            default => 'info',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.scans.result_statuses.'.$this->status);
    }

    public function getRiskCssAttribute(): string
    {
        return match ($this->risk_level) {
            Endpoint::RISK_CRITICAL => 'danger',
            Endpoint::RISK_HIGH => 'warning',
            Endpoint::RISK_PUBLIC => 'info',
            Endpoint::RISK_LOW => 'success',
            default => 'default',
        };
    }

    public function getRiskLabelAttribute(): string
    {
        if (! $this->risk_level) {
            return __('messages.common.not_available');
        }

        return __('messages.endpoints.risks.'.$this->risk_level);
    }
}
