@extends('layouts.app')

@section('title', __('messages.how_it_works.title'))

@section('content')
<div class="normalheader small-header">
    <div class="hpanel">
        <div class="panel-body">
            <div class="pull-right">
                <a href="{{ route('help.index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-book"></i> {{ __('messages.help.title') }}
                </a>
                <a href="{{ route('projects.wizard.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-magic"></i> {{ __('messages.wizard.short_title') }}
                </a>
            </div>
            <h2 class="font-light m-b-xs">{{ __('messages.how_it_works.title') }}</h2>
            <small>{{ __('messages.how_it_works.subtitle') }}</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <i class="fa fa-map-signs text-success"></i> {{ __('messages.how_it_works.tutorial_title') }}
            </div>
            <div class="panel-body">
                <p class="lead">{{ __('messages.how_it_works.intro') }}</p>
                <div class="aptoria-timeline">
                    @foreach($steps as $index => $step)
                        <div class="aptoria-timeline-item">
                            <div class="aptoria-timeline-number">{{ $index + 1 }}</div>
                            <div class="aptoria-timeline-content">
                                <h4>{{ $step['title'] }}</h4>
                                <p>{{ $step['body'] }}</p>
                                @if(!empty($step['tip']))
                                    <div class="alert alert-info m-b-none"><strong>{{ __('messages.help.tip') }}</strong> {{ $step['tip'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <i class="fa fa-shield"></i> {{ __('messages.how_it_works.safety_title') }}
            </div>
            <div class="panel-body">
                <p>{{ __('messages.how_it_works.safety_intro') }}</p>
                <ul class="aptoria-check-list">
                    @foreach($safetyRules as $rule)
                        <li><i class="fa fa-check text-success"></i> {{ $rule }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <i class="fa fa-cogs"></i> {{ __('messages.how_it_works.workflow_title') }}
            </div>
            <div class="panel-body">
                @foreach($workflow as $item)
                    <div class="aptoria-workflow-card">
                        <span class="label label-{{ $item['label'] ?? 'info' }}">{{ $item['stage'] }}</span>
                        <h4>{{ $item['title'] }}</h4>
                        <p>{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <i class="fa fa-play-circle"></i> {{ __('messages.how_it_works.demo_title') }}
            </div>
            <div class="panel-body">
                <p>{{ __('messages.how_it_works.demo_body') }}</p>
                <a href="{{ route('projects.wizard.create') }}" class="btn btn-success btn-block">
                    {{ __('messages.wizard.short_title') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
