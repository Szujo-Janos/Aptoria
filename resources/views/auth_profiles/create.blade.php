@extends('layouts.app')

@section('title', __('messages.auth_profiles.create_title'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.auth_profiles.create_title') }}: {{ $project->name }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.auth-profiles.store', $project) }}">
                    @include('auth_profiles._form')
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.projects.important') }}</div>
            <div class="panel-body">
                <p>{{ __('messages.auth_profiles.type_help') }}</p>
                <p class="m-b-none">{{ __('messages.auth_profiles.notes_help') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
