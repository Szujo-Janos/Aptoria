@extends('layouts.app')

@section('title', __('messages.test_suites.create'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.test-suites.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a></div>
                {{ __('messages.test_suites.create') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.test-suites.store', $project) }}">
                    @include('test_suites._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
