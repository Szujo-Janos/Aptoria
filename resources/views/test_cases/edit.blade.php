@extends('layouts.app')

@section('title', __('messages.test_cases.edit'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.test-cases.show', [$project, $testCase]) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a></div>
                {{ __('messages.test_cases.edit') }} — {{ $testCase->title }}
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.test-cases.update', [$project, $testCase]) }}">
                    @method('PUT')
                    @include('test_cases._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
