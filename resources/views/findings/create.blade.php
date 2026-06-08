@extends('layouts.app')

@section('title', __('messages.findings.create'))

@section('content')
<div class="row">
    <div class="col-lg-10 col-lg-offset-1">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.findings.create') }} — {{ $project->name }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.findings.store', $project) }}">
                    @include('findings._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
