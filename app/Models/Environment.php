<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Environment extends Model
{
    use HasFactory;

    public const TYPE_LOCAL = 'local';
    public const TYPE_DEV = 'dev';
    public const TYPE_STAGING = 'staging';
    public const TYPE_PRODUCTION = 'production';
    public const TYPE_CUSTOM = 'custom';

    public const TYPES = [
        self::TYPE_LOCAL,
        self::TYPE_DEV,
        self::TYPE_STAGING,
        self::TYPE_PRODUCTION,
        self::TYPE_CUSTOM,
    ];

    protected $fillable = [
        'project_id',
        'name',
        'base_url',
        'environment_type',
        'auth_profile_id',
        'is_production',
    ];

    protected function casts(): array
    {
        return [
            'is_production' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function authProfile(): BelongsTo
    {
        return $this->belongsTo(AuthProfile::class);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }

    public function scanRuns(): HasMany
    {
        return $this->hasMany(ScanRun::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    public static function typeOptions(): array
    {
        return collect(self::TYPES)
            ->mapWithKeys(fn (string $type): array => [$type => __('messages.environments.types.'.$type)])
            ->all();
    }

    public function getEnvironmentTypeLabelAttribute(): string
    {
        $type = in_array($this->environment_type, self::TYPES, true) ? $this->environment_type : self::TYPE_CUSTOM;

        return __('messages.environments.types.'.$type);
    }

    public function getEnvironmentTypeCssAttribute(): string
    {
        return match ($this->environment_type) {
            self::TYPE_PRODUCTION => 'danger',
            self::TYPE_STAGING => 'info',
            self::TYPE_DEV => 'primary',
            self::TYPE_LOCAL => 'success',
            default => 'default',
        };
    }

    public function getDisplayBaseUrlAttribute(): string
    {
        return rtrim((string) $this->base_url, '/');
    }
}
