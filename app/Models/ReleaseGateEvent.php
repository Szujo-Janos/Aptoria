<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseGateEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'release_gate_id',
        'release_gate_item_id',
        'user_id',
        'event_type',
        'summary',
        'severity',
        'metadata_json',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function gate(): BelongsTo
    {
        return $this->belongsTo(ReleaseGate::class, 'release_gate_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ReleaseGateItem::class, 'release_gate_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
