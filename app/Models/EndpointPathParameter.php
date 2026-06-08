<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointPathParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'endpoint_id',
        'parameter_name',
        'test_value',
        'description',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EndpointPathParameter $parameter): void {
            $parameter->parameter_name = self::normalizeName((string) $parameter->parameter_name);
            $parameter->test_value = $parameter->test_value !== null ? trim((string) $parameter->test_value) : null;
        });
    }

    public static function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = trim($name, '{}: ');
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '', $name) ?? '';

        return $name;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }
}
