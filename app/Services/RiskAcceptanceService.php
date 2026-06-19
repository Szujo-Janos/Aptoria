<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\Project;
use App\Models\RiskAcceptance;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class RiskAcceptanceService
{
    public function accept(Project $project, Finding $finding, ?User $user, array $data, ?RiskAcceptance $previous = null): RiskAcceptance
    {
        $previous ??= $finding->riskAcceptances()
            ->where('status', 'active')
            ->latest('accepted_at')
            ->first();

        if ($previous) {
            $previous->update([
                'status' => 'renewed',
                'metadata_json' => array_merge($previous->metadata_json ?? [], [
                    'renewed_at' => now()->toDateTimeString(),
                    'renewed_by_user_id' => $user?->id,
                    'was_expired_before_renewal' => $previous->is_expired,
                    'previous_display_status' => $previous->display_status,
                ]),
            ]);
        }

        return $finding->riskAcceptances()->create([
            'project_id' => $project->id,
            'accepted_by_user_id' => $user?->id,
            'renewed_from_id' => $previous?->id,
            'status' => 'active',
            'accepted_at' => now(),
            'accepted_until' => $data['accepted_until'] ?? null,
            'reason' => $data['reason'],
            'business_justification' => $data['business_justification'] ?? null,
            'mitigation_note' => $data['mitigation_note'] ?? null,
            'release_scope' => $data['release_scope'] ?? null,
            'metadata_json' => [
                'finding_status_at_acceptance' => $finding->status,
                'finding_severity_at_acceptance' => $finding->severity,
                'finding_retest_status_at_acceptance' => $finding->retest_status,
                'created_from_workflow' => $previous ? 'renewal' : 'acceptance',
                'renewed_from_status' => $previous?->status,
                'renewed_from_display_status' => $previous?->display_status,
            ],
        ]);
    }

    public function renewLatest(Project $project, Finding $finding, ?User $user, array $data): RiskAcceptance
    {
        $previous = $finding->riskAcceptances()
            ->whereIn('status', ['active', 'expired', 'renewed'])
            ->latest('accepted_at')
            ->latest()
            ->first();

        return $this->accept($project, $finding, $user, $data, $previous);
    }

    public function revoke(Project $project, Finding $finding, ?User $user, ?string $note = null): ?RiskAcceptance
    {
        $acceptance = $finding->activeRiskAcceptance()->first();
        if (! $acceptance) {
            return null;
        }

        $acceptance->update([
            'status' => 'revoked',
            'revoked_by_user_id' => $user?->id,
            'revoked_at' => now(),
            'metadata_json' => array_merge($acceptance->metadata_json ?? [], [
                'revocation_note' => $note,
                'revoked_project_id' => $project->id,
            ]),
        ]);

        return $acceptance;
    }

    public function closeFinding(Project $project, Finding $finding, ?User $user, ?string $note = null): Finding
    {
        $acceptance = $finding->active_risk_acceptance;

        $finding->update([
            'status' => 'verified',
            'retest_required' => false,
            'retest_status' => $finding->retest_status === 'passed' ? 'passed' : 'not_required',
            'metadata_json' => array_merge($finding->metadata_json ?? [], [
                'closed_from_risk_acceptance_review_at' => now()->toDateTimeString(),
                'closed_from_risk_acceptance_review_by_user_id' => $user?->id,
                'closed_from_risk_acceptance_id' => $acceptance?->id,
                'closed_from_risk_acceptance_note' => $note,
                'closed_project_id' => $project->id,
            ]),
        ]);

        return $finding->refresh();
    }

    public function summary(Project $project): array
    {
        if (! Schema::hasTable('risk_acceptances')) {
            return [
                'active' => 0,
                'expiring_soon' => 0,
                'expired' => 0,
                'revoked' => 0,
                'renewed' => 0,
                'open_high_critical_accepted' => 0,
                'open_high_critical_unaccepted' => 0,
                'next_expiry_at' => null,
                'rows' => [],
            ];
        }

        $acceptances = $project->riskAcceptances()->with(['finding', 'acceptedBy', 'revokedBy'])->get();
        $activeValid = $acceptances->filter(fn (RiskAcceptance $acceptance) => $acceptance->is_active_and_valid);
        $expiringSoon = $activeValid->filter(fn (RiskAcceptance $acceptance) => $acceptance->is_expiring_soon);
        $expiredActive = $acceptances->filter(fn (RiskAcceptance $acceptance) => $acceptance->status === 'active' && $acceptance->is_expired);

        $openHighCritical = $project->findings()
            ->whereIn('severity', ['critical', 'high'])
            ->whereNotIn('status', ['verified'])
            ->get();

        $acceptedFindingIds = $activeValid->pluck('finding_id')->unique()->all();
        $watchRows = $acceptances
            ->filter(fn (RiskAcceptance $acceptance) => in_array($acceptance->display_status, ['active', 'expiring_soon', 'expired'], true))
            ->sortBy(fn (RiskAcceptance $acceptance) => $acceptance->accepted_until?->toDateString() ?? '9999-12-31')
            ->take(20)
            ->map(fn (RiskAcceptance $acceptance): array => [
                'id' => $acceptance->id,
                'finding_id' => $acceptance->finding_id,
                'finding_title' => $acceptance->finding?->title,
                'finding_severity' => $acceptance->finding?->severity,
                'finding_status' => $acceptance->finding?->status,
                'release_scope' => $acceptance->release_scope,
                'reason' => $acceptance->reason,
                'status' => $acceptance->display_status,
                'status_label' => $acceptance->status_label,
                'tone' => $acceptance->status_tone,
                'accepted_until' => $acceptance->accepted_until?->toDateString(),
                'days_until_expiry' => $acceptance->days_until_expiry,
                'accepted_by' => $acceptance->acceptedBy?->name,
            ])
            ->values()
            ->all();

        return [
            'active' => $activeValid->count(),
            'expiring_soon' => $expiringSoon->count(),
            'expired' => $expiredActive->count(),
            'revoked' => $acceptances->where('status', 'revoked')->count(),
            'renewed' => $acceptances->where('status', 'renewed')->count(),
            'open_high_critical_accepted' => $openHighCritical->whereIn('id', $acceptedFindingIds)->count(),
            'open_high_critical_unaccepted' => $openHighCritical->whereNotIn('id', $acceptedFindingIds)->count(),
            'latest_active_until' => optional($activeValid->sortByDesc('accepted_until')->first()?->accepted_until)->toDateString(),
            'next_expiry_at' => optional($activeValid->sortBy('accepted_until')->first()?->accepted_until)->toDateString(),
            'watch_window_days' => RiskAcceptance::EXPIRING_SOON_DAYS,
            'rows' => $watchRows,
        ];
    }
}
