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
    public const STATUS_READY_FOR_RETEST = 'ready_for_retest';
    public const STATUS_RETEST_FAILED = 'retest_failed';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_FALSE_POSITIVE = 'false_positive';
    public const STATUS_ACCEPTED_RISK = 'accepted_risk';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_REOPENED = 'reopened';

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

    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_READY_FOR_RETEST = 'ready_for_retest';
    public const VERIFICATION_RETEST_FAILED = 'retest_failed';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_NOT_REQUIRED = 'not_required';

    public const VERIFICATION_STATUSES = [
        self::VERIFICATION_PENDING,
        self::VERIFICATION_READY_FOR_RETEST,
        self::VERIFICATION_RETEST_FAILED,
        self::VERIFICATION_VERIFIED,
        self::VERIFICATION_NOT_REQUIRED,
    ];

    public const RETEST_PENDING = 'pending';
    public const RETEST_PASS = 'pass';
    public const RETEST_FAIL = 'fail';
    public const RETEST_BLOCKED = 'blocked';

    public const RETEST_RESULTS = [
        self::RETEST_PENDING,
        self::RETEST_PASS,
        self::RETEST_FAIL,
        self::RETEST_BLOCKED,
    ];

    /**
     * Canonical finding lifecycle states shown in the v1.1.9 UI and reports.
     * Legacy states are still accepted through STATUSES for older databases.
     */
    public const LIFECYCLE_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_CONFIRMED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_FIXED,
        self::STATUS_READY_FOR_RETEST,
        self::STATUS_RETEST_FAILED,
        self::STATUS_VERIFIED,
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
        self::STATUS_READY_FOR_RETEST,
        self::STATUS_RETEST_FAILED,
        self::STATUS_VERIFIED,
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
        self::STATUS_READY_FOR_RETEST,
        self::STATUS_RETEST_FAILED,
        self::STATUS_REOPENED,
    ];

    public const RESOLVED_STATUSES = [
        self::STATUS_FIXED,
        self::STATUS_VERIFIED,
        self::STATUS_FALSE_POSITIVE,
        self::STATUS_ACCEPTED_RISK,
        self::STATUS_CLOSED,
    ];

    protected $fillable = [
        'project_id',
        'owner_user_id',
        'endpoint_id',
        'test_case_id',
        'linked_release_gate_id',
        'scan_run_id',
        'scan_result_id',
        'contract_validation_result_id',
        'title',
        'description',
        'source',
        'severity',
        'priority',
        'status',
        'verification_status',
        'retest_required',
        'retest_result',
        'fix_evidence_required',
        'verified_by_user_id',
        'verified_at',
        'last_retest_at',
        'due_date',
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
        'accepted_risk_expires_at',
        'accepted_risk_note',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
            'due_date' => 'datetime',
            'verified_at' => 'datetime',
            'last_retest_at' => 'datetime',
            'retest_required' => 'boolean',
            'fix_evidence_required' => 'boolean',
            'accepted_risk_expires_at' => 'datetime',
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

            if ($finding->status === self::STATUS_READY_FOR_RETEST) {
                $finding->verification_status = self::VERIFICATION_READY_FOR_RETEST;
                $finding->retest_required = true;
            } elseif ($finding->status === self::STATUS_RETEST_FAILED) {
                $finding->verification_status = self::VERIFICATION_RETEST_FAILED;
                $finding->retest_required = true;
                $finding->retest_result = self::RETEST_FAIL;
            } elseif ($finding->status === self::STATUS_VERIFIED) {
                $finding->verification_status = self::VERIFICATION_VERIFIED;
                $finding->retest_result = self::RETEST_PASS;
                if (! $finding->verified_at) {
                    $finding->verified_at = now();
                }
            } elseif ($finding->status === self::STATUS_FALSE_POSITIVE || $finding->status === self::STATUS_ACCEPTED_RISK) {
                $finding->verification_status = self::VERIFICATION_NOT_REQUIRED;
            } elseif (in_array($finding->status, [self::STATUS_OPEN, self::STATUS_CONFIRMED, self::STATUS_IN_PROGRESS, self::STATUS_REOPENED], true)
                && in_array($finding->verification_status, [self::VERIFICATION_VERIFIED, self::VERIFICATION_NOT_REQUIRED], true)) {
                $finding->verification_status = self::VERIFICATION_PENDING;
            }

            if (in_array($finding->retest_result, [self::RETEST_PASS, self::RETEST_FAIL, self::RETEST_BLOCKED], true) && ! $finding->last_retest_at) {
                $finding->last_retest_at = now();
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function linkedReleaseGate(): BelongsTo
    {
        return $this->belongsTo(QaReleaseGate::class, 'linked_release_gate_id');
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

    public function comments(): HasMany
    {
        return $this->hasMany(FindingComment::class)->latest();
    }

    public function riskAcceptances(): HasMany
    {
        return $this->hasMany(RiskAcceptance::class)->latest('accepted_at')->latest();
    }

    public function activeRiskAcceptance()
    {
        return $this->hasOne(RiskAcceptance::class)->where('status', RiskAcceptance::STATUS_ACTIVE)->latestOfMany('accepted_at');
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
                self::STATUS_READY_FOR_RETEST => __('messages.findings.lifecycle.actions.ready_for_retest'),
                self::STATUS_FIXED => __('messages.findings.lifecycle.actions.mark_fixed'),
                self::STATUS_FALSE_POSITIVE => __('messages.findings.lifecycle.actions.false_positive'),
                self::STATUS_ACCEPTED_RISK => __('messages.findings.lifecycle.actions.accept_risk'),
            ],
            self::STATUS_CONFIRMED, self::STATUS_TRIAGED => [
                self::STATUS_IN_PROGRESS => __('messages.findings.lifecycle.actions.start'),
                self::STATUS_READY_FOR_RETEST => __('messages.findings.lifecycle.actions.ready_for_retest'),
                self::STATUS_FIXED => __('messages.findings.lifecycle.actions.mark_fixed'),
                self::STATUS_FALSE_POSITIVE => __('messages.findings.lifecycle.actions.false_positive'),
                self::STATUS_ACCEPTED_RISK => __('messages.findings.lifecycle.actions.accept_risk'),
            ],
            self::STATUS_IN_PROGRESS => [
                self::STATUS_READY_FOR_RETEST => __('messages.findings.lifecycle.actions.ready_for_retest'),
                self::STATUS_FIXED => __('messages.findings.lifecycle.actions.mark_fixed'),
                self::STATUS_FALSE_POSITIVE => __('messages.findings.lifecycle.actions.false_positive'),
                self::STATUS_ACCEPTED_RISK => __('messages.findings.lifecycle.actions.accept_risk'),
                self::STATUS_REOPENED => __('messages.findings.lifecycle.actions.reopen'),
            ],
            self::STATUS_FIXED => [
                self::STATUS_READY_FOR_RETEST => __('messages.findings.lifecycle.actions.ready_for_retest'),
                self::STATUS_VERIFIED => __('messages.findings.lifecycle.actions.verify'),
                self::STATUS_REOPENED => __('messages.findings.lifecycle.actions.reopen'),
            ],
            self::STATUS_READY_FOR_RETEST => [
                self::STATUS_RETEST_FAILED => __('messages.findings.lifecycle.actions.retest_failed'),
                self::STATUS_VERIFIED => __('messages.findings.lifecycle.actions.verify'),
                self::STATUS_REOPENED => __('messages.findings.lifecycle.actions.reopen'),
            ],
            self::STATUS_RETEST_FAILED => [
                self::STATUS_IN_PROGRESS => __('messages.findings.lifecycle.actions.start'),
                self::STATUS_READY_FOR_RETEST => __('messages.findings.lifecycle.actions.ready_for_retest'),
                self::STATUS_FIXED => __('messages.findings.lifecycle.actions.mark_fixed'),
            ],
            self::STATUS_VERIFIED, self::STATUS_FALSE_POSITIVE, self::STATUS_ACCEPTED_RISK, self::STATUS_CLOSED => [
                self::STATUS_REOPENED => __('messages.findings.lifecycle.actions.reopen'),
            ],
            self::STATUS_REOPENED => [
                self::STATUS_CONFIRMED => __('messages.findings.lifecycle.actions.confirm'),
                self::STATUS_IN_PROGRESS => __('messages.findings.lifecycle.actions.start'),
                self::STATUS_READY_FOR_RETEST => __('messages.findings.lifecycle.actions.ready_for_retest'),
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

    public function getPriorityLabelAttribute(): string
    {
        return __('messages.findings.priorities.'.($this->priority ?: self::PRIORITY_MEDIUM));
    }

    public function getVerificationStatusLabelAttribute(): string
    {
        return __('messages.findings.verification_statuses.'.($this->verification_status ?: self::VERIFICATION_PENDING));
    }

    public function getRetestResultLabelAttribute(): string
    {
        return $this->retest_result ? __('messages.findings.retest_results.'.$this->retest_result) : __('messages.common.not_available');
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'danger',
            self::STATUS_CONFIRMED => 'warning',
            self::STATUS_TRIAGED => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_REOPENED => 'danger',
            self::STATUS_READY_FOR_RETEST => 'warning',
            self::STATUS_RETEST_FAILED => 'danger',
            self::STATUS_FIXED => 'success',
            self::STATUS_VERIFIED => 'success',
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

    public function getPriorityCssAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'danger',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_LOW => 'success',
            default => 'info',
        };
    }

    public function getVerificationStatusCssAttribute(): string
    {
        return match ($this->verification_status) {
            self::VERIFICATION_VERIFIED => 'success',
            self::VERIFICATION_READY_FOR_RETEST => 'warning',
            self::VERIFICATION_RETEST_FAILED => 'danger',
            self::VERIFICATION_NOT_REQUIRED => 'default',
            default => 'info',
        };
    }

    public function getRetestResultCssAttribute(): string
    {
        return match ($this->retest_result) {
            self::RETEST_PASS => 'success',
            self::RETEST_FAIL => 'danger',
            self::RETEST_BLOCKED => 'warning',
            default => 'default',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! in_array($this->verification_status, [self::VERIFICATION_VERIFIED, self::VERIFICATION_NOT_REQUIRED], true);
    }

    public function getHasRetestEvidenceAttribute(): bool
    {
        return $this->evidence->contains(fn (FindingEvidence $evidence): bool => $evidence->type === FindingEvidence::TYPE_RETEST);
    }

    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
