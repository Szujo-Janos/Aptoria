<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public const EVENT_AUTH = 'auth';
    public const EVENT_MODEL = 'model';
    public const EVENT_REPORT = 'report';
    public const EVENT_DATABASE = 'database';
    public const EVENT_SYSTEM = 'system';
    public const EVENT_MONITOR = 'monitor';

    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_GENERATED = 'generated';
    public const ACTION_EXPORTED = 'exported';
    public const ACTION_IMPORTED = 'imported';
    public const ACTION_REQUESTED = 'requested';

    public const SEVERITY_INFO = 'info';
    public const SEVERITY_NOTICE = 'notice';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'project_id',
        'user_id',
        'event_type',
        'action',
        'severity',
        'auditable_type',
        'auditable_id',
        'subject_label',
        'subject_name',
        'summary',
        'route_name',
        'http_method',
        'url',
        'ip_address',
        'user_agent',
        'before_values',
        'after_values',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return __('messages.audit_log.actions.'.($this->action ?: self::ACTION_UPDATED));
    }

    public function getEventTypeLabelAttribute(): string
    {
        return __('messages.audit_log.event_types.'.($this->event_type ?: self::EVENT_MODEL));
    }

    public function getSeverityLabelAttribute(): string
    {
        return __('messages.audit_log.severities.'.($this->severity ?: self::SEVERITY_INFO));
    }
}
