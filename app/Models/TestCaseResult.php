<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestCaseResult extends Model
{
    use HasFactory;

    public const STATUS_PASS = 'pass';
    public const STATUS_FAIL = 'fail';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_SKIPPED = 'skipped';

    public const STATUSES = [
        self::STATUS_PASS,
        self::STATUS_FAIL,
        self::STATUS_BLOCKED,
        self::STATUS_SKIPPED,
    ];

    protected $fillable = [
        'test_case_id',
        'project_id',
        'scan_run_id',
        'scan_result_id',
        'status',
        'actual_result',
        'notes',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
        ];
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scanRun(): BelongsTo
    {
        return $this->belongsTo(ScanRun::class);
    }

    public function scanResult(): BelongsTo
    {
        return $this->belongsTo(ScanResult::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.test_cases.run_statuses.'.$this->status);
    }

    public function getStatusCssAttribute(): string
    {
        return TestCase::runStatusCss($this->status);
    }
}
