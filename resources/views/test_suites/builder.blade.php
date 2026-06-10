@extends('layouts.app')

@section('title', __('messages.regression_builder.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-suites.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.regression_builder.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.regression_builder.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('projects.test-suites.builder.store', $project) }}">
    @csrf
    <div class="row">
        <div class="col-lg-4">
            <div class="hpanel hgreen">
                <div class="panel-heading hbuilt">{{ __('messages.regression_builder.suite_setup') }}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="name">{{ __('messages.regression_builder.suite_name') }}</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $defaultName) }}" required maxlength="180">
                        @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="description">{{ __('messages.common.description') }}</label>
                        <textarea name="description" id="description" class="form-control" rows="4" placeholder="{{ __('messages.regression_builder.description_placeholder') }}">{{ old('description') }}</textarea>
                        @error('description')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="priority">{{ __('messages.test_cases.priority') }}</label>
                        <select name="priority" id="priority" class="form-control">
                            @foreach(\App\Models\TestCase::PRIORITIES as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', \App\Models\TestCase::PRIORITY_HIGH) === $priority)>{{ __('messages.test_cases.priorities.'.$priority) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="expected_status">{{ __('messages.regression_builder.default_expected_status') }}</label>
                        <input type="number" min="100" max="599" name="expected_status" id="expected_status" class="form-control" value="{{ old('expected_status', 200) }}">
                        <small class="text-muted">{{ __('messages.regression_builder.default_expected_status_help') }}</small>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="hidden" name="include_status_assertions" value="0">
                            <input type="checkbox" name="include_status_assertions" value="1" checked>
                            {{ __('messages.regression_builder.include_status_assertions') }}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="hidden" name="include_json_path_assertions" value="0">
                            <input type="checkbox" name="include_json_path_assertions" value="1" @checked(old('include_json_path_assertions'))>
                            {{ __('messages.regression_builder.include_json_path_assertions') }}
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="required_json_paths">{{ __('messages.regression_builder.required_json_paths') }}</label>
                        <textarea name="required_json_paths" id="required_json_paths" class="form-control" rows="4" placeholder="{{ __('messages.regression_builder.required_json_paths_placeholder') }}">{{ old('required_json_paths') }}</textarea>
                        <small class="text-muted">{{ __('messages.regression_builder.required_json_paths_help') }}</small>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fa fa-plus-circle"></i> {{ __('messages.regression_builder.create_suite') }}</button>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="hpanel hblue">
                <div class="panel-heading hbuilt">{{ __('messages.regression_builder.endpoint_selection') }}</div>
                <div class="panel-body">
                    @error('endpoint_ids')<div class="alert alert-danger">{{ $message }}</div>@enderror
                    @if($endpoints->isEmpty())
                        <div class="text-center p-xl">
                            <h4>{{ __('messages.regression_builder.no_endpoints_title') }}</h4>
                            <p class="text-muted">{{ __('messages.regression_builder.no_endpoints_help') }}</p>
                            <a href="{{ route('projects.endpoints.import.form', $project) }}" class="btn btn-primary">{{ __('messages.endpoints.import_title') }}</a>
                        </div>
                    @else
                        <p class="text-muted">{{ __('messages.regression_builder.endpoint_selection_help') }}</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width:40px;"><input type="checkbox" onclick="document.querySelectorAll('.aptoria-builder-endpoint').forEach(cb => cb.checked = this.checked)"></th>
                                        <th>{{ __('messages.endpoints.method') }}</th>
                                        <th>{{ __('messages.endpoints.path') }}</th>
                                        <th>{{ __('messages.environments.title') }}</th>
                                        <th>{{ __('messages.common.status') }}</th>
                                        <th>{{ __('messages.endpoints.expected_status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($endpoints as $endpoint)
                                    <tr>
                                        <td><input type="checkbox" class="aptoria-builder-endpoint" name="endpoint_ids[]" value="{{ $endpoint->id }}" @checked(in_array((string) $endpoint->id, old('endpoint_ids', []), true) || ($endpoint->isProbeable() && old('endpoint_ids') === null))></td>
                                        <td><span class="label label-default">{{ $endpoint->method }}</span></td>
                                        <td>
                                            <code>{{ $endpoint->path }}</code>
                                            <br><small class="text-muted">{{ $endpoint->name ?: __('messages.common.not_available') }}</small>
                                        </td>
                                        <td>{{ $endpoint->environment?->name ?: __('messages.common.default') }}</td>
                                        <td>
                                            @if($endpoint->isProbeable())
                                                <span class="label label-success">{{ __('messages.regression_builder.probeable') }}</span>
                                            @else
                                                <span class="label label-warning">{{ __('messages.regression_builder.manual_or_blocked') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $endpoint->expected_status ?: __('messages.common.not_available') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
