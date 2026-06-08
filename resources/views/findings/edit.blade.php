@extends('layouts.app')

@section('title', __('messages.findings.edit'))

@section('content')
<div class="row">
    <div class="col-lg-10 col-lg-offset-1">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.findings.edit') }} — {{ $project->name }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.findings.update', [$project, $finding]) }}">
                    @method('PUT')
                    @include('findings._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
