<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceLifecycleEvent extends Model
{
    protected $fillable = [
        'project_id',
        'finding_evidence_id',
        'user_id',
        'action',
        'summary',
        'before_values',
        'after_values',
        'metadata_json',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'metadata_json' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(FindingEvidence::class, 'finding_evidence_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
