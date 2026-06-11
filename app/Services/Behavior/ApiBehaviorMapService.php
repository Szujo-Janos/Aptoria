<?php

namespace App\Services\Behavior;

use App\Models\Endpoint;
use App\Models\EndpointBehaviorLink;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ApiBehaviorMapService
{
    /** @return array<string, mixed> */
    public function summarize(Project $project, bool $persist = true): array
    {
        /** @var EloquentCollection<int, Endpoint> $endpoints */
        $endpoints = $project->endpoints()
            ->with(['producedBehaviorLinks.consumerEndpoint', 'consumedBehaviorLinks.producerEndpoint', 'pathParameters'])
            ->orderBy('path')
            ->orderBy('method')
            ->get();

        $detected = $this->detect($endpoints);

        if ($persist) {
            $this->persist($project, $endpoints, $detected['links']);
            /** @var EloquentCollection<int, Endpoint> $endpoints */
            $endpoints = $project->endpoints()
                ->with(['producedBehaviorLinks.consumerEndpoint', 'consumedBehaviorLinks.producerEndpoint', 'pathParameters'])
                ->orderBy('path')
                ->orderBy('method')
                ->get();
        }

        $links = $project->endpointBehaviorLinks()
            ->with(['producerEndpoint', 'consumerEndpoint'])
            ->orderByDesc('confidence')
            ->orderBy('resource_key')
            ->get();

        $resourceGroups = $endpoints
            ->groupBy(fn (Endpoint $endpoint): string => (string) ($endpoint->behavior_resource ?: $this->resourceKey($endpoint->path)))
            ->sortKeys();

        $sequences = $this->suggestSequences($links);

        return [
            'summary' => [
                'endpoints' => $endpoints->count(),
                'producers' => $endpoints->filter(fn (Endpoint $endpoint): bool => in_array($endpoint->behavior_role, [Endpoint::BEHAVIOR_ROLE_PRODUCER, Endpoint::BEHAVIOR_ROLE_PRODUCER_CONSUMER], true))->count(),
                'consumers' => $endpoints->filter(fn (Endpoint $endpoint): bool => in_array($endpoint->behavior_role, [Endpoint::BEHAVIOR_ROLE_CONSUMER, Endpoint::BEHAVIOR_ROLE_PRODUCER_CONSUMER], true))->count(),
                'destructive' => $endpoints->where('destructive_action', true)->count(),
                'auth_boundaries' => $endpoints->where('auth_boundary', true)->count(),
                'sequence_candidates' => $endpoints->where('sequence_candidate', true)->count(),
                'dependencies' => $links->count(),
                'resources' => $resourceGroups->count(),
            ],
            'endpoints' => $endpoints,
            'links' => $links,
            'resource_groups' => $resourceGroups,
            'sequences' => $sequences,
        ];
    }

    /** @param EloquentCollection<int, Endpoint> $endpoints @return array{metadata: array<int, array<string, mixed>>, links: Collection<int, array<string, mixed>>} */
    public function detect(EloquentCollection $endpoints): array
    {
        $metadata = [];
        $links = collect();
        $byResource = $endpoints->groupBy(fn (Endpoint $endpoint): string => $this->resourceKey($endpoint->path));

        foreach ($endpoints as $endpoint) {
            $pathParameters = $this->pathParameters($endpoint->path);
            $resource = $this->resourceKey($endpoint->path);
            $method = strtoupper($endpoint->method);
            $isProducer = in_array($method, [Endpoint::METHOD_POST], true) && $pathParameters === [];
            $isConsumer = $pathParameters !== [] || in_array($method, [Endpoint::METHOD_GET, Endpoint::METHOD_PUT, Endpoint::METHOD_PATCH, Endpoint::METHOD_DELETE], true);
            $isDestructive = $this->isDestructive($endpoint);
            $hasPublicSibling = $byResource->get($resource, collect())->contains(fn (Endpoint $sibling): bool => ! $sibling->auth_required);
            $hasPrivateSibling = $byResource->get($resource, collect())->contains(fn (Endpoint $sibling): bool => $sibling->auth_required);
            $authBoundary = $hasPublicSibling && $hasPrivateSibling;
            $sequenceCandidate = $isProducer || $pathParameters !== [] || $isDestructive;

            $role = match (true) {
                $isDestructive => Endpoint::BEHAVIOR_ROLE_DESTRUCTIVE,
                $isProducer && $isConsumer => Endpoint::BEHAVIOR_ROLE_PRODUCER_CONSUMER,
                $isProducer => Endpoint::BEHAVIOR_ROLE_PRODUCER,
                $isConsumer => Endpoint::BEHAVIOR_ROLE_CONSUMER,
                default => Endpoint::BEHAVIOR_ROLE_REFERENCE,
            };

            $metadata[$endpoint->id] = [
                'behavior_role' => $role,
                'behavior_resource' => $resource,
                'destructive_action' => $isDestructive,
                'auth_boundary' => $authBoundary,
                'sequence_candidate' => $sequenceCandidate,
                'behavior_notes' => $this->notes($endpoint, $resource, $pathParameters, $isDestructive, $authBoundary),
            ];
        }

        foreach ($byResource as $resource => $resourceEndpoints) {
            $producers = $resourceEndpoints
                ->filter(fn (Endpoint $endpoint): bool => strtoupper($endpoint->method) === Endpoint::METHOD_POST && $this->pathParameters($endpoint->path) === [])
                ->sortBy(fn (Endpoint $endpoint): string => sprintf('%02d|%s|%010d', $this->methodOrder($endpoint->method), $endpoint->path, $endpoint->id))
                ->values();
            $consumers = $resourceEndpoints
                ->filter(fn (Endpoint $endpoint): bool => $this->pathParameters($endpoint->path) !== [] || in_array(strtoupper($endpoint->method), [Endpoint::METHOD_GET, Endpoint::METHOD_PUT, Endpoint::METHOD_PATCH, Endpoint::METHOD_DELETE], true))
                ->sortBy(fn (Endpoint $endpoint): string => sprintf('%02d|%s|%010d', $this->methodOrder($endpoint->method), $endpoint->path, $endpoint->id))
                ->values();

            foreach ($producers as $producer) {
                foreach ($consumers as $consumer) {
                    if ($producer->id === $consumer->id) {
                        continue;
                    }

                    $params = $this->pathParameters($consumer->path);
                    $dependencyType = $params === [] ? EndpointBehaviorLink::TYPE_RESOURCE_FLOW : EndpointBehaviorLink::TYPE_PATH_PARAMETER;
                    $links->push([
                        'producer_endpoint_id' => $producer->id,
                        'consumer_endpoint_id' => $consumer->id,
                        'dependency_type' => $dependencyType,
                        'resource_key' => $resource,
                        'path_parameter' => $params[0] ?? null,
                        'confidence' => $params === [] ? 60 : 85,
                        'suggested_sequence' => $this->sequenceText($producer, $consumer),
                        'notes' => $params === []
                            ? __('messages.api_behavior.link_notes.resource_flow')
                            : __('messages.api_behavior.link_notes.path_parameter', ['parameter' => $params[0]]),
                    ]);
                }
            }
        }

        return ['metadata' => $metadata, 'links' => $links];
    }

    /** @param EloquentCollection<int, Endpoint> $endpoints @param Collection<int, array<string, mixed>> $links */
    private function persist(Project $project, EloquentCollection $endpoints, Collection $links): void
    {
        $detected = $this->detect($endpoints);

        foreach ($detected['metadata'] as $endpointId => $values) {
            Endpoint::query()
                ->whereKey($endpointId)
                ->where('project_id', $project->id)
                ->update($values);
        }

        $project->endpointBehaviorLinks()->delete();

        foreach ($links as $link) {
            EndpointBehaviorLink::query()->create([
                'project_id' => $project->id,
                ...$link,
            ]);
        }
    }

    /** @param Collection<int, EndpointBehaviorLink> $links @return Collection<int, array<string, mixed>> */
    private function suggestSequences(Collection $links): Collection
    {
        return $links
            ->groupBy('resource_key')
            ->map(function (Collection $resourceLinks, string $resource): array {
                $ordered = $resourceLinks->sortBy(fn (EndpointBehaviorLink $link): int => $this->methodOrder($link->consumerEndpoint?->method ?: 'GET'));

                return [
                    'resource' => $resource,
                    'links' => $ordered->values(),
                    'summary' => $ordered->map(fn (EndpointBehaviorLink $link): string => $link->producerEndpoint->method.' '.$link->producerEndpoint->path.' → '.$link->consumerEndpoint->method.' '.$link->consumerEndpoint->path)->unique()->implode(' / '),
                ];
            })
            ->values();
    }

    private function methodOrder(string $method): int
    {
        return match (strtoupper($method)) {
            Endpoint::METHOD_GET => 10,
            Endpoint::METHOD_PATCH, Endpoint::METHOD_PUT => 20,
            Endpoint::METHOD_DELETE => 30,
            default => 40,
        };
    }

    private function sequenceText(Endpoint $producer, Endpoint $consumer): string
    {
        return $producer->method.' '.$producer->path.' → '.$consumer->method.' '.$consumer->path;
    }

    /** @return array<int, string> */
    private function pathParameters(string $path): array
    {
        preg_match_all('/\{([^}]+)\}|:([A-Za-z_][A-Za-z0-9_]*)/', $path, $matches);

        return collect($matches[1])
            ->merge($matches[2])
            ->filter()
            ->map(fn (string $parameter): string => trim($parameter, '{}: '))
            ->unique()
            ->values()
            ->all();
    }

    private function resourceKey(string $path): string
    {
        $pathOnly = parse_url($path, PHP_URL_PATH) ?: $path;
        $segments = collect(explode('/', trim($pathOnly, '/')))
            ->filter(fn (string $segment): bool => $segment !== '' && ! preg_match('/^\{[^}]+\}$|^:/', $segment))
            ->values();

        if ($segments->isEmpty()) {
            return 'root';
        }

        $last = (string) $segments->last();
        $noise = ['api', 'v1', 'v2', 'v3'];
        if (in_array(strtolower($last), $noise, true) && $segments->count() > 1) {
            $last = (string) $segments->get($segments->count() - 2);
        }

        return Str::of($last)->lower()->replaceMatches('/[^a-z0-9_-]/', '-')->trim('-')->toString() ?: 'resource';
    }

    private function isDestructive(Endpoint $endpoint): bool
    {
        $method = strtoupper($endpoint->method);
        $haystack = strtolower($endpoint->path.' '.$endpoint->name.' '.$endpoint->description.' '.$endpoint->tags);

        if ($method === Endpoint::METHOD_DELETE) {
            return true;
        }

        return in_array($method, [Endpoint::METHOD_POST, Endpoint::METHOD_PUT, Endpoint::METHOD_PATCH], true)
            && preg_match('/(delete|remove|destroy|cancel|void|refund|reset|disable|revoke|purge)/', $haystack) === 1;
    }

    /** @param array<int, string> $pathParameters */
    private function notes(Endpoint $endpoint, string $resource, array $pathParameters, bool $isDestructive, bool $authBoundary): string
    {
        $notes = [__('messages.api_behavior.notes.resource', ['resource' => $resource])];

        if ($pathParameters !== []) {
            $notes[] = __('messages.api_behavior.notes.path_parameters', ['parameters' => implode(', ', $pathParameters)]);
        }

        if ($isDestructive) {
            $notes[] = __('messages.api_behavior.notes.destructive');
        }

        if ($authBoundary) {
            $notes[] = __('messages.api_behavior.notes.auth_boundary');
        }

        return implode(' ', $notes);
    }
}
