<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaReleaseGateItem extends Model
{
    use HasFactory;

    public const TYPE_BLOCKER = 'blocker';
    public const TYPE_WARNING = 'warning';
    public const TYPE_EVIDENCE = 'evidence';
    public const TYPE_RECOMMENDATION = 'recommendation';

    public const TYPES = [
        self::TYPE_BLOCKER,
        self::TYPE_WARNING,
        self::TYPE_EVIDENCE,
        self::TYPE_RECOMMENDATION,
    ];

    public const SEVERITY_INFO = 'info';
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITIES = [
        self::SEVERITY_INFO,
        self::SEVERITY_LOW,
        self::SEVERITY_MEDIUM,
        self::SEVERITY_HIGH,
        self::SEVERITY_CRITICAL,
    ];

    protected $fillable = [
        'qa_release_gate_id',
        'project_id',
        'endpoint_id',
        'item_type',
        'source',
        'severity',
        'rule_key',
        'title',
        'message',
        'recommendation',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
        ];
    }

    public function gate(): BelongsTo
    {
        return $this->belongsTo(QaReleaseGate::class, 'qa_release_gate_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.release_gates.item_types.'.$this->item_type);
    }

    public function getSeverityLabelAttribute(): string
    {
        return __('messages.release_gates.severities.'.$this->severity);
    }

    public function getSeverityCssAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_HIGH => 'warning',
            self::SEVERITY_MEDIUM => 'info',
            self::SEVERITY_LOW => 'success',
            default => 'default',
        };
    }
}
