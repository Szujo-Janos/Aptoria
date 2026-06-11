<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointBehaviorLink extends Model
{
    use HasFactory;

    public const TYPE_PATH_PARAMETER = 'path_parameter';
    public const TYPE_RESOURCE_FLOW = 'resource_flow';
    public const TYPE_AUTH_BOUNDARY = 'auth_boundary';
    public const TYPE_DESTRUCTIVE_FOLLOWUP = 'destructive_followup';

    public const TYPES = [
        self::TYPE_PATH_PARAMETER,
        self::TYPE_RESOURCE_FLOW,
        self::TYPE_AUTH_BOUNDARY,
        self::TYPE_DESTRUCTIVE_FOLLOWUP,
    ];

    protected $fillable = [
        'project_id',
        'producer_endpoint_id',
        'consumer_endpoint_id',
        'dependency_type',
        'resource_key',
        'path_parameter',
        'confidence',
        'suggested_sequence',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function producerEndpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class, 'producer_endpoint_id');
    }

    public function consumerEndpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class, 'consumer_endpoint_id');
    }

    public function getDependencyTypeLabelAttribute(): string
    {
        return __('messages.api_behavior.dependency_types.'.$this->dependency_type);
    }

    public function getConfidenceLabelAttribute(): string
    {
        if ($this->confidence >= 80) {
            return __('messages.api_behavior.confidence.high');
        }

        if ($this->confidence >= 55) {
            return __('messages.api_behavior.confidence.medium');
        }

        return __('messages.api_behavior.confidence.low');
    }

    public function getConfidenceCssAttribute(): string
    {
        if ($this->confidence >= 80) {
            return 'success';
        }

        if ($this->confidence >= 55) {
            return 'info';
        }

        return 'default';
    }
}
