@extends('layouts.app')

@section('title', __('messages.newman_import.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-execution.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.newman_import.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <div class="alert alert-info">{{ __('messages.newman_import.help') }}</div>
                <form method="POST" action="{{ route('projects.newman-import.preview', $project) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="format">{{ __('messages.newman_import.format') }}</label>
                                <select name="format" id="format" class="form-control" required>
                                    <option value="json" @selected(old('format') === 'json')>{{ __('messages.newman_import.format_json') }}</option>
                                    <option value="junit" @selected(old('format') === 'junit')>{{ __('messages.newman_import.format_junit') }}</option>
                                </select>
                            </div>
                            <div class="checkbox checkbox-info">
                                <label><input type="checkbox" name="create_findings" value="1" @checked(old('create_findings', '1'))> {{ __('messages.newman_import.create_findings') }}</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="payload">{{ __('messages.newman_import.payload') }}</label>
                                <textarea name="payload" id="payload" class="form-control code-input" rows="18">{{ old('payload') }}</textarea>
                                <span class="help-block">{{ __('messages.newman_import.payload_help') }}</span>
                                <button type="button" id="use-newman-json-sample" class="btn btn-xs btn-default">{{ __('messages.newman_import.use_json_sample') }}</button>
                                <button type="button" id="use-newman-junit-sample" class="btn btn-xs btn-default">{{ __('messages.newman_import.use_junit_sample') }}</button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-info">{{ __('messages.import_preview.preview_button') }}</button>
                    <a href="{{ route('projects.test-execution.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var payload = document.getElementById('payload');
    var format = document.getElementById('format');
    var jsonSample = @json($sampleJsonPayload);
    var junitSample = @json($sampleJUnitPayload);
    var jsonButton = document.getElementById('use-newman-json-sample');
    var junitButton = document.getElementById('use-newman-junit-sample');
    if (jsonButton && payload && format) {
        jsonButton.addEventListener('click', function () { format.value = 'json'; payload.value = jsonSample; payload.focus(); });
    }
    if (junitButton && payload && format) {
        junitButton.addEventListener('click', function () { format.value = 'junit'; payload.value = junitSample; payload.focus(); });
    }
})();
</script>
@endpush
