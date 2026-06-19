<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuthProfile extends Model
{
    use HasFactory;

    public const TYPES = ['none', 'bearer', 'basic', 'custom_header'];

    protected $fillable = [
        'project_id', 'name', 'type', 'encrypted_token', 'username', 'encrypted_password',
        'header_name', 'encrypted_header_value', 'is_default', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_token' => 'encrypted',
            'encrypted_password' => 'encrypted',
            'encrypted_header_value' => 'encrypted',
            'is_default' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }

    public function getToneAttribute(): string
    {
        return match ($this->type) {
            'bearer' => 'primary',
            'basic' => 'warning',
            'custom_header' => 'info',
            default => 'secondary',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return __('messages.auth_profiles.types.'.$this->type);
    }

    public function getMaskedPreviewAttribute(): string
    {
        return match ($this->type) {
            'bearer' => 'Bearer '.$this->maskSecretAttribute('encrypted_token'),
            'basic' => ($this->username ?: 'user').':'.$this->maskSecretAttribute('encrypted_password'),
            'custom_header' => ($this->header_name ?: 'X-Header').': '.$this->maskSecretAttribute('encrypted_header_value'),
            default => __('messages.auth_profiles.no_auth_preview'),
        };
    }

    public function getSecretNeedsRotationAttribute(): bool
    {
        return match ($this->type) {
            'bearer' => $this->rawSecretExists('encrypted_token') && ! $this->secretCanBeDecrypted('encrypted_token'),
            'basic' => $this->rawSecretExists('encrypted_password') && ! $this->secretCanBeDecrypted('encrypted_password'),
            'custom_header' => $this->rawSecretExists('encrypted_header_value') && ! $this->secretCanBeDecrypted('encrypted_header_value'),
            default => false,
        };
    }



    public function runtimeHeaders(): array
    {
        if ($this->type === 'bearer') {
            $token = $this->safeSecret('encrypted_token');

            return $token ? ['Authorization' => 'Bearer '.$token] : [];
        }

        if ($this->type === 'basic') {
            $password = $this->safeSecret('encrypted_password');

            return ($this->username && $password)
                ? ['Authorization' => 'Basic '.base64_encode($this->username.':'.$password)]
                : [];
        }

        if ($this->type === 'custom_header') {
            $value = $this->safeSecret('encrypted_header_value');

            return ($this->header_name && $value) ? [$this->header_name => $value] : [];
        }

        return [];
    }

    public function safeSecret(string $attribute): ?string
    {
        return $this->safeDecryptedSecret($attribute);
    }

    public function hasStoredSecretFor(string $type): bool
    {
        return match ($type) {
            'bearer' => $this->rawSecretExists('encrypted_token'),
            'basic' => $this->rawSecretExists('encrypted_password'),
            'custom_header' => $this->rawSecretExists('encrypted_header_value'),
            default => true,
        };
    }

    public function rawSecretExists(string $attribute): bool
    {
        $raw = $this->getRawOriginal($attribute);

        return is_string($raw) && trim($raw) !== '';
    }

    private function maskSecretAttribute(string $attribute): string
    {
        if (! $this->rawSecretExists($attribute)) {
            return '••••••';
        }

        $value = $this->safeDecryptedSecret($attribute);

        if ($value === null) {
            return '••••••';
        }

        return $this->maskSecret($value);
    }

    private function safeDecryptedSecret(string $attribute): ?string
    {
        try {
            $value = $this->getAttributeValue($attribute);
        } catch (DecryptException) {
            return null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function secretCanBeDecrypted(string $attribute): bool
    {
        return $this->safeDecryptedSecret($attribute) !== null;
    }

    private function maskSecret(string $value): string
    {
        if ($value === '') {
            return '••••••';
        }

        $suffix = mb_substr($value, -4);

        return '••••••'.$suffix;
    }
}
