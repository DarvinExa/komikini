<?php

declare(strict_types=1);

namespace Tests\Feature\Comic;

use App\Contracts\ComicProviderInterface;
use App\DTO\Comic\ChapterPayload;
use App\DTO\Comic\ComicCollection;
use App\DTO\Comic\ComicDetail;
use App\DTO\Comic\ComicPage;
use App\DTO\Comic\GenreCollection;
use App\Enums\ComicType;
use App\Exceptions\ComicProvider\ChapterNotFoundException;
use App\Exceptions\ComicProvider\ComicNotFoundException;
use App\Exceptions\ComicProvider\MalformedUpstreamResponseException;
use App\Exceptions\ComicProvider\UpstreamRateLimitedException;
use App\Exceptions\ComicProvider\UpstreamTimeoutException;
use App\Exceptions\ComicProvider\UpstreamUnavailableException;
use App\Services\Comic\KomikuProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KomikuProviderContractTest extends TestCase
{
    protected ComicProviderInterface $provider;

    protected string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = (string) config('comic.komiku.base_url', 'https://komiku-rest-api.vercel.app');
        $this->provider = $this->app->make(KomikuProvider::class);
    }

    public function test_recommended_returns_comic_collection_from_fixture(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/rekomendasi.json'));
        Http::fake([
            "{$this->baseUrl}/rekomendasi" => Http::response($fixture, 200),
        ]);

        $result = $this->provider->recommended();

        $this->assertInstanceOf(ComicCollection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame('one-piece', $result->items[0]->slug);
        $this->assertSame(ComicType::MANGA, $result->items[0]->comicType);
        $this->assertSame('solo-leveling-ragnarok', $result->items[1]->slug);
        $this->assertSame(ComicType::MANHWA, $result->items[1]->comicType);
    }

    public function test_latest_returns_comic_page_from_fixture(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/terbaru.json'));
        Http::fake([
            "{$this->baseUrl}/terbaru" => Http::response($fixture, 200),
        ]);

        $result = $this->provider->latest(1);

        $this->assertInstanceOf(ComicPage::class, $result);
        $this->assertCount(2, $result->items);
        $this->assertSame('jujutsu-kaisen', $result->items[0]->slug);
        $this->assertTrue($result->hasNextPage);
        $this->assertFalse($result->hasPrevPage);
        $this->assertSame(50, $result->totalPages);
    }

    public function test_popular_returns_comic_collection(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/populer.json'));
        Http::fake([
            "{$this->baseUrl}/komik-populer/manhua" => Http::response($fixture, 200),
        ]);

        $result = $this->provider->popular(ComicType::MANHUA);

        $this->assertInstanceOf(ComicCollection::class, $result);
        $this->assertCount(1, $result);
        $this->assertSame('martial-peak', $result->items[0]->slug);
        $this->assertSame(ComicType::MANHUA, $result->items[0]->comicType);
    }

    public function test_genres_returns_genre_collection(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/genre_all.json'));
        Http::fake([
            "{$this->baseUrl}/genre-all" => Http::response($fixture, 200),
        ]);

        $result = $this->provider->genres();

        $this->assertInstanceOf(GenreCollection::class, $result);
        $this->assertCount(4, $result);
        $this->assertSame('action', $result->items[0]->slug);
        $this->assertSame('Action', $result->items[0]->name);
    }

    public function test_search_and_by_genre_return_comic_pages(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/terbaru.json'));
        Http::fake([
            "{$this->baseUrl}/search*" => Http::response($fixture, 200),
            "{$this->baseUrl}/genre/action" => Http::response($fixture, 200),
        ]);

        $searchResult = $this->provider->search('jujutsu');
        $this->assertInstanceOf(ComicPage::class, $searchResult);

        $genreResult = $this->provider->byGenre('action');
        $this->assertInstanceOf(ComicPage::class, $genreResult);
    }

    public function test_detail_complete_contract(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/detail_complete.json'));
        Http::fake([
            "{$this->baseUrl}/detail-komik/one-piece" => Http::response($fixture, 200),
        ]);

        $detail = $this->provider->detail('one-piece');

        $this->assertInstanceOf(ComicDetail::class, $detail);
        $this->assertSame('one-piece', $detail->slug);
        $this->assertSame('One Piece', $detail->title);
        $this->assertSame('Wan Pīsu', $detail->alternativeTitle);
        $this->assertSame('Eiichiro Oda', $detail->author);
        $this->assertSame('Ongoing', $detail->publicationStatus);
        $this->assertCount(3, $detail->genres);
        $this->assertCount(3, $detail->chapters);
        $this->assertSame('chapter-1110', $detail->latestChapter?->chapterKey);
        $this->assertSame('chapter-1', $detail->firstChapter?->chapterKey);
    }

    public function test_detail_missing_optional_fields_does_not_crash(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/detail_missing_optional.json'));
        Http::fake([
            "{$this->baseUrl}/detail-komik/mystery-comic" => Http::response($fixture, 200),
        ]);

        $detail = $this->provider->detail('mystery-comic');

        $this->assertInstanceOf(ComicDetail::class, $detail);
        $this->assertSame('mystery-comic', $detail->slug);
        $this->assertSame('Mystery Comic', $detail->title);
        $this->assertNull($detail->alternativeTitle);
        $this->assertNull($detail->author);
        $this->assertNull($detail->synopsis);
        $this->assertEmpty($detail->genres);
        $this->assertEmpty($detail->chapters);
        $this->assertNull($detail->firstChapter);
        $this->assertNull($detail->latestChapter);
    }

    public function test_chapter_complete_contract(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/chapter_complete.json'));
        Http::fake([
            "{$this->baseUrl}/baca-chapter/one-piece/chapter-1109" => Http::response($fixture, 200),
        ]);

        $chapter = $this->provider->chapter('one-piece', 'chapter-1109');

        $this->assertInstanceOf(ChapterPayload::class, $chapter);
        $this->assertSame('one-piece', $chapter->comicSlug);
        $this->assertSame('chapter-1109', $chapter->chapterKey);
        $this->assertSame('1109', $chapter->chapterNumber);
        $this->assertCount(3, $chapter->images);
        $this->assertSame('chapter-1108', $chapter->prevChapterKey);
        $this->assertSame('chapter-1110', $chapter->nextChapterKey);
    }

    public function test_chapter_null_navigation_contract(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/chapter_null_navigation.json'));
        Http::fake([
            "{$this->baseUrl}/baca-chapter/oneshot/chapter-1" => Http::response($fixture, 200),
        ]);

        $chapter = $this->provider->chapter('oneshot', 'chapter-1');

        $this->assertNull($chapter->prevChapterKey);
        $this->assertNull($chapter->nextChapterKey);
    }

    public function test_chapter_filters_out_unallowed_hosts_and_private_ips(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/chapter_unallowed_hosts.json'));
        Http::fake([
            "{$this->baseUrl}/baca-chapter/untrusted/chapter-1" => Http::response($fixture, 200),
        ]);

        $chapter = $this->provider->chapter('untrusted', 'chapter-1');

        // Only the valid https://img.komiku.id image should remain
        $this->assertCount(1, $chapter->images);
        $this->assertSame('https://img.komiku.id/uploads/valid-page.jpg', $chapter->images[0]);
    }

    public function test_detail_404_throws_comic_not_found(): void
    {
        Http::fake([
            "{$this->baseUrl}/detail-komik/not-found" => Http::response([], 404),
        ]);

        $this->expectException(ComicNotFoundException::class);
        $this->provider->detail('not-found');
    }

    public function test_chapter_404_throws_chapter_not_found(): void
    {
        Http::fake([
            "{$this->baseUrl}/baca-chapter/one-piece/9999" => Http::response([], 404),
        ]);

        $this->expectException(ChapterNotFoundException::class);
        $this->provider->chapter('one-piece', '9999');
    }

    public function test_upstream_429_throws_rate_limited(): void
    {
        Http::fake([
            "{$this->baseUrl}/*" => Http::response('Too Many Requests', 429),
        ]);

        $this->expectException(UpstreamRateLimitedException::class);
        $this->provider->recommended();
    }

    public function test_upstream_500_throws_unavailable(): void
    {
        Http::fake([
            "{$this->baseUrl}/*" => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(UpstreamUnavailableException::class);
        $this->provider->latest();
    }

    public function test_malformed_json_throws_malformed_exception(): void
    {
        Http::fake([
            "{$this->baseUrl}/*" => Http::response('<html>Not JSON</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $this->expectException(MalformedUpstreamResponseException::class);
        $this->provider->recommended();
    }

    public function test_upstream_connection_timeout_throws_timeout_exception(): void
    {
        Http::fake([
            "{$this->baseUrl}/*" => fn () => throw new ConnectionException('cURL error 28: Operation timed out after 8000 milliseconds'),
        ]);

        $this->expectException(UpstreamTimeoutException::class);
        $this->provider->recommended();
    }
}
