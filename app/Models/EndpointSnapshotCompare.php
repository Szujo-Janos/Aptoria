<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EndpointSnapshotCompare extends Model
{
    protected $fillable = [
        'project_id',
        'baseline_snapshot_id',
        'target_snapshot_id',
        'compared_by_user_id',
        'status',
        'tone',
        'total_items',
        'unchanged_count',
        'changed_count',
        'added_count',
        'removed_count',
        'regressed_count',
        'improved_count',
        'regression_finding_count',
        'regression_findings_generated_at',
        'regression_finding_summary_json',
        'summary_json',
        'notes',
        'compared_at',
    ];

    protected function casts(): array
    {
        return [
            'total_items' => 'integer',
            'unchanged_count' => 'integer',
            'changed_count' => 'integer',
            'added_count' => 'integer',
            'removed_count' => 'integer',
            'regressed_count' => 'integer',
            'improved_count' => 'integer',
            'regression_finding_count' => 'integer',
            'regression_findings_generated_at' => 'datetime',
            'regression_finding_summary_json' => 'array',
            'summary_json' => 'array',
            'compared_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function baselineSnapshot(): BelongsTo
    {
        return $this->belongsTo(EndpointSnapshot::class, 'baseline_snapshot_id');
    }

    public function targetSnapshot(): BelongsTo
    {
        return $this->belongsTo(EndpointSnapshot::class, 'target_snapshot_id');
    }

    public function comparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compared_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EndpointSnapshotCompareItem::class);
    }


    public function regressionFindings(): HasMany
    {
        return $this->hasMany(Finding::class, 'endpoint_snapshot_compare_id');
    }

    public function getRegressionFindingSummaryAttribute(): array
    {
        return is_array($this->regression_finding_summary_json) ? $this->regression_finding_summary_json : [];
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.snapshots.compare_statuses.'.($this->status ?: 'passed'));
    }
}
