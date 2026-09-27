<?php

declare(strict_types=1);

namespace Tests\Feature\Comic;

use App\Contracts\ComicProviderInterface;
use App\DTO\Comic\ComicDetail;
use App\DTO\Comic\ComicPage;
use App\Enums\ComicType;
use App\Exceptions\ComicProvider\UpstreamTimeoutException;
use App\Exceptions\ComicProvider\UpstreamUnavailableException;
use App\Services\Comic\CachedComicProvider;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CachedComicProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    public function test_serves_from_fresh_cache_without_calling_provider(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);
        $mockProvider->shouldNotReceive('detail');

        $cachedProvider = new CachedComicProvider($mockProvider);

        $cachedData = [
            'slug' => 'cached-slug',
            'title' => 'Cached Title',
            'thumbnail_url' => 'https://img.komiku.id/cached.jpg',
            'comic_type' => 'manga',
            'genres' => [],
            'chapters' => [],
        ];

        Cache::put('v1:comic:detail:cached-slug:fresh', $cachedData, 300);

        $result = $cachedProvider->detail('cached-slug');

        $this->assertInstanceOf(ComicDetail::class, $result);
        $this->assertSame('cached-slug', $result->slug);
        $this->assertSame('Cached Title', $result->title);
    }

    public function test_stores_fresh_and_stale_cache_on_successful_upstream(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);

        $detail = new ComicDetail(
            slug: 'upstream-slug',
            title: 'Upstream Comic',
            comicType: ComicType::MANHWA,
        );

        $mockProvider->shouldReceive('detail')
            ->once()
            ->with('upstream-slug')
            ->andReturn($detail);

        $cachedProvider = new CachedComicProvider($mockProvider);
        $result = $cachedProvider->detail('upstream-slug');

        $this->assertSame('Upstream Comic', $result->title);
        $this->assertTrue(Cache::has('v1:comic:detail:upstream-slug:fresh'));
        $this->assertTrue(Cache::has('v1:comic:detail:upstream-slug:stale'));
    }

    public function test_serves_stale_cache_when_upstream_times_out(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);

        // Simulate upstream timeout on call
        $mockProvider->shouldReceive('latest')
            ->once()
            ->with(1)
            ->andThrow(new UpstreamTimeoutException('Upstream timeout'));

        $cachedProvider = new CachedComicProvider($mockProvider);

        $stalePage = [
            'items' => [
                [
                    'slug' => 'stale-one-piece',
                    'title' => 'Stale One Piece',
                    'comic_type' => 'manga',
                ],
            ],
            'current_page' => 1,
            'has_next_page' => true,
            'has_prev_page' => false,
        ];

        // Seed stale cache, but leave fresh cache empty
        Cache::put('v1:comic:latest:1:stale', $stalePage, 3600);

        $result = $cachedProvider->latest(1);

        $this->assertInstanceOf(ComicPage::class, $result);
        $this->assertCount(1, $result->items);
        $this->assertSame('stale-one-piece', $result->items[0]->slug);
    }

    public function test_rethrows_exception_when_upstream_fails_and_no_stale_cache_exists(): void
    {
        $mockProvider = Mockery::mock(ComicProviderInterface::class);
        $mockProvider->shouldReceive('latest')
            ->once()
            ->with(1)
            ->andThrow(new UpstreamUnavailableException('500 error'));

        $cachedProvider = new CachedComicProvider($mockProvider);

        $this->expectException(UpstreamUnavailableException::class);
        $cachedProvider->latest(1);
    }
}
