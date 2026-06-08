@extends('layouts.app')

@section('title', __('messages.environments.create_title'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.environments.create_title') }}: {{ $project->name }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.environments.store', $project) }}">
                    @include('environments._form')
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.environments.title') }}</div>
            <div class="panel-body">
                <p class="m-b-none">{{ __('messages.environments.base_url_help') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
