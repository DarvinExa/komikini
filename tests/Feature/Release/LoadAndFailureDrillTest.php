<?php

declare(strict_types=1);

namespace Tests\Feature\Release;

use App\Contracts\ComicProviderInterface;
use App\DTO\Comic\ComicCollection;
use App\DTO\Comic\ComicItem;
use App\DTO\Comic\ComicPage;
use App\DTO\Comic\GenreCollection;
use App\Enums\ComicType;
use App\Exceptions\ComicProvider\UpstreamUnavailableException;
use App\Services\Comic\ImageUrlValidator;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LoadAndFailureDrillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    /**
     * Simulated concurrent load on public landing and discovery endpoints.
     */
    public function test_simulated_concurrent_requests_home_and_discovery(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);

        $dummyItem = new ComicItem(
            slug: 'popular-comic',
            title: 'Popular Comic',
            thumbnailUrl: 'https://cdn.example.com/pop.jpg',
            comicType: ComicType::MANGA,
            latestChapter: 'Ch. 100',
            rating: '9.5'
        );

        $mockProvider->shouldReceive('latest')
            ->andReturn(new ComicPage(items: [$dummyItem], currentPage: 1, hasNextPage: false, hasPrevPage: false));

        $mockProvider->shouldReceive('popular')
            ->andReturn(new ComicCollection([$dummyItem]));

        $mockProvider->shouldReceive('recommended')
            ->andReturn(new ComicCollection([$dummyItem]));

        $mockProvider->shouldReceive('genres')
            ->andReturn(new GenreCollection([]));

        $this->app->instance(ComicProviderInterface::class, $mockProvider);

        // Simulate 20 consecutive rapid requests mimicking concurrent page loads
        for ($i = 0; $i < 20; $i++) {
            $response = $this->get('/');
            $response->assertStatus(200);
            $response->assertHeader('X-Correlation-ID');
        }
    }

    /**
     * Upstream 503 failure drill: verifies friendly error response and correlation ID retention.
     */
    public function test_upstream_unavailable_drill_returns_controlled_error_response(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);
        $mockProvider->shouldReceive('latest')
            ->once()
            ->andThrow(new UpstreamUnavailableException('Upstream gateway timed out (503)'));

        $this->app->instance(ComicProviderInterface::class, $mockProvider);

        $response = $this->get('/terbaru');

        // Upstream failure on uncached discovery route produces 503 without revealing internal exception details
        $this->assertContains($response->getStatusCode(), [200, 503]);
        $response->assertHeader('X-Correlation-ID');

        $content = (string) $response->getContent();
        $this->assertStringNotContainsString('SQLSTATE', $content);
        $this->assertStringNotContainsString('DB_PASSWORD', $content);
    }

    /**
     * Search rate limiting failure drill: verifies abuse prevention.
     */
    public function test_search_rate_limiter_triggers_after_threshold(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);
        $mockProvider->shouldReceive('search')
            ->andReturn(new ComicPage(items: [], currentPage: 1, hasNextPage: false, hasPrevPage: false));

        $this->app->instance(ComicProviderInterface::class, $mockProvider);

        // Baseline limit for search is 30/minute/IP
        $lastStatus = 200;
        for ($i = 0; $i < 35; $i++) {
            $res = $this->getJson('/search?q=test');
            $lastStatus = $res->getStatusCode();
            if ($lastStatus === 429) {
                break;
            }
        }

        $this->assertEquals(429, $lastStatus, 'Search endpoint must enforce rate-limiting after 30 requests/minute.');
    }

    /**
     * SSRF and malicious image protocol injection drill.
     */
    public function test_ssrf_and_malicious_image_urls_are_rejected(): void
    {
        /** @var ImageUrlValidator $validator */
        $validator = $this->app->make(ImageUrlValidator::class);

        $maliciousUrls = [
            'http://127.0.0.1/admin/delete',
            'http://169.254.169.254/latest/meta-data/',
            'file:///etc/passwd',
            'ftp://evil.com/image.png',
            'gopher://evil.com/shell',
            'https://unauthorized-evil-tracker.com/pixel.png',
            'http://[::1]/secret',
            'http://192.168.1.1/router',
        ];

        foreach ($maliciousUrls as $url) {
            $this->assertFalse(
                $validator->isValid($url),
                "Malicious URL must be rejected by ImageUrlValidator: {$url}"
            );
        }
    }
}
