<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointSnapshotItem extends Model
{
    protected $fillable = [
        'endpoint_snapshot_id',
        'project_id',
        'endpoint_id',
        'endpoint_signature',
        'endpoint_name',
        'method',
        'path',
        'url',
        'state',
        'tone',
        'status_code',
        'content_type',
        'response_time_ms',
        'response_size',
        'assertion_total',
        'assertion_failed',
        'item_checksum',
        'evidence_json',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'response_time_ms' => 'integer',
            'response_size' => 'integer',
            'assertion_total' => 'integer',
            'assertion_failed' => 'integer',
            'evidence_json' => 'array',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(EndpointSnapshot::class, 'endpoint_snapshot_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function getStateLabelAttribute(): string
    {
        return __('messages.endpoints.quick_test_states.'.($this->state ?: 'skipped'));
    }
}
