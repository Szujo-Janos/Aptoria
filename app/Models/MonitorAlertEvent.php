<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitorAlertEvent extends Model
{
    use HasFactory;

    public const CHANNEL_DASHBOARD = 'dashboard';
    public const CHANNEL_WEBHOOK = 'webhook';
    public const CHANNEL_EMAIL = 'email';

    public const DELIVERY_RECORDED = 'recorded';
    public const DELIVERY_SENT = 'sent';
    public const DELIVERY_SKIPPED = 'skipped';
    public const DELIVERY_FAILED = 'failed';

    public const ACK_OPEN = 'open';
    public const ACK_ACKNOWLEDGED = 'acknowledged';

    protected $fillable = [
        'api_monitor_id',
        'project_id',
        'channel',
        'severity',
        'status',
        'previous_status',
        'message',
        'payload_json',
        'delivery_status',
        'delivery_message',
        'delivered_at',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledgement_note',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'delivered_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(ApiMonitor::class, 'api_monitor_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function getAcknowledgementStatusAttribute(): string
    {
        return $this->acknowledged_at ? self::ACK_ACKNOWLEDGED : self::ACK_OPEN;
    }

    public function getIsAcknowledgedAttribute(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'monitor_alert_event_id');
    }
}
