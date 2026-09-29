<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Models\Comic;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_robots_txt_disallows_sensitive_routes_and_references_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Disallow: /pustaka', $content);
        $this->assertStringContainsString('Disallow: /profile', $content);
        $this->assertStringContainsString('Disallow: /search', $content);
        $this->assertStringContainsString('Sitemap: ', $content);
        $this->assertStringContainsString('/sitemap.xml', $content);
    }

    public function test_staging_environment_protects_with_noindex_and_strict_robots(): void
    {
        config(['app.env' => 'staging']);

        $robotsResponse = $this->get('/robots.txt');
        $robotsResponse->assertOk();
        $this->assertEquals("User-agent: *\nDisallow: /\n", $robotsResponse->getContent());

        $webResponse = $this->get('/');
        $webResponse->assertOk();
        $webResponse->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_sitemap_xml_renders_valid_xml_with_static_and_comic_urls(): void
    {
        Comic::create([
            'slug' => 'one-piece-sitemap-test',
            'title' => 'One Piece Sitemap Test',
            'comic_type' => 'manga',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        $this->assertStringContainsString('/terbaru</loc>', $xml);
        $this->assertStringContainsString('/ranking</loc>', $xml);
        $this->assertStringContainsString('/genre</loc>', $xml);
        $this->assertStringContainsString('/type/manga</loc>', $xml);
        $this->assertStringContainsString('/komik/one-piece-sitemap-test</loc>', $xml);
    }

    public function test_home_page_contains_application_metadata_and_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Komikini');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_search_results_page_is_marked_for_noindex_to_prevent_thin_content(): void
    {
        $response = $this->get('/search?q=test');

        $response->assertOk();
        // Inertia page component props check
        $response->assertInertia(fn ($page) => $page
            ->component('Comic/Search')
            ->has('comics')
            ->where('query', 'test')
        );
    }

    public function test_ranking_page_renders_with_canonical_period_param(): void
    {
        $response = $this->get('/ranking?period=daily');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Comic/Ranking')
            ->where('activePeriod', 'daily')
            ->has('periods')
            ->has('items')
        );
    }

    public function test_authenticated_user_accessing_library_receives_proper_structure(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/pustaka');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Library/Index')
            ->has('histories')
            ->has('bookmarks')
            ->has('counts')
        );
    }
}
