<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProgramSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('program_settings')) {
                return $default;
            }

            $setting = static::query()->where('key', $key)->first();

            return $setting?->value ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }
}
