<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanRun extends Model
{
    use HasFactory;

    public const STATUSES = ['queued', 'running', 'completed', 'failed'];
    public const PROFILES = ['safe'];

    protected $fillable = [
        'project_id',
        'environment_id',
        'auth_profile_id',
        'profile',
        'status',
        'started_at',
        'completed_at',
        'duration_ms',
        'summary_json',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_ms' => 'integer',
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

    public function authProfile(): BelongsTo
    {
        return $this->belongsTo(AuthProfile::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ScanResult::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.labels.scan_statuses.'.($this->status ?: 'queued'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'running' => 'primary',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    public function getSummaryValueAttribute(): array
    {
        return is_array($this->summary_json) ? $this->summary_json : [];
    }
}
