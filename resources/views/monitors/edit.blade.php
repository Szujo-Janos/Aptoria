@extends('layouts.app')

@section('title', __('messages.monitors.edit'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.monitors.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a></div>
                {{ __('messages.monitors.edit') }} — {{ $monitor->name }}
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.monitors.update', [$project, $monitor]) }}">
                    @method('PUT')
                    @include('monitors._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
