@csrf

<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="name">{{ __('messages.common.name') }}</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $testSuite->name) }}" required maxlength="180" placeholder="{{ __('messages.test_suites.name_placeholder') }}">
            @error('name')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="status">{{ __('messages.common.status') }}</label>
            <select name="status" id="status" class="form-control" required>
                @foreach(\App\Models\TestSuite::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $testSuite->status) === $status)>{{ __('messages.test_suites.statuses.'.$status) }}</option>
                @endforeach
            </select>
            @error('status')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="description">{{ __('messages.common.description') }}</label>
    <textarea name="description" id="description" class="form-control" rows="5" placeholder="{{ __('messages.test_suites.description_placeholder') }}">{{ old('description', $testSuite->description) }}</textarea>
    @error('description')<span class="text-danger">{{ $message }}</span>@enderror
</div>

<button type="submit" class="btn btn-success">{{ __('messages.common.save') }}</button>
<a href="{{ route('projects.test-suites.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
