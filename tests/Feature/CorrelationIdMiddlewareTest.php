<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class CorrelationIdMiddlewareTest extends TestCase
{
    public function test_request_without_correlation_id_receives_generated_uuid(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Correlation-ID');

        $correlationId = $response->headers->get('X-Correlation-ID');
        $this->assertNotNull($correlationId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $correlationId
        );
    }

    public function test_request_with_valid_correlation_id_preserves_it(): void
    {
        $customId = 'test-corr-id-12345678';
        $response = $this->withHeaders([
            'X-Correlation-ID' => $customId,
        ])->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Correlation-ID', $customId);
    }

    public function test_request_with_invalid_correlation_id_replaces_with_uuid(): void
    {
        $maliciousId = '<script>alert("xss")</script>';
        $response = $this->withHeaders([
            'X-Correlation-ID' => $maliciousId,
        ])->get('/');

        $response->assertStatus(200);
        $correlationId = $response->headers->get('X-Correlation-ID');
        $this->assertNotEquals($maliciousId, $correlationId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            (string) $correlationId
        );
    }
}
