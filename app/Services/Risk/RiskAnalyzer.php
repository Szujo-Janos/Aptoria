<?php

namespace App\Services\Risk;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\ScanResult;
use App\Services\Settings\ProjectSettingService;
use App\Services\Settings\SettingService;

class RiskAnalyzer
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly ProjectSettingService $projectSettings,
    ) {
    }

    /** @var array<string, int> */
    private array $levelWeights = [
        Endpoint::RISK_LOW => 1,
        Endpoint::RISK_PUBLIC => 2,
        Endpoint::RISK_REVIEW => 3,
        Endpoint::RISK_HIGH => 4,
        Endpoint::RISK_CRITICAL => 5,
    ];

    /** @var array<string, int> */
    private array $manualScoreBaselines = [
        Endpoint::RISK_LOW => 0,
        Endpoint::RISK_PUBLIC => 10,
        Endpoint::RISK_REVIEW => 30,
        Endpoint::RISK_HIGH => 60,
        Endpoint::RISK_CRITICAL => 90,
    ];

    /**
     * @return array{
     *     manual_level: string,
     *     manual_label: string,
     *     manual_css: string,
     *     calculated_level: string,
     *     calculated_label: string,
     *     calculated_css: string,
     *     final_level: string,
     *     final_label: string,
     *     final_css: string,
     *     score: int,
     *     signals: array<int, array<string, mixed>>,
     *     explanation: string,
     *     why_it_matters: string,
     *     qa_actions: array<int, string>,
     *     qa_action: string,
     *     developer_actions: array<int, string>,
     *     developer_action: string
     * }
     */
    public function analyze(?Endpoint $endpoint, ?ScanResult $scanResult = null): array
    {
        $endpoint?->loadMissing(['project.authProfiles', 'environment', 'authProfile']);
        $scanResult?->loadMissing('scanRun.environment');

        $signals = [];

        if ($endpoint) {
            $path = strtolower((string) $endpoint->path);
            $method = strtoupper((string) $endpoint->method);

            if ($this->settings->boolean('risk.enable_exposure_checks', true)) {
                if (! $endpoint->auth_required && $this->pathContainsKeyword($path, $this->keywordList($endpoint, 'risk.sensitive_keywords'))) {
                    $signals[] = $this->signal('public_sensitive_endpoint', Endpoint::RISK_HIGH, $this->settings->integer('risk.sensitive_keyword_weight', 8));
                }

                if ($this->pathContainsKeyword($path, $this->keywordList($endpoint, 'risk.internal_keywords'))) {
                    $signals[] = $this->signal('admin_internal_debug_path', Endpoint::RISK_HIGH, $this->settings->integer('risk.internal_keyword_weight', 8));

                    if (! $endpoint->auth_required) {
                        $signals[] = $this->signal('public_sensitive_endpoint', Endpoint::RISK_HIGH, $this->settings->integer('risk.public_admin_endpoint_weight', 20));
                    }
                }
            }

            if ($this->settings->boolean('risk.enable_https_checks', true) && strtolower((string) parse_url($endpoint->full_url, PHP_URL_SCHEME)) === 'http') {
                $signals[] = $this->signal('insecure_http_endpoint', Endpoint::RISK_REVIEW, $this->settings->integer('risk.http_without_https_weight', 15));
            }

            if (! in_array($method, [Endpoint::METHOD_GET, Endpoint::METHOD_HEAD], true)) {
                $signals[] = $this->signal('destructive_method_excluded', Endpoint::RISK_REVIEW, 20);
            }

            if ($endpoint->excluded_from_scan) {
                $signals[] = $this->signal('endpoint_excluded_from_scan', Endpoint::RISK_REVIEW, 15);
            }

            if ($endpoint->expected_status === null) {
                $signals[] = $this->signal('missing_expected_status', Endpoint::RISK_REVIEW, 12);
            }

            if (! $endpoint->expected_content_type) {
                $signals[] = $this->signal('missing_expected_content_type', Endpoint::RISK_REVIEW, 12);
            }
        }

        if ($scanResult) {
            $statusCode = $scanResult->status_code;

            if ($endpoint && $endpoint->auth_required && $statusCode !== null && $statusCode >= 200 && $statusCode < 400 && ! $this->hasAuthenticationContext($endpoint)) {
                $signals[] = $this->signal('auth_required_success_without_context', Endpoint::RISK_HIGH, 45);
            }

            if ($endpoint && $endpoint->auth_required && in_array((int) $statusCode, [401, 403], true)) {
                $protectedMode = $this->settings->string('risk.protected_endpoint_mode', 'warn_401_403');
                if ($protectedMode === 'fail_401_403') {
                    $signals[] = $this->signal('unexpected_status_code', Endpoint::RISK_HIGH, 30);
                } elseif ($protectedMode === 'warn_401_403') {
                    $signals[] = $this->signal('unexpected_status_code', Endpoint::RISK_REVIEW, 10);
                }
            }

            if ($statusCode !== null && $statusCode >= 500) {
                $signals[] = $this->signal('unexpected_server_error', Endpoint::RISK_HIGH, $this->settings->integer('risk.server_error_weight', 20));
            }

            if ($endpoint?->expected_status !== null && $statusCode !== null && $statusCode !== (int) $endpoint->expected_status) {
                $signals[] = $this->signal('unexpected_status_code', Endpoint::RISK_REVIEW, 25);
            }

            if (
                $scanResult->status === ScanResult::STATUS_COMPLETED
                &&
                $endpoint?->expected_content_type
                && ! str_contains(strtolower((string) $scanResult->content_type), strtolower($endpoint->expected_content_type))
            ) {
                $signals[] = $this->signal('unexpected_content_type', Endpoint::RISK_REVIEW, 20);
            }

            if ($scanResult->response_time_ms !== null) {
                $slowThreshold = $this->settings->integer('risk.slow_response_ms', 1000);
                $verySlowThreshold = $this->settings->integer('risk.very_slow_response_ms', 3000);

                if ($scanResult->response_time_ms > $verySlowThreshold) {
                    $signals[] = $this->signal('slow_response_time', Endpoint::RISK_HIGH, $this->settings->integer('risk.very_slow_response_weight', 10));
                } elseif ($scanResult->response_time_ms > $slowThreshold) {
                    $signals[] = $this->signal('slow_response_time', Endpoint::RISK_REVIEW, $this->settings->integer('risk.slow_response_weight', 5));
                }
            }

            if ($this->settings->boolean('risk.enable_response_size_checks', true) && $scanResult->response_size !== null) {
                $maxBytes = max(1, $this->settings->integer('scan.max_response_size_kb', 2048)) * 1024;
                if ((int) $scanResult->response_size > $maxBytes) {
                    $signals[] = $this->signal('response_size_exceeded', Endpoint::RISK_REVIEW, $this->settings->integer('risk.large_response_weight', 6));
                }
            }


            if ($scanResult->sensitive_data_detected) {
                $summary = is_array($scanResult->sensitive_data_summary_json) ? $scanResult->sensitive_data_summary_json : [];
                $severity = (string) ($summary['highest_severity'] ?? 'high');
                $level = $severity === 'critical' ? Endpoint::RISK_CRITICAL : Endpoint::RISK_HIGH;
                $signals[] = $this->signal('sensitive_data_exposed', $level, $level === Endpoint::RISK_CRITICAL ? 75 : 55);
            }

            if ($scanResult->broken_auth_detected) {
                $summary = is_array($scanResult->broken_auth_summary_json) ? $scanResult->broken_auth_summary_json : [];
                $severity = (string) ($summary['severity'] ?? 'high');
                $level = $severity === 'critical' ? Endpoint::RISK_CRITICAL : Endpoint::RISK_HIGH;
                $signals[] = $this->signal('broken_auth_exposure', $level, $level === Endpoint::RISK_CRITICAL ? 90 : 70);
            }

            if ($scanResult->schema_drift_detected) {
                $summary = is_array($scanResult->schema_drift_summary_json) ? $scanResult->schema_drift_summary_json : [];
                $severity = (string) ($summary['highest_severity'] ?? 'high');
                $level = $severity === 'critical' ? Endpoint::RISK_CRITICAL : Endpoint::RISK_HIGH;
                $signals[] = $this->signal('schema_drift_detected', $level, $level === Endpoint::RISK_CRITICAL ? 80 : 60);
            }

            if ($this->settings->boolean('risk.enable_security_header_checks', true) && is_array($scanResult->headers_json)) {
                $headers = $this->normalizedHeaders($scanResult->headers_json);
                if ($headers !== [] && (! array_key_exists('x-content-type-options', $headers) || ! array_key_exists('strict-transport-security', $headers))) {
                    $signals[] = $this->signal('security_headers_missing', Endpoint::RISK_REVIEW, $this->settings->integer('risk.missing_security_header_weight', 12));
                }
            }

            if ($this->settings->boolean('scan.treat_redirect_as_warning', true) && $statusCode !== null && $statusCode >= 300 && $statusCode < 400) {
                $signals[] = $this->signal('unexpected_status_code', Endpoint::RISK_REVIEW, 10);
            }

            if ($scanResult->status === ScanResult::STATUS_FAILED) {
                $message = strtolower((string) $scanResult->error_message);
                $isSslError = str_contains($message, 'ssl') || str_contains($message, 'certificate') || str_contains($message, 'tls');
                $signals[] = $this->signal('request_failed', ($isSslError && $this->settings->boolean('scan.treat_ssl_error_as_critical', true)) ? Endpoint::RISK_CRITICAL : Endpoint::RISK_REVIEW, $isSslError ? $this->settings->integer('risk.http_without_https_weight', 15) : 25);
            }
        }

        $signals = $this->uniqueSignals($signals);
        $manualLevel = $this->normalizeLevel($endpoint?->risk_level ?? $scanResult?->risk_level);
        $calculatedLevel = $this->calculatedLevel($signals);
        $finalLevel = $this->higherLevel($manualLevel, $calculatedLevel);
        $score = $this->applyScoringMode(min(100, max(
            $this->manualScoreBaseline($manualLevel),
            array_sum(array_column($signals, 'score'))
        )));

        $qaActions = $this->uniqueText(array_column($signals, 'qa_action'));
        $developerActions = $this->uniqueText(array_column($signals, 'developer_action'));

        if ($qaActions === []) {
            $qaActions[] = __('messages.risk.defaults.qa_action');
        }

        if ($developerActions === []) {
            $developerActions[] = __('messages.risk.defaults.developer_action');
        }

        return [
            'manual_level' => $manualLevel,
            'manual_label' => $this->levelLabel($manualLevel),
            'manual_css' => $this->levelCss($manualLevel),
            'calculated_level' => $calculatedLevel,
            'calculated_label' => $this->levelLabel($calculatedLevel),
            'calculated_css' => $this->levelCss($calculatedLevel),
            'final_level' => $finalLevel,
            'final_label' => $this->levelLabel($finalLevel),
            'final_css' => $this->levelCss($finalLevel),
            'score' => $score,
            'signals' => $signals,
            'explanation' => $signals === []
                ? __('messages.risk.defaults.explanation')
                : __('messages.risk.analysis_summary', [
                    'count' => count($signals),
                    'calculated' => $this->levelLabel($calculatedLevel),
                    'final' => $this->levelLabel($finalLevel),
                ]),
            'why_it_matters' => $signals === []
                ? __('messages.risk.defaults.why_it_matters')
                : implode(' ', $this->uniqueText(array_column($signals, 'explanation'))),
            'qa_actions' => $qaActions,
            'qa_action' => implode(' ', $qaActions),
            'developer_actions' => $developerActions,
            'developer_action' => implode(' ', $developerActions),
        ];
    }

    public function buildQaBugReport(Endpoint $endpoint, ?ScanResult $scanResult = null, ?array $analysis = null): string
    {
        $endpoint->loadMissing(['project', 'environment']);
        $scanResult?->loadMissing('scanRun.environment');
        $analysis ??= $this->analyze($endpoint, $scanResult);

        $environment = $scanResult?->scanRun?->environment?->name
            ?: $endpoint->environment?->name
            ?: __('messages.endpoints.project_default');

        $expected = __('messages.risk.bug_report.expected_value', [
            'status' => $endpoint->expected_status ?: __('messages.common.not_available'),
            'content_type' => $endpoint->expected_content_type ?: __('messages.common.not_available'),
        ]);

        $actual = $scanResult
            ? __('messages.risk.bug_report.actual_value', [
                'result_status' => $scanResult->status_label,
                'status' => $scanResult->status_code ?: __('messages.common.not_available'),
                'content_type' => $scanResult->content_type ?: __('messages.common.not_available'),
                'response_time' => $scanResult->response_time_ms !== null ? $scanResult->response_time_ms.' ms' : __('messages.common.not_available'),
            ])
            : __('messages.risk.bug_report.no_scan_result');

        $steps = [
            __('messages.risk.bug_report.step_open_project', ['project' => $endpoint->project?->name ?: __('messages.common.not_available')]),
            __('messages.risk.bug_report.step_open_endpoint', ['method' => $endpoint->method, 'path' => $endpoint->path]),
            __('messages.risk.bug_report.step_run_safe_probe'),
            __('messages.risk.bug_report.step_compare'),
        ];

        return implode(PHP_EOL, [
            __('messages.risk.bug_report.title_label').': '.__('messages.risk.bug_report.title_value', [
                'method' => $endpoint->method,
                'path' => $endpoint->path,
                'level' => $analysis['final_label'],
            ]),
            '',
            __('messages.risk.bug_report.environment_label').': '.$environment,
            __('messages.risk.bug_report.endpoint_label').': '.$endpoint->method.' '.$endpoint->full_url,
            '',
            __('messages.risk.bug_report.expected_label').':',
            $expected,
            '',
            __('messages.risk.bug_report.actual_label').':',
            $actual,
            '',
            __('messages.risk.bug_report.risk_reason_label').':',
            $analysis['why_it_matters'],
            '',
            __('messages.risk.bug_report.reproduction_label').':',
            ...array_map(fn (string $step, int $index): string => ($index + 1).'. '.$step, $steps, array_keys($steps)),
            '',
            __('messages.risk.bug_report.next_action_label').':',
            $analysis['qa_action'],
        ]);
    }

    public function buildDeveloperReviewSnippet(Endpoint $endpoint, ?ScanResult $scanResult = null, ?array $analysis = null): string
    {
        $analysis ??= $this->analyze($endpoint, $scanResult);

        $checklist = [
            __('messages.risk.developer_checklist.verify_authentication'),
            __('messages.risk.developer_checklist.verify_authorization'),
            __('messages.risk.developer_checklist.check_data_minimization'),
            __('messages.risk.developer_checklist.confirm_expected_status'),
            __('messages.risk.developer_checklist.add_regression_test'),
            __('messages.risk.developer_checklist.document_ownership'),
            __('messages.risk.developer_checklist.risk_specific', ['action' => $analysis['developer_action']]),
        ];

        return implode(PHP_EOL, [
            __('messages.risk.developer_checklist.title', ['method' => $endpoint->method, 'path' => $endpoint->path]),
            ...array_map(fn (string $item): string => '- [ ] '.$item, $checklist),
        ]);
    }

    /** @return array<string, mixed> */
    private function signal(string $key, string $level, int $score): array
    {
        return [
            'key' => $key,
            'level' => $level,
            'level_label' => $this->levelLabel($level),
            'css' => $this->levelCss($level),
            'score' => $score,
            'label' => __('messages.risk.signals.'.$key.'.label'),
            'explanation' => __('messages.risk.signals.'.$key.'.explanation'),
            'qa_action' => __('messages.risk.signals.'.$key.'.qa_action'),
            'developer_action' => __('messages.risk.signals.'.$key.'.developer_action'),
        ];
    }

    /** @param array<int, string> $keywords */
    private function pathContainsKeyword(string $path, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            $keyword = preg_quote($keyword, '~');

            if ($keyword !== '' && preg_match('~(?:^|[/._-])'.$keyword.'(?:[/._?-]|$|\d)~i', $path)) {
                return true;
            }
        }

        return false;
    }

    private function hasAuthenticationContext(Endpoint $endpoint): bool
    {
        $profile = $endpoint->authProfile;

        if (! $profile && $endpoint->project) {
            $profile = $endpoint->project->defaultAuthProfile();
        }

        return $profile instanceof AuthProfile && $profile->type !== AuthProfile::TYPE_NONE;
    }



    private function applyScoringMode(int $score): int
    {
        return match ($this->settings->string('risk.scoring_mode', 'balanced')) {
            'strict' => min(100, (int) round($score * 1.20)),
            'security_focused' => min(100, (int) round($score * 1.15)),
            'performance_focused' => min(100, (int) round($score * 1.10)),
            default => $score,
        };
    }

    private function manualScoreBaseline(string $level): int
    {
        return match ($level) {
            Endpoint::RISK_CRITICAL => $this->settings->integer('risk.critical_threshold', $this->manualScoreBaselines[Endpoint::RISK_CRITICAL]),
            Endpoint::RISK_HIGH => $this->settings->integer('risk.high_threshold', $this->manualScoreBaselines[Endpoint::RISK_HIGH]),
            Endpoint::RISK_REVIEW => $this->settings->integer('risk.medium_threshold', $this->manualScoreBaselines[Endpoint::RISK_REVIEW]),
            Endpoint::RISK_PUBLIC => max(10, (int) floor($this->settings->integer('risk.medium_threshold', $this->manualScoreBaselines[Endpoint::RISK_REVIEW]) / 2)),
            default => $this->settings->integer('risk.low_threshold', $this->manualScoreBaselines[Endpoint::RISK_LOW]),
        };
    }

    /** @param array<string, mixed> $headers */
    private function normalizedHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $values) {
            $normalized[strtolower((string) $name)] = $values;
        }

        return $normalized;
    }

    /** @return array<int, string> */
    private function keywordList(Endpoint $endpoint, string $key): array
    {
        $global = $this->settings->csv($key);
        $project = $endpoint->project ? $this->projectSettings->csv($endpoint->project, $key) : [];

        return array_values(array_unique(array_filter([...$global, ...$project])));
    }

    /** @param array<int, array<string, mixed>> $signals */
    private function calculatedLevel(array $signals): string
    {
        $level = Endpoint::RISK_LOW;

        foreach ($signals as $signal) {
            $level = $this->higherLevel($level, (string) $signal['level']);
        }

        return $level;
    }

    private function higherLevel(string $current, string $candidate): string
    {
        return $this->levelWeights[$candidate] > $this->levelWeights[$current] ? $candidate : $current;
    }

    private function normalizeLevel(?string $level): string
    {
        return in_array($level, Endpoint::RISKS, true) ? $level : Endpoint::RISK_REVIEW;
    }

    private function levelLabel(string $level): string
    {
        return __('messages.endpoints.risks.'.$level);
    }

    private function levelCss(string $level): string
    {
        return match ($level) {
            Endpoint::RISK_CRITICAL => 'danger',
            Endpoint::RISK_HIGH => 'warning',
            Endpoint::RISK_PUBLIC => 'info',
            Endpoint::RISK_LOW => 'success',
            default => 'default',
        };
    }

    /**
     * @param array<int, array<string, mixed>> $signals
     * @return array<int, array<string, mixed>>
     */
    private function uniqueSignals(array $signals): array
    {
        $unique = [];

        foreach ($signals as $signal) {
            $unique[$signal['key']] = $signal;
        }

        return array_values($unique);
    }

    /**
     * @param array<int, string> $items
     * @return array<int, string>
     */
    private function uniqueText(array $items): array
    {
        return array_values(array_unique(array_filter($items)));
    }
}
