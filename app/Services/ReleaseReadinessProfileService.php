<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectSetting;
use App\Models\ReleaseReadinessRule;
use Illuminate\Support\Collection;

class ReleaseReadinessProfileService
{
    public const SETTING_KEY = 'release_readiness.profile';

    public function profiles(): array
    {
        return [
            'standard' => ['icon' => 'shield-check', 'tone' => 'primary', 'rules' => []],
            'strict' => ['icon' => 'shield-alert', 'tone' => 'danger', 'rules' => $this->strictOverrides()],
            'client_delivery' => ['icon' => 'door-open', 'tone' => 'success', 'rules' => $this->clientDeliveryOverrides()],
            'regression_heavy' => ['icon' => 'git-compare', 'tone' => 'warning', 'rules' => $this->regressionOverrides()],
            'import_heavy' => ['icon' => 'brackets-contain', 'tone' => 'info', 'rules' => $this->importOverrides()],
            'demo_relaxed' => ['icon' => 'flask-conical', 'tone' => 'secondary', 'rules' => $this->demoOverrides()],
        ];
    }

    public function currentProfile(Project $project): string
    {
        return (string) ProjectSetting::get($project, self::SETTING_KEY, 'standard');
    }

    public function apply(Project $project, string $profileKey): array
    {
        $profiles = $this->profiles();
        $profileKey = array_key_exists($profileKey, $profiles) ? $profileKey : 'standard';
        ReleaseReadinessRule::syncDefaults($project);
        $rules = $project->releaseReadinessRules()->get()->keyBy('rule_key');
        $overrides = $profiles[$profileKey]['rules'] ?? [];

        foreach ($rules as $rule) {
            $override = $overrides[$rule->rule_key] ?? null;
            $rule->update([
                'enabled' => (bool) ($override['enabled'] ?? true),
                'failure_level' => (string) ($override['failure_level'] ?? $rule->default_failure_level ?? 'warning'),
                'metadata_json' => array_merge(is_array($rule->metadata_json) ? $rule->metadata_json : [], [
                    'profile_key' => $profileKey,
                    'profile_applied_at' => now()->toDateTimeString(),
                ]),
            ]);
        }

        ProjectSetting::set($project, self::SETTING_KEY, $profileKey);

        return $this->summary($project);
    }

    public function summary(Project $project): array
    {
        ReleaseReadinessRule::syncDefaults($project);
        $profileKey = $this->currentProfile($project);
        $rules = $project->releaseReadinessRules()->orderBy('sort_order')->get();
        $deviations = $this->deviations($project, $profileKey, $rules);

        return [
            'profile_key' => $profileKey,
            'profile_label' => __('messages.release_readiness.profiles.'.$profileKey),
            'profile_icon' => $this->profiles()[$profileKey]['icon'] ?? 'sliders-horizontal',
            'profile_tone' => $this->profiles()[$profileKey]['tone'] ?? 'primary',
            'deviation_count' => count($deviations),
            'deviations' => $deviations,
        ];
    }

    public function deviations(Project $project, string $profileKey, ?Collection $rules = null): array
    {
        $profiles = $this->profiles();
        $overrides = $profiles[$profileKey]['rules'] ?? [];
        $rules ??= $project->releaseReadinessRules()->orderBy('sort_order')->get();
        $deviations = [];

        foreach ($rules as $rule) {
            $expectedEnabled = (bool) ($overrides[$rule->rule_key]['enabled'] ?? true);
            $expectedLevel = (string) ($overrides[$rule->rule_key]['failure_level'] ?? $rule->default_failure_level ?? 'warning');
            if ((bool) $rule->enabled !== $expectedEnabled || (string) $rule->failure_level !== $expectedLevel) {
                $deviations[] = [
                    'key' => $rule->rule_key,
                    'label' => $rule->rule_label,
                    'enabled' => (bool) $rule->enabled,
                    'failure_level' => $rule->failure_level,
                    'profile_enabled' => $expectedEnabled,
                    'profile_failure_level' => $expectedLevel,
                ];
            }
        }

        return $deviations;
    }

    private function strictOverrides(): array
    {
        return array_fill_keys(['endpoint_quick_test_coverage','endpoint_quick_test_failures','endpoint_batch_evidence','endpoint_batch_failures','endpoint_snapshot_baseline','endpoint_regression_compare','endpoint_regression_clean','endpoint_regression_triage','high_findings','missing_evidence','retest_evidence','accepted_risk_ledger','contract_validation_present','contract_validation_clean','external_qa_import_present','external_qa_import_applied','external_qa_import_blockers','release_goal'], ['failure_level' => 'blocker']);
    }

    private function clientDeliveryOverrides(): array
    {
        return array_merge($this->strictOverrides(), [
            'external_qa_import_present' => ['failure_level' => 'warning'],
            'external_qa_import_applied' => ['failure_level' => 'warning'],
            'release_goal' => ['failure_level' => 'blocker'],
        ]);
    }

    private function regressionOverrides(): array
    {
        return [
            'endpoint_snapshot_baseline' => ['failure_level' => 'blocker'],
            'endpoint_regression_compare' => ['failure_level' => 'blocker'],
            'endpoint_regression_clean' => ['failure_level' => 'blocker'],
            'endpoint_regression_triage' => ['failure_level' => 'blocker'],
            'regression_retest_closure' => ['failure_level' => 'blocker'],
        ];
    }

    private function importOverrides(): array
    {
        return [
            'external_qa_import_present' => ['failure_level' => 'blocker'],
            'external_qa_import_applied' => ['failure_level' => 'blocker'],
            'external_qa_import_blockers' => ['failure_level' => 'blocker'],
            'external_qa_import_conflicts' => ['failure_level' => 'blocker'],
        ];
    }

    private function demoOverrides(): array
    {
        return [
            'safe_scan' => ['failure_level' => 'warning'],
            'endpoint_quick_test_coverage' => ['enabled' => false],
            'endpoint_batch_evidence' => ['enabled' => false],
            'contract_validation_present' => ['failure_level' => 'warning'],
            'external_qa_import_present' => ['failure_level' => 'warning'],
            'release_goal' => ['enabled' => false],
        ];
    }
}
