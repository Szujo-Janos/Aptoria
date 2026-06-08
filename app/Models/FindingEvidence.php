<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingEvidence extends Model
{
    use HasFactory;

    public const TYPE_NOTE = 'note';
    public const TYPE_HTTP = 'http';
    public const TYPE_SCREENSHOT = 'screenshot';
    public const TYPE_LOG = 'log';
    public const TYPE_LINK = 'link';
    public const TYPE_CONTRACT = 'contract';

    public const TYPES = [
        self::TYPE_NOTE,
        self::TYPE_HTTP,
        self::TYPE_SCREENSHOT,
        self::TYPE_LOG,
        self::TYPE_LINK,
        self::TYPE_CONTRACT,
    ];

    protected $table = 'finding_evidence';

    protected $fillable = [
        'finding_id',
        'project_id',
        'type',
        'source_label',
        'content',
        'url',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.findings.evidence_types.'.$this->type);
    }
}
