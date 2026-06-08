<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Snapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'environment_id',
        'scan_run_id',
        'created_by',
        'name',
        'description',
        'snapshot_hash',
        'endpoint_count',
        'summary_json',
    ];

    protected function casts(): array
    {
        return [
            'summary_json' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function scanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SnapshotItem::class);
    }

    public function getShortHashAttribute(): string
    {
        return $this->snapshot_hash ? substr($this->snapshot_hash, 0, 10) : __('messages.common.not_available');
    }
}
