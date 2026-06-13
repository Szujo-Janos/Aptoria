<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'locale',
        'timezone',
        'report_display_name',
        'report_role_title',
        'report_organization',
        'report_github_url',
        'report_website_url',
        'first_login_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    public function memberProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_memberships')
            ->withPivot(['role', 'notes', 'invited_by_user_id', 'joined_at'])
            ->withTimestamps();
    }

    public function isSystemAdmin(): bool
    {
        return ($this->role ?? null) === 'admin';
    }

    public function clientPortalAccesses(): HasMany
    {
        return $this->hasMany(ClientPortalAccess::class, 'created_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
