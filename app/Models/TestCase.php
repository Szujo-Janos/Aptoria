<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestCase extends Model
{
    use HasFactory;

    public const TYPE_MANUAL = 'manual';
    public const TYPE_AUTOMATED = 'automated';
    public const TYPE_HYBRID = 'hybrid';

    public const TYPES = [
        self::TYPE_MANUAL,
        self::TYPE_AUTOMATED,
        self::TYPE_HYBRID,
    ];

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_CRITICAL,
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_READY,
        self::STATUS_ACTIVE,
        self::STATUS_ARCHIVED,
    ];

    public const RUN_NOT_RUN = 'not_run';
    public const RUN_PASS = 'pass';
    public const RUN_FAIL = 'fail';
    public const RUN_BLOCKED = 'blocked';
    public const RUN_SKIPPED = 'skipped';

    public const RUN_STATUSES = [
        self::RUN_NOT_RUN,
        self::RUN_PASS,
        self::RUN_FAIL,
        self::RUN_BLOCKED,
        self::RUN_SKIPPED,
    ];

    protected $fillable = [
        'project_id',
        'test_suite_id',
        'endpoint_id',
        'title',
        'description',
        'preconditions',
        'steps',
        'expected_result',
        'actual_result',
        'type',
        'priority',
        'status',
        'execution_order',
        'builder_metadata_json',
        'last_run_status',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'last_run_at' => 'datetime',
            'builder_metadata_json' => 'array',
            'execution_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TestCase $testCase): void {
            if (! $testCase->type) {
                $testCase->type = self::TYPE_MANUAL;
            }

            if (! $testCase->priority) {
                $testCase->priority = self::PRIORITY_MEDIUM;
            }

            if (! $testCase->status) {
                $testCase->status = self::STATUS_DRAFT;
            }

            if (! $testCase->last_run_status) {
                $testCase->last_run_status = self::RUN_NOT_RUN;
            }

            if ($testCase->execution_order === null) {
                $testCase->execution_order = 0;
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function testSuite(): BelongsTo
    {
        return $this->belongsTo(TestSuite::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TestCaseResult::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function latestResult(): HasOne
    {
        return $this->hasOne(TestCaseResult::class)->latestOfMany('executed_at');
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.test_cases.types.'.$this->type);
    }

    public function getPriorityLabelAttribute(): string
    {
        return __('messages.test_cases.priorities.'.$this->priority);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.test_cases.statuses.'.$this->status);
    }

    public function getLastRunStatusLabelAttribute(): string
    {
        return __('messages.test_cases.run_statuses.'.($this->last_run_status ?: self::RUN_NOT_RUN));
    }

    public function getPriorityCssAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'danger',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_LOW => 'info',
            default => 'default',
        };
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_READY => 'info',
            self::STATUS_ARCHIVED => 'warning',
            default => 'default',
        };
    }

    public function getLastRunStatusCssAttribute(): string
    {
        return self::runStatusCss($this->last_run_status ?: self::RUN_NOT_RUN);
    }

    public static function runStatusCss(?string $status): string
    {
        return match ($status) {
            self::RUN_PASS => 'success',
            self::RUN_FAIL => 'danger',
            self::RUN_BLOCKED => 'warning',
            self::RUN_SKIPPED => 'default',
            default => 'info',
        };
    }
}
