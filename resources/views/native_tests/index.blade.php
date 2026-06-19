@extends('layouts.app')
@section('title', __('messages.native_tests.title'))
@section('page_title', __('messages.native_tests.title'))
@section('page_actions')
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSuiteModal"><i data-lucide="clipboard-plus" class="me-1"></i>{{ __('messages.native_tests.new_suite') }}</button>
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="flask-conical"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.native_tests.metrics.suites') }}</p><h3 class="mb-0 fw-light">{{ $metrics['suites'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-info"><span class="avatar-title"><i data-lucide="clipboard-list"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.native_tests.metrics.cases') }}</p><h3 class="mb-0 fw-light">{{ $metrics['cases'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-success"><span class="avatar-title"><i data-lucide="play"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.native_tests.metrics.runs') }}</p><h3 class="mb-0 fw-light">{{ $metrics['runs'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-{{ $metrics['failed'] > 0 ? 'danger' : 'success' }}"><span class="avatar-title"><i data-lucide="badge-check"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.native_tests.metrics.pass_rate') }}</p><h3 class="mb-0 fw-light">{{ $metrics['pass_rate'] }}%</h3></div></div></div></div>
</div>

<div class="card aptoria-panel-card mb-3">
    <div class="card-header border-light d-flex justify-content-between align-items-center">
        <div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="workflow"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.native_tests.pipeline_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.native_tests.pipeline_copy') }}</p></div></div>
        <span class="badge badge-soft-success"><i data-lucide="folder-check" class="me-1"></i>{{ __('messages.native_tests.repository_backed') }}</span>
    </div>
</div>

<div class="card aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="flask-conical"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.native_tests.suites_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.native_tests.suites_copy') }}</p></div></div>
        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createSuiteModal"><i data-lucide="clipboard-plus" class="me-1"></i>{{ __('messages.native_tests.new_suite') }}</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="native-tests" data-aptoria-paging="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead class="thead-sm text-uppercase fs-xxs"><tr><th>{{ __('messages.native_tests.suite') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.native_tests.priority') }}</th><th>{{ __('messages.native_tests.cases') }}</th><th>{{ __('messages.native_tests.runs') }}</th><th class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th></tr></thead>
                <tbody>
                @forelse($suites as $suite)
                    <tr>
                        <td><div class="d-flex gap-2 align-items-start"><span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="flask-conical"></i></span></span><div><a class="fw-medium text-body" href="{{ route('projects.tests.suites.show', [$project, $suite]) }}">{{ $suite->name }}</a><small class="text-muted d-block">{{ $suite->owner_name ?: __('messages.native_tests.no_owner') }}</small></div></div></td>
                        <td><span class="badge badge-soft-{{ $suite->status_tone }}">{{ $suite->status_label }}</span></td>
                        <td><span class="badge badge-soft-{{ $suite->priority_tone }}">{{ $suite->priority_label }}</span></td>
                        <td><span class="badge badge-soft-info"><i data-lucide="clipboard-list" class="me-1"></i>{{ $suite->cases_count }}</span></td>
                        <td><span class="badge badge-soft-success"><i data-lucide="play" class="me-1"></i>{{ $suite->runs_count }}</span></td>
                        <td class="text-end"><div class="dropdown"><button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">{{ __('messages.common.actions') }}</button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="{{ route('projects.tests.suites.show', [$project, $suite]) }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.view') }}</a><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#createCaseModal{{ $suite->id }}"><i data-lucide="clipboard-plus" class="me-2"></i>{{ __('messages.native_tests.new_case') }}</button></div></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">{{ __('messages.native_tests.empty_suites') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('native_tests.partials.create-suite-modal', ['project' => $project, 'modalId' => 'createSuiteModal'])
@foreach($suites as $suite)
    @include('native_tests.partials.create-case-modal', ['project' => $project, 'suite' => $suite, 'endpoints' => $endpoints, 'modalId' => 'createCaseModal'.$suite->id])
@endforeach
@include('native_tests.partials.reopen-modal-script')
@endsection
