@extends('layouts.app')
@section('title', __('messages.projects.edit'))
@section('page_title', __('messages.projects.edit'))
@section('content')
<form method="POST" action="{{ route('projects.update', $project) }}" data-aptoria-form-scope="project" data-aptoria-form-plugin>
    @method('PUT')
    @include('projects._form')
</form>
@endsection
