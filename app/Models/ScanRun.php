<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScanRun extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'project_id',
        'environment_id',
        'created_by',
        'status',
        'mode',
        'started_at',
        'finished_at',
        'duration_ms',
        'total_endpoints',
        'scanned_count',
        'skipped_count',
        'success_count',
        'warning_count',
        'error_count',
        'summary_json',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ScanResult::class);
    }

    public function testCaseResults(): HasMany
    {
        return $this->hasMany(TestCaseResult::class);
    }

    public function contractValidationRuns(): HasMany
    {
        return $this->hasMany(ContractValidationRun::class);
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(Snapshot::class);
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_RUNNING => 'info',
            self::STATUS_FAILED => 'danger',
            default => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.scans.statuses.'.$this->status);
    }

    public function getDurationLabelAttribute(): string
    {
        if ($this->duration_ms === null) {
            return __('messages.common.not_available');
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms.' ms';
        }

        return number_format($this->duration_ms / 1000, 2).' s';
    }
}
