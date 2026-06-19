<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointTestRun extends Model
{
    protected $fillable = [
        'project_id',
        'endpoint_test_batch_id',
        'endpoint_id',
        'environment_id',
        'auth_profile_id',
        'method',
        'url',
        'state',
        'tone',
        'message',
        'expected_status',
        'status_code',
        'status_matched',
        'expected_content_type',
        'content_type',
        'content_type_matched',
        'assertion_total',
        'assertion_passed',
        'assertion_failed',
        'assertion_summary_json',
        'response_time_ms',
        'response_size',
        'body_preview',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_status' => 'integer',
            'status_code' => 'integer',
            'status_matched' => 'boolean',
            'content_type_matched' => 'boolean',
            'assertion_total' => 'integer',
            'assertion_passed' => 'integer',
            'assertion_failed' => 'integer',
            'assertion_summary_json' => 'array',
            'response_time_ms' => 'integer',
            'response_size' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(EndpointTestBatch::class, 'endpoint_test_batch_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function authProfile(): BelongsTo
    {
        return $this->belongsTo(AuthProfile::class);
    }

    public function getStateLabelAttribute(): string
    {
        return __('messages.endpoints.quick_test_states.'.($this->state ?: 'skipped'));
    }

    public function getStatusSummaryAttribute(): string
    {
        if ($this->status_code) {
            return 'HTTP '.$this->status_code;
        }

        return __('messages.common.not_available');
    }
}
