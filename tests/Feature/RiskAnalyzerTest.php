<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\ScanResult;
use App\Services\Risk\RiskAnalyzer;
use Tests\TestCase;

class RiskAnalyzerTest extends TestCase
{
    public function test_basic_scoring_keeps_a_documented_low_risk_endpoint_low(): void
    {
        $endpoint = new Endpoint([
            'method' => Endpoint::METHOD_GET,
            'path' => '/health',
            'risk_level' => Endpoint::RISK_LOW,
            'auth_required' => false,
            'expected_status' => 200,
            'expected_content_type' => 'application/json',
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $analysis = app(RiskAnalyzer::class)->analyze($endpoint);

        $this->assertSame(Endpoint::RISK_LOW, $analysis['calculated_level']);
        $this->assertSame(Endpoint::RISK_LOW, $analysis['final_level']);
        $this->assertSame(0, $analysis['score']);
        $this->assertSame([], $analysis['signals']);
    }

    public function test_sensitive_public_endpoint_signal_is_detected(): void
    {
        $endpoint = new Endpoint([
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/1',
            'risk_level' => Endpoint::RISK_LOW,
            'auth_required' => false,
            'expected_status' => 200,
            'expected_content_type' => 'application/json',
        ]);

        $analysis = app(RiskAnalyzer::class)->analyze($endpoint);

        $this->assertContains('public_sensitive_endpoint', array_column($analysis['signals'], 'key'));
        $this->assertSame(Endpoint::RISK_HIGH, $analysis['calculated_level']);
        $this->assertGreaterThanOrEqual(35, $analysis['score']);
    }

    public function test_5xx_response_signal_is_detected(): void
    {
        $endpoint = new Endpoint([
            'method' => Endpoint::METHOD_GET,
            'path' => '/health',
            'risk_level' => Endpoint::RISK_LOW,
            'auth_required' => false,
            'expected_status' => 200,
            'expected_content_type' => 'application/json',
        ]);
        $result = new ScanResult([
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 500,
            'content_type' => 'application/json',
            'response_time_ms' => 100,
        ]);

        $analysis = app(RiskAnalyzer::class)->analyze($endpoint, $result);

        $this->assertContains('unexpected_server_error', array_column($analysis['signals'], 'key'));
        $this->assertContains('unexpected_status_code', array_column($analysis['signals'], 'key'));
        $this->assertSame(Endpoint::RISK_HIGH, $analysis['final_level']);
    }

    public function test_destructive_method_skipped_signal_is_detected(): void
    {
        $endpoint = new Endpoint([
            'method' => Endpoint::METHOD_POST,
            'path' => '/orders',
            'risk_level' => Endpoint::RISK_LOW,
            'auth_required' => true,
            'expected_status' => 201,
            'expected_content_type' => 'application/json',
        ]);
        $result = new ScanResult([
            'status' => ScanResult::STATUS_SKIPPED,
        ]);

        $analysis = app(RiskAnalyzer::class)->analyze($endpoint, $result);

        $this->assertContains('destructive_method_excluded', array_column($analysis['signals'], 'key'));
        $this->assertSame(Endpoint::RISK_REVIEW, $analysis['calculated_level']);
    }
}
