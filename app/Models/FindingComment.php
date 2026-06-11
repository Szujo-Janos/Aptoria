<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingComment extends Model
{
    use HasFactory;

    public const TYPE_QA_NOTE = 'qa_note';
    public const TYPE_DEVELOPER_NOTE = 'developer_note';
    public const TYPE_VERIFICATION_NOTE = 'verification_note';
    public const TYPE_RISK_ACCEPTANCE_NOTE = 'risk_acceptance_note';
    public const TYPE_RETEST_NOTE = 'retest_note';

    public const TYPES = [
        self::TYPE_QA_NOTE,
        self::TYPE_DEVELOPER_NOTE,
        self::TYPE_VERIFICATION_NOTE,
        self::TYPE_RISK_ACCEPTANCE_NOTE,
        self::TYPE_RETEST_NOTE,
    ];

    protected $fillable = [
        'finding_id',
        'project_id',
        'user_id',
        'type',
        'body',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.findings.comment_types.'.$this->type);
    }
}
