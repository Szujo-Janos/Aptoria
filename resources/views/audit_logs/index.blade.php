@extends('layouts.app')

@section('title', $project ? __('messages.audit_log.project_title') : __('messages.audit_log.title'))

@section('page_actions')
    <a href="{{ $project ? route('projects.audit-log.json', array_merge([$project], request()->query())) : route('audit-log.json', request()->query()) }}" class="btn btn-sm btn-default">
        <i class="fa fa-download"></i> {{ __('messages.audit_log.export_json') }}
    </a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-body aptoria-audit-hero">
                <div class="row">
                    <div class="col-md-7">
                        <span class="label label-info"><i class="fa fa-history"></i> {{ __('messages.audit_log.badge') }}</span>
                        <h2 class="m-t-sm m-b-xs">{{ $project ? __('messages.audit_log.project_heading', ['project' => $project->name]) : __('messages.audit_log.heading') }}</h2>
                        <p class="text-muted m-b-none">{{ __('messages.audit_log.intro') }}</p>
                    </div>
                    <div class="col-md-5">
                        <div class="row text-center aptoria-audit-summary">
                            <div class="col-xs-3">
                                <div class="aptoria-health-metric-card">
                                    <div class="aptoria-health-metric-value text-primary">{{ $summary['total'] ?? 0 }}</div>
                                    <small>{{ __('messages.audit_log.summary.total') }}</small>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="aptoria-health-metric-card">
                                    <div class="aptoria-health-metric-value text-success">{{ $summary['today'] ?? 0 }}</div>
                                    <small>{{ __('messages.audit_log.summary.today') }}</small>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="aptoria-health-metric-card">
                                    <div class="aptoria-health-metric-value text-warning">{{ $summary['warning'] ?? 0 }}</div>
                                    <small>{{ __('messages.audit_log.summary.warning') }}</small>
                                </div>
                            </div>
                            <div class="col-xs-3">
                                <div class="aptoria-health-metric-card">
                                    <div class="aptoria-health-metric-value text-danger">{{ $summary['critical'] ?? 0 }}</div>
                                    <small>{{ __('messages.audit_log.summary.critical') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <i class="fa fa-filter"></i> {{ __('messages.audit_log.filters') }}
            </div>
            <div class="panel-body">
                <form method="GET" class="row">
                    @unless($project)
                        <div class="col-md-2">
                            <label>{{ __('messages.audit_log.project') }}</label>
                            <select name="project_id" class="form-control input-sm">
                                <option value="">{{ __('messages.common.all') ?? 'All' }}</option>
                                @foreach($projects as $optionProject)
                                    <option value="{{ $optionProject->id }}" @selected((string)($filters['project_id'] ?? '') === (string)$optionProject->id)>{{ $optionProject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endunless
                    <div class="col-md-2">
                        <label>{{ __('messages.audit_log.user') }}</label>
                        <select name="user_id" class="form-control input-sm">
                            <option value="">{{ __('messages.common.all') ?? 'All' }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>{{ $user->name }} · {{ $user->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>{{ __('messages.audit_log.event_type') }}</label>
                        <select name="event_type" class="form-control input-sm">
                            <option value="">{{ __('messages.common.all') ?? 'All' }}</option>
                            @foreach(['auth', 'model', 'report', 'database', 'system', 'monitor'] as $eventType)
                                <option value="{{ $eventType }}" @selected(($filters['event_type'] ?? '') === $eventType)>{{ __('messages.audit_log.event_types.'.$eventType) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>{{ __('messages.audit_log.action') }}</label>
                        <select name="action" class="form-control input-sm">
                            <option value="">{{ __('messages.common.all') ?? 'All' }}</option>
                            @foreach(['login', 'logout', 'created', 'updated', 'deleted', 'generated', 'exported', 'imported', 'requested'] as $action)
                                <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ __('messages.audit_log.actions.'.$action) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>{{ __('messages.audit_log.severity') }}</label>
                        <select name="severity" class="form-control input-sm">
                            <option value="">{{ __('messages.common.all') ?? 'All' }}</option>
                            @foreach(['info', 'notice', 'warning', 'critical'] as $severity)
                                <option value="{{ $severity }}" @selected(($filters['severity'] ?? '') === $severity)>{{ __('messages.audit_log.severities.'.$severity) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>{{ __('messages.audit_log.search') }}</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control input-sm" placeholder="{{ __('messages.audit_log.search_placeholder') }}">
                    </div>
                    <div class="col-md-2 m-t-md">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> {{ __('messages.common.filter') }}</button>
                        <a href="{{ $project ? route('projects.audit-log.index', $project) : route('audit-log.index') }}" class="btn btn-default btn-sm">{{ __('messages.common.reset') ?? 'Reset' }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <i class="fa fa-list"></i> {{ __('messages.audit_log.timeline') }}
            </div>
            <div class="panel-body no-padding">
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-b-none">
                        <thead>
                            <tr>
                                <th>{{ __('messages.audit_log.occurred_at') }}</th>
                                <th>{{ __('messages.audit_log.event_type') }}</th>
                                <th>{{ __('messages.audit_log.action') }}</th>
                                <th>{{ __('messages.audit_log.subject') }}</th>
                                <th>{{ __('messages.audit_log.user') }}</th>
                                <th>{{ __('messages.audit_log.project') }}</th>
                                <th>{{ __('messages.audit_log.request') }}</th>
                                <th>{{ __('messages.common.details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td class="text-nowrap"><i class="fa fa-clock-o text-muted"></i> {{ optional($log->occurred_at)->format('Y-m-d H:i:s') }}</td>
                                    <td><span class="label label-default">{{ $log->event_type_label }}</span></td>
                                    <td><span class="label label-{{ $log->severity === 'critical' ? 'danger' : ($log->severity === 'warning' ? 'warning' : ($log->severity === 'notice' ? 'info' : 'success')) }}">{{ $log->action_label }}</span></td>
                                    <td>
                                        <strong>{{ $log->subject_name ?: $log->summary ?: '-' }}</strong><br>
                                        <small class="text-muted">{{ $log->subject_label ?: class_basename((string)$log->auditable_type) }} #{{ $log->auditable_id ?: 'n/a' }}</small>
                                    </td>
                                    <td>{{ $log->user?->name ?: __('messages.audit_log.system_user') }}</td>
                                    <td>{{ $log->project?->name ?: '-' }}</td>
                                    <td>
                                        <span class="text-muted">{{ $log->http_method ?: '-' }}</span><br>
                                        <small>{{ $log->route_name ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-default" data-toggle="collapse" data-target="#audit-log-{{ $log->id }}">
                                            <i class="fa fa-eye"></i> {{ __('messages.common.details') }}
                                        </button>
                                    </td>
                                </tr>
                                <tr id="audit-log-{{ $log->id }}" class="collapse">
                                    <td colspan="8">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h5>{{ __('messages.audit_log.summary_label') }}</h5>
                                                <p>{{ $log->summary ?: '-' }}</p>
                                                <p class="small text-muted m-b-none">{{ $log->url }}</p>
                                                <p class="small text-muted">{{ $log->ip_address }} · {{ \Illuminate\Support\Str::limit((string)$log->user_agent, 140) }}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <h5>{{ __('messages.audit_log.before_after') }}</h5>
                                                <pre class="small m-b-sm">{{ json_encode(['before' => $log->before_values, 'after' => $log->after_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                            <div class="col-md-4">
                                                <h5>{{ __('messages.audit_log.metadata') }}</h5>
                                                <pre class="small m-b-none">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted p-lg">{{ __('messages.audit_log.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($logs->hasPages())
                <div class="panel-footer text-center">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
