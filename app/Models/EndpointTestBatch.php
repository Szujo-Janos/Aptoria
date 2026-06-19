<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EndpointTestBatch extends Model
{
    protected $fillable = [
        'project_id',
        'state',
        'tone',
        'total',
        'passed',
        'warning',
        'failed',
        'skipped',
        'summary_json',
        'started_at',
        'completed_at',
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
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function testRuns(): HasMany
    {
        return $this->hasMany(EndpointTestRun::class);
    }

    public function getStateLabelAttribute(): string
    {
        return __('messages.endpoints.quick_test_states.'.($this->state ?: 'skipped'));
    }

    public function getDurationLabelAttribute(): string
    {
        if (! $this->started_at || ! $this->completed_at) {
            return __('messages.common.not_available');
        }

        return $this->started_at->diffInSeconds($this->completed_at).'s';
    }

    public function getRiskCountAttribute(): int
    {
        return (int) $this->failed + (int) $this->warning + (int) $this->skipped;
    }
}
