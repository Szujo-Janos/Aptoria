<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiMonitor extends Model
{
    use HasFactory;

    public const FREQUENCY_HOURLY = 'hourly';
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';

    public const STATUS_NEVER_RUN = 'never_run';
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_WARNING = 'warning';
    public const STATUS_REGRESSION = 'regression_detected';
    public const STATUS_FAILED = 'failed';

    public const FREQUENCIES = [
        self::FREQUENCY_HOURLY,
        self::FREQUENCY_DAILY,
        self::FREQUENCY_WEEKLY,
    ];

    protected $fillable = [
        'project_id',
        'environment_id',
        'baseline_snapshot_id',
        'created_by',
        'name',
        'frequency',
        'is_enabled',
        'auto_snapshot',
        'auto_compare',
        'notify_dashboard',
        'alert_email',
        'alert_webhook_url',
        'alert_on_recovery',
        'last_run_at',
        'next_run_at',
        'last_scan_run_id',
        'last_snapshot_id',
        'last_compare_run_id',
        'last_status',
        'last_message',
        'last_alert_at',
        'last_alert_status',
        'summary_json',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'auto_snapshot' => 'boolean',
            'auto_compare' => 'boolean',
            'notify_dashboard' => 'boolean',
            'alert_on_recovery' => 'boolean',
            'last_run_at' => 'datetime',
            'last_alert_at' => 'datetime',
            'next_run_at' => 'datetime',
            'summary_json' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function baselineSnapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class, 'baseline_snapshot_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastScanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class, 'last_scan_run_id');
    }

    public function lastSnapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class, 'last_snapshot_id');
    }

    public function lastCompareRun(): BelongsTo
    {
        return $this->belongsTo(CompareRun::class, 'last_compare_run_id');
    }


    public function alertEvents(): HasMany
    {
        return $this->hasMany(MonitorAlertEvent::class, 'api_monitor_id');
    }

    public function openAlertEvents(): HasMany
    {
        return $this->hasMany(MonitorAlertEvent::class, 'api_monitor_id')->whereNull('acknowledged_at');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'api_monitor_id');
    }

    public function getFrequencyLabelAttribute(): string
    {
        return __('messages.monitors.frequencies.'.$this->frequency);
    }

    public function getLastStatusLabelAttribute(): string
    {
        return __('messages.monitors.statuses.'.($this->last_status ?: self::STATUS_NEVER_RUN));
    }

    public function getLastStatusCssAttribute(): string
    {
        return match ($this->last_status ?: self::STATUS_NEVER_RUN) {
            self::STATUS_HEALTHY => 'success',
            self::STATUS_WARNING => 'warning',
            self::STATUS_REGRESSION, self::STATUS_FAILED => 'danger',
            default => 'default',
        };
    }

    public function getNextRunLabelAttribute(): string
    {
        return $this->next_run_at?->format('Y-m-d H:i') ?: __('messages.common.not_available');
    }

    public function getLastRunLabelAttribute(): string
    {
        return $this->last_run_at?->format('Y-m-d H:i') ?: __('messages.common.not_available');
    }
}
