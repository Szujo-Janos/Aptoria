<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingLifecycleEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'finding_id',
        'project_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFromStatusLabelAttribute(): string
    {
        return $this->from_status ? __('messages.findings.statuses.'.$this->from_status) : __('messages.common.none');
    }

    public function getToStatusLabelAttribute(): string
    {
        return __('messages.findings.statuses.'.$this->to_status);
    }
}
