<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_browser_security_headers_are_added_without_enforcing_csp(): void
    {
        config(['app.url' => 'https://mini.leadochat.test']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );
        $response->assertHeaderMissing('Content-Security-Policy');
        $response->assertHeaderMissing('Strict-Transport-Security');
        $response->assertHeader(
            'Reporting-Endpoints',
            'csp-endpoint="https://mini.leadochat.test/csp-reports"'
        );

        $policy = (string) $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https://fonts.bunny.net", $policy);
        $this->assertStringContainsString("img-src 'self' data: blob: https:", $policy);
        $this->assertStringContainsString('report-uri /csp-reports', $policy);
        $this->assertStringContainsString('report-to csp-endpoint', $policy);
    }

    public function test_hsts_is_added_only_for_https_requests(): void
    {
        $response = $this->get('https://localhost/login');

        $response->assertOk();
        $response->assertHeader('Strict-Transport-Security', 'max-age=86400');
    }

    public function test_header_layers_can_be_disabled_for_controlled_rollback(): void
    {
        config([
            'security.csp_report_only.enabled' => false,
            'security.hsts.enabled' => false,
        ]);

        $response = $this->get('https://localhost/login');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
        $response->assertHeaderMissing('Reporting-Endpoints');
        $response->assertHeaderMissing('Strict-Transport-Security');

        config(['security.headers_enabled' => false]);

        $this->get('/login')
            ->assertOk()
            ->assertHeaderMissing('X-Content-Type-Options')
            ->assertHeaderMissing('X-Frame-Options');
    }

    public function test_legacy_csp_report_is_accepted_without_csrf_and_sensitive_url_parts_are_removed(): void
    {
        Log::spy();

        $payload = json_encode([
            'csp-report' => [
                'document-uri' => 'https://mini.leadochat.test/inbox/42?signature=secret#message',
                'blocked-uri' => 'https://evil.example/script.js?token=secret',
                'effective-directive' => 'script-src-elem',
                'disposition' => 'report',
                'status-code' => 200,
                'script-sample' => 'secret sample must not be logged',
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            '/csp-reports',
            server: ['CONTENT_TYPE' => 'application/csp-report'],
            content: $payload
        );

        $response->assertNoContent();
        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
            $encoded = json_encode($context, JSON_THROW_ON_ERROR);

            return $message === 'Content Security Policy violation reported.'
                && ($context['document_uri'] ?? null) === 'https://mini.leadochat.test/inbox/42'
                && ($context['blocked_uri'] ?? null) === 'https://evil.example/script.js'
                && ($context['effective_directive'] ?? null) === 'script-src-elem'
                && ! str_contains($encoded, 'secret')
                && ! str_contains($encoded, 'script-sample');
        });
    }

    public function test_reporting_api_batch_is_normalized_and_the_route_is_rate_limited(): void
    {
        Log::spy();

        $response = $this->postJson('/csp-reports', [[
            'type' => 'csp-violation',
            'url' => 'https://mini.leadochat.test/settings?temporary=secret',
            'body' => [
                'blockedURL' => 'inline',
                'documentURL' => 'https://mini.leadochat.test/settings?temporary=secret',
                'effectiveDirective' => 'style-src-attr',
                'disposition' => 'report',
                'statusCode' => 200,
            ],
        ]]);

        $response->assertNoContent();
        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
            return $message === 'Content Security Policy violation reported.'
                && ($context['document_uri'] ?? null) === 'https://mini.leadochat.test/settings'
                && ($context['blocked_uri'] ?? null) === 'inline'
                && ($context['effective_directive'] ?? null) === 'style-src-attr';
        });

        $route = Route::getRoutes()->getByName('csp.report');

        $this->assertNotNull($route);
        $this->assertContains('throttle:60,1', $route->gatherMiddleware());
    }

    public function test_csp_report_receiver_rejects_invalid_or_oversized_payloads_without_logging(): void
    {
        Log::spy();

        $this->call(
            'POST',
            '/csp-reports',
            server: ['CONTENT_TYPE' => 'application/csp-report'],
            content: '{invalid'
        )->assertStatus(400);

        $this->call(
            'POST',
            '/csp-reports',
            server: ['CONTENT_TYPE' => 'application/csp-report'],
            content: json_encode(['padding' => str_repeat('x', 65536)], JSON_THROW_ON_ERROR)
        )->assertStatus(413);

        Log::shouldNotHaveReceived('warning');
    }
}
