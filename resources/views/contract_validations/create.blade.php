@extends('layouts.app')

@section('title', __('messages.contract_validations.new'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.contract-validations.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.contract_validations.view_all') }}</a>
                </div>
                {{ __('messages.contract_validations.new') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.contract-validations.store', $project) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.contract_validations.source_name') }}</label>
                                <input type="text" name="source_name" value="{{ old('source_name') }}" class="form-control" placeholder="openapi.yaml / swagger.json / release candidate contract">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.contract_validations.scan_run') }}</label>
                                <select name="scan_run_id" class="form-control">
                                    <option value="">{{ __('messages.contract_validations.use_latest_scan') }}</option>
                                    @foreach($scanRuns as $scanRun)
                                        <option value="{{ $scanRun->id }}" @selected((string) old('scan_run_id') === (string) $scanRun->id)>
                                            #{{ $scanRun->id }} — {{ $scanRun->started_at?->format('Y-m-d H:i') ?: $scanRun->created_at->format('Y-m-d H:i') }} — {{ $scanRun->environment?->name ?: __('messages.endpoints.project_default') }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="help-block">{{ __('messages.contract_validations.scan_run_help') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.contract_validations.contract_payload') }}</label>
                        <textarea name="contract_payload" rows="22" class="form-control code-block" required placeholder="openapi: 3.0.0&#10;paths:&#10;  /users:&#10;    get:&#10;      responses:&#10;        '200':&#10;          content:&#10;            application/json:&#10;              schema:&#10;                type: object">{{ old('contract_payload') }}</textarea>
                        <span class="help-block">{{ __('messages.contract_validations.contract_payload_help') }}</span>
                    </div>
                    <button class="btn btn-success" type="submit">{{ __('messages.contract_validations.run_validation') }}</button>
                    <a href="{{ route('projects.contract-validations.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
