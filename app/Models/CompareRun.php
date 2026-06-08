<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompareRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'snapshot_a_id',
        'snapshot_b_id',
        'created_by',
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

    public function snapshotA(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class, 'snapshot_a_id');
    }

    public function snapshotB(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class, 'snapshot_b_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompareItem::class);
    }
}
