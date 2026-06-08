@extends('layouts.app')

@section('title', __('messages.test_suites.edit'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.test-suites.show', [$project, $testSuite]) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a></div>
                {{ __('messages.test_suites.edit') }} — {{ $testSuite->name }}
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.test-suites.update', [$project, $testSuite]) }}">
                    @method('PUT')
                    @include('test_suites._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
