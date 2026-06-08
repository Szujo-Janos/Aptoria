<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractValidationRun extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'project_id',
        'scan_run_id',
        'source_name',
        'contract_hash',
        'status',
        'total_checks',
        'passed_count',
        'warning_count',
        'failed_count',
        'breaking_count',
        'missing_endpoint_count',
        'undocumented_endpoint_count',
        'schema_checked_count',
        'started_at',
        'finished_at',
        'error_message',
        'summary_json',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'summary_json' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ContractValidationResult::class);
    }

    public function getStatusCssAttribute(): string
    {
        if ($this->status === self::STATUS_FAILED || $this->failed_count > 0 || $this->breaking_count > 0) {
            return 'danger';
        }

        if ($this->warning_count > 0) {
            return 'warning';
        }

        return 'success';
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.contract_validations.run_statuses.'.$this->status);
    }

    public function getHealthLabelAttribute(): string
    {
        if ($this->status === self::STATUS_FAILED) {
            return __('messages.contract_validations.health.failed');
        }

        if ($this->breaking_count > 0) {
            return __('messages.contract_validations.health.breaking');
        }

        if ($this->failed_count > 0) {
            return __('messages.contract_validations.health.failed_checks');
        }

        if ($this->warning_count > 0) {
            return __('messages.contract_validations.health.warnings');
        }

        return __('messages.contract_validations.health.pass');
    }
}
