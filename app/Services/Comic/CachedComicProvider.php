<?php

declare(strict_types=1);

namespace App\Services\Comic;

use App\Contracts\ComicProviderInterface;
use App\DTO\Comic\ChapterPayload;
use App\DTO\Comic\ComicCollection;
use App\DTO\Comic\ComicDetail;
use App\DTO\Comic\ComicPage;
use App\DTO\Comic\GenreCollection;
use App\Enums\ComicType;
use App\Exceptions\ComicProvider\ComicProviderException;
use App\Exceptions\ComicProvider\UpstreamRateLimitedException;
use App\Exceptions\ComicProvider\UpstreamTimeoutException;
use App\Exceptions\ComicProvider\UpstreamUnavailableException;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CachedComicProvider implements ComicProviderInterface
{
    protected string $prefix;

    public function __construct(
        protected ComicProviderInterface $provider,
        ?string $prefix = null
    ) {
        $this->prefix = $prefix ?? (string) config('comic.cache.prefix', 'v1:comic');
    }

    public function latest(int $page = 1): ComicPage
    {
        $key = "{$this->prefix}:latest:{$page}";
        $freshTtl = (int) config('comic.cache.ttl.latest.fresh', 300);
        $staleTtl = (int) config('comic.cache.ttl.latest.stale', 3600);

        $data = $this->rememberWithStale(
            $key,
            $freshTtl,
            $staleTtl,
            fn (): array => $this->provider->latest($page)->toArray()
        );

        return ComicPage::fromArray($data);
    }

    public function recommended(): ComicCollection
    {
        $key = "{$this->prefix}:recommended";
        $freshTtl = (int) config('comic.cache.ttl.recommended.fresh', 900);
        $staleTtl = (int) config('comic.cache.ttl.recommended.stale', 21600);

        $data = $this->rememberWithStale(
            $key,
            $freshTtl,
            $staleTtl,
            fn (): array => $this->provider->recommended()->toArray()
        );

        return ComicCollection::fromArray($data);
    }

    public function popular(?ComicType $type = null): ComicCollection
    {
        $typeKey = $type ? $type->value : 'all';
        $key = "{$this->prefix}:popular:{$typeKey}";
        $freshTtl = (int) config('comic.cache.ttl.popular.fresh', 900);
        $staleTtl = (int) config('comic.cache.ttl.popular.stale', 21600);

        $data = $this->rememberWithStale(
            $key,
            $freshTtl,
            $staleTtl,
            fn (): array => $this->provider->popular($type)->toArray()
        );

        return ComicCollection::fromArray($data);
    }

    public function byType(ComicType $type, int $page = 1): ComicPage
    {
        $typeKey = $type ? $type->value : 'all';
        $key = "{$this->prefix}:type:{$typeKey}:{$page}";
        $freshTtl = (int) config('comic.cache.ttl.latest.fresh', 300);
        $staleTtl = (int) config('comic.cache.ttl.latest.stale', 7200);

        $data = $this->rememberWithStale(
            $key,
            $freshTtl,
            $staleTtl,
            fn (): array => $this->provider->byType($type, $page)->toArray()
        );

        return ComicPage::fromArray($data);
    }

    public function search(string $query, int $page = 1): ComicPage
    {
        $normalizedQuery = md5(strtolower(trim($query)));
        $key = "{$this->prefix}:search:{$normalizedQuery}:{$page}";
        $freshTtl = (int) config('comic.cache.ttl.search.fresh', 600);
        $staleTtl = (int) config('comic.cache.ttl.search.stale', 3600);

        $data = $this->rememberWithStale(
            $key,
            $freshTtl,
            $staleTtl,
            fn (): array => $this->provider->search($query, $page)->toArray()
        );

        return ComicPage::fromArray($data);
    }

    public function genres(): GenreCollection
    {
        $key = "{$this->prefix}:genres";
        $freshTtl = (int) config('comic.cache.ttl.genre.fresh', 86400);
        $staleTtl = (int) config('comic.cache.ttl.genre.stale', 604800);

        $data = $this->rememberWithStale(
            $key,
            $freshTtl,
            $staleTtl,
            fn (): array => $this->provider->genres()->toArray()
        );

        return GenreCollection::fromArray($data);
    }

    public function byGenre(string $slug, int $page = 1): ComicPage
    {
        $slug = trim($slug, '/');
        $key = "{$this->prefix}:genre:{$slug}:{$page}";
        $freshTtl = (int) config('comic.cache.ttl.genre.fresh', 86400);
        $staleTtl = (int) config('comic.cache.ttl.genre.stale', 604800);

        $data = $this->rememberWithStale(
            $key,
            $freshTtl,
            $staleTtl,
            fn (): array => $this->provider->byGenre($slug, $page)->toArray()
        );

        return ComicPage::fromArray($data);
    }

    public function detail(string $slug): ComicDetail
    {
        $slug = trim($slug, '/');
        $key = "{$this->prefix}:detail:{$slug}";
        $freshTtl = (int) config('comic.cache.ttl.detail.fresh', 1800);
        $staleTtl = (int) config('comic.cache.ttl.detail.stale', 86400);

        $data = $this->rememberWithStale(
            $key,
            $freshTtl,
            $staleTtl,
            fn (): array => $this->provider->detail($slug)->toArray()
        );

        return ComicDetail::fromArray($data);
    }

    public function chapter(string $slug, string $chapter): ChapterPayload
    {
        $slug = trim($slug, '/');
        $chapter = trim($chapter, '/');
        $key = "{$this->prefix}:chapter:{$slug}:{$chapter}";
        $freshTtl = (int) config('comic.cache.ttl.chapter.fresh', 21600);
        $staleTtl = (int) config('comic.cache.ttl.chapter.stale', 604800);

        $data = $this->rememberWithStale(
            $key,
            $freshTtl,
            $staleTtl,
            fn (): array => $this->provider->chapter($slug, $chapter)->toArray()
        );

        return ChapterPayload::fromArray($data);
    }

    /**
     * Stale-while-revalidate / Stale-fallback cache runner.
     *
     * @param  Closure(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    protected function rememberWithStale(string $key, int $freshTtl, int $staleTtl, Closure $callback): array
    {
        $freshKey = "{$key}:fresh";
        $staleKey = "{$key}:stale";

        // 1. Try fresh cache
        $freshData = Cache::get($freshKey);
        if (is_array($freshData)) {
            return $freshData;
        }

        // 2. Fresh cache missed: attempt upstream fetch
        try {
            $data = $callback();

            Cache::put($freshKey, $data, $freshTtl);
            Cache::put($staleKey, $data, $staleTtl);

            return $data;
        } catch (Throwable $e) {
            // Check if upstream failed with recoverable/network error
            if ($this->isRecoverableUpstreamError($e)) {
                $staleData = Cache::get($staleKey);
                if (is_array($staleData)) {
                    Log::warning("Upstream failure on key '{$key}': {$e->getMessage()}. Serving stale fallback cache.");

                    return $staleData;
                }
            }

            throw $e;
        }
    }

    /**
     * Check if the exception qualifies for stale fallback.
     */
    protected function isRecoverableUpstreamError(Throwable $e): bool
    {
        return $e instanceof UpstreamTimeoutException
            || $e instanceof UpstreamUnavailableException
            || $e instanceof UpstreamRateLimitedException
            || $e instanceof ComicProviderException;
    }
}
