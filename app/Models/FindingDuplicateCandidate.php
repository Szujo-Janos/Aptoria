<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingDuplicateCandidate extends Model
{
    use HasFactory;

    public const STATUSES = ['candidate', 'merged', 'dismissed'];

    protected $fillable = [
        'project_id', 'primary_finding_id', 'duplicate_finding_id', 'score', 'status', 'signals_json', 'detected_at', 'merged_at',
    ];

    protected function casts(): array
    {
        return [
            'signals_json' => 'array',
            'detected_at' => 'datetime',
            'merged_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function primaryFinding(): BelongsTo { return $this->belongsTo(Finding::class, 'primary_finding_id'); }
    public function duplicateFinding(): BelongsTo { return $this->belongsTo(Finding::class, 'duplicate_finding_id'); }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.finding_dedup.statuses.'.($this->status ?: 'candidate'));
    }

    public function getScoreToneAttribute(): string
    {
        return $this->score >= 90 ? 'danger' : ($this->score >= 75 ? 'warning' : 'info');
    }
}
