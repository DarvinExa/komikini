<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Contracts\ComicProviderInterface;
use App\DTO\Comic\ComicPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class OperationsAndHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_liveness_endpoint_returns_ok_status(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_health_ready_returns_200_when_subsystems_healthy(): void
    {
        $response = $this->getJson('/health/ready');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
        ]);
        $response->assertJsonStructure([
            'status',
            'timestamp',
        ]);

        // Detail leakage check: verify no internal secrets or credentials leaked
        $content = $response->getContent();
        $this->assertStringNotContainsString('password', (string) $content);
        $this->assertStringNotContainsString('DB_HOST', (string) $content);
        $this->assertStringNotContainsString('sqlite', (string) $content);
        $this->assertStringNotContainsString('pgsql', (string) $content);
        $this->assertStringNotContainsString('root', (string) $content);
    }

    public function test_health_ready_returns_503_degraded_without_leaking_details_when_db_fails(): void
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \PDOException('SQLSTATE[08006] [7] could not connect to server: Connection refused at /var/secret'));

        $response = $this->getJson('/health/ready');

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'degraded',
        ]);

        $content = $response->getContent();
        $this->assertStringNotContainsString('SQLSTATE', (string) $content);
        $this->assertStringNotContainsString('could not connect to server', (string) $content);
        $this->assertStringNotContainsString('secret', (string) $content);
    }

    public function test_health_ready_with_auth_header_returns_service_breakdown(): void
    {
        Config::set('services.health.secret', 'secret-health-token-xyz');

        $response = $this->withHeader('X-Health-Key', 'secret-health-token-xyz')
            ->getJson('/health/ready');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'checks' => [
                'database' => 'ok',
                'cache' => 'ok',
                'storage' => 'ok',
            ],
        ]);
    }

    public function test_health_upstream_checks_provider_connectivity(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);
        $dummyPage = new ComicPage(
            items: [],
            currentPage: 1,
            hasNextPage: false,
            hasPrevPage: false
        );

        $mockProvider->shouldReceive('latest')
            ->once()
            ->with(1)
            ->andReturn($dummyPage);

        $this->app->instance(ComicProviderInterface::class, $mockProvider);

        $response = $this->getJson('/health/upstream');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
        ]);
    }

    public function test_health_upstream_failure_does_not_break_health_ready(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);
        $mockProvider->shouldReceive('latest')
            ->once()
            ->with(1)
            ->andThrow(new \RuntimeException('Upstream connection timeout'));

        $this->app->instance(ComicProviderInterface::class, $mockProvider);

        // Upstream check returns degraded
        $upstreamResponse = $this->getJson('/health/upstream');
        $upstreamResponse->assertStatus(503);
        $upstreamResponse->assertJson([
            'status' => 'degraded',
        ]);

        // But app readiness remains 200 OK because upstream health is isolated per OBSERVABILITY.md
        $readyResponse = $this->getJson('/health/ready');
        $readyResponse->assertStatus(200);
        $readyResponse->assertJson(['status' => 'ok']);
    }

    public function test_telemetry_middleware_records_correlation_id_and_status(): void
    {
        $correlationId = 'test-corr-id-12345678';

        $response = $this->withHeader('X-Correlation-ID', $correlationId)
            ->getJson('/health/ready');

        $response->assertStatus(200);
        $response->assertHeader('X-Correlation-ID', $correlationId);
    }

    public function test_sentry_configuration_is_present_with_safe_defaults(): void
    {
        $config = Config::get('sentry');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dsn', $config);
        $this->assertArrayHasKey('send_default_pii', $config);
        $this->assertFalse($config['send_default_pii']);
        $this->assertFalse($config['breadcrumbs']['sql_bindings']);
    }
}
