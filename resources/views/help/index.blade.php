@extends('layouts.app')

@section('title', __('messages.help.title'))

@section('content')
<div class="normalheader small-header">
    <div class="hpanel">
        <div class="panel-body">
            <div class="pull-right">
                <a href="{{ route('how-it-works') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-play-circle"></i> {{ __('messages.how_it_works.title') }}
                </a>
            </div>
            <h2 class="font-light m-b-xs">{{ __('messages.help.title') }}</h2>
            <small>{{ __('messages.help.subtitle') }}</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <i class="fa fa-search"></i> {{ __('messages.help.search_title') }}
            </div>
            <div class="panel-body">
                <form method="GET" action="{{ route('help.index') }}">
                    <div class="form-group">
                        <label for="help-search-input">{{ __('messages.help.search_label') }}</label>
                        <input
                            type="search"
                            id="help-search-input"
                            name="q"
                            value="{{ $query }}"
                            class="form-control"
                            placeholder="{{ __('messages.help.search_placeholder') }}"
                            data-aptoria-help-search="true"
                            data-aptoria-no-results="{{ __('messages.help.no_live_results') }}"
                        >
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">{{ __('messages.help.search_button') }}</button>
                    @if($query !== '')
                        <a href="{{ route('help.index') }}" class="btn btn-default btn-block">{{ __('messages.help.clear_search') }}</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.help.quick_nav') }}</div>
            <div class="list-group aptoria-doc-nav">
                @foreach($allSections as $section)
                    <a class="list-group-item" href="#help-{{ $section['id'] }}">{{ $section['title'] }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        @if($query !== '')
            <div class="alert alert-info">
                {{ __('messages.help.search_results_for', ['query' => $query, 'count' => count($sections)]) }}
            </div>
        @endif

        <div id="aptoria-help-no-results" class="alert alert-warning hidden">
            {{ __('messages.help.no_live_results') }}
        </div>

        @forelse($sections as $section)
            <div class="hpanel aptoria-doc-section" id="help-{{ $section['id'] }}" data-aptoria-help-section="true" data-search-text="{{ strtolower($section['title'].' '.$section['summary'].' '.$section['keywords']) }} @foreach($section['items'] as $item) {{ strtolower(($item['title'] ?? '').' '.($item['body'] ?? '')) }} @endforeach">
                <div class="panel-heading hbuilt">
                    <span class="label label-success pull-right">{{ $section['keywords'] }}</span>
                    <i class="fa fa-book"></i> {{ $section['title'] }}
                </div>
                <div class="panel-body">
                    <p class="lead">{{ $section['summary'] }}</p>
                    <div class="row">
                        @foreach($section['items'] as $item)
                            <div class="col-md-6">
                                <div class="aptoria-doc-card">
                                    <h4>{{ $item['title'] }}</h4>
                                    <p>{{ $item['body'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="hpanel">
                <div class="panel-body text-center">
                    <i class="fa fa-search fa-3x text-muted"></i>
                    <h3>{{ __('messages.help.no_results_title') }}</h3>
                    <p>{{ __('messages.help.no_results_body') }}</p>
                    <a href="{{ route('help.index') }}" class="btn btn-primary">{{ __('messages.help.clear_search') }}</a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
