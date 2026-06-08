<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompareItem extends Model
{
    use HasFactory;

    public const TYPE_NEW = 'new';
    public const TYPE_REMOVED = 'removed';
    public const TYPE_CHANGED = 'changed';
    public const TYPE_UNCHANGED = 'unchanged';

    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_REVIEW = 'review';
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_LOW = 'low';

    protected $fillable = [
        'compare_run_id',
        'change_type',
        'method',
        'path',
        'field_changed',
        'old_value',
        'new_value',
        'severity',
    ];

    public function compareRun(): BelongsTo
    {
        return $this->belongsTo(CompareRun::class);
    }

    public function getChangeLabelAttribute(): string
    {
        return __('messages.snapshots.change_types.'.$this->change_type);
    }

    public function getChangeCssAttribute(): string
    {
        return match ($this->change_type) {
            self::TYPE_NEW => 'success',
            self::TYPE_REMOVED => 'danger',
            self::TYPE_CHANGED => 'warning',
            default => 'default',
        };
    }

    public function getSeverityCssAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_HIGH => 'warning',
            self::SEVERITY_INFO => 'info',
            self::SEVERITY_LOW => 'success',
            default => 'default',
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        return __('messages.snapshots.severities.'.$this->severity);
    }
}
