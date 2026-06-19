<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'locale', 'timezone',
        'report_organization', 'report_prepared_by', 'report_role_title',
        'report_confidentiality_label', 'report_disclaimer',
        'password_change_required', 'first_login_at', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function getRoleLabelAttribute(): string
    {
        return __('messages.profile.roles.'.($this->role ?: 'admin'));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_change_required' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
