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
use App\Exceptions\ComicProvider\ChapterNotFoundException;
use App\Exceptions\ComicProvider\ComicNotFoundException;
use App\Exceptions\ComicProvider\ComicProviderException;
use App\Exceptions\ComicProvider\MalformedUpstreamResponseException;
use App\Exceptions\ComicProvider\UpstreamRateLimitedException;
use App\Exceptions\ComicProvider\UpstreamTimeoutException;
use App\Exceptions\ComicProvider\UpstreamUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class KomikuProvider implements ComicProviderInterface
{
    protected string $baseUrl;

    protected int $connectTimeout;

    protected int $timeout;

    protected int $maxRetries;

    protected int $retryDelayMs;

    public function __construct(
        protected KomikuResponseMapper $mapper,
        ?string $baseUrl = null,
        ?int $connectTimeout = null,
        ?int $timeout = null,
        ?int $maxRetries = null,
        ?int $retryDelayMs = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('comic.komiku.base_url', 'https://komiku-rest-api.vercel.app'), '/');
        $this->connectTimeout = $connectTimeout ?? (int) config('comic.komiku.connect_timeout', 3);
        $this->timeout = $timeout ?? (int) config('comic.komiku.timeout', 8);
        $this->maxRetries = $maxRetries ?? (int) config('comic.komiku.max_retries', 2);
        $this->retryDelayMs = $retryDelayMs ?? (int) config('comic.komiku.retry_delay_ms', 100);
    }

    public function latest(int $page = 1): ComicPage
    {
        $endpoint = $page > 1 ? "/pustaka/page/{$page}" : '/terbaru';
        $data = $this->get($endpoint);

        return $this->mapper->mapComicPage($data, $page);
    }

    public function recommended(): ComicCollection
    {
        $data = $this->get('/rekomendasi');

        return $this->mapper->mapComicCollection($data);
    }

    public function popular(?ComicType $type = null): ComicCollection
    {
        $endpoint = ($type !== null && $type !== ComicType::UNKNOWN)
            ? "/komik-populer/{$type->value}"
            : '/komik-populer';

        $data = $this->get($endpoint);

        return $this->mapper->mapComicCollection($data);
    }

    public function search(string $query, int $page = 1): ComicPage
    {
        $query = trim($query);
        if ($query === '') {
            return new ComicPage(items: [], currentPage: $page);
        }

        $endpoint = '/search';
        $data = $this->get($endpoint, ['q' => $query, 'page' => $page]);

        return $this->mapper->mapComicPage($data, $page);
    }

    public function genres(): GenreCollection
    {
        $data = $this->get('/genre-all');

        return $this->mapper->mapGenres($data);
    }

    public function byGenre(string $slug, int $page = 1): ComicPage
    {
        $slug = trim($slug, '/');
        $endpoint = $page > 1 ? "/genre/{$slug}/page/{$page}" : "/genre/{$slug}";
        $data = $this->get($endpoint);

        return $this->mapper->mapComicPage($data, $page);
    }

    public function detail(string $slug): ComicDetail
    {
        $slug = trim($slug, '/');
        $data = $this->get("/detail-komik/{$slug}");

        return $this->mapper->mapComicDetail($data, $slug);
    }

    public function chapter(string $slug, string $chapter): ChapterPayload
    {
        $slug = trim($slug, '/');
        $chapter = trim($chapter, '/');
        $data = $this->get("/baca-chapter/{$slug}/{$chapter}");

        return $this->mapper->mapChapterPayload($data, $slug, $chapter);
    }

    /**
     * Perform HTTP GET request to upstream with resilience (timeout, retry, error mapping).
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|list<mixed>
     */
    protected function get(string $endpoint, array $query = []): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->retry(
                    $this->maxRetries,
                    $this->retryDelayMs,
                    function (Throwable $exception): bool {
                        if ($exception instanceof ConnectionException) {
                            return true;
                        }

                        if ($exception instanceof RequestException) {
                            $status = $exception->response->status();

                            return $status === 408 || $status === 429 || ($status >= 500 && $status <= 599);
                        }

                        return false;
                    },
                    throw: false
                )
                ->get($endpoint, $query);

            if ($response->status() === 404) {
                if (str_starts_with($endpoint, '/detail-komik/')) {
                    throw new ComicNotFoundException("Komik '{$endpoint}' tidak ditemukan.");
                }
                if (str_starts_with($endpoint, '/baca-chapter/')) {
                    throw new ChapterNotFoundException("Chapter '{$endpoint}' tidak ditemukan.");
                }

                throw new ComicNotFoundException("Resource '{$endpoint}' tidak ditemukan.");
            }

            if ($response->status() === 429) {
                throw new UpstreamRateLimitedException("Upstream rate limited pada endpoint '{$endpoint}'.");
            }

            if ($response->status() >= 500) {
                throw new UpstreamUnavailableException("Upstream mengembalikan error status {$response->status()} pada endpoint '{$endpoint}'.");
            }

            if (! $response->successful()) {
                throw new UpstreamUnavailableException("Upstream mengembalikan HTTP status {$response->status()} pada endpoint '{$endpoint}'.");
            }

            $json = $response->json();
            if (! is_array($json)) {
                throw new MalformedUpstreamResponseException("Respons upstream pada endpoint '{$endpoint}' bukan format JSON array yang valid.");
            }

            return $json;
        } catch (ComicProviderException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'timed out') || str_contains($message, 'timeout') || str_contains($message, 'timed_out')) {
                throw new UpstreamTimeoutException("Timeout saat menghubungi upstream pada '{$endpoint}': {$e->getMessage()}", previous: $e);
            }

            throw new UpstreamUnavailableException("Koneksi ke upstream gagal pada '{$endpoint}': {$e->getMessage()}", previous: $e);
        } catch (Throwable $e) {
            throw new UpstreamUnavailableException("Kesalahan tidak terduga saat menghubungi upstream: {$e->getMessage()}", previous: $e);
        }
    }
}
