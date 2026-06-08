<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnapshotItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_id',
        'endpoint_id',
        'method',
        'path',
        'auth_required',
        'risk_level',
        'status_code',
        'content_type',
        'response_time_ms',
        'expected_status',
        'expected_content_type',
        'source_hash',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'auth_required' => 'boolean',
            'metadata_json' => 'array',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }


    public function getRiskCssAttribute(): string
    {
        return match ($this->risk_level) {
            Endpoint::RISK_CRITICAL => 'danger',
            Endpoint::RISK_HIGH => 'warning',
            Endpoint::RISK_PUBLIC => 'info',
            Endpoint::RISK_LOW => 'success',
            default => 'default',
        };
    }

    public function getRiskLabelAttribute(): string
    {
        return $this->risk_level ? __('messages.endpoints.risks.'.$this->risk_level) : __('messages.common.not_available');
    }
}
