<?php

namespace App\Services\Risk;

use App\Models\Finding;
use App\Models\Project;
use App\Models\RiskAcceptance;
use Illuminate\Support\Collection;

class RiskAcceptanceLedgerService
{
    /** @return array<string, mixed> */
    public function summarize(Project $project): array
    {
        $items = $project->riskAcceptances()
            ->with(['finding.endpoint', 'acceptedBy'])
            ->latest('accepted_at')
            ->latest()
            ->get();

        $active = $items->where('status', RiskAcceptance::STATUS_ACTIVE);
        $expired = $items->filter(fn (RiskAcceptance $acceptance): bool => $acceptance->is_expired);
        $expiringSoon = $items->filter(fn (RiskAcceptance $acceptance): bool => $acceptance->expires_soon);
        $withoutExpiry = $active->filter(fn (RiskAcceptance $acceptance): bool => ! $acceptance->has_expiry);
        $activeHighOrCritical = $active->filter(fn (RiskAcceptance $acceptance): bool => in_array($acceptance->finding?->severity, [Finding::SEVERITY_HIGH, Finding::SEVERITY_CRITICAL], true));

        return [
            'summary' => [
                'total' => $items->count(),
                'active' => $active->count(),
                'active_high_or_critical' => $activeHighOrCritical->count(),
                'without_expiry' => $withoutExpiry->count(),
                'expiring_soon' => $expiringSoon->count(),
                'expired' => $expired->count(),
                'renewed' => $items->where('status', RiskAcceptance::STATUS_RENEWED)->count(),
                'revoked' => $items->where('status', RiskAcceptance::STATUS_REVOKED)->count(),
            ],
            'items' => $items,
            'active_items' => $active->values(),
            'expired_items' => $expired->values(),
            'expiring_soon_items' => $expiringSoon->values(),
            'without_expiry_items' => $withoutExpiry->values(),
        ];
    }

    /** @return array<string, mixed> */
    public function empty(): array
    {
        return [
            'summary' => [
                'total' => 0,
                'active' => 0,
                'active_high_or_critical' => 0,
                'without_expiry' => 0,
                'expiring_soon' => 0,
                'expired' => 0,
                'renewed' => 0,
                'revoked' => 0,
            ],
            'items' => collect(),
            'active_items' => collect(),
            'expired_items' => collect(),
            'expiring_soon_items' => collect(),
            'without_expiry_items' => collect(),
        ];
    }
}
