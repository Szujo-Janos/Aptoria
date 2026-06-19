<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestCase extends Model
{
    use HasFactory;

    public const TYPES = ['manual', 'imported', 'hybrid'];
    public const STATUSES = ['active', 'draft', 'deprecated'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $fillable = [
        'project_id',
        'test_suite_id',
        'endpoint_id',
        'created_by_user_id',
        'title',
        'description',
        'preconditions',
        'steps',
        'expected_result',
        'type',
        'priority',
        'status',
        'tags',
        'source',
        'external_reference',
        'last_run_status',
        'last_run_at',
        'run_count',
        'pass_count',
        'fail_count',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'last_run_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function suite(): BelongsTo
    {
        return $this->belongsTo(TestSuite::class, 'test_suite_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TestRun::class);
    }

    public function latestRun(): HasOne
    {
        return $this->hasOne(TestRun::class)->latestOfMany('executed_at');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(FindingEvidence::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.native_tests.case_statuses.'.($this->status ?: 'active'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'draft' => 'warning',
            'deprecated' => 'secondary',
            default => 'light',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.native_tests.case_types.'.($this->type ?: 'manual'));
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'imported' => 'brackets-contain',
            'hybrid' => 'workflow',
            default => 'clipboard-list',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return __('messages.native_tests.priorities.'.($this->priority ?: 'normal'));
    }

    public function getPriorityToneAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'low' => 'secondary',
            default => 'primary',
        };
    }

    public function getLastRunToneAttribute(): string
    {
        return match ($this->last_run_status) {
            'pass' => 'success',
            'fail' => 'danger',
            'blocked' => 'warning',
            'skipped' => 'secondary',
            default => 'light',
        };
    }
}
