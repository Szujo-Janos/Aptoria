@extends('layouts.app')
@php
    $label = __('messages.modules.'.$module.'.title');
    $moduleConfig = collect($modules ?? [])->firstWhere('slug', $module);
@endphp
@section('title', $label)
@section('page_title', $label)
@section('page_actions')
    @if ($project)
        <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.workspace.back_to_workspace') }}</a>
    @endif
@endsection
@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm aptoria-placeholder-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3 mb-4">
                    <span class="avatar avatar-xl rounded text-bg-{{ $moduleConfig['tone'] ?? 'primary' }}"><span class="avatar-title"><i data-lucide="{{ $moduleConfig['icon'] ?? 'box' }}"></i></span></span>
                    <div>
                        <span class="badge text-bg-light badge-label mb-2">{{ __('messages.workspace.module_placeholder') }}</span>
                        <h1 class="h3 mb-1">{{ $label }}</h1>
                        <p class="text-muted mb-0">{{ __('messages.modules.'.$module.'.description') }}</p>
                    </div>
                </div>

                @if ($project)
                    <div class="alert alert-primary bg-primary-subtle border-primary-subtle">
                        <div class="d-flex gap-2 align-items-start">
                            <i data-lucide="folder-kanban" class="mt-1"></i>
                            <div>
                                <strong>{{ __('messages.workspace.project_scoped_module') }}</strong><br>
                                {{ __('messages.workspace.project_scoped_module_copy', ['project' => $project->name]) }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning bg-warning-subtle border-warning-subtle aptoria-project-required-ribbon">
                        <div class="d-flex gap-2 align-items-start">
                            <i data-lucide="bug" class="mt-1"></i>
                            <div>
                                <strong>{{ __('messages.workspace.no_current_project') }}</strong><br>
                                {{ __('messages.workspace.select_project_before_module') }}
                            </div>
                        </div>
                    </div>
                    <x-no-project-state class="mb-3" :module="$module" />
                @endif

                <div class="card bg-light bg-opacity-50 border-0 mb-0">
                    <div class="card-body">
                        <strong>{{ __('messages.common.next_step') }}</strong><br>
                        <span class="text-muted">{{ __('messages.modules.'.$module.'.next') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('messages.workspace.module_stack') }}</h5></div>
            <div class="list-group list-group-flush">
                @foreach ($modules ?? [] as $item)
                    <a href="{{ $project ? route('projects.modules.show', [$project, $item['slug']]) : route('modules.show', $item['slug']) }}" class="list-group-item list-group-item-action d-flex gap-3 align-items-start {{ $item['slug'] === $module ? 'active' : '' }}">
                        <span class="avatar avatar-xs rounded {{ $item['slug'] === $module ? 'text-bg-light' : 'text-bg-'.$item['tone'] }}"><span class="avatar-title"><i data-lucide="{{ $item['icon'] }}"></i></span></span>
                        <span><span class="d-block">{{ $item['title'] }}</span><small>{{ $item['phase'] }}</small></span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
