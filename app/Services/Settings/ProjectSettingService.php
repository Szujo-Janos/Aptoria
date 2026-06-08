<?php

namespace App\Services\Settings;

use App\Models\Project;
use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ProjectSettingService
{
    /**
     * @return array<string, array{value: mixed, type: string, group: string}>
     */
    public function defaults(): array
    {
        return [
            'scan.enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'scan_defaults'],
            'scan.default_environment_id' => ['value' => '', 'type' => 'string', 'group' => 'scan_defaults'],
            'scan.default_auth_profile_id' => ['value' => '', 'type' => 'string', 'group' => 'scan_defaults'],
            'scan.max_endpoints_per_scan' => ['value' => 100, 'type' => 'integer', 'group' => 'scan_defaults'],
            'scan.allow_private_networks' => ['value' => false, 'type' => 'boolean', 'group' => 'scan_safety'],
            'scan.require_confirmation' => ['value' => true, 'type' => 'boolean', 'group' => 'scan_safety'],
            'scan.store_response_body_preview' => ['value' => true, 'type' => 'boolean', 'group' => 'data_retention'],
            'risk.sensitive_keywords' => ['value' => '', 'type' => 'csv', 'group' => 'risk_overrides'],
            'risk.internal_keywords' => ['value' => '', 'type' => 'csv', 'group' => 'risk_overrides'],
            'project.notes' => ['value' => '', 'type' => 'text', 'group' => 'general'],
        ];
    }

    /** @return array<string, array<string, array{key: string, value: mixed, type: string, group: string, raw_value: mixed}>> */
    public function grouped(Project $project): array
    {
        $settings = [];

        foreach ($this->defaults() as $key => $meta) {
            $settings[$key] = [
                'key' => $key,
                'value' => $this->get($project, $key),
                'type' => $meta['type'],
                'group' => $meta['group'],
                'raw_value' => $this->raw($project, $key),
            ];
        }

        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting['group']][$setting['key']] = $setting;
        }

        return $grouped;
    }

    public function get(Project $project, string $key, mixed $fallback = null): mixed
    {
        $defaults = $this->defaults();
        $meta = $defaults[$key] ?? ['value' => $fallback, 'type' => 'string', 'group' => 'general'];
        $raw = $this->raw($project, $key);

        if ($raw === null) {
            return $meta['value'];
        }

        return $this->cast($raw, (string) $meta['type']);
    }

    public function integer(Project $project, string $key, int $fallback = 0): int
    {
        return (int) $this->get($project, $key, $fallback);
    }

    public function boolean(Project $project, string $key, bool $fallback = false): bool
    {
        return (bool) $this->get($project, $key, $fallback);
    }

    /** @return array<int, string> */
    public function csv(Project $project, string $key): array
    {
        $value = $this->get($project, $key, '');

        if (is_array($value)) {
            return $value;
        }

        return $this->csvToArray((string) $value);
    }

    /** @param array<string, mixed> $values */
    public function updateMany(Project $project, array $values): void
    {
        if (! $this->databaseAvailable()) {
            return;
        }

        foreach ($this->defaults() as $key => $meta) {
            if (! array_key_exists($key, $values)) {
                if ($meta['type'] === 'boolean') {
                    $values[$key] = false;
                } else {
                    continue;
                }
            }

            $this->set($project, $key, $values[$key]);
        }
    }

    public function set(Project $project, string $key, mixed $value): void
    {
        $defaults = $this->defaults();
        $meta = $defaults[$key] ?? ['type' => 'string', 'group' => 'general'];
        $stored = $this->serialize($value, (string) $meta['type']);

        ProjectSetting::query()->updateOrCreate(
            ['project_id' => $project->id, 'key' => $key],
            [
                'value' => $stored,
                'type' => (string) $meta['type'],
                'group' => (string) $meta['group'],
            ]
        );
    }

    public function seedDefaults(Project $project): void
    {
        if (! $this->databaseAvailable()) {
            return;
        }

        Model::withoutEvents(function () use ($project): void {
            foreach ($this->defaults() as $key => $meta) {
                ProjectSetting::query()->firstOrCreate(
                    ['project_id' => $project->id, 'key' => $key],
                    [
                        'value' => $this->serialize($meta['value'], (string) $meta['type']),
                        'type' => (string) $meta['type'],
                        'group' => (string) $meta['group'],
                    ]
                );
            }
        });
    }

    public function reset(Project $project): void
    {
        if (! $this->databaseAvailable()) {
            return;
        }

        foreach ($this->defaults() as $key => $meta) {
            $this->set($project, $key, $meta['value']);
        }
    }

    /** @return array<string, mixed> */
    public function export(Project $project): array
    {
        return collect($this->defaults())
            ->mapWithKeys(fn (array $meta, string $key): array => [$key => $this->get($project, $key)])
            ->all();
    }

    private function raw(Project $project, string $key): mixed
    {
        if (! $this->databaseAvailable()) {
            return null;
        }

        try {
            return ProjectSetting::query()
                ->where('project_id', $project->id)
                ->where('key', $key)
                ->value('value');
        } catch (Throwable) {
            return null;
        }
    }

    private function databaseAvailable(): bool
    {
        try {
            return Schema::hasTable('project_settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'csv' => $this->csvToArray((string) $value),
            default => (string) $value,
        };
    }

    private function serialize(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'boolean') {
            return $value ? '1' : '0';
        }

        if ($type === 'csv') {
            if (is_array($value)) {
                return implode(',', array_filter(array_map('trim', $value)));
            }

            return implode(',', $this->csvToArray((string) $value));
        }

        return trim((string) $value);
    }

    /** @return array<int, string> */
    private function csvToArray(string $value): array
    {
        $parts = preg_split('/[,\r\n]+/', $value) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn (string $item): string => trim(strtolower($item)),
            $parts
        ))));
    }
}
