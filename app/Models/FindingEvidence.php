<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FindingEvidence extends Model
{
    use HasFactory;

    public const TYPES = ['note', 'http', 'json_response', 'request_response', 'log', 'link', 'retest', 'contract', 'test_result'];
    public const STATUS_ACTIVE = 'active';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_VERIFIED, self::STATUS_ARCHIVED];
    public const INTEGRITY_CURRENT = 'current';
    public const INTEGRITY_CHANGED = 'changed';
    public const CHECKSUM_ALGORITHM = 'sha256-v1';

    protected $table = 'finding_evidence';

    protected $fillable = [
        'project_id',
        'finding_id',
        'endpoint_id',
        'scan_result_id',
        'test_case_id',
        'test_run_id',
        'type',
        'title',
        'source_label',
        'content',
        'url',
        'request_excerpt',
        'response_excerpt',
        'captured_at',
        'captured_by_user_id',
        'sha256',
        'checksum_algorithm',
        'repository_status',
        'integrity_status',
        'repository_notes',
        'reviewed_by_user_id',
        'reviewed_at',
        'archived_by_user_id',
        'archived_at',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'archived_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function scanResult(): BelongsTo
    {
        return $this->belongsTo(ScanResult::class);
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class);
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(EvidenceLifecycleEvent::class)->latest('occurred_at');
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.evidence.types.'.($this->type ?: 'note'));
    }

    public function getTypeToneAttribute(): string
    {
        return match ($this->type) {
            'http', 'request_response', 'json_response' => 'primary',
            'retest' => 'success',
            'log' => 'warning',
            'contract' => 'info',
            'test_result' => 'success',
            'link' => 'secondary',
            default => 'light',
        };
    }

    public function getRepositoryStatusLabelAttribute(): string
    {
        return __('messages.evidence.statuses.'.($this->repository_status ?: self::STATUS_ACTIVE));
    }

    public function getRepositoryStatusToneAttribute(): string
    {
        return match ($this->repository_status) {
            self::STATUS_VERIFIED => 'success',
            self::STATUS_ARCHIVED => 'secondary',
            default => 'primary',
        };
    }

    public function getRepositoryStatusIconAttribute(): string
    {
        return match ($this->repository_status) {
            self::STATUS_VERIFIED => 'badge-check',
            self::STATUS_ARCHIVED => 'archive',
            default => 'folder-check',
        };
    }

    public function getIntegrityStatusLabelAttribute(): string
    {
        return __('messages.evidence.integrity.'.($this->integrity_status ?: self::INTEGRITY_CURRENT));
    }

    public function getIntegrityStatusToneAttribute(): string
    {
        return $this->integrity_status === self::INTEGRITY_CHANGED ? 'danger' : 'success';
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'http' => 'globe',
            'json_response' => 'braces',
            'request_response' => 'file-delta',
            'log' => 'scroll-text',
            'link' => 'external-link',
            'retest' => 'rotate-ccw',
            'contract' => 'file-check-2',
            'test_result' => 'test-tube',
            default => 'file-text',
        };
    }
}
