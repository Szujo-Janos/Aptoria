@extends('layouts.app')

@section('title', __('messages.api_behavior.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <form method="POST" action="{{ route('projects.api-behavior.refresh', $project) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-xs btn-primary"><i class="fa fa-refresh"></i> {{ __('messages.api_behavior.refresh') }}</button>
                    </form>
                    <a href="{{ route('projects.endpoint-inventory.index', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-list-alt"></i> {{ __('messages.endpoint_inventory.short_title') }}</a>
                </div>
                {{ __('messages.api_behavior.heading') }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.api_behavior.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @foreach([
        ['label' => __('messages.api_behavior.metrics.endpoints'), 'value' => $summary['endpoints'], 'panel' => 'hblue', 'icon' => 'fa-plug'],
        ['label' => __('messages.api_behavior.metrics.producers'), 'value' => $summary['producers'], 'panel' => 'hgreen', 'icon' => 'fa-plus-circle'],
        ['label' => __('messages.api_behavior.metrics.consumers'), 'value' => $summary['consumers'], 'panel' => 'hblue', 'icon' => 'fa-share-alt'],
        ['label' => __('messages.api_behavior.metrics.dependencies'), 'value' => $summary['dependencies'], 'panel' => 'hviolet', 'icon' => 'fa-link'],
        ['label' => __('messages.api_behavior.metrics.destructive'), 'value' => $summary['destructive'], 'panel' => $summary['destructive'] > 0 ? 'hred' : 'hgreen', 'icon' => 'fa-warning'],
        ['label' => __('messages.api_behavior.metrics.sequence_candidates'), 'value' => $summary['sequence_candidates'], 'panel' => 'hyellow', 'icon' => 'fa-random'],
    ] as $card)
        <div class="col-md-2">
            <div class="hpanel {{ $card['panel'] }}">
                <div class="panel-body text-center">
                    <i class="fa {{ $card['icon'] }} fa-2x m-b-sm"></i>
                    <h3>{{ $card['value'] }}</h3>
                    <small>{{ $card['label'] }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.api_behavior.dependencies_title') }}</div>
            <div class="panel-body">
                @if($links->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.api_behavior.no_dependencies') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.api_behavior.producer') }}</th>
                                    <th>{{ __('messages.api_behavior.consumer') }}</th>
                                    <th>{{ __('messages.api_behavior.resource') }}</th>
                                    <th>{{ __('messages.api_behavior.dependency_type') }}</th>
                                    <th>{{ __('messages.api_behavior.confidence_label') }}</th>
                                    <th>{{ __('messages.api_behavior.sequence') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($links as $link)
                                    <tr>
                                        <td><a href="{{ route('projects.endpoints.show', [$project, $link->producerEndpoint]) }}">{{ $link->producerEndpoint->method }} {{ $link->producerEndpoint->path }}</a></td>
                                        <td><a href="{{ route('projects.endpoints.show', [$project, $link->consumerEndpoint]) }}">{{ $link->consumerEndpoint->method }} {{ $link->consumerEndpoint->path }}</a></td>
                                        <td><code>{{ $link->resource_key }}</code></td>
                                        <td>{{ $link->dependency_type_label }}</td>
                                        <td><span class="label label-{{ $link->confidence_css }}">{{ $link->confidence }}% · {{ $link->confidence_label }}</span></td>
                                        <td><small>{{ $link->suggested_sequence }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.api_behavior.sequences_title') }}</div>
            <div class="panel-body">
                @if($sequences->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.api_behavior.no_sequences') }}</p>
                @else
                    @foreach($sequences as $sequence)
                        <div class="panel panel-default m-b-sm">
                            <div class="panel-heading"><strong>{{ $sequence['resource'] }}</strong></div>
                            <div class="panel-body">
                                <p class="small text-muted">{{ $sequence['summary'] }}</p>
                                <ol class="m-b-none">
                                    @foreach($sequence['links'] as $link)
                                        <li><code>{{ $link->producerEndpoint->method }} {{ $link->producerEndpoint->path }}</code> → <code>{{ $link->consumerEndpoint->method }} {{ $link->consumerEndpoint->path }}</code></li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.api_behavior.endpoint_roles_title') }}</div>
            <div class="panel-body">
                @if($endpoints->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.endpoints.empty_title') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.endpoints.method') }}</th>
                                    <th>{{ __('messages.endpoints.path') }}</th>
                                    <th>{{ __('messages.api_behavior.role') }}</th>
                                    <th>{{ __('messages.api_behavior.resource') }}</th>
                                    <th>{{ __('messages.api_behavior.flags.title') }}</th>
                                    <th>{{ __('messages.common.notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($endpoints as $endpoint)
                                    <tr>
                                        <td><span class="label label-default">{{ $endpoint->method }}</span></td>
                                        <td><a href="{{ route('projects.endpoints.show', [$project, $endpoint]) }}">{{ $endpoint->path }}</a></td>
                                        <td>{{ $endpoint->behavior_role_label }}</td>
                                        <td><code>{{ $endpoint->behavior_resource ?: __('messages.common.not_available') }}</code></td>
                                        <td>
                                            @if($endpoint->destructive_action)<span class="label label-danger">{{ __('messages.api_behavior.flags.destructive') }}</span>@endif
                                            @if($endpoint->auth_boundary)<span class="label label-warning">{{ __('messages.api_behavior.flags.auth_boundary') }}</span>@endif
                                            @if($endpoint->sequence_candidate)<span class="label label-info">{{ __('messages.api_behavior.flags.sequence_candidate') }}</span>@endif
                                        </td>
                                        <td><small>{{ $endpoint->behavior_notes ?: __('messages.api_behavior.no_behavior_detected') }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
