<?php

namespace App\Services\Auth;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\Environment;
use App\Services\Security\SensitiveValueMasker;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

class AuthProfileRuntimeService
{
    public function __construct(private readonly SensitiveValueMasker $masker)
    {
    }

    public function resolveForEndpoint(Endpoint $endpoint, ?Environment $forcedEnvironment = null): ?AuthProfile
    {
        $endpoint->loadMissing(['authProfile', 'environment.authProfile', 'project']);

        if ($forcedEnvironment instanceof Environment) {
            $forcedEnvironment->loadMissing('authProfile');
        }

        if ($endpoint->authProfile instanceof AuthProfile) {
            return $endpoint->authProfile;
        }

        if ($forcedEnvironment?->authProfile instanceof AuthProfile) {
            return $forcedEnvironment->authProfile;
        }

        if ($endpoint->environment?->authProfile instanceof AuthProfile) {
            return $endpoint->environment->authProfile;
        }

        return $endpoint->project?->defaultAuthProfile();
    }

    public function isComplete(?AuthProfile $profile): bool
    {
        if (! $profile instanceof AuthProfile) {
            return false;
        }

        return match ($profile->type) {
            AuthProfile::TYPE_NONE => true,
            AuthProfile::TYPE_BEARER => filled($profile->encrypted_token),
            AuthProfile::TYPE_BASIC => filled($profile->username) && filled($profile->encrypted_password),
            AuthProfile::TYPE_CUSTOM_HEADER => filled($profile->header_name) && filled($profile->encrypted_header_value),
            default => false,
        };
    }

    public function shouldApply(?AuthProfile $profile): bool
    {
        return $profile instanceof AuthProfile
            && $profile->type !== AuthProfile::TYPE_NONE
            && $this->isComplete($profile);
    }

    public function missingReason(Endpoint $endpoint, ?AuthProfile $profile): ?string
    {
        if (! $endpoint->auth_required) {
            return null;
        }

        if (! $profile instanceof AuthProfile || $profile->type === AuthProfile::TYPE_NONE) {
            return __('messages.auth_profiles.scan_missing_required');
        }

        if (! $this->isComplete($profile)) {
            return __('messages.auth_profiles.scan_incomplete_profile', ['profile' => $profile->name]);
        }

        return null;
    }

    public function applyToRequest(PendingRequest $request, ?AuthProfile $profile): PendingRequest
    {
        if (! $this->shouldApply($profile)) {
            return $request;
        }

        return match ($profile->type) {
            AuthProfile::TYPE_BEARER => $request->withToken((string) $profile->encrypted_token),
            AuthProfile::TYPE_BASIC => $request->withBasicAuth((string) $profile->username, (string) $profile->encrypted_password),
            AuthProfile::TYPE_CUSTOM_HEADER => $request->withHeaders([(string) $profile->header_name => (string) $profile->encrypted_header_value]),
            default => $request,
        };
    }

    public function maskedSummary(?AuthProfile $profile): string
    {
        if (! $profile instanceof AuthProfile) {
            return __('messages.common.none');
        }

        return match ($profile->type) {
            AuthProfile::TYPE_BEARER => $profile->encrypted_token ? 'Authorization: Bearer '.$this->masker->maskedCredential($profile->encrypted_token) : __('messages.auth_profiles.bearer_missing'),
            AuthProfile::TYPE_BASIC => $profile->username ? $profile->username.':'.$this->masker->maskedCredential($profile->encrypted_password) : __('messages.auth_profiles.basic_missing'),
            AuthProfile::TYPE_CUSTOM_HEADER => $profile->header_name ? $profile->header_name.': '.$this->masker->maskedCredential($profile->encrypted_header_value) : __('messages.auth_profiles.custom_missing'),
            default => __('messages.auth_profiles.no_auth_summary'),
        };
    }

    public function maskedReadiness(?AuthProfile $profile): array
    {
        if (! $profile instanceof AuthProfile) {
            return [
                'label' => __('messages.auth_profiles.not_configured'),
                'css' => 'default',
                'summary' => __('messages.common.none'),
            ];
        }

        if ($profile->type === AuthProfile::TYPE_NONE) {
            return [
                'label' => __('messages.auth_profiles.no_auth_ready'),
                'css' => 'success',
                'summary' => $this->maskedSummary($profile),
            ];
        }

        return [
            'label' => $this->isComplete($profile) ? __('messages.auth_profiles.scan_ready') : __('messages.auth_profiles.incomplete'),
            'css' => $this->isComplete($profile) ? 'success' : 'warning',
            'summary' => $this->maskedSummary($profile),
        ];
    }

    /** @param array<string, array<int, string>|string> $headers */
    public function maskHeaders(array $headers): array
    {
        return $this->masker->maskHeaders($headers);
    }

    public function maskValue(?string $value): string
    {
        return $this->masker->mask((string) $value);
    }

    public function maskForExport(?string $value): string
    {
        return $this->masker->maskForExport((string) $value);
    }

    public function maskForStorage(?string $value): string
    {
        return $this->masker->maskForStorage((string) $value);
    }

    public function safeLabel(?AuthProfile $profile): string
    {
        if (! $profile instanceof AuthProfile) {
            return __('messages.common.none');
        }

        return Str::limit($profile->name.' — '.$this->maskedSummary($profile), 160);
    }
}
