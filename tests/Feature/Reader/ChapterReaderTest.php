<?php

declare(strict_types=1);

namespace Tests\Feature\Reader;

use App\Jobs\RecordQualifiedView;
use App\Models\Comic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChapterReaderTest extends TestCase
{
    use RefreshDatabase;

    protected string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = (string) config('comic.komiku.base_url', 'https://komiku-rest-api.vercel.app');
    }

    public function test_reader_page_renders_with_chapter_payload_and_navigation(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/chapter_complete.json'));

        Http::fake([
            "{$this->baseUrl}/baca-chapter/one-piece/chapter-1109" => Http::response($fixture, 200),
        ]);

        $comic = Comic::create([
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
        ]);

        $response = $this->get('/komik/one-piece/chapter-1109');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Reader')
            ->where('comic.slug', 'one-piece')
            ->where('comic.title', 'One Piece')
            ->where('chapter.chapter_key', 'chapter-1109')
            ->where('chapter.prev_chapter_key', 'chapter-1108')
            ->where('chapter.next_chapter_key', 'chapter-1110')
            ->has('chapter.images', 3)
            ->where('chapter.images.0', 'https://img.komiku.id/uploads/op-1109-01.jpg')
        );

        // Assert view event was recorded in DB
        $this->assertDatabaseHas('comic_view_events', [
            'comic_id' => $comic->id,
            'chapter_key' => 'chapter-1109',
            'qualified' => true,
        ]);
    }

    public function test_reader_page_dispatches_record_qualified_view_job(): void
    {
        Queue::fake([RecordQualifiedView::class]);

        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/chapter_complete.json'));

        Http::fake([
            "{$this->baseUrl}/baca-chapter/one-piece/chapter-1109" => Http::response($fixture, 200),
        ]);

        $response = $this->get('/komik/one-piece/chapter-1109');

        $response->assertStatus(200);
        Queue::assertPushed(RecordQualifiedView::class, function (RecordQualifiedView $job) {
            return $job->chapterKey === 'chapter-1109' && $job->visitorHash !== null;
        });
    }

    public function test_reader_filters_out_malicious_and_unallowed_image_hosts(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/chapter_unallowed_hosts.json'));

        Http::fake([
            "{$this->baseUrl}/baca-chapter/one-piece/chapter-1109" => Http::response($fixture, 200),
        ]);

        $response = $this->get('/komik/one-piece/chapter-1109');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Reader')
            ->has('chapter.images', 1)
            ->where('chapter.images.0', 'https://img.komiku.id/uploads/valid-page.jpg')
        );
    }

    public function test_reader_handles_null_navigation_gracefully(): void
    {
        $fixture = file_get_contents(base_path('tests/Fixtures/Komiku/chapter_null_navigation.json'));

        Http::fake([
            "{$this->baseUrl}/baca-chapter/one-piece/chapter-1" => Http::response($fixture, 200),
        ]);

        $response = $this->get('/komik/one-piece/chapter-1');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Reader')
            ->where('chapter.prev_chapter_key', null)
            ->where('chapter.next_chapter_key', null)
        );
    }

    public function test_reader_returns_404_when_chapter_not_found(): void
    {
        Http::fake([
            "{$this->baseUrl}/baca-chapter/one-piece/chapter-9999" => Http::response(['message' => 'Not Found'], 404),
        ]);

        $response = $this->get('/komik/one-piece/chapter-9999');

        $response->assertStatus(404);
    }

    public function test_reader_returns_503_when_upstream_unavailable_without_cache(): void
    {
        Http::fake([
            "{$this->baseUrl}/baca-chapter/one-piece/chapter-1109" => Http::response(null, 500),
        ]);

        $response = $this->get('/komik/one-piece/chapter-1109');

        $response->assertStatus(503);
    }
}
