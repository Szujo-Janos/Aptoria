<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointAssertionRule extends Model
{
    public const RULE_KEYS = ['status_code', 'max_response_time', 'content_type_contains', 'max_response_size', 'body_contains', 'body_not_contains'];
    public const OPERATORS = ['equals', 'not_equals', 'contains', 'not_contains', 'less_than_or_equal', 'greater_than_or_equal'];
    public const SEVERITIES = ['info', 'warning', 'blocker'];

    protected $fillable = [
        'project_id',
        'endpoint_id',
        'name',
        'rule_key',
        'operator',
        'expected_value',
        'target_path',
        'severity',
        'enabled',
        'description',
    ];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function getSeverityToneAttribute(): string
    {
        return match ($this->severity) {
            'blocker' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    }

    public function getRuleLabelAttribute(): string
    {
        return __('messages.assertions.rule_keys.'.($this->rule_key ?: 'status_code'));
    }
}
