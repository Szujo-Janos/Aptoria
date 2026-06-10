<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FindingEvidence extends Model
{
    use HasFactory;

    public const TYPE_NOTE = 'note';
    public const TYPE_SCREENSHOT = 'screenshot';
    public const TYPE_JSON_RESPONSE = 'json_response';
    public const TYPE_CURL_COMMAND = 'curl_command';
    public const TYPE_REQUEST_RESPONSE = 'request_response';
    public const TYPE_FILE = 'file';
    public const TYPE_LINK = 'link';

    /** Legacy evidence types are kept readable for older records. */
    public const TYPE_HTTP = 'http';
    public const TYPE_LOG = 'log';
    public const TYPE_CONTRACT = 'contract';

    public const ACTIVE_TYPES = [
        self::TYPE_NOTE,
        self::TYPE_SCREENSHOT,
        self::TYPE_JSON_RESPONSE,
        self::TYPE_CURL_COMMAND,
        self::TYPE_REQUEST_RESPONSE,
        self::TYPE_FILE,
        self::TYPE_LINK,
    ];

    public const TYPES = [
        self::TYPE_NOTE,
        self::TYPE_SCREENSHOT,
        self::TYPE_JSON_RESPONSE,
        self::TYPE_CURL_COMMAND,
        self::TYPE_REQUEST_RESPONSE,
        self::TYPE_FILE,
        self::TYPE_LINK,
        self::TYPE_HTTP,
        self::TYPE_LOG,
        self::TYPE_CONTRACT,
    ];

    protected $table = 'finding_evidence';

    protected $fillable = [
        'finding_id',
        'project_id',
        'type',
        'source_label',
        'content',
        'request_excerpt',
        'response_excerpt',
        'curl_command',
        'url',
        'attachment_disk',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime_type',
        'attachment_size',
        'attachment_sha256',
        'metadata_json',
        'captured_at',
        'captured_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'captured_at' => 'datetime',
            'attachment_size' => 'integer',
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

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by_user_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.findings.evidence_types.'.$this->type);
    }

    public function getHasAttachmentAttribute(): bool
    {
        return filled($this->attachment_path);
    }

    public function getAttachmentSizeLabelAttribute(): string
    {
        $bytes = (int) ($this->attachment_size ?? 0);

        if ($bytes <= 0) {
            return __('messages.common.not_available');
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    protected function summary(): Attribute
    {
        return Attribute::get(function (): string {
            foreach ([$this->content, $this->request_excerpt, $this->response_excerpt, $this->curl_command, $this->url, $this->attachment_original_name] as $value) {
                if (filled($value)) {
                    return Str::limit(trim((string) $value), 260);
                }
            }

            return __('messages.common.not_available');
        });
    }

    public function deleteAttachmentFile(): void
    {
        if (! $this->attachment_path) {
            return;
        }

        Storage::disk($this->attachment_disk ?: 'local')->delete($this->attachment_path);
    }
}
