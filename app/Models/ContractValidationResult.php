<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractValidationResult extends Model
{
    use HasFactory;

    public const STATUS_PASS = 'pass';
    public const STATUS_FAIL = 'fail';
    public const STATUS_WARNING = 'warning';
    public const STATUS_SKIPPED = 'skipped';

    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    public const CHECK_OPERATION_DOCUMENTED = 'operation_documented';
    public const CHECK_OPERATION_IMPLEMENTED = 'operation_implemented';
    public const CHECK_STATUS_CODE = 'status_code';
    public const CHECK_CONTENT_TYPE = 'content_type';
    public const CHECK_RESPONSE_SCHEMA = 'response_schema';
    public const CHECK_SCAN_EVIDENCE = 'scan_evidence';
    public const CHECK_AUTH_REQUIREMENT = 'auth_requirement';
    public const CHECK_UNDOCUMENTED_RESPONSE_FIELD = 'undocumented_response_field';

    protected $fillable = [
        'contract_validation_run_id',
        'project_id',
        'endpoint_id',
        'scan_result_id',
        'method',
        'path',
        'check_type',
        'severity',
        'status',
        'message',
        'expected',
        'actual',
        'evidence_json',
    ];

    protected function casts(): array
    {
        return [
            'evidence_json' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ContractValidationRun::class, 'contract_validation_run_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function scanResult(): BelongsTo
    {
        return $this->belongsTo(ScanResult::class);
    }

    public function findings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PASS => 'success',
            self::STATUS_FAIL => 'danger',
            self::STATUS_WARNING => 'warning',
            self::STATUS_SKIPPED => 'default',
            default => 'info',
        };
    }

    public function getSeverityCssAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_HIGH => 'warning',
            self::SEVERITY_LOW => 'success',
            default => 'info',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.contract_validations.result_statuses.'.$this->status);
    }

    public function getSeverityLabelAttribute(): string
    {
        return __('messages.contract_validations.severities.'.$this->severity);
    }

    public function getCheckTypeLabelAttribute(): string
    {
        return __('messages.contract_validations.check_types.'.$this->check_type);
    }
}
