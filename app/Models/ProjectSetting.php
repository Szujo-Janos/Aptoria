<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSetting extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'key', 'value'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function set(Project $project, string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['project_id' => $project->id, 'key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );
    }

    public static function get(Project $project, string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('project_id', $project->id)->where('key', $key)->first();

        return $setting?->value ?? $default;
    }
}
