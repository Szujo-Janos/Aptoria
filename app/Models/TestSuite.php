<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestSuite extends Model
{
    use HasFactory;

    public const STATUSES = ['active', 'draft', 'archived'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $fillable = [
        'project_id',
        'created_by_user_id',
        'name',
        'description',
        'status',
        'priority',
        'owner_name',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
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

    public function cases(): HasMany
    {
        return $this->hasMany(TestCase::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TestRun::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.native_tests.statuses.'.($this->status ?: 'active'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'draft' => 'warning',
            'archived' => 'secondary',
            default => 'light',
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
}
