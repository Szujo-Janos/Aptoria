<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Environment extends Model
{
    use HasFactory;

    public const TYPES = ['local', 'dev', 'staging', 'production', 'custom'];

    protected $fillable = [
        'project_id', 'name', 'base_url', 'environment_type', 'is_production', 'is_default', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_production' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }

    public function scanRuns(): HasMany
    {
        return $this->hasMany(ScanRun::class);
    }

    public function getToneAttribute(): string
    {
        return match ($this->environment_type) {
            'production' => 'danger',
            'staging' => 'warning',
            'dev' => 'info',
            'local' => 'secondary',
            default => 'primary',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.environments.types.'.$this->environment_type);
    }
}
