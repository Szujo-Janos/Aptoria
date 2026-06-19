<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalImportRun extends Model
{
    use HasFactory;

    public const SOURCE_TYPES = ['postman_collection', 'newman_json', 'jira_csv', 'jira_json', 'openapi_json', 'qa_csv', 'har_json'];
    public const STATUSES = ['previewed', 'applied', 'reverted', 'failed'];

    protected $fillable = [
        'project_id',
        'created_by_user_id',
        'reverted_by_user_id',
        'source_type',
        'source_name',
        'source_version',
        'status',
        'item_count',
        'endpoint_count',
        'assertion_count',
        'finding_count',
        'evidence_count',
        'warning_count',
        'blocker_count',
        'summary_json',
        'trace_summary_json',
        'revert_summary_json',
        'raw_excerpt',
        'previewed_at',
        'applied_at',
        'reverted_at',
    ];

    protected function casts(): array
    {
        return [
            'item_count' => 'integer',
            'endpoint_count' => 'integer',
            'assertion_count' => 'integer',
            'finding_count' => 'integer',
            'evidence_count' => 'integer',
            'warning_count' => 'integer',
            'blocker_count' => 'integer',
            'summary_json' => 'array',
            'trace_summary_json' => 'array',
            'revert_summary_json' => 'array',
            'previewed_at' => 'datetime',
            'applied_at' => 'datetime',
            'reverted_at' => 'datetime',
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

    public function revertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reverted_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExternalImportItem::class);
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return __('messages.import_center.source_types.'.($this->source_type ?: 'postman_collection'));
    }


    public function getSourceTypeIconAttribute(): string
    {
        return match ($this->source_type) {
            'postman_collection' => 'file-code-2',
            'newman_json' => 'test-tube',
            'jira_csv' => 'clipboard-search',
            'jira_json' => 'bug',
            'openapi_json' => 'brackets-contain',
            'qa_csv' => 'table-export',
            'har_json' => 'scan-eye',
            default => 'brackets-contain',
        };
    }

    public function getSourceTypeToneAttribute(): string
    {
        return match ($this->source_type) {
            'newman_json' => 'success',
            'jira_csv' => 'warning',
            'jira_json' => 'danger',
            'openapi_json' => 'info',
            'qa_csv' => 'secondary',
            'har_json' => 'dark',
            default => 'primary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return __('messages.import_center.statuses.'.($this->status ?: 'previewed'));
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'applied' => 'success',
            'reverted' => 'secondary',
            'failed' => 'danger',
            default => 'warning',
        };
    }

    public function getSummaryAttribute(): array
    {
        return is_array($this->summary_json) ? $this->summary_json : [];
    }

    public function getTraceSummaryAttribute(): array
    {
        return is_array($this->trace_summary_json) ? $this->trace_summary_json : [];
    }

    public function getRevertSummaryAttribute(): array
    {
        return is_array($this->revert_summary_json) ? $this->revert_summary_json : [];
    }
}
