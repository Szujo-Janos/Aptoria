<?php

namespace App\Services;

class SensitiveValueMasker
{
    public function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = preg_replace('/(Authorization:\s*Bearer\s+)[^\s\r\n]+/i', '$1••••••', $value) ?? $value;
        $value = preg_replace('/(api[_-]?key["\'\s:=]+)[A-Za-z0-9_\-.]{8,}/i', '$1••••••', $value) ?? $value;
        $value = preg_replace('/(token["\'\s:=]+)[A-Za-z0-9_\-.]{8,}/i', '$1••••••', $value) ?? $value;
        $value = preg_replace('/([A-Z0-9._%+-]{2})[A-Z0-9._%+-]*(@[A-Z0-9.-]+\.[A-Z]{2,})/i', '$1••••$2', $value) ?? $value;

        return $value;
    }
}
