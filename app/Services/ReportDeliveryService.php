<?php

namespace App\Services;

use App\Models\ClientPortalAccess;
use App\Models\Project;
use App\Models\ReportVersion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReportDeliveryService
{
    public function approvedReports(Project $project)
    {
        return $project->reportVersions()
            ->where('status', 'approved')
            ->latest('approved_at')
            ->latest('generated_at')
            ->latest();
    }

    public function isDeliverable(ReportVersion $report): bool
    {
        return $report->status === 'approved';
    }

    public function ensureDeliverable(ReportVersion $report): void
    {
        if (! $this->isDeliverable($report)) {
            throw ValidationException::withMessages([
                'report_version_id' => __('messages.client_portal.approved_only_error'),
            ]);
        }
    }

    public function createDeliveryLink(Project $project, ReportVersion $report, ?User $user, array $options = []): ClientPortalAccess
    {
        abort_unless((int) $report->project_id === (int) $project->id, 404);
        $this->ensureDeliverable($report);

        $name = trim((string) ($options['name'] ?? ''));
        if ($name === '') {
            $name = __('messages.client_portal.delivery_default_name', [
                'report' => $report->title,
            ]);
        }

        $access = $project->clientPortalAccesses()->create([
            'report_version_id' => $report->id,
            'created_by_user_id' => $user?->id,
            'name' => $name,
            'role' => $options['role'] ?? 'client_approver',
            'permissions_json' => ['reports', 'readiness'],
            'is_active' => true,
            'acknowledge_required' => (bool) ($options['acknowledge_required'] ?? true),
            'acknowledgement_status' => (bool) ($options['acknowledge_required'] ?? true) ? 'pending' : 'not_required',
            'expires_at' => $this->parseDate($options['expires_at'] ?? null),
        ]);

        $this->markDelivered($report, $access);

        return $access;
    }

    public function markDelivered(ReportVersion $report, ClientPortalAccess $access): void
    {
        $summary = is_array($report->client_delivery_summary_json) ? $report->client_delivery_summary_json : [];
        $summary['last_delivery'] = [
            'client_portal_access_id' => $access->id,
            'name' => $access->name,
            'role' => $access->role,
            'acknowledge_required' => $access->acknowledge_required,
            'delivered_at' => now()->toDateTimeString(),
        ];

        $report->forceFill([
            'client_delivery_count' => ((int) $report->client_delivery_count) + 1,
            'client_last_delivered_at' => now(),
            'client_delivery_summary_json' => $summary,
        ])->save();
    }

    public function recordPublicDownload(ReportVersion $report, ClientPortalAccess $access, string $format): void
    {
        $summary = is_array($report->client_delivery_summary_json) ? $report->client_delivery_summary_json : [];
        $summary['last_download'] = [
            'client_portal_access_id' => $access->id,
            'format' => $format,
            'downloaded_at' => now()->toDateTimeString(),
        ];

        $report->forceFill([
            'client_download_count' => ((int) $report->client_download_count) + 1,
            'client_last_downloaded_at' => now(),
            'client_delivery_summary_json' => $summary,
        ])->save();
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->endOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
