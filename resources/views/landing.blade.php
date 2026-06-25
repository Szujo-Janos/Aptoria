@extends('layouts.auth')

@section('title', 'Aptoria')
@section('body_class', 'aptoria-landing-body min-vh-100')

@php
    $terminalCommands = [
        'aptoria safe-scan run --target sandbox-api --profile smoke',
        'aptoria import preview --source postman --file collection.json',
        'aptoria contract:check compare --source openapi.json --inventory current',
        'aptoria release-gate evaluate --project payments-api --evidence latest',
    ];
    $terminalOutputs = [
        __('messages.product.landing_terminal_output_1'),
        __('messages.product.landing_terminal_output_2'),
        __('messages.product.landing_terminal_output_3'),
        __('messages.product.landing_terminal_output_4'),
    ];
@endphp

@section('content')
<div class="aptoria-landing-scene">
    <div class="aptoria-landing-backdrop"></div>
    <div class="aptoria-landing-grid"></div>

    <section class="aptoria-landing-shell py-4 py-xl-5">
        <div class="aptoria-landing-frame">
            <div class="row g-4 g-xxl-5 align-items-stretch">
                <div class="col-xl-6 d-flex">
                    <div class="aptoria-landing-copy w-100">
                        <div class="d-inline-flex align-items-center gap-2 aptoria-landing-badge mb-4">
                            <span class="aptoria-landing-badge-dot"></span>
                            <span>{{ __('messages.product.landing_badge_title') }}</span>
                        </div>

                        <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo aptoria-landing-logo mb-4">

                        <h1 class="aptoria-landing-title">{{ __('messages.product.headline') }}</h1>
                        <p class="aptoria-landing-lead">{{ __('messages.product.landing_copy') }}</p>

                        <div class="aptoria-landing-chip-row mb-4">
                            <span class="aptoria-landing-chip">{{ __('messages.product.landing_badge_evidence') }}</span>
                            <span class="aptoria-landing-chip">{{ __('messages.product.landing_badge_safe_scan') }}</span>
                            <span class="aptoria-landing-chip">{{ __('messages.product.landing_badge_release_gate') }}</span>
                            <span class="aptoria-landing-chip">{{ __('messages.product.landing_badge_audit_trail') }}</span>
                        </div>

                        <div class="aptoria-landing-value-list mb-4">
                            <div class="aptoria-landing-value-item">
                                <i data-lucide="scan-search"></i>
                                <div>
                                    <strong>{{ __('messages.product.value_1') }}</strong>
                                    <span>{{ __('messages.product.landing_value_copy_1') }}</span>
                                </div>
                            </div>
                            <div class="aptoria-landing-value-item">
                                <i data-lucide="folder-check"></i>
                                <div>
                                    <strong>{{ __('messages.product.value_2') }}</strong>
                                    <span>{{ __('messages.product.landing_value_copy_2') }}</span>
                                </div>
                            </div>
                            <div class="aptoria-landing-value-item">
                                <i data-lucide="shield-chevron"></i>
                                <div>
                                    <strong>{{ __('messages.product.value_3') }}</strong>
                                    <span>{{ __('messages.product.landing_value_copy_3') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                                <i data-lucide="log-in" class="me-1"></i>{{ __('messages.auth.sign_in') }}
                            </a>
                            <a href="{{ route('demo-guide.public') }}" class="btn btn-outline-light btn-lg">
                                <i data-lucide="map" class="me-1"></i>{{ __('messages.demo_guide.open_public') }}
                            </a>
                            <a href="{{ route('demo-api.health') }}" class="btn btn-outline-info btn-lg" target="_blank">
                                <i data-lucide="braces" class="me-1"></i>{{ __('messages.product.try_live_api') }}
                            </a>
                            <a href="{{ route('setup.index') }}" class="btn btn-light btn-lg text-dark">
                                <i data-lucide="settings-2" class="me-1"></i>{{ __('messages.setup.open_setup') }}
                            </a>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="aptoria-landing-stat-card">
                                    <small>{{ __('messages.product.landing_stat_1_label') }}</small>
                                    <strong>{{ __('messages.product.landing_stat_1_value') }}</strong>
                                    <span>{{ __('messages.product.landing_stat_1_copy') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="aptoria-landing-stat-card">
                                    <small>{{ __('messages.product.landing_stat_2_label') }}</small>
                                    <strong>{{ __('messages.product.landing_stat_2_value') }}</strong>
                                    <span>{{ __('messages.product.landing_stat_2_copy') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="aptoria-landing-stat-card">
                                    <small>{{ __('messages.product.landing_stat_3_label') }}</small>
                                    <strong>{{ __('messages.product.landing_stat_3_value') }}</strong>
                                    <span>{{ __('messages.product.landing_stat_3_copy') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 d-flex">
                    <div class="aptoria-landing-console-wrap w-100">
                        <div class="aptoria-landing-panel aptoria-landing-terminal-panel mb-4">
                            <div class="aptoria-console-toolbar">
                                <span class="aptoria-console-dot is-danger"></span>
                                <span class="aptoria-console-dot is-warning"></span>
                                <span class="aptoria-console-dot is-success"></span>
                                <span class="aptoria-console-toolbar-label">{{ __('messages.product.landing_terminal_title') }}</span>
                            </div>
                            <div class="aptoria-console-screen">
                                <div class="aptoria-console-section-label">{{ __('messages.product.landing_terminal_section') }}</div>
                                <div class="aptoria-console-command-line">
                                    <span class="aptoria-console-prompt">$</span>
                                    <span id="aptoriaTypewriterText"></span>
                                    <span class="aptoria-console-caret"></span>
                                </div>
                                <div class="aptoria-console-output" id="aptoriaTypewriterOutput">{{ $terminalOutputs[0] }}</div>

                                <div class="aptoria-console-metrics row g-3 mt-1">
                                    <div class="col-sm-6">
                                        <div class="aptoria-console-mini-card">
                                            <span>{{ __('messages.product.landing_console_card_1_title') }}</span>
                                            <strong>{{ __('messages.product.landing_console_card_1_value') }}</strong>
                                            <small>{{ __('messages.product.landing_console_card_1_copy') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="aptoria-console-mini-card">
                                            <span>{{ __('messages.product.landing_console_card_2_title') }}</span>
                                            <strong>{{ __('messages.product.landing_console_card_2_value') }}</strong>
                                            <small>{{ __('messages.product.landing_console_card_2_copy') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="aptoria-landing-panel aptoria-landing-note-card h-100">
                                    <small class="aptoria-landing-eyebrow">{{ __('messages.product.not_a_clone') }}</small>
                                    <h2 class="h4 mb-2">{{ __('messages.product.landing_note_title') }}</h2>
                                    <p class="mb-0 text-muted">{{ __('messages.product.not_a_clone_copy') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="aptoria-landing-panel aptoria-landing-stack-card h-100">
                                    <small class="aptoria-landing-eyebrow">{{ __('messages.product.landing_stack_title') }}</small>
                                    <ul class="list-unstyled mb-0 aptoria-landing-stack-list">
                                        <li><i data-lucide="shield-check"></i><span>{{ __('messages.product.landing_stack_item_1') }}</span></li>
                                        <li><i data-lucide="database"></i><span>{{ __('messages.product.landing_stack_item_2') }}</span></li>
                                        <li><i data-lucide="git-merge"></i><span>{{ __('messages.product.landing_stack_item_3') }}</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const commands = @json($terminalCommands);
        const outputs = @json($terminalOutputs);
        const textEl = document.getElementById('aptoriaTypewriterText');
        const outputEl = document.getElementById('aptoriaTypewriterOutput');

        if (!textEl || !outputEl || !commands.length) {
            return;
        }

        let commandIndex = 0;
        let charIndex = 0;
        let deleting = false;

        const type = () => {
            const currentCommand = commands[commandIndex] ?? '';
            const nextLength = deleting ? charIndex - 1 : charIndex + 1;
            charIndex = Math.max(0, Math.min(currentCommand.length, nextLength));
            textEl.textContent = currentCommand.slice(0, charIndex);

            if (!deleting && charIndex === currentCommand.length) {
                outputEl.textContent = outputs[commandIndex] ?? '';
                deleting = true;
                setTimeout(type, 1700);
                return;
            }

            if (deleting && charIndex === 0) {
                deleting = false;
                commandIndex = (commandIndex + 1) % commands.length;
                setTimeout(type, 300);
                return;
            }

            const delay = deleting ? 28 : 46;
            setTimeout(type, delay);
        };

        type();
    })();
</script>
@endpush
