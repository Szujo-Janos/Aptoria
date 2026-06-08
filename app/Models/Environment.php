<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Environment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'base_url',
        'auth_profile_id',
        'is_production',
    ];

    protected function casts(): array
    {
        return [
            'is_production' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function authProfile(): BelongsTo
    {
        return $this->belongsTo(AuthProfile::class);
    }
}
