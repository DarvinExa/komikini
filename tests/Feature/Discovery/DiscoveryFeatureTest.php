<?php

declare(strict_types=1);

namespace Tests\Feature\Discovery;

use App\Models\Comic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DiscoveryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = (string) config('comic.komiku.base_url', 'https://komiku-rest-api.vercel.app');
    }

    public function test_home_page_loads_with_discovery_sections(): void
    {
        $rekomendasi = file_get_contents(base_path('tests/Fixtures/Komiku/rekomendasi.json'));
        $populer = file_get_contents(base_path('tests/Fixtures/Komiku/populer.json'));
        $terbaru = file_get_contents(base_path('tests/Fixtures/Komiku/terbaru.json'));
        $genres = file_get_contents(base_path('tests/Fixtures/Komiku/genre_all.json'));

        Http::fake([
            "{$this->baseUrl}/rekomendasi" => Http::response($rekomendasi, 200),
            "{$this->baseUrl}/populer" => Http::response($populer, 200),
            "{$this->baseUrl}/terbaru" => Http::response($terbaru, 200),
            "{$this->baseUrl}/genre" => Http::response($genres, 200),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('recommended')
            ->has('popular')
            ->has('latest')
            ->has('genres')
            ->where('recommended.0.slug', 'one-piece')
        );
    }

    public function test_browse_latest_comics_renders_paginated_grid(): void
    {
        $terbaru = file_get_contents(base_path('tests/Fixtures/Komiku/terbaru.json'));

        Http::fake([
            "{$this->baseUrl}/terbaru*" => Http::response($terbaru, 200),
        ]);

        $response = $this->get('/terbaru?page=1');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Browse')
            ->where('title', 'Komik Rilis Terbaru')
            ->has('comics.items')
            ->where('comics.current_page', 1)
        );
    }

    public function test_browse_by_type_renders_catalog(): void
    {
        $populer = file_get_contents(base_path('tests/Fixtures/Komiku/populer.json'));

        Http::fake([
            "{$this->baseUrl}/type/manhwa*" => Http::response($populer, 200),
            "{$this->baseUrl}/komik-populer/manhwa" => Http::response($populer, 200),
        ]);

        $response = $this->get('/type/manhwa');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Browse')
            ->where('type', 'manhwa')
            ->has('comics.items')
        );
    }

    public function test_browse_by_type_with_invalid_type_returns_404(): void
    {
        $response = $this->get('/type/novel');

        $response->assertStatus(404);
    }

    public function test_browse_all_genres_directory_renders(): void
    {
        $genres = file_get_contents(base_path('tests/Fixtures/Komiku/genre_all.json'));

        Http::fake([
            "{$this->baseUrl}/genre-all" => Http::response($genres, 200),
        ]);

        $response = $this->get('/genre');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Genres')
            ->has('genres', 4)
            ->where('genres.0.slug', 'action')
        );
    }

    public function test_browse_by_genre_renders_comics(): void
    {
        $terbaru = file_get_contents(base_path('tests/Fixtures/Komiku/terbaru.json'));

        Http::fake([
            "{$this->baseUrl}/genre/action*" => Http::response($terbaru, 200),
        ]);

        $response = $this->get('/genre/action');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Browse')
            ->where('genre', 'action')
            ->has('comics.items')
        );
    }

    public function test_comic_detail_renders_and_upserts_metadata_to_database(): void
    {
        $detail = file_get_contents(base_path('tests/Fixtures/Komiku/detail_complete.json'));

        Http::fake([
            "{$this->baseUrl}/detail-komik/one-piece" => Http::response($detail, 200),
        ]);

        $response = $this->get('/komik/one-piece');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Show')
            ->where('comic.slug', 'one-piece')
            ->where('comic.title', 'One Piece')
            ->where('comic.author', 'Eiichiro Oda')
            ->has('comic.chapters', 3)
        );

        // Check local database upsert for Comic
        $this->assertDatabaseHas('comics', [
            'slug' => 'one-piece',
            'title' => 'One Piece',
            'comic_type' => 'manga',
            'publication_status' => 'Ongoing',
        ]);

        // Check local database upsert for Genres
        $this->assertDatabaseHas('genres', [
            'slug' => 'action',
            'name' => 'Action',
        ]);
        $this->assertDatabaseHas('genres', [
            'slug' => 'adventure',
            'name' => 'Adventure',
        ]);

        // Check pivot relationship
        $comic = Comic::where('slug', 'one-piece')->firstOrFail();
        $this->assertCount(3, $comic->genres);
        $this->assertTrue($comic->genres->pluck('slug')->contains('action'));
    }

    public function test_comic_detail_not_found_returns_404(): void
    {
        Http::fake([
            "{$this->baseUrl}/detail-komik/non-existent-comic" => Http::response(['message' => 'Not Found'], 404),
        ]);

        $response = $this->get('/komik/non-existent-comic');

        $response->assertStatus(404);
    }

    public function test_search_with_query_renders_results(): void
    {
        $terbaru = file_get_contents(base_path('tests/Fixtures/Komiku/terbaru.json'));

        Http::fake([
            "{$this->baseUrl}/search*" => Http::response($terbaru, 200),
        ]);

        $response = $this->get('/search?q=solo');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Search')
            ->where('query', 'solo')
            ->has('comics.items')
        );
    }

    public function test_search_with_empty_query_renders_empty_state(): void
    {
        $response = $this->get('/search');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Comic/Search')
            ->where('query', '')
            ->where('comics.items', [])
        );
    }

    public function test_search_rate_limiter_throttles_after_thirty_requests(): void
    {
        $terbaru = file_get_contents(base_path('tests/Fixtures/Komiku/terbaru.json'));

        Http::fake([
            "{$this->baseUrl}/search*" => Http::response($terbaru, 200),
        ]);

        // Execute 30 search requests (allowed quota)
        for ($i = 1; $i <= 30; $i++) {
            $response = $this->get('/search?q=test');
            $response->assertStatus(200);
        }

        // The 31st request must trigger HTTP 429 Too Many Requests
        $response = $this->get('/search?q=test');
        $response->assertStatus(429);
    }
}
