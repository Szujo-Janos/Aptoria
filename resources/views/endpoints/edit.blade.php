@extends('layouts.app')

@section('title', __('messages.endpoints.edit_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.endpoints.edit_title') }} — {{ $endpoint->method }} {{ $endpoint->path }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.endpoints.update', [$project, $endpoint]) }}">
                    @csrf
                    @method('PUT')
                    @include('endpoints._form', ['buttonLabel' => __('messages.endpoints.update_button')])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
