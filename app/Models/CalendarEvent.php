<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use HasFactory;

    public const TYPES = [
        'manual_qa_task',
        'regression_retest',
        'release_checkpoint',
        'maintenance_window',
        'alert_follow_up',
        'security_review',
        'monitor_run',
        'activity_log',
    ];

    public const STATUSES = ['planned', 'in_progress', 'completed', 'cancelled'];
    public const PRIORITIES = ['low', 'normal', 'high', 'critical'];

    protected $fillable = [
        'project_id',
        'created_by_user_id',
        'title',
        'description',
        'event_type',
        'status',
        'priority',
        'start_at',
        'end_at',
        'location',
        'is_all_day',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_all_day' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.calendar.types.'.($this->event_type ?: 'manual_qa_task'));
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.calendar.statuses.'.($this->status ?: 'planned'));
    }

    public function getPriorityLabelAttribute(): string
    {
        return __('messages.calendar.priorities.'.($this->priority ?: 'normal'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'in_progress' => 'primary',
            'cancelled' => 'secondary',
            default => 'warning',
        };
    }

    public function getPriorityToneAttribute(): string
    {
        return match ($this->priority) {
            'critical' => 'danger',
            'high' => 'warning',
            'low' => 'secondary',
            default => 'info',
        };
    }

    public function getTypeToneAttribute(): string
    {
        return match ($this->event_type) {
            'regression_retest' => 'warning',
            'release_checkpoint' => 'primary',
            'maintenance_window' => 'secondary',
            'alert_follow_up' => 'danger',
            'security_review' => 'info',
            'monitor_run' => 'success',
            'activity_log' => 'dark',
            default => 'success',
        };
    }
}
