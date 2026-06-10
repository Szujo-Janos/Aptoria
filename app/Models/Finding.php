<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Finding extends Model
{
    use HasFactory;

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SCAN = 'scan';
    public const SOURCE_ASSERTION = 'assertion';
    public const SOURCE_CONTRACT = 'contract';
    public const SOURCE_TEST_CASE = 'test_case';
    public const SOURCE_REGRESSION = 'regression';

    public const SOURCES = [
        self::SOURCE_MANUAL,
        self::SOURCE_SCAN,
        self::SOURCE_ASSERTION,
        self::SOURCE_CONTRACT,
        self::SOURCE_TEST_CASE,
        self::SOURCE_REGRESSION,
    ];

    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITIES = [
        self::SEVERITY_LOW,
        self::SEVERITY_MEDIUM,
        self::SEVERITY_HIGH,
        self::SEVERITY_CRITICAL,
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_TRIAGED = 'triaged';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_FIXED = 'fixed';
    public const STATUS_FALSE_POSITIVE = 'false_positive';
    public const STATUS_ACCEPTED_RISK = 'accepted_risk';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_REOPENED = 'reopened';

    /**
     * Canonical finding lifecycle states shown in the v1.1.9 UI and reports.
     * Legacy states are still accepted through STATUSES for older databases.
     */
    public const LIFECYCLE_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_CONFIRMED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_FIXED,
        self::STATUS_FALSE_POSITIVE,
        self::STATUS_ACCEPTED_RISK,
        self::STATUS_REOPENED,
    ];

    public const LEGACY_STATUSES = [
        self::STATUS_TRIAGED,
        self::STATUS_CLOSED,
    ];

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_CONFIRMED,
        self::STATUS_TRIAGED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_FIXED,
        self::STATUS_FALSE_POSITIVE,
        self::STATUS_ACCEPTED_RISK,
        self::STATUS_CLOSED,
        self::STATUS_REOPENED,
    ];

    public const OPEN_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_CONFIRMED,
        self::STATUS_TRIAGED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_REOPENED,
    ];

    public const RESOLVED_STATUSES = [
        self::STATUS_FIXED,
        self::STATUS_FALSE_POSITIVE,
        self::STATUS_ACCEPTED_RISK,
        self::STATUS_CLOSED,
    ];

    protected $fillable = [
        'project_id',
        'endpoint_id',
        'test_case_id',
        'scan_run_id',
        'scan_result_id',
        'contract_validation_result_id',
        'title',
        'description',
        'source',
        'severity',
        'status',
        'reproduction_steps',
        'expected_result',
        'actual_result',
        'recommendation',
        'lifecycle_note',
        'lifecycle_changed_at',
        'lifecycle_changed_by_user_id',
        'reopened_count',
        'detected_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
            'lifecycle_changed_at' => 'datetime',
            'reopened_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Finding $finding): void {
            if (in_array($finding->status, self::RESOLVED_STATUSES, true)) {
                if (! $finding->resolved_at) {
                    $finding->resolved_at = now();
                }
            } else {
                $finding->resolved_at = null;
            }

            if (! $finding->detected_at) {
                $finding->detected_at = now();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    public function scanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class);
    }

    public function scanResult(): BelongsTo
    {
        return $this->belongsTo(ScanResult::class);
    }

    public function contractValidationResult(): BelongsTo
    {
        return $this->belongsTo(ContractValidationResult::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(FindingEvidence::class)->latest();
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(FindingLifecycleEvent::class)->latest('changed_at')->latest();
    }

    public function lifecycleChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifecycle_changed_by_user_id');
    }


    /** @return array<string, string> */
    public static function lifecycleStatusOptions(): array
    {
        return collect(self::LIFECYCLE_STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => __('messages.findings.statuses.'.$status)])
            ->all();
    }

    /** @return array<string, string> */
    public static function lifecycleTransitionsFor(string $status): array
    {
        return match ($status) {
            self::STATUS_OPEN => [
                self::STATUS_CONFIRMED => __('messages.findings.lifecycle.actions.confirm'),
                self::STATUS_IN_PROGRESS => __('messages.findings.lifecycle.actions.start'),
                self::STATUS_FIXED => __('messages.findings.lifecycle.actions.mark_fixed'),
                self::STATUS_FALSE_POSITIVE => __('messages.findings.lifecycle.actions.false_positive'),
                self::STATUS_ACCEPTED_RISK => __('messages.findings.lifecycle.actions.accept_risk'),
            ],
            self::STATUS_CONFIRMED, self::STATUS_TRIAGED => [
                self::STATUS_IN_PROGRESS => __('messages.findings.lifecycle.actions.start'),
                self::STATUS_FIXED => __('messages.findings.lifecycle.actions.mark_fixed'),
                self::STATUS_FALSE_POSITIVE => __('messages.findings.lifecycle.actions.false_positive'),
                self::STATUS_ACCEPTED_RISK => __('messages.findings.lifecycle.actions.accept_risk'),
            ],
            self::STATUS_IN_PROGRESS => [
                self::STATUS_FIXED => __('messages.findings.lifecycle.actions.mark_fixed'),
                self::STATUS_FALSE_POSITIVE => __('messages.findings.lifecycle.actions.false_positive'),
                self::STATUS_ACCEPTED_RISK => __('messages.findings.lifecycle.actions.accept_risk'),
                self::STATUS_REOPENED => __('messages.findings.lifecycle.actions.reopen'),
            ],
            self::STATUS_FIXED, self::STATUS_FALSE_POSITIVE, self::STATUS_ACCEPTED_RISK, self::STATUS_CLOSED => [
                self::STATUS_REOPENED => __('messages.findings.lifecycle.actions.reopen'),
            ],
            self::STATUS_REOPENED => [
                self::STATUS_CONFIRMED => __('messages.findings.lifecycle.actions.confirm'),
                self::STATUS_IN_PROGRESS => __('messages.findings.lifecycle.actions.start'),
                self::STATUS_FIXED => __('messages.findings.lifecycle.actions.mark_fixed'),
                self::STATUS_FALSE_POSITIVE => __('messages.findings.lifecycle.actions.false_positive'),
                self::STATUS_ACCEPTED_RISK => __('messages.findings.lifecycle.actions.accept_risk'),
            ],
            default => [],
        };
    }

    /** @return array<string, string> */
    public function availableLifecycleTransitions(): array
    {
        return self::lifecycleTransitionsFor($this->status);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.findings.statuses.'.$this->status);
    }

    public function getSeverityLabelAttribute(): string
    {
        return __('messages.findings.severities.'.$this->severity);
    }

    public function getSourceLabelAttribute(): string
    {
        return __('messages.findings.sources.'.$this->source);
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'danger',
            self::STATUS_CONFIRMED => 'warning',
            self::STATUS_TRIAGED => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_REOPENED => 'danger',
            self::STATUS_FIXED => 'success',
            self::STATUS_FALSE_POSITIVE => 'default',
            self::STATUS_ACCEPTED_RISK => 'warning',
            self::STATUS_CLOSED => 'default',
            default => 'default',
        };
    }

    public function getSeverityCssAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_HIGH => 'warning',
            self::SEVERITY_LOW => 'success',
            default => 'info',
        };
    }

    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
