@php
    $title = __('messages.landing.meta.title');
    $description = __('messages.landing.meta.description');
    $canonical = url('/');
    $locale = str_replace('_', '-', app()->getLocale());
    $packages = __('messages.landing.packages.items');
    $features = __('messages.landing.features.items');
    $workflow = __('messages.landing.workflow.steps');
    $useCases = __('messages.landing.use_cases.items');
    $faq = __('messages.landing.faq.items');
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Aptoria',
        'applicationCategory' => 'DeveloperApplication',
        'operatingSystem' => 'Windows, Linux, macOS, self-hosted web server',
        'description' => $description,
        'url' => $canonical,
        'offers' => [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'EUR',
            'lowPrice' => '0',
            'highPrice' => '299',
            'offerCount' => is_array($packages) ? count($packages) : 4,
        ],
        'featureList' => is_array($features) ? array_values(array_map(fn ($item) => $item['title'] ?? '', $features)) : [],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="keywords" content="{{ __('messages.landing.meta.keywords') }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="icon" href="{{ asset('assets/aptoria/img/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/aptoria/img/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/aptoria/img/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:site_name" content="Aptoria">
    <meta property="og:image" content="{{ asset('assets/aptoria/img/android-chrome-512.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ asset('assets/aptoria/img/android-chrome-512.png') }}">
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/fontawesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/animate/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria/css/landing.css') }}?v={{ config('aptoria.version') }}">
</head>
<body class="aptoria-landing">
    <nav class="aptoria-landing-nav" aria-label="{{ __('messages.landing.nav.aria') }}">
        <div class="container">
            <div class="aptoria-landing-nav-inner">
                <a href="{{ route('landing') }}" class="aptoria-landing-brand" aria-label="Aptoria">
                    <img src="{{ asset('assets/aptoria/img/aptoria-logo-horizontal.png') }}" alt="Aptoria">
                </a>
                <div class="aptoria-landing-links hidden-xs hidden-sm">
                    <a href="#features">{{ __('messages.landing.nav.features') }}</a>
                    <a href="#workflow">{{ __('messages.landing.nav.workflow') }}</a>
                    <a href="#pricing">{{ __('messages.landing.nav.pricing') }}</a>
                    <a href="#faq">{{ __('messages.landing.nav.faq') }}</a>
                </div>
                <div class="aptoria-landing-actions">
                    <div class="btn-group aptoria-landing-lang">
                        <a href="{{ route('language.switch', 'en') }}" class="btn btn-xs {{ app()->getLocale() === 'en' ? 'btn-primary' : 'btn-default' }}">EN</a>
                        <a href="{{ route('language.switch', 'hu') }}" class="btn btn-xs {{ app()->getLocale() === 'hu' ? 'btn-primary' : 'btn-default' }}">HU</a>
                    </div>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">{{ __('messages.landing.nav.open_app') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary btn-sm">{{ __('messages.landing.nav.sign_in') }}</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <header class="aptoria-landing-hero">
        <div class="container">
            <div class="row aptoria-hero-row">
                <div class="col-md-7 aptoria-hero-copy">
                    <span class="aptoria-landing-kicker">{{ __('messages.landing.hero.kicker') }}</span>
                    <h1>{{ __('messages.landing.hero.title') }}</h1>
                    <p class="aptoria-landing-lead">{{ __('messages.landing.hero.lead') }}</p>
                    <div class="aptoria-landing-cta-row">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">{{ __('messages.landing.hero.primary_cta_auth') }}</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">{{ __('messages.landing.hero.primary_cta') }}</a>
                        @endauth
                        <a href="#workflow" class="btn btn-default btn-lg">{{ __('messages.landing.hero.secondary_cta') }}</a>
                    </div>
                    <div class="aptoria-landing-trust-row">
                        <span><i class="fa fa-shield"></i> {{ __('messages.landing.hero.trust.safe') }}</span>
                        <span><i class="fa fa-server"></i> {{ __('messages.landing.hero.trust.self_hosted') }}</span>
                        <span><i class="fa fa-file-text-o"></i> {{ __('messages.landing.hero.trust.report_ready') }}</span>
                    </div>
                </div>
                <div class="col-md-5 aptoria-hero-card-col">
                    <div class="aptoria-landing-console hpanel hgreen">
                        <div class="panel-heading hbuilt">
                            <i class="fa fa-check-circle text-success"></i> {{ __('messages.landing.hero.card_title') }}
                        </div>
                        <div class="panel-body">
                            <div class="aptoria-release-gate-preview aptoria-release-gate-{{ __('messages.landing.hero.card_status_class') }}">
                                <span>{{ __('messages.landing.hero.card_status_label') }}</span>
                                <strong>{{ __('messages.landing.hero.card_status') }}</strong>
                            </div>
                            <ul class="aptoria-landing-signal-list">
                                <li><span>{{ __('messages.landing.hero.metrics.coverage') }}</span><strong>92%</strong></li>
                                <li><span>{{ __('messages.landing.hero.metrics.pass_rate') }}</span><strong>97%</strong></li>
                                <li><span>{{ __('messages.landing.hero.metrics.blockers') }}</span><strong>0</strong></li>
                                <li><span>{{ __('messages.landing.hero.metrics.report') }}</span><strong>Ready</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="aptoria-landing-section aptoria-landing-problem">
            <div class="container">
                <div class="row">
                    <div class="col-md-5">
                        <h2>{{ __('messages.landing.problem.title') }}</h2>
                        <p>{{ __('messages.landing.problem.body') }}</p>
                    </div>
                    <div class="col-md-7">
                        <div class="row aptoria-problem-grid">
                            @foreach(__('messages.landing.problem.points') as $point)
                                <div class="col-sm-6 aptoria-card-col">
                                    <div class="aptoria-mini-card">
                                        <i class="fa {{ $point['icon'] }}"></i>
                                        <h3>{{ $point['title'] }}</h3>
                                        <p>{{ $point['text'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="aptoria-landing-section">
            <div class="container">
                <div class="aptoria-section-heading text-center">
                    <span>{{ __('messages.landing.features.kicker') }}</span>
                    <h2>{{ __('messages.landing.features.title') }}</h2>
                    <p>{{ __('messages.landing.features.lead') }}</p>
                </div>
                <div class="row aptoria-feature-grid">
                    @foreach($features as $feature)
                        <div class="col-md-4 col-sm-6 aptoria-card-col">
                            <article class="hpanel aptoria-landing-feature-card">
                                <div class="panel-body">
                                    <i class="fa {{ $feature['icon'] }}"></i>
                                    <h3>{{ $feature['title'] }}</h3>
                                    <p>{{ $feature['text'] }}</p>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="workflow" class="aptoria-landing-section aptoria-landing-workflow">
            <div class="container">
                <div class="aptoria-section-heading text-center">
                    <span>{{ __('messages.landing.workflow.kicker') }}</span>
                    <h2>{{ __('messages.landing.workflow.title') }}</h2>
                    <p>{{ __('messages.landing.workflow.lead') }}</p>
                </div>
                <div class="aptoria-workflow-line">
                    @foreach($workflow as $index => $step)
                        <div class="aptoria-workflow-step">
                            <div class="aptoria-workflow-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="aptoria-landing-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-5">
                        <div class="aptoria-section-heading aptoria-section-heading-left">
                            <span>{{ __('messages.landing.use_cases.kicker') }}</span>
                            <h2>{{ __('messages.landing.use_cases.title') }}</h2>
                            <p>{{ __('messages.landing.use_cases.lead') }}</p>
                        </div>
                    </div>
                    <div class="col-md-7">
                        @foreach($useCases as $useCase)
                            <div class="aptoria-use-case">
                                <i class="fa {{ $useCase['icon'] }}"></i>
                                <div>
                                    <h3>{{ $useCase['title'] }}</h3>
                                    <p>{{ $useCase['text'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" class="aptoria-landing-section aptoria-pricing-section">
            <div class="container">
                <div class="aptoria-section-heading text-center">
                    <span>{{ __('messages.landing.packages.kicker') }}</span>
                    <h2>{{ __('messages.landing.packages.title') }}</h2>
                    <p>{{ __('messages.landing.packages.lead') }}</p>
                </div>
                <div class="row aptoria-pricing-grid">
                    @foreach($packages as $package)
                        <div class="col-md-3 col-sm-6 aptoria-card-col">
                            <article class="hpanel aptoria-pricing-card {{ !empty($package['highlight']) ? 'aptoria-pricing-card-highlight' : '' }}">
                                @if(!empty($package['highlight']))
                                    <div class="aptoria-pricing-ribbon">{{ __('messages.landing.packages.recommended') }}</div>
                                @endif
                                <div class="panel-body">
                                    <h3>{{ $package['name'] }}</h3>
                                    <p class="aptoria-price">{{ $package['price'] }}</p>
                                    <p class="aptoria-price-note">{{ $package['note'] }}</p>
                                    <ul>
                                        @foreach($package['features'] as $packageFeature)
                                            <li><i class="fa fa-check"></i> {{ $packageFeature }}</li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('login') }}" class="btn {{ !empty($package['highlight']) ? 'btn-primary' : 'btn-default' }} btn-block">{{ $package['cta'] }}</a>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
                <p class="aptoria-pricing-disclaimer text-center">{{ __('messages.landing.packages.disclaimer') }}</p>
            </div>
        </section>

        <section class="aptoria-landing-section aptoria-seo-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <h2>{{ __('messages.landing.seo.title') }}</h2>
                    </div>
                    <div class="col-md-8">
                        <p>{{ __('messages.landing.seo.paragraph_1') }}</p>
                        <p>{{ __('messages.landing.seo.paragraph_2') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="faq" class="aptoria-landing-section aptoria-faq-section">
            <div class="container">
                <div class="aptoria-section-heading text-center">
                    <span>{{ __('messages.landing.faq.kicker') }}</span>
                    <h2>{{ __('messages.landing.faq.title') }}</h2>
                </div>
                <div class="row aptoria-faq-grid">
                    @foreach($faq as $item)
                        <div class="col-md-6 aptoria-card-col">
                            <div class="aptoria-faq-item">
                                <h3>{{ $item['question'] }}</h3>
                                <p>{{ $item['answer'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="aptoria-final-cta">
            <div class="container text-center">
                <h2>{{ __('messages.landing.final_cta.title') }}</h2>
                <p>{{ __('messages.landing.final_cta.text') }}</p>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">{{ __('messages.landing.final_cta.button_auth') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">{{ __('messages.landing.final_cta.button') }}</a>
                @endauth
            </div>
        </section>
    </main>

    <footer class="aptoria-landing-footer">
        <div class="container">
            <div class="row">
                <div class="col-sm-6">
                    <strong>Aptoria</strong>
                    <span>v{{ config('aptoria.version') }} · © 2026 János Szujó</span>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="#features">{{ __('messages.landing.nav.features') }}</a>
                    <a href="#pricing">{{ __('messages.landing.nav.pricing') }}</a>
                    <a href="{{ route('login') }}">{{ __('messages.landing.nav.sign_in') }}</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="{{ asset('assets/aptoria-ui/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/aptoria-ui/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
</body>
</html>
