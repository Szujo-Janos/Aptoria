@extends('layouts.app')

@section('title', __('messages.calendar.create'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.calendar.create') }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('calendar.store') }}">
                    @include('calendar._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
