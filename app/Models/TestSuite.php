<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestSuite extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DRAFT,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (TestSuite $suite): void {
            if (! $suite->status) {
                $suite->status = self::STATUS_ACTIVE;
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.test_suites.statuses.'.$this->status);
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_DRAFT => 'default',
            self::STATUS_ARCHIVED => 'warning',
            default => 'default',
        };
    }
}
