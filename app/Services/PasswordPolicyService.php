<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Validation\Rules\Password;

class PasswordPolicyService
{
    /** @return array<int,mixed> */
    public function rules(?User $user = null, bool $compareWithCurrentPassword = false): array
    {
        return [
            'required',
            'confirmed',
            Password::min(12)->mixedCase()->numbers()->symbols(),
            function (string $attribute, mixed $value, Closure $fail) use ($user, $compareWithCurrentPassword): void {
                $password = (string) $value;

                if ($this->isBlockedPlainPassword($password)) {
                    $fail(__('messages.security.password_blocked'));
                }

                if ($user && $this->containsUserIdentity($password, $user)) {
                    $fail(__('messages.security.password_contains_identity'));
                }

                if ($user && $compareWithCurrentPassword && password_verify($password, (string) $user->password)) {
                    $fail(__('messages.security.password_reused'));
                }
            },
        ];
    }

    public function isBlockedPlainPassword(string $password): bool
    {
        $normalized = $this->normalize($password);

        return in_array($normalized, $this->blockedPasswords(), true);
    }

    /** @return array<int,string> */
    public function blockedPasswords(): array
    {
        return [
            'password',
            'password1',
            'password12',
            'password123',
            'admin',
            'admin123',
            'admin1234',
            '12345678',
            '123456789',
            '1234567890',
            'change-me-now',
            'changemenow',
            'changeme',
            'changeit',
            'aptoria',
            'aptoria123',
            'aptoriaadmin',
            'qwerty123',
        ];
    }

    private function containsUserIdentity(string $password, User $user): bool
    {
        $normalizedPassword = $this->normalize($password);
        $identityParts = array_filter([
            $user->name,
            $user->email,
            str_contains((string) $user->email, '@') ? str((string) $user->email)->before('@')->toString() : null,
        ]);

        foreach ($identityParts as $identityPart) {
            $normalizedIdentity = $this->normalize((string) $identityPart);

            if (strlen($normalizedIdentity) >= 4 && str_contains($normalizedPassword, $normalizedIdentity)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '', $value) ?? ''));
    }
}
