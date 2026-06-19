<?php

namespace App\Services;

use App\Models\ClientPortalAccess;
use App\Models\ClientPortalAcknowledgement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientPortalAcknowledgementService
{
    public function record(ClientPortalAccess $access, array $data, Request $request): ClientPortalAcknowledgement
    {
        $decision = (string) ($data['decision_status'] ?? 'reviewed');
        if (! in_array($decision, ClientPortalAcknowledgement::DECISIONS, true)) {
            $decision = 'reviewed';
        }

        $report = $access->reportVersion;
        $acknowledgement = ClientPortalAcknowledgement::create([
            'project_id' => $access->project_id,
            'client_portal_access_id' => $access->id,
            'report_version_id' => $report?->id,
            'decision_status' => $decision,
            'acknowledged_by_name' => $data['acknowledged_by_name'],
            'acknowledged_by_email' => $data['acknowledged_by_email'] ?? null,
            'comment' => $data['comment'] ?? null,
            'acknowledge_terms' => true,
            'evidence_summary_json' => $this->summary($access),
            'acknowledged_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        $access->forceFill([
            'acknowledgement_status' => 'acknowledged',
            'acknowledgement_decision' => $decision,
            'acknowledgement_comment' => $data['comment'] ?? null,
            'latest_acknowledgement_id' => $acknowledgement->id,
            'acknowledged_at' => $acknowledgement->acknowledged_at,
            'acknowledged_by_name' => $acknowledgement->acknowledged_by_name,
            'acknowledged_by_email' => $acknowledgement->acknowledged_by_email,
        ])->save();

        if ($report) {
            $summary = is_array($report->client_delivery_summary_json) ? $report->client_delivery_summary_json : [];
            $summary['last_acknowledgement'] = [
                'client_portal_access_id' => $access->id,
                'client_portal_acknowledgement_id' => $acknowledgement->id,
                'decision_status' => $decision,
                'acknowledged_by_name' => $acknowledgement->acknowledged_by_name,
                'acknowledged_by_email' => $acknowledgement->acknowledged_by_email,
                'acknowledged_at' => $acknowledgement->acknowledged_at?->toDateTimeString(),
                'comment_present' => filled($acknowledgement->comment),
            ];
            $summary['acknowledgement_count'] = $report->clientPortalAcknowledgements()->count();

            $report->forceFill([
                'client_delivery_summary_json' => $summary,
            ])->save();
        }

        return $acknowledgement;
    }

    private function summary(ClientPortalAccess $access): array
    {
        $report = $access->reportVersion;

        return [
            'project_id' => $access->project_id,
            'project_name' => $access->project?->name,
            'access_name' => $access->name,
            'role' => $access->role,
            'permissions' => $access->permissions,
            'report_version_id' => $report?->id,
            'report_title' => $report?->title,
            'report_type' => $report?->type,
            'report_checksum' => $report?->checksum,
            'report_status' => $report?->status,
        ];
    }
}
