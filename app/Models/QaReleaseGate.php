<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaReleaseGate extends Model
{
    use HasFactory;

    public const STATUS_PASS = 'pass';
    public const STATUS_WARNING = 'warning';
    public const STATUS_BLOCKED = 'blocked';

    public const STATUSES = [
        self::STATUS_PASS,
        self::STATUS_WARNING,
        self::STATUS_BLOCKED,
    ];

    public const DECISION_PENDING = 'pending';
    public const DECISION_PASS = 'pass';
    public const DECISION_CONDITIONAL_PASS = 'conditional_pass';
    public const DECISION_BLOCKED = 'blocked';

    public const DECISIONS = [
        self::DECISION_PENDING,
        self::DECISION_PASS,
        self::DECISION_CONDITIONAL_PASS,
        self::DECISION_BLOCKED,
    ];

    public const PROFILE_STANDARD = 'standard';
    public const PROFILE_STRICT = 'strict';

    public const PROFILES = [
        self::PROFILE_STANDARD,
        self::PROFILE_STRICT,
    ];

    protected $fillable = [
        'project_id',
        'release_name',
        'target_environment',
        'gate_profile',
        'automated_status',
        'final_decision',
        'score',
        'grade',
        'endpoint_count',
        'endpoint_coverage_percent',
        'qa_coverage_percent',
        'test_execution_percent',
        'test_pass_rate',
        'blocker_count',
        'warning_count',
        'evidence_count',
        'reviewed_by',
        'reviewed_at',
        'decision_notes',
        'summary_json',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'endpoint_count' => 'integer',
            'endpoint_coverage_percent' => 'integer',
            'qa_coverage_percent' => 'integer',
            'test_execution_percent' => 'integer',
            'test_pass_rate' => 'integer',
            'blocker_count' => 'integer',
            'warning_count' => 'integer',
            'evidence_count' => 'integer',
            'reviewed_at' => 'datetime',
            'summary_json' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QaReleaseGateItem::class)->orderByRaw("CASE item_type WHEN 'blocker' THEN 1 WHEN 'warning' THEN 2 WHEN 'evidence' THEN 3 ELSE 4 END")->orderBy('id');
    }

    public function blockers(): HasMany
    {
        return $this->hasMany(QaReleaseGateItem::class)->where('item_type', QaReleaseGateItem::TYPE_BLOCKER)->orderBy('id');
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(QaReleaseGateItem::class)->where('item_type', QaReleaseGateItem::TYPE_WARNING)->orderBy('id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(QaReleaseGateItem::class)->where('item_type', QaReleaseGateItem::TYPE_EVIDENCE)->orderBy('id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(QaReleaseGateItem::class)->where('item_type', QaReleaseGateItem::TYPE_RECOMMENDATION)->orderBy('id');
    }

    public function getAutomatedStatusLabelAttribute(): string
    {
        return __('messages.release_gates.statuses.'.$this->automated_status);
    }

    public function getFinalDecisionLabelAttribute(): string
    {
        return __('messages.release_gates.decisions.'.$this->final_decision);
    }

    public function getProfileLabelAttribute(): string
    {
        return __('messages.release_gates.profiles.'.$this->gate_profile);
    }

    public function getAutomatedStatusCssAttribute(): string
    {
        return match ($this->automated_status) {
            self::STATUS_PASS => 'success',
            self::STATUS_WARNING => 'warning',
            self::STATUS_BLOCKED => 'danger',
            default => 'default',
        };
    }

    public function getFinalDecisionCssAttribute(): string
    {
        return match ($this->final_decision) {
            self::DECISION_PASS => 'success',
            self::DECISION_CONDITIONAL_PASS => 'warning',
            self::DECISION_BLOCKED => 'danger',
            default => 'default',
        };
    }
}
