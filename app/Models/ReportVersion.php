<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportVersion extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEWED,
        self::STATUS_APPROVED,
        self::STATUS_ARCHIVED,
    ];

    public const TYPE_EXECUTIVE = 'executive';
    public const TYPE_TECHNICAL = 'technical';
    public const TYPE_RELEASE_READINESS = 'release_readiness';
    public const TYPE_FULL_PROJECT = 'full_project';

    public const TYPES = [
        self::TYPE_EXECUTIVE,
        self::TYPE_TECHNICAL,
        self::TYPE_RELEASE_READINESS,
        self::TYPE_FULL_PROJECT,
    ];

    protected $fillable = [
        'project_id',
        'generated_by_user_id',
        'approved_by_user_id',
        'title',
        'report_type',
        'report_format',
        'status',
        'content_checksum',
        'markdown_content',
        'source_scan_ids',
        'source_snapshot_ids',
        'source_compare_ids',
        'source_finding_state',
        'source_release_gate_ids',
        'source_release_decision_ids',
        'source_evidence_ids',
        'source_options_json',
        'generated_at',
        'reviewed_at',
        'approved_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'source_scan_ids' => 'array',
            'source_snapshot_ids' => 'array',
            'source_compare_ids' => 'array',
            'source_finding_state' => 'array',
            'source_release_gate_ids' => 'array',
            'source_release_decision_ids' => 'array',
            'source_evidence_ids' => 'array',
            'source_options_json' => 'array',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ReportVersion $version): void {
            if (! $version->status) {
                $version->status = self::STATUS_DRAFT;
            }
            if (! $version->report_format) {
                $version->report_format = 'markdown';
            }
            if (! $version->generated_at) {
                $version->generated_at = now();
            }
            if (! $version->content_checksum) {
                $version->content_checksum = hash('sha256', (string) $version->markdown_content);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.report_versions.statuses.'.($this->status ?: self::STATUS_DRAFT));
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.report_versions.types.'.($this->report_type ?: self::TYPE_TECHNICAL));
    }

    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REVIEWED => 'info',
            self::STATUS_ARCHIVED => 'default',
            self::STATUS_DRAFT => 'warning',
            default => 'default',
        };
    }

    public function getShortChecksumAttribute(): string
    {
        return substr((string) $this->content_checksum, 0, 12);
    }
}
