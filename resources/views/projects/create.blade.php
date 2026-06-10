@extends('layouts.app')

@section('title', __('messages.projects.create_title'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.projects.create_title') }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data">
                    @include('projects._form')
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.app.mvp') }} {{ config('aptoria.version') }}</div>
            <div class="panel-body">
                <p>{{ __('messages.projects.next_text') }}</p>
                <p class="text-muted">{{ __('messages.projects.base_url_help') }}</p>
                <a href="{{ route('projects.wizard.create') }}" class="btn btn-info btn-block">{{ __('messages.wizard.short_title') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection
