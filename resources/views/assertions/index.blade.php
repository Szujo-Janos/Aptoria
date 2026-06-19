@extends('layouts.app')
@section('title', __('messages.assertions.title') . ' · ' . $project->name)
@section('page_title', __('messages.assertions.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assertionCreateModal"><i data-lucide="list-plus" class="me-1"></i>{{ __('messages.assertions.new') }}</button>
@endsection

@section('content')
<div class="row g-3">
    @foreach ([['total','list-checks','primary'],['enabled','toggle-right','success'],['project_level','layers-3','info'],['blockers','octagon-alert','danger']] as [$key,$icon,$tone])
        <div class="col-md-3">
            <div class="card aptoria-panel-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar rounded text-bg-{{ $tone }}"><span class="avatar-title"><i data-lucide="{{ $icon }}"></i></span></span>
                    <div>
                        <div class="text-muted small">{{ __('messages.assertions.metrics.'.$key) }}</div>
                        <h3 class="mb-0 fw-light">{{ $metrics[$key] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mt-3 aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.assertions.registry') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.assertions.copy') }}</p>
        </div>
        <span class="badge badge-soft-primary badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.common.live') }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="assertions" data-aptoria-actions="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-assertions-table">
                <thead>
                    <tr>
                        <th data-priority="1">{{ __('messages.assertions.rule') }}</th>
                        <th>{{ __('messages.assertions.scope') }}</th>
                        <th>{{ __('messages.assertions.expectation') }}</th>
                        <th>{{ __('messages.assertions.severity') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th data-priority="2" class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="checklist"></i></span></span>
                                    <div class="min-w-0">
                                        <div class="fw-normal text-truncate">{{ $rule->name }}</div>
                                        <div class="text-muted small text-truncate">{{ $rule->rule_label }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($rule->endpoint)
                                    <span class="badge text-bg-{{ in_array($rule->endpoint->method, ['GET','HEAD'], true) ? 'success' : 'warning' }}">{{ $rule->endpoint->method }}</span>
                                    <code class="ms-1">{{ $rule->endpoint->path }}</code>
                                @else
                                    <span class="badge badge-soft-info">{{ __('messages.assertions.project_level') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span><code>{{ __('messages.assertions.operators.'.$rule->operator) }}</code> <span class="text-muted">{{ $rule->expected_value }}</span></span>
                                    @if ($rule->target_path)
                                        <small class="text-muted"><i data-lucide="route" class="me-1"></i>{{ __('messages.assertions.target_path') }}: <code>{{ $rule->target_path }}</code></small>
                                    @endif
                                </div>
                            </td>
                            <td><span class="badge badge-soft-{{ $rule->severity_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.assertions.severities.'.$rule->severity) }}</span></td>
                            <td><span class="badge badge-soft-{{ $rule->enabled ? 'success' : 'secondary' }}">{{ $rule->enabled ? __('messages.common.enabled') : __('messages.common.disabled') }}</span></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#assertionEditModal{{ $rule->id }}"><i data-lucide="pencil" class="me-2"></i>{{ __('messages.common.edit') }}</button>
                                        <form method="POST" action="{{ route('projects.assertions.destroy', [$project, $rule]) }}" data-aptoria-form-plugin data-aptoria-confirm="danger" data-confirm-title="{{ __('messages.common.delete') }}" data-confirm-text="{{ __('messages.assertions.delete_confirm') }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"><i data-lucide="trash-2" class="me-2"></i>{{ __('messages.common.delete') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ __('messages.assertions.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('assertions.partials.form-modal', ['modalId' => 'assertionCreateModal', 'action' => route('projects.assertions.store', $project), 'method' => 'POST', 'rule' => null])
@foreach ($rules as $rule)
    @include('assertions.partials.form-modal', ['modalId' => 'assertionEditModal'.$rule->id, 'action' => route('projects.assertions.update', [$project, $rule]), 'method' => 'PUT', 'rule' => $rule])
@endforeach
@endsection
