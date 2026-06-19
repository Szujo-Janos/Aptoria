<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseGateItem extends Model
{
    use HasFactory;

    public const STATES = ['pass', 'warning', 'blocked', 'waived'];
    public const CATEGORIES = ['readiness', 'evidence', 'tests', 'findings', 'imports', 'contract', 'risk', 'review'];

    protected $fillable = [
        'project_id',
        'release_gate_id',
        'reviewed_by_user_id',
        'item_key',
        'category',
        'label',
        'icon',
        'automated_state',
        'manual_state',
        'effective_state',
        'severity',
        'source_type',
        'source_id',
        'evidence_count',
        'required_action',
        'reviewer_note',
        'sort_order',
        'metadata_json',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_count' => 'integer',
            'sort_order' => 'integer',
            'metadata_json' => 'array',
            'reviewed_at' => 'datetime',
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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function getEffectiveStateLabelAttribute(): string
    {
        return __('messages.release_gates.item_states.'.($this->effective_state ?: 'warning'));
    }

    public function getEffectiveStateToneAttribute(): string
    {
        return match ($this->effective_state) {
            'pass' => 'success',
            'blocked' => 'danger',
            'waived', 'warning' => 'warning',
            default => 'secondary',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return __('messages.release_gates.categories.'.($this->category ?: 'readiness'));
    }
}
