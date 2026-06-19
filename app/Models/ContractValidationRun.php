<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractValidationRun extends Model
{
    use HasFactory;

    public const STATUSES = ['passed', 'warning', 'blocked'];

    protected $fillable = [
        'project_id',
        'validated_by_user_id',
        'source_name',
        'source_version',
        'openapi_version',
        'status',
        'documented_operations',
        'inventory_operations',
        'matched_operations',
        'undocumented_inventory_operations',
        'missing_inventory_operations',
        'blocker_count',
        'warning_count',
        'summary_json',
        'contract_json',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'documented_operations' => 'integer',
            'inventory_operations' => 'integer',
            'matched_operations' => 'integer',
            'undocumented_inventory_operations' => 'integer',
            'missing_inventory_operations' => 'integer',
            'blocker_count' => 'integer',
            'warning_count' => 'integer',
            'summary_json' => 'array',
            'validated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ContractValidationResult::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.contract_validation.statuses.'.($this->status ?: 'warning'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'passed' => 'success',
            'blocked' => 'danger',
            default => 'warning',
        };
    }

    public function getSummaryAttribute(): array
    {
        return is_array($this->summary_json) ? $this->summary_json : [];
    }
}
