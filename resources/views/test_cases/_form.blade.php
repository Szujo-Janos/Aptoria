@csrf

@if($project->testSuites->isEmpty())
    <div class="alert alert-warning">
        {{ __('messages.test_cases.no_suite_warning') }}
        <a href="{{ route('projects.test-suites.create', $project) }}" class="alert-link">{{ __('messages.test_suites.create') }}</a>
    </div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="test_suite_id">{{ __('messages.test_suites.single') }}</label>
            <select name="test_suite_id" id="test_suite_id" class="form-control" required @disabled($project->testSuites->isEmpty())>
                <option value="">{{ __('messages.common.select') }}</option>
                @foreach($project->testSuites as $suite)
                    <option value="{{ $suite->id }}" @selected((string) old('test_suite_id', $testCase->test_suite_id) === (string) $suite->id)>{{ $suite->name }}</option>
                @endforeach
            </select>
            @error('test_suite_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="endpoint_id">{{ __('messages.endpoints.title') }}</label>
            <select name="endpoint_id" id="endpoint_id" class="form-control">
                <option value="">{{ __('messages.test_cases.no_endpoint_link') }}</option>
                @foreach($project->endpoints as $endpoint)
                    <option value="{{ $endpoint->id }}" @selected((string) old('endpoint_id', $testCase->endpoint_id) === (string) $endpoint->id)>{{ $endpoint->method }} {{ $endpoint->path }}</option>
                @endforeach
            </select>
            @error('endpoint_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="title">{{ __('messages.test_cases.title_field') }}</label>
    <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $testCase->title) }}" required maxlength="220" placeholder="{{ __('messages.test_cases.title_placeholder') }}">
    @error('title')<span class="text-danger">{{ $message }}</span>@enderror
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="type">{{ __('messages.common.type') }}</label>
            <select name="type" id="type" class="form-control" required>
                @foreach(\App\Models\TestCase::TYPES as $type)
                    <option value="{{ $type }}" @selected(old('type', $testCase->type) === $type)>{{ __('messages.test_cases.types.'.$type) }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="priority">{{ __('messages.test_cases.priority') }}</label>
            <select name="priority" id="priority" class="form-control" required>
                @foreach(\App\Models\TestCase::PRIORITIES as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $testCase->priority) === $priority)>{{ __('messages.test_cases.priorities.'.$priority) }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="status">{{ __('messages.common.status') }}</label>
            <select name="status" id="status" class="form-control" required>
                @foreach(\App\Models\TestCase::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $testCase->status) === $status)>{{ __('messages.test_cases.statuses.'.$status) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="form-group">
    <label for="description">{{ __('messages.common.description') }}</label>
    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $testCase->description) }}</textarea>
</div>

<div class="form-group">
    <label for="preconditions">{{ __('messages.test_cases.preconditions') }}</label>
    <textarea name="preconditions" id="preconditions" class="form-control" rows="3" placeholder="{{ __('messages.test_cases.preconditions_placeholder') }}">{{ old('preconditions', $testCase->preconditions) }}</textarea>
</div>

<div class="form-group">
    <label for="steps">{{ __('messages.test_cases.steps') }}</label>
    <textarea name="steps" id="steps" class="form-control" rows="7" required placeholder="{{ __('messages.test_cases.steps_placeholder') }}">{{ old('steps', $testCase->steps) }}</textarea>
</div>

<div class="form-group">
    <label for="expected_result">{{ __('messages.test_cases.expected_result') }}</label>
    <textarea name="expected_result" id="expected_result" class="form-control" rows="4" required placeholder="{{ __('messages.test_cases.expected_result_placeholder') }}">{{ old('expected_result', $testCase->expected_result) }}</textarea>
</div>

<div class="form-group">
    <label for="actual_result">{{ __('messages.test_cases.actual_result') }}</label>
    <textarea name="actual_result" id="actual_result" class="form-control" rows="4">{{ old('actual_result', $testCase->actual_result) }}</textarea>
</div>

<button type="submit" class="btn btn-success" @disabled($project->testSuites->isEmpty())>{{ __('messages.common.save') }}</button>
<a href="{{ route('projects.test-cases.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
