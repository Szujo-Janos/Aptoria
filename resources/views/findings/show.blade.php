@extends('layouts.app')

@section('title', $finding->title)

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.findings.edit', [$project, $finding]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                    <a href="{{ route('projects.findings.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.findings.detail_title') }}
            </div>
            <div class="panel-body">
                <h2 class="m-t-none">{{ $finding->title }}</h2>
                <p class="text-muted">{{ $finding->description ?: __('messages.common.not_available') }}</p>

                <div class="row m-b-md">
                    <div class="col-sm-3"><strong>{{ __('messages.findings.severity') }}</strong><br><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span></div>
                    <div class="col-sm-3"><strong>{{ __('messages.common.status') }}</strong><br><span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></div>
                    <div class="col-sm-3"><strong>{{ __('messages.findings.source') }}</strong><br>{{ $finding->source_label }}</div>
                    <div class="col-sm-3"><strong>{{ __('messages.findings.detected_at') }}</strong><br>{{ $finding->detected_at?->format('Y-m-d H:i') ?: $finding->created_at->format('Y-m-d H:i') }}</div>
                </div>

                <div class="row m-b-md">
                    <div class="col-sm-6">
                        <strong>{{ __('messages.endpoints.title') }}</strong><br>
                        @if($finding->endpoint)
                            <a href="{{ route('projects.endpoints.show', [$project, $finding->endpoint]) }}"><code>{{ $finding->endpoint->method }} {{ $finding->endpoint->path }}</code></a>
                        @else
                            <span class="text-muted">{{ __('messages.common.none') }}</span>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <strong>{{ __('messages.test_cases.title') }}</strong><br>
                        @if($finding->testCase)
                            <a href="{{ route('projects.test-cases.show', [$project, $finding->testCase]) }}">{{ $finding->testCase->title }}</a>
                        @else
                            <span class="text-muted">{{ __('messages.common.none') }}</span>
                        @endif
                    </div>
                </div>

                <hr>
                <h4>{{ __('messages.findings.reproduction_steps') }}</h4>
                <div class="well well-sm">{!! nl2br(e($finding->reproduction_steps ?: __('messages.common.not_available'))) !!}</div>

                <div class="row">
                    <div class="col-sm-6">
                        <h4>{{ __('messages.test_cases.expected_result') }}</h4>
                        <div class="well well-sm">{!! nl2br(e($finding->expected_result ?: __('messages.common.not_available'))) !!}</div>
                    </div>
                    <div class="col-sm-6">
                        <h4>{{ __('messages.test_cases.actual_result') }}</h4>
                        <div class="well well-sm">{!! nl2br(e($finding->actual_result ?: __('messages.common.not_available'))) !!}</div>
                    </div>
                </div>

                <h4>{{ __('messages.findings.recommendation') }}</h4>
                <div class="well well-sm">{!! nl2br(e($finding->recommendation ?: __('messages.common.not_available'))) !!}</div>
            </div>
            <div class="panel-footer">
                <form method="POST" action="{{ route('projects.findings.destroy', [$project, $finding]) }}" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.findings.confirm_delete') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('messages.common.delete') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.findings.linked_evidence') }}</div>
            <div class="panel-body">
                <p><strong>{{ __('messages.scans.title') }}:</strong>
                    @if($finding->scanRun)
                        <a href="{{ route('projects.scans.show', [$project, $finding->scanRun]) }}">#{{ $finding->scanRun->id }}</a>
                    @else
                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                    @endif
                </p>
                <p><strong>{{ __('messages.findings.scan_result') }}:</strong>
                    @if($finding->scanResult && $finding->scanResult->scanRun)
                        <a href="{{ route('projects.scans.show', [$project, $finding->scanResult->scanRun]) }}">#{{ $finding->scanResult->id }}</a>
                    @else
                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                    @endif
                </p>
                <p><strong>{{ __('messages.findings.contract_result') }}:</strong>
                    @if($finding->contractValidationResult && $finding->contractValidationResult->run)
                        <a href="{{ route('projects.contract-validations.show', [$project, $finding->contractValidationResult->run]) }}">#{{ $finding->contractValidationResult->id }}</a>
                    @else
                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                    @endif
                </p>
                @if($finding->resolved_at)
                    <p><strong>{{ __('messages.findings.resolved_at') }}:</strong> {{ $finding->resolved_at->format('Y-m-d H:i') }}</p>
                @endif
            </div>
        </div>

        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.findings.add_evidence') }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.findings.evidence.store', [$project, $finding]) }}">
                    @csrf
                    <div class="form-group">
                        <label for="type">{{ __('messages.common.type') }}</label>
                        <select name="type" id="type" class="form-control" required>
                            @foreach(\App\Models\FindingEvidence::TYPES as $type)
                                <option value="{{ $type }}">{{ __('messages.findings.evidence_types.'.$type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="source_label">{{ __('messages.findings.source_label') }}</label>
                        <input type="text" name="source_label" id="source_label" class="form-control" maxlength="160" placeholder="{{ __('messages.findings.source_label_placeholder') }}">
                    </div>
                    <div class="form-group">
                        <label for="url">{{ __('messages.findings.url') }}</label>
                        <input type="url" name="url" id="url" class="form-control" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label for="content">{{ __('messages.findings.evidence_content') }}</label>
                        <textarea name="content" id="content" class="form-control" rows="5"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">{{ __('messages.findings.add_evidence') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.findings.evidence') }}</div>
            <div class="panel-body">
                @if($finding->evidence->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.findings.no_evidence') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.common.type') }}</th>
                                    <th>{{ __('messages.findings.source_label') }}</th>
                                    <th>{{ __('messages.findings.evidence_content') }}</th>
                                    <th>{{ __('messages.findings.url') }}</th>
                                    <th>{{ __('messages.common.created') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($finding->evidence as $evidence)
                                <tr>
                                    <td>{{ $evidence->type_label }}</td>
                                    <td>{{ $evidence->source_label ?: __('messages.common.not_available') }}</td>
                                    <td>{!! nl2br(e(\Illuminate\Support\Str::limit($evidence->content ?: __('messages.common.not_available'), 400))) !!}</td>
                                    <td>@if($evidence->url)<a href="{{ $evidence->url }}" target="_blank" rel="noopener">{{ __('messages.findings.open_evidence_link') }}</a>@else<span class="text-muted">{{ __('messages.common.none') }}</span>@endif</td>
                                    <td>{{ $evidence->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('projects.findings.evidence.destroy', [$project, $finding, $evidence]) }}" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.findings.confirm_delete_evidence') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">{{ __('messages.common.delete') }}</button>
                                        </form>
                                    </td>
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
@endsection
