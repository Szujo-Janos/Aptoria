<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CalendarEvent extends Model
{
    use HasFactory;

    public const TYPE_MANUAL_QA_TASK = 'manual_qa_task';
    public const TYPE_REGRESSION_RETEST = 'regression_retest';
    public const TYPE_RELEASE_CHECKPOINT = 'release_checkpoint';
    public const TYPE_MAINTENANCE_WINDOW = 'maintenance_window';
    public const TYPE_ALERT_FOLLOW_UP = 'alert_follow_up';
    public const TYPE_SECURITY_REVIEW = 'security_review';
    public const TYPE_MONITOR_RUN = 'monitor_run';
    public const TYPE_ACTIVITY_LOG = 'activity_log';

    public const TYPES = [
        self::TYPE_MANUAL_QA_TASK,
        self::TYPE_REGRESSION_RETEST,
        self::TYPE_RELEASE_CHECKPOINT,
        self::TYPE_MAINTENANCE_WINDOW,
        self::TYPE_ALERT_FOLLOW_UP,
        self::TYPE_SECURITY_REVIEW,
        self::TYPE_MONITOR_RUN,
        self::TYPE_ACTIVITY_LOG,
    ];


    public const MANUAL_TYPES = [
        self::TYPE_MANUAL_QA_TASK,
        self::TYPE_REGRESSION_RETEST,
        self::TYPE_RELEASE_CHECKPOINT,
        self::TYPE_MAINTENANCE_WINDOW,
        self::TYPE_ALERT_FOLLOW_UP,
        self::TYPE_SECURITY_REVIEW,
        self::TYPE_MONITOR_RUN,
    ];

    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
        self::PRIORITY_CRITICAL,
    ];

    protected $fillable = [
        'project_id',
        'endpoint_id',
        'api_monitor_id',
        'monitor_alert_event_id',
        'qa_release_gate_id',
        'created_by',
        'title',
        'description',
        'event_type',
        'status',
        'priority',
        'starts_at',
        'ends_at',
        'all_day',
        'completed_at',
        'is_system_locked',
        'activity_action',
        'activity_subject_type',
        'activity_subject_id',
        'activity_route',
        'activity_payload',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'completed_at' => 'datetime',
            'all_day' => 'boolean',
            'is_system_locked' => 'boolean',
            'activity_payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CalendarEvent $event): void {
            if (! $event->event_type) {
                $event->event_type = self::TYPE_MANUAL_QA_TASK;
            }

            if (! $event->status) {
                $event->status = self::STATUS_PLANNED;
            }

            if (! $event->priority) {
                $event->priority = self::PRIORITY_NORMAL;
            }

            if ($event->status === self::STATUS_COMPLETED && $event->completed_at === null) {
                $event->completed_at = now();
            }

            if ($event->status !== self::STATUS_COMPLETED) {
                $event->completed_at = null;
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(ApiMonitor::class, 'api_monitor_id');
    }

    public function alertEvent(): BelongsTo
    {
        return $this->belongsTo(MonitorAlertEvent::class, 'monitor_alert_event_id');
    }

    public function releaseGate(): BelongsTo
    {
        return $this->belongsTo(QaReleaseGate::class, 'qa_release_gate_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.calendar.types.'.($this->event_type ?: self::TYPE_MANUAL_QA_TASK));
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.calendar.statuses.'.($this->status ?: self::STATUS_PLANNED));
    }

    public function getPriorityLabelAttribute(): string
    {
        return __('messages.calendar.priorities.'.($this->priority ?: self::PRIORITY_NORMAL));
    }



    public function getDisplayTitleAttribute(): string
    {
        if (! $this->isActivityLogEntry()) {
            return (string) $this->title;
        }

        if (! $this->activity_action || ! $this->activity_subject_type) {
            return (string) $this->title;
        }

        return __('messages.calendar.activity_title', [
            'action' => $this->activityActionLabel('title'),
            'subject' => $this->activitySubjectLabel(),
            'name' => $this->activitySubjectName(),
        ]);
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        if (! $this->isActivityLogEntry()) {
            return $this->description;
        }

        if (! $this->activity_action || ! $this->activity_subject_type) {
            return $this->description;
        }

        return __('messages.calendar.activity_description', [
            'action' => $this->activityActionLabel('sentence'),
            'subject' => $this->activitySubjectLabel(),
            'id' => (string) ($this->activity_subject_id ?? 'n/a'),
        ]);
    }

    private function isActivityLogEntry(): bool
    {
        return (bool) $this->is_system_locked && $this->event_type === self::TYPE_ACTIVITY_LOG;
    }

    private function activityActionLabel(string $context = 'title'): string
    {
        $action = (string) ($this->activity_action ?: 'updated');
        $key = 'messages.calendar.activity_actions_'.$context.'.'.$action;
        $label = __($key);

        if ($label !== $key) {
            return $label;
        }

        $fallbackKey = 'messages.calendar.activity_actions.'.$action;
        $fallback = __($fallbackKey);

        return $fallback === $fallbackKey ? (string) Str::of($action)->replace('_', ' ')->headline() : $fallback;
    }

    private function activitySubjectLabel(): string
    {
        $subjectKey = $this->activitySubjectKey();
        $translationKey = 'messages.calendar.activity_subjects.'.$subjectKey;
        $label = __($translationKey);

        if ($label !== $translationKey) {
            return $label;
        }

        return Str::of(class_basename((string) $this->activity_subject_type))->headline()->lower()->toString();
    }

    private function activitySubjectKey(): string
    {
        $base = class_basename((string) $this->activity_subject_type);

        return $base !== '' ? Str::snake($base) : 'record';
    }

    private function activitySubjectName(): string
    {
        $payload = is_array($this->activity_payload) ? $this->activity_payload : [];

        $localizedName = $this->localizedActivitySubjectName($payload);
        if ($localizedName !== null) {
            return $localizedName;
        }

        foreach (['name', 'title', 'release_name', 'email', 'slug', 'key'] as $attribute) {
            $value = $payload[$attribute] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return Str::limit(trim($value), 90);
            }
        }

        $method = trim((string) ($payload['method'] ?? ''));
        $path = trim((string) ($payload['path'] ?? ''));
        if ($method !== '' || $path !== '') {
            return trim($method.' '.$path) ?: '#'.($this->activity_subject_id ?? 'n/a');
        }

        return '#'.($this->activity_subject_id ?? 'n/a');
    }

    /** @param array<string, mixed> $payload */
    private function localizedActivitySubjectName(array $payload): ?string
    {
        $subjectKey = $this->activitySubjectKey();

        if ($subjectKey === 'project_setting') {
            $settingKey = trim((string) ($payload['key'] ?? ''));
            if ($settingKey !== '') {
                $translationKey = 'messages.calendar.activity_project_setting_keys.'.str_replace('.', '_', $settingKey);
                $label = __($translationKey);

                return $label === $translationKey ? $settingKey : $label;
            }
        }

        if ($subjectKey === 'setting') {
            $settingKey = trim((string) ($payload['key'] ?? ''));
            if ($settingKey !== '') {
                $translationKey = 'messages.calendar.activity_setting_keys.'.str_replace('.', '_', $settingKey);
                $label = __($translationKey);

                return $label === $translationKey ? $settingKey : $label;
            }
        }

        return null;
    }



    public function getToneCssAttribute(): string
    {
        if ($this->isActivityLogEntry()) {
            return match ($this->activity_action) {
                'created' => 'created',
                'updated' => 'updated',
                'deleted' => 'deleted',
                default => 'activity',
            };
        }

        return match ($this->event_type) {
            self::TYPE_REGRESSION_RETEST => 'regression',
            self::TYPE_RELEASE_CHECKPOINT => 'release',
            self::TYPE_MAINTENANCE_WINDOW => 'maintenance',
            self::TYPE_ALERT_FOLLOW_UP => 'alert',
            self::TYPE_SECURITY_REVIEW => 'security',
            self::TYPE_MONITOR_RUN => 'monitor',
            default => 'manual',
        };
    }

    public function getToneLabelAttribute(): string
    {
        return __('messages.calendar.tones.'.($this->tone_css ?: 'manual'));
    }

    public function spansMultipleDays(): bool
    {
        return $this->starts_at && $this->ends_at && ! $this->starts_at->isSameDay($this->ends_at);
    }

    public function segmentClassFor($day): string
    {
        if (! $this->spansMultipleDays()) {
            return 'is-single-day';
        }

        $date = $day instanceof \Carbon\CarbonInterface ? $day->copy()->startOfDay() : \Carbon\Carbon::parse($day)->startOfDay();
        $startsAt = $this->starts_at?->copy()->startOfDay();
        $endsAt = $this->ends_at?->copy()->startOfDay();

        if ($startsAt && $date->isSameDay($startsAt)) {
            return 'is-range-start';
        }

        if ($endsAt && $date->isSameDay($endsAt)) {
            return 'is-range-end';
        }

        return 'is-range-middle';
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_CANCELLED => 'default',
            default => 'warning',
        };
    }

    public function getPriorityCssAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'danger',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_LOW => 'info',
            default => 'default',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->event_type) {
            self::TYPE_REGRESSION_RETEST => 'fa-repeat',
            self::TYPE_RELEASE_CHECKPOINT => 'fa-flag-checkered',
            self::TYPE_MAINTENANCE_WINDOW => 'fa-wrench',
            self::TYPE_ALERT_FOLLOW_UP => 'fa-bell',
            self::TYPE_SECURITY_REVIEW => 'fa-shield',
            self::TYPE_MONITOR_RUN => 'fa-clock-o',
            self::TYPE_ACTIVITY_LOG => 'fa-history',
            default => 'fa-check-square-o',
        };
    }
}
