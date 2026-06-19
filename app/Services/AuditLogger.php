<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function record(string $action, string $summary, ?Model $subject = null, array $metadata = [], string $eventType = 'system', string $severity = 'info'): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'project_id' => $subject instanceof Project ? $subject->id : ($metadata['project_id'] ?? null),
                'event_type' => $eventType,
                'action' => $action,
                'severity' => $severity,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject instanceof Project ? $subject->name : ($metadata['subject_label'] ?? null),
                'summary' => $summary,
                'metadata' => $metadata,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Audit logging must not break the primary workflow during the MVP rebuild.
        }
    }
}
