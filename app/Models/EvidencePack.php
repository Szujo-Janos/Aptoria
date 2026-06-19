<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidencePack extends Model
{
    use HasFactory;

    public const TYPES = ['release_evidence', 'client_delivery', 'audit_archive', 'import_trace'];
    public const SECTIONS = ['readiness', 'report', 'findings', 'evidence', 'retest', 'risk_acceptance', 'imports', 'contract', 'manifest'];

    protected $fillable = [
        'project_id', 'created_by_user_id', 'release_readiness_run_id', 'report_version_id', 'title', 'pack_type', 'status',
        'included_sections_json', 'manifest_json', 'content_markdown', 'content_html', 'checksum', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'included_sections_json' => 'array',
            'manifest_json' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function releaseReadinessRun(): BelongsTo { return $this->belongsTo(ReleaseReadinessRun::class); }
    public function reportVersion(): BelongsTo { return $this->belongsTo(ReportVersion::class); }

    public function getPackTypeLabelAttribute(): string
    {
        return __('messages.evidence_packs.types.'.($this->pack_type ?: 'release_evidence'));
    }

    public function getStatusToneAttribute(): string
    {
        return $this->status === 'archived' ? 'secondary' : 'success';
    }
}
