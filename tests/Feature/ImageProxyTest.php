<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImageProxyTest extends TestCase
{
    public function test_rejects_missing_url_parameter(): void
    {
        $response = $this->get('/img-proxy');
        $response->assertStatus(400);
    }

    public function test_rejects_ssrf_and_unallowlisted_domains(): void
    {
        $response = $this->get('/img-proxy?url=' . urlencode('http://127.0.0.1/admin.png'));
        $response->assertStatus(403);

        $response2 = $this->get('/img-proxy?url=' . urlencode('https://evil-hacker.com/malicious.jpg'));
        $response2->assertStatus(403);

        $response3 = $this->get('/img-proxy?url=' . urlencode('https://169.254.169.254/meta-data'));
        $response3->assertStatus(403);
    }

    public function test_proxies_valid_comic_image_successfully(): void
    {
        $targetUrl = 'https://img.komiku.org/upload5/test.webp';

        Http::fake([
            'https://img.komiku.org/*' => Http::response('fake-image-bytes', 200, [
                'Content-Type' => 'image/webp',
                'Content-Length' => '16',
            ]),
        ]);

        $response = $this->get('/img-proxy?url=' . urlencode($targetUrl));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/webp');
        $response->assertHeader('Cache-Control', 'immutable, max-age=2592000, public');
        $response->assertHeader('Access-Control-Allow-Origin', '*');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
