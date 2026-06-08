@extends('layouts.app')

@section('title', __('messages.assertions.edit_title'))

@section('content')
<div class="row">
    <div class="col-lg-9">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.assertions.edit_title') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.assertions.form_intro') }}</p>
                <form method="POST" action="{{ route('projects.assertion-rules.update', [$project, $rule]) }}">
                    @csrf
                    @method('PUT')
                    @include('assertion_rules._form')
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.assertions.how_precedence_works') }}</div>
            <div class="panel-body">
                <p class="m-b-none">{{ __('messages.assertions.precedence_help') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
