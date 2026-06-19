<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EndpointSnapshot extends Model
{
    protected $fillable = [
        'project_id',
        'endpoint_test_batch_id',
        'created_by_user_id',
        'title',
        'status',
        'tone',
        'total',
        'passed',
        'warning',
        'failed',
        'skipped',
        'checksum',
        'summary_json',
        'notes',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'passed' => 'integer',
            'warning' => 'integer',
            'failed' => 'integer',
            'skipped' => 'integer',
            'summary_json' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(EndpointTestBatch::class, 'endpoint_test_batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EndpointSnapshotItem::class);
    }

    public function baselineCompares(): HasMany
    {
        return $this->hasMany(EndpointSnapshotCompare::class, 'baseline_snapshot_id');
    }

    public function targetCompares(): HasMany
    {
        return $this->hasMany(EndpointSnapshotCompare::class, 'target_snapshot_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.snapshots.statuses.'.($this->status ?: 'captured'));
    }

    public function getShortChecksumAttribute(): string
    {
        return $this->checksum ? substr($this->checksum, 0, 12) : __('messages.common.not_available');
    }
}
