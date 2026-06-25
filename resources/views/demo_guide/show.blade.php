@extends($layout)

@section('title', __('messages.demo_guide.title') . ' · Aptoria')
@section('page_title', __('messages.demo_guide.title'))
@section('body_class', 'auth-bg min-vh-100 py-4')

@section('page_actions')
    @if (!$publicMode && $project)
        <a href="{{ route('projects.qa-cockpit.show', $project) }}" class="btn btn-primary"><i data-lucide="scan-search" class="me-1"></i>{{ __('messages.nav.qa_cockpit') }}</a>
        <a href="{{ route('projects.safe-scans.index', $project) }}" class="btn btn-light"><i data-lucide="radar" class="me-1"></i>{{ __('messages.nav.safe_scan') }}</a>
    @endif
@endsection

@section('content')
<div class="{{ $publicMode ? 'row justify-content-center' : '' }}">
    <div class="{{ $publicMode ? 'col-xxl-10 col-xl-11' : 'col-12' }}">
        @if ($publicMode)
            <div class="text-center mb-4">
                <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo aptoria-auth-logo mx-auto mb-3">
                <span class="badge badge-soft-primary badge-label"><i data-lucide="server-cog" class="me-1"></i>{{ __('messages.demo_guide.public_badge') }}</span>
                <h1 class="mt-3 mb-2">{{ __('messages.demo_guide.headline') }}</h1>
                <p class="text-muted mb-0">{{ __('messages.demo_guide.copy') }}</p>
                <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                    <a href="{{ route('login') }}" class="btn btn-primary"><i data-lucide="log-in" class="me-1"></i>{{ __('messages.auth.sign_in') }}</a>
                    <a href="{{ route('demo-api.health') }}" target="_blank" class="btn btn-light"><i data-lucide="braces" class="me-1"></i>{{ __('messages.product.try_live_api') }}</a>
                    <a href="{{ route('landing') }}" class="btn btn-outline-light"><i data-lucide="home" class="me-1"></i>Aptoria</a>
                </div>
            </div>
        @endif

        @if (config('aptoria.demo.mode'))
            <div class="alert alert-info d-flex gap-2 align-items-start">
                <i data-lucide="shield-check" class="mt-1"></i>
                <div>
                    <strong>{{ __('messages.demo_guide.demo_mode_enabled') }}</strong>
                    <div class="small mt-1">{{ __('messages.demo_guide.demo_mode_copy') }}</div>
                </div>
            </div>
        @endif

        @php
            $guideRoute = $publicMode ? route('demo-guide.public') : route('projects.demo-guide.show', $project);
        @endphp

        @if ($selectedScenario)
            <div class="row g-3 mb-3">
                <div class="col-xl-5">
                    <div class="card aptoria-panel-card h-100">
                        <div class="card-header border-light">
                            <div>
                                <h5 class="card-title mb-1"><i data-lucide="list-checks" class="me-1"></i>{{ __('messages.demo_guide.scenario_templates_title') }}</h5>
                                <p class="text-muted small mb-0">{{ __('messages.demo_guide.scenario_templates_copy') }}</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="vstack gap-2">
                                @foreach ($scenarios as $scenario)
                                    <a href="{{ $guideRoute }}?scenario={{ $scenario['slug'] }}" class="border rounded p-3 text-decoration-none text-body d-block {{ $selectedScenario['slug'] === $scenario['slug'] ? 'border-primary bg-primary-subtle' : '' }}">
                                        <div class="d-flex align-items-start gap-3">
                                            <span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="{{ $scenario['icon'] }}"></i></span></span>
                                            <span class="min-w-0">
                                                <span class="d-flex gap-2 align-items-center flex-wrap">
                                                    <strong>{{ $scenario['title'] }}</strong>
                                                    <span class="badge badge-soft-{{ $scenario['tone'] }} badge-label">{{ $scenario['badge'] }}</span>
                                                </span>
                                                <span class="d-block text-muted small mt-1">{{ $scenario['summary'] }}</span>
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="card aptoria-panel-card h-100">
                        <div class="card-header border-light justify-content-between align-items-start">
                            <div>
                                <span class="badge badge-soft-{{ $selectedScenario['tone'] }} badge-label mb-2"><i data-lucide="{{ $selectedScenario['icon'] }}" class="me-1"></i>{{ $selectedScenario['badge'] }}</span>
                                <h5 class="card-title mb-1">{{ $selectedScenario['title'] }}</h5>
                                <p class="text-muted small mb-0">{{ $selectedScenario['objective'] }}</p>
                            </div>
                            <span class="badge badge-soft-secondary badge-label"><i data-lucide="clock-3" class="me-1"></i>{{ $selectedScenario['duration'] }}</span>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-light border d-flex gap-2 align-items-start">
                                <i data-lucide="flag" class="mt-1"></i>
                                <div>
                                    <strong>{{ __('messages.demo_guide.expected_result') }}</strong>
                                    <div class="small text-muted mt-1">{{ $selectedScenario['expected_result'] }}</div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-lg-7">
                                    <h6 class="mb-2"><i data-lucide="list-checks" class="me-1"></i>{{ __('messages.demo_guide.guided_steps_title') }}</h6>
                                    <div class="vstack gap-2">
                                        @foreach ($selectedScenario['steps'] as $index => $step)
                                            @php($actionUrl = $scenarioActions[$step['action']] ?? null)
                                            <div class="border rounded p-3">
                                                <div class="d-flex gap-3 align-items-start">
                                                    <span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title">{{ $index + 1 }}</span></span>
                                                    <div class="min-w-0 flex-grow-1">
                                                        <h6 class="mb-1"><i data-lucide="{{ $step['icon'] }}" class="me-1"></i>{{ $step['title'] }}</h6>
                                                        <p class="text-muted small mb-2">{{ $step['copy'] }}</p>
                                                        @if ($actionUrl)
                                                            <a href="{{ $actionUrl }}" class="small fw-semibold">{{ __('messages.demo_guide.open_step') }} <i data-lucide="arrow-up-right" class="ms-1"></i></a>
                                                        @else
                                                            <span class="small text-muted">{{ __('messages.demo_guide.login_to_open') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <h6 class="mb-2"><i data-lucide="plug-connected" class="me-1"></i>{{ __('messages.demo_guide.scenario_endpoints') }}</h6>
                                    <div class="vstack gap-2 mb-3">
                                        @foreach ($selectedScenario['endpoints'] as $path)
                                            <a href="{{ $baseUrl.$path }}" target="_blank" class="border rounded p-2 small text-decoration-none text-body d-flex justify-content-between gap-2">
                                                <code class="text-break">{{ $path }}</code>
                                                <i data-lucide="external-link" class="text-muted"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                    <h6 class="mb-2"><i data-lucide="file-json" class="me-1"></i>{{ __('messages.demo_guide.scenario_artifacts') }}</h6>
                                    <div class="d-flex gap-1 flex-wrap mb-3">
                                        @foreach ($selectedScenario['artifacts'] as $artifact)
                                            <span class="badge badge-soft-primary badge-label">{{ $artifact }}</span>
                                        @endforeach
                                    </div>
                                    <a href="{{ route('demo-api.scenarios.evidence', $selectedScenario['slug']) }}" target="_blank" class="btn btn-sm btn-light w-100"><i data-lucide="file-json" class="me-1"></i>{{ __('messages.demo_guide.open_scenario_evidence') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card card-h-100 aptoria-panel-card">
                    <div class="card-header border-light">
                        <div>
                            <h5 class="card-title mb-1"><i data-lucide="id-card" class="me-1"></i>{{ __('messages.demo_guide.credentials_title') }}</h5>
                            <p class="text-muted small mb-0">{{ __('messages.demo_guide.credentials_copy') }}</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="vstack gap-2">
                            <div>
                                <div class="small text-muted">{{ __('messages.auth.email') }}</div>
                                <code>{{ $demoUserEmail }}</code>
                            </div>
                            <div>
                                <div class="small text-muted">{{ __('messages.auth.password') }}</div>
                                <code>{{ $demoUserPassword }}</code>
                            </div>
                            <div>
                                <div class="small text-muted">{{ __('messages.projects.base_url') }}</div>
                                <code class="text-break">{{ $baseUrl }}</code>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer aptoria-card-footer-subtle text-center">
                        <a href="{{ route('login') }}" class="btn btn-sm btn-primary"><i data-lucide="log-in" class="me-1"></i>{{ __('messages.demo_guide.open_demo_login') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card card-h-100 aptoria-panel-card">
                    <div class="card-header border-light justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1"><i data-lucide="map" class="me-1"></i>{{ __('messages.demo_guide.workflow_title') }}</h5>
                            <p class="text-muted small mb-0">{{ __('messages.demo_guide.workflow_copy') }}</p>
                        </div>
                        @if (!$publicMode && $project)
                            <span class="badge badge-soft-success badge-label"><i data-lucide="folder-kanban" class="me-1"></i>{{ $project->name }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($workflow as $step)
                                <div class="col-lg-6">
                                    <div class="border rounded p-3 h-100 d-flex gap-3">
                                        <span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="{{ $step['icon'] }}"></i></span></span>
                                        <div class="min-w-0">
                                            <h6 class="mb-1">{{ $step['title'] }}</h6>
                                            <p class="text-muted small mb-2">{{ $step['copy'] }}</p>
                                            @if ($step['route'])
                                                <a href="{{ $step['route'] }}" class="small fw-semibold">{{ __('messages.common.open') }} <i data-lucide="arrow-up-right" class="ms-1"></i></a>
                                            @else
                                                <span class="small text-muted">{{ __('messages.demo_guide.login_to_open') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-xl-7">
                <div class="card aptoria-table-card aptoria-panel-card">
                    <div class="card-header border-light">
                        <div>
                            <h5 class="card-title mb-1"><i data-lucide="route" class="me-1"></i>{{ __('messages.demo_guide.endpoints_title') }}</h5>
                            <p class="text-muted small mb-0">{{ __('messages.demo_guide.endpoints_copy') }}</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0 w-100 aptoria-resource-table">
                                <thead class="thead-sm text-uppercase fs-xxs">
                                    <tr>
                                        <th>{{ __('messages.endpoints.method') }}</th>
                                        <th>{{ __('messages.endpoints.path') }}</th>
                                        <th>{{ __('messages.common.status') }}</th>
                                        <th>{{ __('messages.common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($endpoints as $endpoint)
                                        <tr>
                                            <td><span class="badge badge-soft-secondary badge-label">{{ $endpoint['method'] }}</span></td>
                                            <td><code>{{ $endpoint['path'] }}</code><div class="small text-muted">{{ $endpoint['purpose'] }}</div></td>
                                            <td><span class="badge badge-soft-{{ $endpoint['tone'] }} badge-label"><i data-lucide="activity" class="me-1"></i>{{ ucfirst($endpoint['tone']) }}</span></td>
                                            <td class="aptoria-actions-cell"><a href="{{ $baseUrl.$endpoint['path'] }}" target="_blank" class="btn btn-sm btn-light"><i data-lucide="external-link" class="me-1"></i>{{ __('messages.common.open') }}</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card aptoria-panel-card">
                    <div class="card-header border-light">
                        <div>
                            <h5 class="card-title mb-1"><i data-lucide="brackets-contain" class="me-1"></i>{{ __('messages.demo_guide.artifacts_title') }}</h5>
                            <p class="text-muted small mb-0">{{ __('messages.demo_guide.artifacts_copy') }}</p>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach ($artifacts as $artifact)
                            <a href="{{ $artifact['url'] }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i data-lucide="{{ $artifact['icon'] }}" class="me-2"></i>{{ $artifact['label'] }}</span>
                                <i data-lucide="download" class="text-muted"></i>
                            </a>
                        @endforeach
                    </div>
                    <div class="card-footer aptoria-card-footer-subtle text-center small text-muted">
                        {{ __('messages.demo_guide.artifacts_help') }}
                    </div>
                </div>

                <div class="card mt-3 aptoria-panel-card">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0"><i data-lucide="shield-alert" class="me-1"></i>{{ __('messages.demo_guide.guard_title') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">{{ __('messages.demo_guide.guard_copy') }}</p>
                        <div class="small text-muted">{{ __('messages.demo_guide.allowed_targets') }}</div>
                        @forelse ($allowedTargets as $target)
                            <span class="badge badge-soft-primary badge-label me-1"><i data-lucide="globe" class="me-1"></i>{{ $target }}</span>
                        @empty
                            <span class="badge badge-soft-warning badge-label"><i data-lucide="triangle-alert" class="me-1"></i>{{ __('messages.demo_guide.allowed_targets_empty') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
