@extends('layouts.app')

@section('title', __('messages.environments.edit_title'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.environments.edit_title') }}: {{ $environment->name }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.environments.update', [$project, $environment]) }}">
                    @method('PUT')
                    @include('environments._form')
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.common.delete') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.environments.must_keep_one') }}</p>
                <form method="POST" action="{{ route('projects.environments.destroy', [$project, $environment]) }}" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.environments.confirm_delete') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">{{ __('messages.common.delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
