<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'key',
        'value',
        'type',
        'group',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
