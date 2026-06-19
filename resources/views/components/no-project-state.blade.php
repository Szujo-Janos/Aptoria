@props([
    'compact' => false,
    'module' => null,
])

<div {{ $attributes->merge(['class' => 'card aptoria-panel-card aptoria-no-project-card']) }}>
    <div class="card-body {{ $compact ? 'p-3' : 'p-4' }}">
        <div class="d-flex {{ $compact ? 'align-items-start gap-3' : 'align-items-start gap-4 flex-column flex-md-row' }}">
            <span class="avatar {{ $compact ? 'avatar-md' : 'avatar-xl' }} rounded text-bg-primary flex-shrink-0">
                <span class="avatar-title"><i data-lucide="folder-plus"></i></span>
            </span>
            <div class="flex-grow-1 min-w-0">
                <span class="badge badge-soft-warning badge-label mb-2"><i class="ti ti-point-filled"></i>{{ __('messages.workspace.no_project_badge') }}</span>
                <h4 class="mb-1 {{ $compact ? 'fs-16' : '' }}">{{ __('messages.workspace.no_project_title') }}</h4>
                <p class="text-muted mb-3 {{ $compact ? 'small' : '' }}">{{ __('messages.workspace.no_project_copy') }}</p>

                @unless ($compact)
                    <div class="row g-2 mb-3 aptoria-no-project-steps">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light bg-opacity-50">
                                <div class="fw-medium mb-1"><i data-lucide="folder-kanban" class="me-1"></i>{{ __('messages.workspace.no_project_step_project') }}</div>
                                <small class="text-muted">{{ __('messages.workspace.no_project_step_project_copy') }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light bg-opacity-50">
                                <div class="fw-medium mb-1"><i data-lucide="globe" class="me-1"></i>{{ __('messages.workspace.no_project_step_target') }}</div>
                                <small class="text-muted">{{ __('messages.workspace.no_project_step_target_copy') }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light bg-opacity-50">
                                <div class="fw-medium mb-1"><i data-lucide="radar" class="me-1"></i>{{ __('messages.workspace.no_project_step_evidence') }}</div>
                                <small class="text-muted">{{ __('messages.workspace.no_project_step_evidence_copy') }}</small>
                            </div>
                        </div>
                    </div>
                @endunless

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('projects.create') }}" class="btn btn-primary {{ $compact ? 'btn-sm' : '' }}">
                        <i data-lucide="plus" class="me-1"></i>{{ __('messages.projects.new') }}
                    </a>
                    <a href="{{ route('projects.index') }}" class="btn btn-light {{ $compact ? 'btn-sm' : '' }}">
                        <i data-lucide="folder-kanban" class="me-1"></i>{{ __('messages.workspace.open_projects') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
