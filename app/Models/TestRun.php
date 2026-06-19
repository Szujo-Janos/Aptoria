<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestRun extends Model
{
    use HasFactory;

    public const STATUSES = ['pass', 'fail', 'blocked', 'skipped'];

    protected $fillable = [
        'project_id',
        'test_suite_id',
        'test_case_id',
        'endpoint_id',
        'executed_by_user_id',
        'finding_id',
        'finding_evidence_id',
        'status',
        'executed_at',
        'duration_ms',
        'environment_label',
        'actual_result',
        'failure_summary',
        'evidence_summary',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
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

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(FindingEvidence::class, 'finding_evidence_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.native_tests.run_statuses.'.($this->status ?: 'skipped'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'pass' => 'success',
            'fail' => 'danger',
            'blocked' => 'warning',
            'skipped' => 'secondary',
            default => 'light',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'pass' => 'badge-check',
            'fail' => 'circle-x',
            'blocked' => 'octagon-alert',
            'skipped' => 'skip-forward',
            default => 'play',
        };
    }
}
