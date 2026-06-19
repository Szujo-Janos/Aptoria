<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EndpointSnapshotCompareItem extends Model
{
    protected $fillable = [
        'endpoint_snapshot_compare_id',
        'project_id',
        'baseline_item_id',
        'target_item_id',
        'endpoint_signature',
        'method',
        'path',
        'change_type',
        'tone',
        'baseline_state',
        'target_state',
        'baseline_status_code',
        'target_status_code',
        'baseline_checksum',
        'target_checksum',
        'summary_json',
    ];

    protected function casts(): array
    {
        return [
            'baseline_status_code' => 'integer',
            'target_status_code' => 'integer',
            'summary_json' => 'array',
        ];
    }

    public function compare(): BelongsTo
    {
        return $this->belongsTo(EndpointSnapshotCompare::class, 'endpoint_snapshot_compare_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function baselineItem(): BelongsTo
    {
        return $this->belongsTo(EndpointSnapshotItem::class, 'baseline_item_id');
    }

    public function targetItem(): BelongsTo
    {
        return $this->belongsTo(EndpointSnapshotItem::class, 'target_item_id');
    }


    public function regressionFindings(): HasMany
    {
        return $this->hasMany(Finding::class, 'endpoint_snapshot_compare_item_id');
    }

    public function getChangeTypeLabelAttribute(): string
    {
        return __('messages.snapshots.change_types.'.($this->change_type ?: 'changed'));
    }
}
