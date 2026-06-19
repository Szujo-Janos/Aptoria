<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseReadinessRun extends Model
{
    use HasFactory;

    public const STATUSES = ['ready', 'warning', 'blocked'];

    protected $fillable = [
        'project_id',
        'generated_by_user_id',
        'status',
        'score',
        'grade',
        'blocker_count',
        'warning_count',
        'check_count',
        'passed_check_count',
        'metrics_json',
        'checks_json',
        'rules_json',
        'readiness_profile_key',
        'rule_deviations_json',
        'summary_json',
        'retest_closure_json',
        'risk_acceptance_json',
        'contract_validation_json',
        'decision_note',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'blocker_count' => 'integer',
            'warning_count' => 'integer',
            'check_count' => 'integer',
            'passed_check_count' => 'integer',
            'metrics_json' => 'array',
            'checks_json' => 'array',
            'rules_json' => 'array',
            'rule_deviations_json' => 'array',
            'summary_json' => 'array',
            'retest_closure_json' => 'array',
            'risk_acceptance_json' => 'array',
            'contract_validation_json' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.release_readiness.statuses.'.($this->status ?: 'blocked'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'ready' => 'success',
            'warning' => 'warning',
            default => 'danger',
        };
    }

    public function getChecksAttribute(): array
    {
        return is_array($this->checks_json) ? $this->checks_json : [];
    }

    public function getRulesAttribute(): array
    {
        return is_array($this->rules_json) ? $this->rules_json : [];
    }

    public function getRuleDeviationsAttribute(): array
    {
        return is_array($this->rule_deviations_json) ? $this->rule_deviations_json : [];
    }

    public function getReadinessProfileLabelAttribute(): string
    {
        return __('messages.release_readiness.profiles.'.($this->readiness_profile_key ?: 'custom'));
    }

    public function getMetricsAttribute(): array
    {
        return is_array($this->metrics_json) ? $this->metrics_json : [];
    }

    public function getSummaryAttribute(): array
    {
        return is_array($this->summary_json) ? $this->summary_json : [];
    }

    public function getRetestClosureAttribute(): array
    {
        return is_array($this->retest_closure_json) ? $this->retest_closure_json : [];
    }

    public function getRiskAcceptanceAttribute(): array
    {
        return is_array($this->risk_acceptance_json) ? $this->risk_acceptance_json : [];
    }
    public function getContractValidationAttribute(): array
    {
        return is_array($this->contract_validation_json) ? $this->contract_validation_json : [];
    }

}


