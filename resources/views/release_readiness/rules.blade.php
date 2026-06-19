@extends('layouts.app')
@section('title', __('messages.release_readiness.rules_title'))
@section('page_title', __('messages.release_readiness.rules_title'))
@section('page_actions')
    <a href="{{ route('projects.release-readiness.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.release_readiness.back_to_readiness') }}</a>
    <form method="POST" action="{{ route('projects.release-readiness.rules.reset', $project) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.release_readiness.rules_reset_confirm_title') }}" data-confirm-text="{{ __('messages.release_readiness.rules_reset_confirm_text') }}" data-confirm-button="{{ __('messages.release_readiness.rules_reset') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-warning"><i data-lucide="rotate-ccw" class="me-1"></i>{{ __('messages.release_readiness.rules_reset') }}</button>
    </form>
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="min-w-0">
                    <span class="badge badge-soft-{{ $profileSummary['profile_tone'] ?? 'primary' }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.active_profile') }}</span>
                    <h2 class="fw-normal mb-2"><i data-lucide="{{ $profileSummary['profile_icon'] ?? 'sliders-horizontal' }}" class="me-2"></i>{{ $profileSummary['profile_label'] ?? __('messages.release_readiness.profiles.standard') }}</h2>
                    <p class="text-muted mb-0">{{ __('messages.release_readiness.profile_copy') }}</p>
                </div>
                <span class="badge badge-soft-secondary badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.profile_deviations') }}: {{ $profileSummary['deviation_count'] ?? 0 }}</span>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_readiness.profile_footer') }}</div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="layers" class="me-2"></i>{{ __('messages.release_readiness.profile_presets') }}</h5></div>
            <div class="card-body d-flex flex-wrap gap-2">
                @foreach($profiles as $key => $profile)
                    <form method="POST" action="{{ route('projects.release-readiness.rules.apply-profile', $project) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.release_readiness.profile_apply_confirm_title') }}" data-confirm-text="{{ __('messages.release_readiness.profile_apply_confirm_text') }}" data-confirm-button="{{ __('messages.release_readiness.apply_profile') }}">
                        @csrf
                        <input type="hidden" name="profile_key" value="{{ $key }}">
                        <button class="btn btn-sm {{ ($profileSummary['profile_key'] ?? '') === $key ? 'btn-primary' : 'btn-light' }}" type="submit"><i data-lucide="{{ $profile['icon'] }}" class="me-1"></i>{{ __('messages.release_readiness.profiles.'.$key) }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if($simulation)
    <div class="card aptoria-panel-card border-primary mb-3">
        <div class="card-header border-light justify-content-between align-items-center">
            <div><h5 class="card-title mb-1"><i data-lucide="scan-eye" class="me-2"></i>{{ __('messages.release_readiness.simulation_title') }}</h5><p class="text-muted small mb-0">{{ __('messages.release_readiness.simulation_copy') }}</p></div>
            <span class="badge badge-soft-primary">{{ __('messages.release_readiness.preview_only') }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach ([['current_status','preview_status','shield-chevron',__('messages.common.status')], ['current_score','preview_score','gauge',__('messages.release_readiness.score')], ['current_blockers','preview_blockers','shield-alert',__('messages.release_readiness.blockers')], ['current_warnings','preview_warnings','triangle-alert',__('messages.release_readiness.warnings')]] as [$currentKey, $previewKey, $icon, $label])
                    <div class="col-md-3"><div class="border rounded p-3"><div class="text-muted small mb-1"><i data-lucide="{{ $icon }}" class="me-1"></i>{{ $label }}</div><div class="d-flex justify-content-between align-items-center gap-2"><span>{{ $simulation[$currentKey] }}</span><i data-lucide="arrow-right" class="text-muted"></i><strong>{{ $simulation[$previewKey] }}</strong></div></div></div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('projects.release-readiness.rules.update', $project) }}" id="readinessRulesForm">
    @csrf
    @method('PUT')
    <div class="card aptoria-panel-card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <span class="badge badge-soft-primary badge-label mb-2"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.rule_builder') }}</span>
                <h2 class="fw-normal mb-2">{{ __('messages.release_readiness.rules_heading') }}</h2>
                <p class="text-muted mb-0">{{ __('messages.release_readiness.rules_copy') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" formaction="{{ route('projects.release-readiness.rules.simulate', $project) }}" formmethod="POST" class="btn btn-light"><i data-lucide="scan-eye" class="me-1"></i>{{ __('messages.release_readiness.simulate') }}</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
            </div>
        </div>
        <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_readiness.rules_footer') }}</div>
    </div>

    @foreach($rulesByCategory as $category => $rules)
        <div class="card aptoria-table-card aptoria-panel-card mb-3">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1"><i data-lucide="sliders-horizontal" class="me-2"></i>{{ __('messages.release_readiness.rule_categories.'.($category ?: 'core')) }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.release_readiness.rule_category_copy') }}</p>
                </div>
                <span class="badge badge-soft-primary">{{ $rules->count() }}</span>
            </div>
            <div class="card-body p-0"><div class="table-responsive"><table data-tables="release-readiness-rules-{{ $category }}" data-aptoria-search="false" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table"><thead><tr><th>{{ __('messages.release_readiness.rule') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.release_readiness.failure_level') }}</th><th>{{ __('messages.release_readiness.default_level') }}</th></tr></thead><tbody>
                @foreach($rules as $rule)
                    <tr><td><div class="d-flex align-items-center gap-2 min-w-0"><span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="{{ $rule->icon }}"></i></span></span><div class="min-w-0"><span class="fw-medium d-block text-truncate">{{ $rule->rule_label }}</span><small class="text-muted d-block text-truncate">{{ $rule->rule_hint }}</small></div></div></td><td><div class="form-check form-switch mb-0"><input type="hidden" name="rules[{{ $rule->id }}][enabled]" value="0"><input class="form-check-input" type="checkbox" role="switch" name="rules[{{ $rule->id }}][enabled]" value="1" @checked($rule->enabled)><label class="form-check-label small text-muted">{{ $rule->enabled ? __('messages.common.enabled') : __('messages.common.disabled') }}</label></div></td><td><select name="rules[{{ $rule->id }}][failure_level]" class="form-select form-select-sm">@foreach(\App\Models\ReleaseReadinessRule::FAILURE_LEVELS as $level)<option value="{{ $level }}" @selected($rule->failure_level === $level)>{{ __('messages.release_readiness.levels.'.$level) }}</option>@endforeach</select></td><td><span class="badge badge-soft-{{ $rule->default_failure_level === 'blocker' ? 'danger' : 'warning' }} badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.levels.'.$rule->default_failure_level) }}</span></td></tr>
                @endforeach
            </tbody></table></div></div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_readiness.rule_table_footer') }}</div>
        </div>
    @endforeach
</form>
@endsection
