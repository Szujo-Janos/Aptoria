<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseDecision extends Model
{
    use HasFactory;

    public const STATUS_GO = 'go';
    public const STATUS_NO_GO = 'no_go';
    public const STATUS_CONDITIONAL_GO = 'conditional_go';
    public const STATUS_PENDING_EVIDENCE = 'pending_evidence';
    public const STATUS_BLOCKED = 'blocked';

    public const STATUSES = [
        self::STATUS_GO,
        self::STATUS_NO_GO,
        self::STATUS_CONDITIONAL_GO,
        self::STATUS_PENDING_EVIDENCE,
        self::STATUS_BLOCKED,
    ];

    protected $fillable = [
        'project_id',
        'decision_owner_user_id',
        'qa_release_gate_id',
        'release_name',
        'target_environment',
        'decision_status',
        'decided_at',
        'decision_notes',
        'release_score',
        'readiness_status',
        'blocker_count',
        'warning_count',
        'accepted_risk_count',
        'blind_spot_count',
        'decision_package_json',
        'package_checksum',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
            'release_score' => 'integer',
            'blocker_count' => 'integer',
            'warning_count' => 'integer',
            'accepted_risk_count' => 'integer',
            'blind_spot_count' => 'integer',
            'decision_package_json' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ReleaseDecision $decision): void {
            if (! $decision->decision_status) {
                $decision->decision_status = self::STATUS_PENDING_EVIDENCE;
            }

            if ($decision->decision_status !== self::STATUS_PENDING_EVIDENCE && ! $decision->decided_at) {
                $decision->decided_at = now();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_owner_user_id');
    }

    public function releaseGate(): BelongsTo
    {
        return $this->belongsTo(QaReleaseGate::class, 'qa_release_gate_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.release_decisions.statuses.'.($this->decision_status ?: self::STATUS_PENDING_EVIDENCE));
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->decision_status) {
            self::STATUS_GO => 'success',
            self::STATUS_CONDITIONAL_GO => 'warning',
            self::STATUS_NO_GO, self::STATUS_BLOCKED => 'danger',
            self::STATUS_PENDING_EVIDENCE => 'info',
            default => 'default',
        };
    }
}
