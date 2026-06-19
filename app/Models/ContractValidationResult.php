<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractValidationResult extends Model
{
    use HasFactory;

    public const TYPES = ['matched', 'undocumented_endpoint', 'missing_inventory'];
    public const SEVERITIES = ['info', 'warning', 'blocker'];

    protected $fillable = [
        'project_id',
        'contract_validation_run_id',
        'endpoint_id',
        'result_type',
        'severity',
        'method',
        'path',
        'operation_id',
        'summary',
        'details_json',
    ];

    protected function casts(): array
    {
        return ['details_json' => 'array'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ContractValidationRun::class, 'contract_validation_run_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.contract_validation.result_types.'.($this->result_type ?: 'matched'));
    }

    public function getSeverityLabelAttribute(): string
    {
        return __('messages.contract_validation.severities.'.($this->severity ?: 'info'));
    }

    public function getSeverityToneAttribute(): string
    {
        return match ($this->severity) {
            'blocker' => 'danger',
            'warning' => 'warning',
            default => 'success',
        };
    }

    public function getTypeToneAttribute(): string
    {
        return match ($this->result_type) {
            'undocumented_endpoint' => 'warning',
            'missing_inventory' => 'info',
            default => 'success',
        };
    }
}
