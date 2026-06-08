@extends('layouts.app')

@section('title', __('messages.calendar.edit'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.calendar.edit') }} — {{ $calendarEvent->title }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('calendar.update', $calendarEvent) }}">
                    @method('PUT')
                    @include('calendar._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
