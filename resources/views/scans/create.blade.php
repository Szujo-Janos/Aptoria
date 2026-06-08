@extends('layouts.app')

@section('title', __('messages.scans.new'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.scans.safe_probe_engine') }}</div>
            <div class="panel-body">
                <h3 class="m-t-none">{{ __('messages.scans.new') }}</h3>
                <p class="text-muted">{{ __('messages.scans.create_intro') }}</p>

                <form method="POST" action="{{ route('projects.scans.store', $project) }}" data-aptoria-scan-form="true" data-aptoria-requires-confirm="{{ $requireConfirmation ? 'true' : 'false' }}">
                    @csrf
                    <div class="form-group">
                        <label for="environment_id">{{ __('messages.environments.title') }}</label>
                        <select class="form-control" id="environment_id" name="environment_id">
                            <option value="">{{ __('messages.scans.use_endpoint_or_project_base_url') }}</option>
                            @foreach($project->environments as $environment)
                                <option value="{{ $environment->id }}" @selected((string) old('environment_id', $defaultEnvironmentId ?? '') === (string) $environment->id)>{{ $environment->name }} — {{ $environment->base_url }}{{ $environment->is_production ? ' ['.__('messages.environments.production').']' : '' }}</option>
                            @endforeach
                        </select>
                        <span class="help-block">{{ __('messages.scans.environment_help') }}</span>
                    </div>

                    <div class="form-group">
                        <label for="scan_profile">Scan profile</label>
                        <select class="form-control" id="scan_profile" name="scan_profile">
                            @foreach($scanProfiles as $profileKey => $profile)
                                <option value="{{ $profileKey }}" @selected(old('scan_profile', $defaultScanProfile) === $profileKey)>
                                    {{ $profile['label'] ?? $profileKey }} — {{ $profile['mode'] ?? 'safe' }} · {{ $profile['max_endpoints'] ?? 100 }} endpoints · {{ $profile['rate_limit_ms'] ?? 250 }}ms delay
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block">Profiles apply the saved Settings Center limits to this scan without enabling destructive HTTP methods.</span>
                    </div>

                    <div class="well">
                        <strong>{{ __('messages.scans.safety_guards') }}</strong>
                        <ul class="m-t-sm m-b-none">
                            <li>{{ __('messages.scans.guard_get_head_only') }}</li>
                            <li>{{ __('messages.scans.guard_no_body') }}</li>
                            <li>{{ __('messages.scans.guard_private_network') }}</li>
                            <li>{{ __('messages.scans.guard_timeout') }}</li>
                        </ul>
                    </div>

                    @if($requireConfirmation)
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="confirm_safe_scan" value="1" required @checked(old('confirm_safe_scan'))>
                                {{ __('messages.scans.confirm_safe_scan') }}
                            </label>
                        </div>
                    @else
                        <div class="alert alert-info">{{ __('messages.settings.confirmation_disabled_notice') }}</div>
                    @endif

                    @if($requireProductionConfirmation)
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="confirm_production_scan" value="1" @checked(old('confirm_production_scan'))>
                                {{ __('messages.scans.confirm_production_scan') }}
                            </label>
                            <span class="help-block">{{ __('messages.scans.confirm_production_scan_help') }}</span>
                        </div>
                        @if($requireTypedProductionConfirmation ?? true)
                            <div class="form-group">
                                <label for="production_confirmation_phrase">Production typed confirmation</label>
                                <input id="production_confirmation_phrase" name="production_confirmation_phrase" class="form-control" value="{{ old('production_confirmation_phrase') }}" placeholder="{{ $productionConfirmationPhrase ?? 'SCAN PRODUCTION' }}">
                                <span class="help-block">Required only when the selected environment is marked as production.</span>
                            </div>
                        @endif
                    @endif

                    <button type="submit" class="btn btn-success" data-aptoria-submit-label="{{ __('messages.scans.scanning') }}">{{ __('messages.scans.run_scan') }}</button>
                    <a href="{{ route('projects.scans.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.scans.scope') }}</div>
            <div class="panel-body">
                <dl>
                    <dt>{{ __('messages.projects.title') }}</dt><dd>{{ $project->name }}</dd>
                    <dt>{{ __('messages.nav.endpoints') }}</dt><dd>{{ $project->endpoints->count() }}</dd>
                    <dt>{{ __('messages.scans.probeable') }}</dt><dd>{{ $project->endpoints->filter->isProbeable()->count() }}</dd>
                </dl>
                @isset($projectScanSettings)
                    <hr>
                    <dl>
                        <dt>{{ __('messages.project_settings.fields.max_endpoints_per_scan') }}</dt>
                        <dd>{{ $projectScanSettings['scan_defaults']['scan.max_endpoints_per_scan']['value'] ?? 100 }}</dd>
                        <dt>{{ __('messages.project_settings.fields.allow_private_networks') }}</dt>
                        <dd>{{ ($projectScanSettings['scan_safety']['scan.allow_private_networks']['value'] ?? false) ? __('messages.common.yes') : __('messages.common.no') }}</dd>
                        <dt>{{ __('messages.project_settings.fields.store_response_body_preview') }}</dt>
                        <dd>{{ ($projectScanSettings['data_retention']['scan.store_response_body_preview']['value'] ?? true) ? __('messages.common.yes') : __('messages.common.no') }}</dd>
                    </dl>
                @endisset
                <p class="m-b-none text-muted">{{ __('messages.scans.create_note') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
