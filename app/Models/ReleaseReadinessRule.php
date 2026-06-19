<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseReadinessRule extends Model
{
    use HasFactory;

    public const FAILURE_LEVELS = ['blocker', 'warning'];

    public const DEFAULT_RULES = [
        ['environment', 'core', 'server-cog', 'blocker'],
        ['endpoint_inventory', 'core', 'list-tree', 'blocker'],
        ['safe_endpoint_inventory', 'core', 'shield-check', 'blocker'],
        ['safe_scan', 'scan', 'radar', 'blocker'],
        ['endpoint_quick_test_coverage', 'test', 'activity', 'warning'],
        ['endpoint_quick_test_failures', 'test', 'zap-off', 'warning'],
        ['endpoint_batch_evidence', 'test', 'layers', 'warning'],
        ['endpoint_batch_failures', 'test', 'list-checks', 'warning'],
        ['endpoint_snapshot_baseline', 'regression', 'camera', 'warning'],
        ['endpoint_regression_compare', 'regression', 'arrows-diff', 'warning'],
        ['endpoint_regression_clean', 'regression', 'git-compare', 'warning'],
        ['endpoint_regression_triage', 'regression', 'bug', 'warning'],
        ['scan_failures', 'scan', 'circle-alert', 'blocker'],
        ['critical_findings', 'findings', 'bug', 'blocker'],
        ['high_findings', 'findings', 'triangle-alert', 'warning'],
        ['evidence_repository', 'evidence', 'archive', 'warning'],
        ['missing_evidence', 'evidence', 'file-warning', 'warning'],
        ['retest_queue', 'retest', 'rotate-ccw', 'blocker'],
        ['retest_failures', 'retest', 'shield-x', 'blocker'],
        ['retest_evidence', 'retest', 'test-tube', 'warning'],
        ['retest_closure_clean', 'retest', 'shield-check', 'blocker'],
        ['retest_closure_evidence', 'retest', 'certificate', 'warning'],
        ['regression_retest_closure', 'retest', 'git-pull-request-closed', 'blocker'],
        ['accepted_risk_expiry', 'risk', 'shield-alert', 'blocker'],
        ['accepted_risk_renewal_window', 'risk', 'calendar-clock', 'warning'],
        ['accepted_risk_ledger', 'risk', 'shield-check', 'warning'],
        ['contract_validation_present', 'contract', 'file-check-2', 'warning'],
        ['contract_validation_blockers', 'contract', 'file-warning', 'blocker'],
        ['contract_validation_clean', 'contract', 'file-search', 'warning'],
        ['external_qa_import_present', 'import', 'brackets-contain', 'warning'],
        ['external_qa_import_applied', 'import', 'save', 'warning'],
        ['external_qa_import_blockers', 'import', 'file-warning', 'warning'],
        ['external_qa_import_conflicts', 'import', 'shield-alert', 'blocker'],
        ['release_goal', 'release', 'target', 'warning'],
    ];

    protected $fillable = [
        'project_id', 'rule_key', 'category', 'icon', 'enabled', 'failure_level', 'default_failure_level', 'sort_order', 'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'metadata_json' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function syncDefaults(Project $project): void
    {
        foreach (self::DEFAULT_RULES as $index => [$key, $category, $icon, $level]) {
            self::firstOrCreate([
                'project_id' => $project->id,
                'rule_key' => $key,
            ], [
                'category' => $category,
                'icon' => $icon,
                'enabled' => true,
                'failure_level' => $level,
                'default_failure_level' => $level,
                'sort_order' => $index + 1,
                'metadata_json' => [],
            ]);
        }
    }

    public function getRuleLabelAttribute(): string
    {
        return __('messages.release_readiness.checks.'.($this->rule_key ?: 'environment'));
    }

    public function getRuleHintAttribute(): string
    {
        return __('messages.release_readiness.hints.'.($this->rule_key ?: 'environment'));
    }

    public function getFailureLevelLabelAttribute(): string
    {
        return __('messages.release_readiness.levels.'.($this->failure_level ?: 'warning'));
    }

    public function getFailureLevelToneAttribute(): string
    {
        return $this->failure_level === 'blocker' ? 'danger' : 'warning';
    }

    public function getCategoryLabelAttribute(): string
    {
        return __('messages.release_readiness.rule_categories.'.($this->category ?: 'core'));
    }
}
