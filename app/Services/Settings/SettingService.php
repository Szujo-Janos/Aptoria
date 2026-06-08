<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SettingService
{
    /**
     * @return array<string, array{value: mixed, type: string, group: string, description?: string, min?: int, max?: int, options?: array<int, string>}>
     */
    public function defaults(): array
    {
        return [
            // General
            'app.name' => ['value' => 'Aptoria', 'type' => 'string', 'group' => 'general', 'description' => 'Displayed product name.'],
            'app.default_locale' => ['value' => 'en', 'type' => 'string', 'group' => 'general', 'options' => ['en', 'hu'], 'description' => 'Fallback UI language when the session has no language override.'],
            'app.timezone' => ['value' => 'Europe/Budapest', 'type' => 'string', 'group' => 'general', 'description' => 'Default timezone used by reports and dashboard hints.'],
            'app.date_format' => ['value' => 'Y-m-d H:i', 'type' => 'string', 'group' => 'general', 'options' => ['Y-m-d H:i', 'd.m.Y H:i', 'm/d/Y H:i'], 'description' => 'Default display format for date/time values.'],
            'app.items_per_page' => ['value' => 25, 'type' => 'integer', 'group' => 'general', 'min' => 10, 'max' => 200, 'description' => 'Pagination size for index screens.'],
            'app.default_landing_page' => ['value' => 'dashboard', 'type' => 'string', 'group' => 'general', 'options' => ['dashboard', 'projects', 'reports', 'release_readiness'], 'description' => 'Controls the first page shown after login.'],
            'app.default_project_view' => ['value' => 'overview', 'type' => 'string', 'group' => 'general', 'options' => ['overview', 'endpoints', 'scans', 'qa_evidence', 'release_readiness', 'calendar'], 'description' => 'Preferred project detail landing section.'],
            'app.default_dashboard_range_days' => ['value' => 30, 'type' => 'integer', 'group' => 'general', 'min' => 1, 'max' => 365, 'description' => 'Default reporting range for dashboard cards.'],

            // Scan profiles
            'scan.default_profile' => ['value' => 'safe', 'type' => 'string', 'group' => 'scan_profiles', 'options' => ['safe', 'staging', 'production', 'aggressive_local'], 'description' => 'Named scan profile preselected for scan launches.'],
            'scan.profile_safe_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'scan_profiles', 'description' => 'Enable the conservative safe profile.'],
            'scan.profile_staging_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'scan_profiles', 'description' => 'Enable the staging profile.'],
            'scan.profile_production_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'scan_profiles', 'description' => 'Enable the production profile.'],
            'scan.profile_aggressive_local_enabled' => ['value' => false, 'type' => 'boolean', 'group' => 'scan_profiles', 'description' => 'Enable an aggressive local-only profile.'],
            'scan.default_mode' => ['value' => 'safe', 'type' => 'string', 'group' => 'scan_profiles', 'options' => ['safe', 'balanced', 'strict'], 'description' => 'Mode stored on scan runs launched with the safe profile.'],
            'scan.max_concurrent_scans' => ['value' => 1, 'type' => 'integer', 'group' => 'scan_profiles', 'min' => 1, 'max' => 10, 'description' => 'Active concurrency limit for queued scanning and CI-readable runtime policy.'],
            'scan.run_timeout_seconds' => ['value' => 300, 'type' => 'integer', 'group' => 'scan_profiles', 'min' => 30, 'max' => 7200, 'description' => 'Maximum total runtime for a full project scan.'],

            // HTTP scan behavior
            'scan.timeout_seconds' => ['value' => 10, 'type' => 'integer', 'group' => 'scan', 'min' => 1, 'max' => 120, 'description' => 'Maximum request duration for safe probes.'],
            'scan.connect_timeout_seconds' => ['value' => 5, 'type' => 'integer', 'group' => 'scan', 'min' => 1, 'max' => 60, 'description' => 'Connection timeout for safe probes.'],
            'scan.follow_redirects' => ['value' => true, 'type' => 'boolean', 'group' => 'scan', 'description' => 'Allow HTTP redirects during safe probes.'],
            'scan.verify_ssl' => ['value' => true, 'type' => 'boolean', 'group' => 'scan', 'description' => 'Verify TLS certificates for HTTPS targets.'],
            'scan.max_redirects' => ['value' => 3, 'type' => 'integer', 'group' => 'scan', 'min' => 0, 'max' => 10, 'description' => 'Maximum redirect hops when redirects are enabled.'],
            'scan.max_response_size_kb' => ['value' => 2048, 'type' => 'integer', 'group' => 'scan', 'min' => 1, 'max' => 102400, 'description' => 'Soft response size limit used for warnings and exports.'],
            'scan.max_body_preview_kb' => ['value' => 64, 'type' => 'integer', 'group' => 'scan', 'min' => 1, 'max' => 1024, 'description' => 'Maximum stored response body preview.'],
            'scan.user_agent' => ['value' => 'Aptoria/{version}', 'type' => 'string', 'group' => 'scan', 'description' => 'User-Agent sent by safe probes. Use {version} as placeholder.'],
            'scan.retry_count' => ['value' => 0, 'type' => 'integer', 'group' => 'scan', 'min' => 0, 'max' => 5, 'description' => 'Number of retry attempts for transient probe failures.'],
            'scan.retry_delay_ms' => ['value' => 250, 'type' => 'integer', 'group' => 'scan', 'min' => 0, 'max' => 10000, 'description' => 'Delay between retry attempts.'],
            'scan.rate_limit_ms' => ['value' => 250, 'type' => 'integer', 'group' => 'scan', 'min' => 0, 'max' => 10000, 'description' => 'Delay between endpoints in a project scan.'],
            'scan.delay_between_requests_ms' => ['value' => 250, 'type' => 'integer', 'group' => 'scan', 'ui' => false, 'runtime_only' => 'Backward-compatible fallback consumed by SafeProbeService.', 'min' => 0, 'max' => 10000, 'description' => 'Backward-compatible fallback for the scan rate limit.'],
            'scan.max_endpoints_per_scan' => ['value' => 100, 'type' => 'integer', 'group' => 'scan', 'min' => 1, 'max' => 2000, 'description' => 'Global default endpoint limit for a project scan.'],
            'scan.allowed_methods' => ['value' => 'GET,HEAD', 'type' => 'csv', 'group' => 'scan', 'description' => 'Allowed automated scan methods, further constrained by the safety switches.'],
            'scan.treat_redirect_as_warning' => ['value' => true, 'type' => 'boolean', 'group' => 'scan', 'description' => 'Flag redirect responses as review warnings.'],
            'scan.treat_ssl_error_as_critical' => ['value' => true, 'type' => 'boolean', 'group' => 'scan', 'description' => 'Escalate SSL/TLS probe failures to critical findings.'],
            'scan.stop_after_failed_endpoints' => ['value' => 0, 'type' => 'integer', 'group' => 'scan', 'min' => 0, 'max' => 2000, 'description' => 'Stop a project scan after this many failed endpoints. 0 disables the limit.'],
            'scan.stop_after_critical_findings' => ['value' => 0, 'type' => 'integer', 'group' => 'scan', 'min' => 0, 'max' => 2000, 'description' => 'Stop a project scan after this many critical findings. 0 disables the limit.'],

            // Probe safety
            'probe.safe_methods_only' => ['value' => true, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Keep automated probes limited to GET and HEAD.'],
            'probe.block_destructive_methods' => ['value' => true, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Never execute POST, PUT, PATCH or DELETE automatically.'],
            'probe.allow_localhost' => ['value' => false, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Allow localhost targets when private network protection is otherwise enabled.'],
            'scan.block_private_networks' => ['value' => true, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Block RFC1918/private network targets by default.'],
            'scan.require_confirmation' => ['value' => true, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Require scan safety confirmation before full scans.'],
            'scan.require_production_confirmation' => ['value' => true, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Require extra confirmation for production environments.'],
            'probe.head_fallback_to_get' => ['value' => false, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Retry a failed HEAD probe as GET.'],
            'probe.block_dangerous_query_keywords' => ['value' => true, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Block scan URLs containing dangerous query keys.'],
            'probe.dangerous_query_keywords' => ['value' => 'delete,drop,truncate,reset,remove,destroy,force,purge', 'type' => 'csv', 'group' => 'probe_safety', 'description' => 'Query keywords treated as destructive signals.'],
            'probe.block_destructive_path_keywords' => ['value' => true, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Block paths containing destructive keywords.'],
            'probe.destructive_path_keywords' => ['value' => 'delete,destroy,truncate,reset,purge,drop', 'type' => 'csv', 'group' => 'probe_safety', 'description' => 'Path keywords treated as destructive signals.'],
            'probe.allow_private_network_per_project' => ['value' => true, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Allow project-level private-network overrides.'],
            'probe.require_typed_production_confirmation' => ['value' => true, 'type' => 'boolean', 'group' => 'probe_safety', 'description' => 'Typed confirmation policy before production scans.'],
            'probe.production_confirmation_phrase' => ['value' => 'SCAN PRODUCTION', 'type' => 'string', 'group' => 'probe_safety', 'description' => 'Phrase required for typed production scan confirmation.'],

            // Risk engine
            'risk.scoring_mode' => ['value' => 'balanced', 'type' => 'string', 'group' => 'risk_engine', 'options' => ['balanced', 'strict', 'security_focused', 'performance_focused'], 'description' => 'Scoring profile used by the risk analyzer.'],
            'risk.low_threshold' => ['value' => 0, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Low risk score baseline.'],
            'risk.medium_threshold' => ['value' => 25, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Medium/review risk score baseline.'],
            'risk.high_threshold' => ['value' => 50, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'High risk score baseline.'],
            'risk.critical_threshold' => ['value' => 75, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Critical risk score baseline.'],
            'risk.slow_response_ms' => ['value' => 1000, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 100, 'max' => 60000, 'description' => 'Slow response threshold.'],
            'risk.very_slow_response_ms' => ['value' => 3000, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 100, 'max' => 120000, 'description' => 'Very slow response threshold.'],
            'risk.enable_security_header_checks' => ['value' => true, 'type' => 'boolean', 'group' => 'risk_engine', 'description' => 'Enable security header related risk signals.'],
            'risk.enable_https_checks' => ['value' => true, 'type' => 'boolean', 'group' => 'risk_engine', 'description' => 'Enable HTTPS related risk signals.'],
            'risk.enable_response_size_checks' => ['value' => true, 'type' => 'boolean', 'group' => 'risk_engine', 'description' => 'Enable response size related risk signals.'],
            'risk.enable_exposure_checks' => ['value' => true, 'type' => 'boolean', 'group' => 'risk_engine', 'description' => 'Enable public exposure and path keyword checks.'],
            'risk.sensitive_keywords' => ['value' => 'users,user,customers,customer,accounts,account,profiles,profile,orders,order,payments,payment,invoice,invoices,billing,token,auth,login,admin,debug,config', 'type' => 'csv', 'group' => 'risk_engine', 'description' => 'Sensitive path keywords.'],
            'risk.internal_keywords' => ['value' => 'internal,private,admin,debug,diagnostic,dev,devtool,devtools', 'type' => 'csv', 'group' => 'risk_engine', 'description' => 'Internal/admin path keywords.'],
            'risk.missing_security_header_weight' => ['value' => 12, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Score added for missing security headers.'],
            'risk.http_without_https_weight' => ['value' => 15, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Score added for HTTP targets without HTTPS.'],
            'risk.slow_response_weight' => ['value' => 5, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Score added for slow responses.'],
            'risk.very_slow_response_weight' => ['value' => 10, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Score added for very slow responses.'],
            'risk.large_response_weight' => ['value' => 6, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Score added for oversized responses.'],
            'risk.sensitive_keyword_weight' => ['value' => 35, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Risk score weight for public sensitive path keywords.'],
            'risk.internal_keyword_weight' => ['value' => 8, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Score added for internal/admin path keywords.'],
            'risk.server_error_weight' => ['value' => 20, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Score added for 5xx responses.'],
            'risk.public_admin_endpoint_weight' => ['value' => 20, 'type' => 'integer', 'group' => 'risk_engine', 'min' => 0, 'max' => 100, 'description' => 'Score added for public admin endpoints.'],
            'risk.protected_endpoint_mode' => ['value' => 'warn_401_403', 'type' => 'string', 'group' => 'risk_engine', 'options' => ['fail_401_403', 'allow_401_403', 'warn_401_403'], 'description' => 'Preferred treatment for protected endpoint status codes.'],

            // Assertions
            'assertions.enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'assertions', 'description' => 'Enable assertion evaluation globally.'],
            'assertions.default_status_code' => ['value' => 200, 'type' => 'integer', 'group' => 'assertions', 'min' => 100, 'max' => 599, 'description' => 'Default expected status code for newly created assertion rules.'],
            'assertions.treat_warning_as_failure' => ['value' => false, 'type' => 'boolean', 'group' => 'assertions', 'description' => 'Treat assertion warnings as failed scan evidence in summaries.'],
            'assertions.treat_regression_as_failure' => ['value' => true, 'type' => 'boolean', 'group' => 'assertions', 'description' => 'Treat detected regressions as failed scan evidence in summaries.'],
            'assertions.allow_401_403_for_protected_endpoints' => ['value' => true, 'type' => 'boolean', 'group' => 'assertions', 'description' => 'Treat 401/403 as expected for explicitly protected endpoints.'],

            // Evidence and snapshots
            'snapshots.auto_save' => ['value' => false, 'type' => 'boolean', 'group' => 'snapshots', 'description' => 'Create a snapshot after a successful scan.'],
            'scan.store_response_headers' => ['value' => true, 'type' => 'boolean', 'group' => 'snapshots', 'description' => 'Store response headers.'],
            'scan.store_response_body_preview' => ['value' => true, 'type' => 'boolean', 'group' => 'snapshots', 'description' => 'Store response body previews.'],
            'snapshots.auto_baseline_after_successful_scan' => ['value' => false, 'type' => 'boolean', 'group' => 'snapshots', 'description' => 'Automatic baseline creation policy after successful scans.'],
            'snapshots.auto_snapshot_only_if_assertions_pass' => ['value' => true, 'type' => 'boolean', 'group' => 'snapshots', 'description' => 'Only create automatic snapshots when assertions pass.'],
            'snapshots.auto_snapshot_allow_warnings' => ['value' => true, 'type' => 'boolean', 'group' => 'snapshots', 'description' => 'Allow automatic snapshots when only warnings exist.'],

            // Reports and exports
            'exports.include_endpoint_details' => ['value' => true, 'type' => 'boolean', 'group' => 'exports', 'description' => 'Include endpoint details in reports.'],
            'exports.include_timestamps' => ['value' => true, 'type' => 'boolean', 'group' => 'exports', 'description' => 'Include timestamps in reports.'],
            'report.default_type' => ['value' => 'technical', 'type' => 'string', 'group' => 'exports', 'options' => ['technical', 'executive', 'release_readiness', 'evidence'], 'description' => 'Default report style.'],
            'report.include_executive_summary' => ['value' => true, 'type' => 'boolean', 'group' => 'exports', 'description' => 'Include executive summary sections.'],
            'report.include_qa_evidence' => ['value' => true, 'type' => 'boolean', 'group' => 'exports', 'description' => 'Include QA evidence sections.'],
            'report.include_release_readiness' => ['value' => true, 'type' => 'boolean', 'group' => 'exports', 'description' => 'Include release readiness sections.'],
            'report.include_failed_endpoints_only' => ['value' => false, 'type' => 'boolean', 'group' => 'exports', 'description' => 'Export failed endpoints only by default.'],
            'report.include_copyright_footer' => ['value' => true, 'type' => 'boolean', 'group' => 'exports', 'description' => 'Include copyright footer in generated reports.'],

            // Dashboard and UI
            'ui.compact_dashboard' => ['value' => false, 'type' => 'boolean', 'group' => 'ui', 'description' => 'Enable compact dashboard rendering classes.'],
            'ui.show_header_logo' => ['value' => true, 'type' => 'boolean', 'group' => 'ui', 'description' => 'Show the Aptoria logo in the header/sidebar.'],
            'ui.show_scan_summary_cards' => ['value' => true, 'type' => 'boolean', 'group' => 'ui', 'description' => 'Show scan summary cards where available.'],
            'ui.default_sidebar_state' => ['value' => 'expanded', 'type' => 'string', 'group' => 'ui', 'options' => ['expanded', 'collapsed'], 'description' => 'Preferred sidebar state.'],
            'ui.enable_sweetalert' => ['value' => true, 'type' => 'boolean', 'group' => 'ui', 'description' => 'Enable SweetAlert confirmations and flash messages.'],
            'ui.dashboard_density' => ['value' => 'comfortable', 'type' => 'string', 'group' => 'ui', 'options' => ['comfortable', 'compact'], 'description' => 'Dashboard density class.'],
            'ui.show_dashboard_calendar_preview' => ['value' => true, 'type' => 'boolean', 'group' => 'ui', 'description' => 'Show calendar preview on dashboard where available.'],
            'ui.show_project_calendar_preview' => ['value' => true, 'type' => 'boolean', 'group' => 'ui', 'description' => 'Show project calendar preview where available.'],
            'ui.show_release_readiness_widget' => ['value' => true, 'type' => 'boolean', 'group' => 'ui', 'description' => 'Show release readiness widget where available.'],
            'ui.show_qa_evidence_widget' => ['value' => true, 'type' => 'boolean', 'group' => 'ui', 'description' => 'Show QA evidence widget where available.'],
            'ui.theme' => ['value' => 'light', 'type' => 'string', 'group' => 'ui', 'options' => ['light', 'dark', 'system'], 'description' => 'Theme preference stored for the next visual pass.'],
            'ui.table_density' => ['value' => 'comfortable', 'type' => 'string', 'group' => 'ui', 'options' => ['comfortable', 'compact'], 'description' => 'Table density preference.'],

            // Security and privacy
            'security.mask_auth_secrets' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Mask auth secrets in forms and previews.'],
            'security.hide_tokens_in_ui' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Hide tokens in UI surfaces.'],
            'security.hide_tokens_in_exports' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Hide tokens in exported reports.'],
            'security.enable_audit_log' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Enable audit log policy for audit-aware workflows.'],
            'security.session_timeout_minutes' => ['value' => 120, 'type' => 'integer', 'group' => 'security', 'min' => 5, 'max' => 1440, 'description' => 'Session timeout policy exposed to runtime and deployment checks.'],
            'scan.mask_secrets' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Mask secrets in stored response previews.'],
            'security.mask_authorization_header' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Mask Authorization headers.'],
            'security.mask_cookie_header' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Mask Cookie headers.'],
            'security.mask_set_cookie_header' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Mask Set-Cookie headers.'],
            'security.custom_sensitive_headers' => ['value' => 'x-api-key,x-auth-token,authorization,cookie,set-cookie', 'type' => 'csv', 'group' => 'security', 'description' => 'Custom sensitive header names.'],
            'security.custom_sensitive_json_fields' => ['value' => 'password,token,secret,api_key,access_token,refresh_token', 'type' => 'csv', 'group' => 'security', 'description' => 'Sensitive JSON field names masked in stored body content.'],
            'security.require_setup_lock_before_public_access' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Require setup lock before public access.'],
            'security.warn_app_debug_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Warn when APP_DEBUG is enabled.'],
            'security.warn_unknown_app_env' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Warn when APP_ENV is not an expected value.'],
            'security.audit_strict_mode' => ['value' => false, 'type' => 'boolean', 'group' => 'security', 'description' => 'Strict mode flag for security audit scoring.'],
            'security.audit_fail_on_warnings' => ['value' => false, 'type' => 'boolean', 'group' => 'security', 'description' => 'Fail security audit when warnings exist.'],

            // Release readiness gate
            'release.minimum_successful_scan_required' => ['value' => true, 'type' => 'boolean', 'group' => 'release_readiness', 'description' => 'Require at least one successful scan before release.'],
            'release.failed_assertions_block_release' => ['value' => true, 'type' => 'boolean', 'group' => 'release_readiness', 'description' => 'Failed assertions block release readiness.'],
            'release.critical_findings_block_release' => ['value' => true, 'type' => 'boolean', 'group' => 'release_readiness', 'description' => 'Critical findings block release readiness.'],
            'release.high_findings_require_review' => ['value' => true, 'type' => 'boolean', 'group' => 'release_readiness', 'description' => 'High findings require review before release.'],
            'release.regressions_block_release' => ['value' => true, 'type' => 'boolean', 'group' => 'release_readiness', 'description' => 'Regressions block release readiness.'],
            'release.security_audit_must_pass' => ['value' => true, 'type' => 'boolean', 'group' => 'release_readiness', 'description' => 'Security audit must pass before release.'],
            'release.required_evidence_before_release' => ['value' => true, 'type' => 'boolean', 'group' => 'release_readiness', 'description' => 'Require QA evidence before release.'],
            'release.required_snapshot_before_release' => ['value' => false, 'type' => 'boolean', 'group' => 'release_readiness', 'description' => 'Require a baseline snapshot before release.'],
            'release.required_report_before_release' => ['value' => true, 'type' => 'boolean', 'group' => 'release_readiness', 'description' => 'Require a generated report before release.'],
            'release.minimum_coverage_percent' => ['value' => 80, 'type' => 'integer', 'group' => 'release_readiness', 'min' => 0, 'max' => 100, 'description' => 'Minimum QA coverage percentage target.'],
        ];
    }

    /** @return array<int, string> */
    public function groups(): array
    {
        return ['general', 'scan_profiles', 'scan', 'probe_safety', 'risk_engine', 'assertions', 'snapshots', 'exports', 'ui', 'security', 'release_readiness'];
    }

    /** @return array<string, array<string, array{key: string, value: mixed, type: string, group: string, raw_value: mixed, description: string, status: string, options?: array<int, string>}>> */
    public function grouped(): array
    {
        $settings = [];

        foreach ($this->defaults() as $key => $meta) {
            if (($meta['ui'] ?? true) === false) {
                continue;
            }

            $settings[$key] = [
                'key' => $key,
                'value' => $this->get($key),
                'type' => $meta['type'],
                'group' => $meta['group'],
                'raw_value' => $this->raw($key),
                'description' => (string) ($meta['description'] ?? ''),
                'options' => $meta['options'] ?? [],
            ];
        }

        $grouped = [];
        foreach ($settings as $key => $setting) {
            $grouped[$setting['group']][$key] = $setting;
        }

        return $grouped;
    }

    /** @return array<string, string> */
    public function runtimeOnly(): array
    {
        return collect($this->defaults())
            ->filter(fn (array $meta): bool => ($meta['ui'] ?? true) === false)
            ->mapWithKeys(fn (array $meta, string $key): array => [$key => (string) ($meta['runtime_only'] ?? '')])
            ->all();
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $defaults = $this->defaults();
        $meta = $defaults[$key] ?? ['value' => $fallback, 'type' => 'string', 'group' => 'general'];
        $raw = $this->raw($key);

        if ($raw === null) {
            return $meta['value'];
        }

        return $this->cast($raw, (string) $meta['type']);
    }

    public function string(string $key, string $fallback = ''): string
    {
        return (string) $this->get($key, $fallback);
    }

    public function integer(string $key, int $fallback = 0): int
    {
        return (int) $this->get($key, $fallback);
    }

    public function boolean(string $key, bool $fallback = false): bool
    {
        return (bool) $this->get($key, $fallback);
    }

    /** @return array<int, string> */
    public function csv(string $key): array
    {
        $value = $this->get($key, '');

        if (is_array($value)) {
            return $value;
        }

        return $this->csvToArray((string) $value);
    }

    /** @param array<string, mixed> $values */
    public function updateMany(array $values): void
    {
        if (! $this->databaseAvailable()) {
            return;
        }

        foreach ($this->defaults() as $key => $meta) {
            if (! array_key_exists($key, $values)) {
                if ($meta['type'] === 'boolean') {
                    $values[$key] = false;
                } else {
                    continue;
                }
            }

            $this->set($key, $values[$key]);
        }
    }

    public function set(string $key, mixed $value): void
    {
        $defaults = $this->defaults();
        $meta = $defaults[$key] ?? ['type' => 'string', 'group' => 'general', 'description' => ''];
        $stored = $this->serialize($value, (string) $meta['type']);
        $payload = [
            'value' => $stored,
            'type' => (string) $meta['type'],
            'group' => (string) $meta['group'],
        ];

        if ($this->descriptionColumnAvailable()) {
            $payload['description'] = (string) ($meta['description'] ?? '');
        }

        Setting::query()->updateOrCreate(['key' => $key], $payload);
    }

    public function seedDefaults(): void
    {
        if (! $this->databaseAvailable()) {
            return;
        }

        foreach ($this->defaults() as $key => $meta) {
            $payload = [
                'value' => $this->serialize($meta['value'], (string) $meta['type']),
                'type' => (string) $meta['type'],
                'group' => (string) $meta['group'],
            ];

            if ($this->descriptionColumnAvailable()) {
                $payload['description'] = (string) ($meta['description'] ?? '');
            }

            Setting::query()->firstOrCreate(['key' => $key], $payload);
        }
    }

    public function resetToDefaults(): void
    {
        if (! $this->databaseAvailable()) {
            return;
        }

        foreach ($this->defaults() as $key => $meta) {
            $this->set($key, $meta['value']);
        }
    }

    public function resetGroupToDefaults(string $group): void
    {
        if (! $this->databaseAvailable()) {
            return;
        }

        foreach ($this->defaults() as $key => $meta) {
            if (($meta['group'] ?? '') === $group) {
                $this->set($key, $meta['value']);
            }
        }
    }

    /** @return array<string, mixed> */
    public function export(): array
    {
        return collect($this->defaults())
            ->mapWithKeys(fn (array $meta, string $key): array => [$key => $this->get($key)])
            ->all();
    }

    /** @return array<string, array<string, mixed>> */
    public function exportGrouped(): array
    {
        return collect($this->grouped())
            ->map(fn (array $group): array => collect($group)->mapWithKeys(fn (array $setting, string $key): array => [$key => $setting['value']])->all())
            ->all();
    }

    private function raw(string $key): mixed
    {
        if (! $this->databaseAvailable()) {
            return null;
        }

        try {
            return Setting::query()->where('key', $key)->value('value');
        } catch (Throwable) {
            return null;
        }
    }

    private function databaseAvailable(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function descriptionColumnAvailable(): bool
    {
        try {
            return Schema::hasColumn('settings', 'description');
        } catch (Throwable) {
            return false;
        }
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'csv' => $this->csvToArray((string) $value),
            default => (string) $value,
        };
    }

    private function serialize(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'boolean') {
            return $value ? '1' : '0';
        }

        if ($type === 'csv') {
            if (is_array($value)) {
                return implode(',', array_filter(array_map('trim', $value)));
            }

            return implode(',', $this->csvToArray((string) $value));
        }

        return trim((string) $value);
    }

    /** @return array<int, string> */
    private function csvToArray(string $value): array
    {
        $parts = preg_split('/[,\r\n]+/', $value) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn (string $item): string => trim(strtolower($item)),
            $parts
        ))));
    }
}
