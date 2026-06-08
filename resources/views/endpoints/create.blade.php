@extends('layouts.app')

@section('title', __('messages.endpoints.create_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.endpoints.create_title') }} — {{ $project->name }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.endpoints.store', $project) }}">
                    @csrf
                    @include('endpoints._form', ['buttonLabel' => __('messages.endpoints.create_button')])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
