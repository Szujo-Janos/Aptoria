<?php

namespace App\Services\Endpoints;

use App\Models\Endpoint;
use App\Models\EndpointPathParameter;
use App\Models\Environment;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class PathParameterResolver
{
    /** @return array<int, string> */
    public function extractNames(string $path): array
    {
        preg_match_all('/\{([A-Za-z0-9_\-]+)\}|:([A-Za-z0-9_\-]+)/', $path, $matches, PREG_SET_ORDER);

        $names = [];
        foreach ($matches as $match) {
            $name = EndpointPathParameter::normalizeName($match[1] !== '' ? $match[1] : ($match[2] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    public function hasParameters(string $path): bool
    {
        return $this->extractNames($path) !== [];
    }

    /** @return array<int, array{name:string,project_value:?string,endpoint_value:?string,effective_value:?string,source:string,resolved:bool}> */
    public function displayRows(Endpoint $endpoint): array
    {
        $names = $this->extractNames((string) $endpoint->path);
        if ($names === []) {
            return [];
        }

        $projectValues = $this->projectDefaults($endpoint->project);
        $endpointValues = $this->endpointOverrides($endpoint);
        $rows = [];

        foreach ($names as $name) {
            $endpointValue = $endpointValues[$name] ?? null;
            $projectValue = $projectValues[$name] ?? null;
            $fallback = $this->suggestedValue($name);
            $effective = $endpointValue ?: ($projectValue ?: $fallback);
            $source = $endpointValue !== null && $endpointValue !== ''
                ? 'endpoint'
                : (($projectValue !== null && $projectValue !== '') ? 'project' : 'suggested');

            $rows[] = [
                'name' => $name,
                'project_value' => $projectValue,
                'endpoint_value' => $endpointValue,
                'effective_value' => $effective,
                'source' => $source,
                'resolved' => $effective !== null && $effective !== '',
            ];
        }

        return $rows;
    }

    /** @return array<string, string> */
    public function effectiveValues(Endpoint $endpoint): array
    {
        return collect($this->displayRows($endpoint))
            ->filter(fn (array $row): bool => (bool) $row['resolved'])
            ->mapWithKeys(fn (array $row): array => [$row['name'] => (string) $row['effective_value']])
            ->all();
    }

    /** @return array<int, string> */
    public function unresolvedNames(Endpoint $endpoint): array
    {
        return collect($this->displayRows($endpoint))
            ->filter(fn (array $row): bool => ! (bool) $row['resolved'])
            ->pluck('name')
            ->values()
            ->all();
    }

    public function resolvePath(Endpoint $endpoint): string
    {
        $path = (string) $endpoint->path;

        foreach ($this->effectiveValues($endpoint) as $name => $value) {
            $encoded = rawurlencode($value);
            $path = str_replace('{'.$name.'}', $encoded, $path);
            $path = preg_replace('/(?<=\/):'.preg_quote($name, '/').'(?=\/|$|\?)/', $encoded, $path) ?? $path;
        }

        return $path;
    }

    public function buildUrl(Project $project, Endpoint $endpoint, ?Environment $forcedEnvironment = null): string
    {
        $baseUrl = $forcedEnvironment?->base_url
            ?: $endpoint->environment?->base_url
            ?: $project->defaultEnvironment()?->base_url
            ?: $project->base_url;

        return rtrim((string) $baseUrl, '/').'/'.ltrim($this->resolvePath($endpoint), '/');
    }

    public function formatText(Project $project, ?Endpoint $endpoint = null): string
    {
        $values = $endpoint instanceof Endpoint ? $this->endpointOverrides($endpoint) : $this->projectDefaults($project);

        if ($values === []) {
            return '';
        }

        ksort($values);

        return collect($values)
            ->map(fn (string $value, string $name): string => $name.'='.$value)
            ->implode("\n");
    }

    public function updateProjectDefaultsFromText(Project $project, string $text): void
    {
        $this->replaceValues($project, null, $this->parseText($text));
    }

    public function updateEndpointOverridesFromText(Endpoint $endpoint, string $text): void
    {
        $this->replaceValues($endpoint->project, $endpoint, $this->parseText($text));
    }

    public function ensureProjectDefaultsFromPath(Project $project, string $path): void
    {
        if (! $this->tableAvailable()) {
            return;
        }

        foreach ($this->extractNames($path) as $name) {
            EndpointPathParameter::query()->firstOrCreate(
                ['project_id' => $project->id, 'endpoint_id' => null, 'parameter_name' => $name],
                [
                    'test_value' => $this->suggestedValue($name),
                    'description' => __('messages.path_parameters.auto_created_description'),
                    'enabled' => true,
                ]
            );
        }
    }

    /** @return array<string, string> */
    public function parseText(string $text): array
    {
        $values = [];
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$name, $value] = array_map('trim', explode('=', $line, 2));
            } elseif (str_contains($line, ':')) {
                [$name, $value] = array_map('trim', explode(':', $line, 2));
            } else {
                continue;
            }

            $name = EndpointPathParameter::normalizeName($name);
            if ($name === '' || $value === '') {
                continue;
            }

            $values[$name] = Str::limit($value, 500, '');
        }

        return $values;
    }

    public function suggestedValue(string $name): string
    {
        $normalized = strtolower($name);

        return match (true) {
            in_array($normalized, ['id', 'userid', 'user_id', 'postid', 'post_id', 'commentid', 'comment_id', 'todoid', 'todo_id', 'albumid', 'album_id', 'photoid', 'photo_id', 'orderid', 'order_id', 'productid', 'product_id'], true) => '1',
            str_contains($normalized, 'uuid') => '00000000-0000-4000-8000-000000000001',
            str_contains($normalized, 'slug') => 'sample-slug',
            str_contains($normalized, 'email') => 'user@example.com',
            str_contains($normalized, 'date') => now()->toDateString(),
            default => '1',
        };
    }

    /** @return array<string, string> */
    private function projectDefaults(?Project $project): array
    {
        if (! $project instanceof Project || ! $this->tableAvailable()) {
            return [];
        }

        return $this->valuesQuery($project, null)
            ->pluck('test_value', 'parameter_name')
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }

    /** @return array<string, string> */
    private function endpointOverrides(Endpoint $endpoint): array
    {
        if (! $this->tableAvailable()) {
            return [];
        }

        return $this->valuesQuery($endpoint->project, $endpoint)
            ->pluck('test_value', 'parameter_name')
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }

    /** @return Collection<int, EndpointPathParameter> */
    private function valuesQuery(Project $project, ?Endpoint $endpoint): mixed
    {
        return EndpointPathParameter::query()
            ->where('project_id', $project->id)
            ->where('enabled', true)
            ->when($endpoint instanceof Endpoint, fn ($query) => $query->where('endpoint_id', $endpoint->id), fn ($query) => $query->whereNull('endpoint_id'))
            ->orderBy('parameter_name')
            ->get();
    }

    /** @param array<string, string> $values */
    private function replaceValues(Project $project, ?Endpoint $endpoint, array $values): void
    {
        if (! $this->tableAvailable()) {
            return;
        }

        EndpointPathParameter::query()
            ->where('project_id', $project->id)
            ->when($endpoint instanceof Endpoint, fn ($query) => $query->where('endpoint_id', $endpoint->id), fn ($query) => $query->whereNull('endpoint_id'))
            ->delete();

        foreach ($values as $name => $value) {
            EndpointPathParameter::query()->create([
                'project_id' => $project->id,
                'endpoint_id' => $endpoint?->id,
                'parameter_name' => $name,
                'test_value' => $value,
                'description' => $endpoint instanceof Endpoint ? __('messages.path_parameters.endpoint_override') : __('messages.path_parameters.project_default'),
                'enabled' => true,
            ]);
        }
    }

    private function tableAvailable(): bool
    {
        try {
            return Schema::hasTable('endpoint_path_parameters');
        } catch (Throwable) {
            return false;
        }
    }
}
