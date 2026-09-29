<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_standard_web_response_contains_hardened_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        // 1. Content-Security-Policy baseline
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp, 'CSP header must be present.');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp, 'CSP must not allow unsafe-eval.');

        // 2. Anti-Clickjacking
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));

        // 3. Anti-MIME-Sniffing
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));

        // 4. Referrer Policy
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));

        // 5. Modern XSS filter deprecation
        $this->assertEquals('0', $response->headers->get('X-XSS-Protection'));

        // 6. Permissions Policy
        $permissionsPolicy = $response->headers->get('Permissions-Policy');
        $this->assertNotNull($permissionsPolicy);
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);

        // 7. Correlation ID
        $this->assertNotNull($response->headers->get('X-Correlation-ID'));
    }

    public function test_hsts_is_sent_when_request_is_over_https(): void
    {
        $response = $this->get('/', [
            'HTTPS' => 'on',
            'X-Forwarded-Proto' => 'https',
        ]);

        $response->assertOk();
        $hsts = $response->headers->get('Strict-Transport-Security');
        $this->assertNotNull($hsts);
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
    }

    public function test_error_responses_retain_security_headers_and_correlation_id(): void
    {
        $response = $this->get('/non-existent-page-404-check');

        $response->assertNotFound();
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertNotNull($response->headers->get('X-Correlation-ID'));
    }
}
