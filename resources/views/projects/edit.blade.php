@extends('layouts.app')

@section('title', __('messages.projects.edit_title'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.projects.edit_title') }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.update', $project) }}">
                    @method('PUT')
                    @include('projects._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
