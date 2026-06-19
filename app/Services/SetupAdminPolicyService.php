<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SetupAdminPolicyService
{
    public function __construct(private readonly PasswordPolicyService $passwordPolicy)
    {
    }

    /** @return array<int,string> */
    public function setupLockBlockerKeys(): array
    {
        if (Schema::hasTable('users') && ! $this->hasAdminUser()) {
            return ['admin_required_before_lock'];
        }

        return [];
    }

    public function hasAdminUser(): bool
    {
        if (! Schema::hasTable('users')) {
            return false;
        }

        return User::query()->where('role', 'admin')->exists();
    }

    /** @return array<int,mixed> */
    public function adminPasswordRules(): array
    {
        return $this->passwordPolicy->rules();
    }

    public function isBlockedPlainPassword(string $password): bool
    {
        return $this->passwordPolicy->isBlockedPlainPassword($password);
    }

    public function hasUnsafeBlockedAdminPassword(): bool
    {
        return $this->adminUsersWithUnsafeBlockedPasswords()->isNotEmpty();
    }

    public function adminUsersWithUnsafeBlockedPasswords(): Collection
    {
        return collect();
    }

    public function adminUsersWithBlockedPasswords(): Collection
    {
        return collect();
    }
}
