<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalImportItem extends Model
{
    use HasFactory;

    public const ENTITY_TYPES = ['endpoint', 'assertion', 'finding', 'evidence'];
    public const SEVERITIES = ['info', 'warning', 'blocker'];
    public const MATCH_STATUSES = ['new', 'update', 'duplicate', 'conflict', 'skip', 'needs_review'];

    protected $fillable = [
        'project_id',
        'external_import_run_id',
        'endpoint_id',
        'finding_id',
        'entity_type',
        'action',
        'match_status',
        'apply_strategy',
        'conflict_reason',
        'target_type',
        'target_id',
        'severity',
        'external_key',
        'normalized_key',
        'source_hash',
        'method',
        'path',
        'title',
        'summary',
        'payload_json',
        'original_payload_json',
        'trace_note',
        'status',
        'revert_status',
        'revert_action',
        'reverted_at',
        'applied_at',
        'created_record_type',
        'created_record_id',
        'updated_record_type',
        'updated_record_id',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'original_payload_json' => 'array',
            'target_id' => 'integer',
            'created_record_id' => 'integer',
            'updated_record_id' => 'integer',
            'applied_at' => 'datetime',
            'reverted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ExternalImportRun::class, 'external_import_run_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }


    public function getEntityIconAttribute(): string
    {
        return match ($this->entity_type) {
            'endpoint' => 'plug-connected',
            'assertion' => 'checklist',
            'finding' => 'bug',
            'evidence' => 'folder-check',
            default => 'brackets-contain',
        };
    }

    public function getEntityTypeLabelAttribute(): string
    {
        return __('messages.import_center.entity_types.'.($this->entity_type ?: 'endpoint'));
    }

    public function getActionLabelAttribute(): string
    {
        return __('messages.import_center.actions.'.($this->action ?: 'create'));
    }


    public function getMatchStatusLabelAttribute(): string
    {
        return __('messages.import_center.match_statuses.'.($this->match_status ?: 'new'));
    }

    public function getMatchToneAttribute(): string
    {
        return match ($this->match_status) {
            'conflict' => 'danger',
            'needs_review' => 'warning',
            'duplicate', 'skip' => 'secondary',
            'update' => 'info',
            default => 'success',
        };
    }

    public function getApplyStrategyLabelAttribute(): string
    {
        return __('messages.import_center.apply_strategies.'.($this->apply_strategy ?: 'create'));
    }

    public function getRevertStatusLabelAttribute(): string
    {
        return $this->revert_status ? __('messages.import_center.revert_statuses.'.($this->revert_status ?: 'not_reverted')) : __('messages.import_center.revert_statuses.not_reverted');
    }

    public function getTraceTargetLabelAttribute(): string
    {
        if ($this->created_record_type && $this->created_record_id) {
            return class_basename($this->created_record_type).' #'.$this->created_record_id;
        }
        if ($this->updated_record_type && $this->updated_record_id) {
            return class_basename($this->updated_record_type).' #'.$this->updated_record_id;
        }
        if ($this->target_type && $this->target_id) {
            return class_basename($this->target_type).' #'.$this->target_id;
        }

        return '—';
    }

    public function getSeverityLabelAttribute(): string
    {
        return __('messages.import_center.severities.'.($this->severity ?: 'info'));
    }

    public function getSeverityToneAttribute(): string
    {
        return match ($this->severity) {
            'blocker' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    }

    public function getEntityToneAttribute(): string
    {
        return match ($this->entity_type) {
            'finding' => 'danger',
            'assertion' => 'warning',
            'evidence' => 'success',
            default => 'primary',
        };
    }
}
