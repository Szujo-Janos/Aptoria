@extends('layouts.app')
@section('title', __('messages.projects.new'))
@section('page_title', __('messages.projects.new'))
@section('content')
<form method="POST" action="{{ route('projects.store') }}" data-aptoria-form-scope="project" data-aptoria-form-plugin>
    @include('projects._form')
</form>
@endsection
