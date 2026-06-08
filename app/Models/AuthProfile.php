<?php

namespace App\Models;

use App\Services\Auth\AuthProfileRuntimeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthProfile extends Model
{
    use HasFactory;

    public const TYPE_NONE = 'none';
    public const TYPE_BEARER = 'bearer';
    public const TYPE_BASIC = 'basic';
    public const TYPE_CUSTOM_HEADER = 'custom_header';

    public const TYPES = [
        self::TYPE_NONE,
        self::TYPE_BEARER,
        self::TYPE_BASIC,
        self::TYPE_CUSTOM_HEADER,
    ];

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'encrypted_token',
        'username',
        'encrypted_password',
        'header_name',
        'encrypted_header_value',
        'notes',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'encrypted_token' => 'encrypted',
            'encrypted_password' => 'encrypted',
            'encrypted_header_value' => 'encrypted',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BEARER => __('messages.auth_profiles.types.bearer'),
            self::TYPE_BASIC => __('messages.auth_profiles.types.basic'),
            self::TYPE_CUSTOM_HEADER => __('messages.auth_profiles.types.custom_header'),
            default => __('messages.auth_profiles.types.none'),
        };
    }

    public function getMaskedSummaryAttribute(): string
    {
        return app(AuthProfileRuntimeService::class)->maskedSummary($this);
    }

    public function getScanReadyAttribute(): bool
    {
        return app(AuthProfileRuntimeService::class)->isComplete($this);
    }

    public function getScanReadyCssAttribute(): string
    {
        return app(AuthProfileRuntimeService::class)->maskedReadiness($this)['css'];
    }

    public function getScanReadyLabelAttribute(): string
    {
        return app(AuthProfileRuntimeService::class)->maskedReadiness($this)['label'];
    }
}
