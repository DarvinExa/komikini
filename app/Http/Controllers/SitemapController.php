<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comic;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Generate dynamic and cached XML sitemap for public discovery pages and known comics.
     */
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap:xml:v1', 3600, function (): string {
            $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');

            $staticPages = [
                ['loc' => $baseUrl.'/', 'freq' => 'hourly', 'priority' => '1.0'],
                ['loc' => $baseUrl.'/terbaru', 'freq' => 'hourly', 'priority' => '0.9'],
                ['loc' => $baseUrl.'/ranking', 'freq' => 'daily', 'priority' => '0.8'],
                ['loc' => $baseUrl.'/genre', 'freq' => 'weekly', 'priority' => '0.7'],
                ['loc' => $baseUrl.'/type/manga', 'freq' => 'daily', 'priority' => '0.8'],
                ['loc' => $baseUrl.'/type/manhwa', 'freq' => 'daily', 'priority' => '0.8'],
                ['loc' => $baseUrl.'/type/manhua', 'freq' => 'daily', 'priority' => '0.8'],
            ];

            $comics = Comic::query()
                ->select(['slug', 'updated_at'])
                ->orderByDesc('updated_at')
                ->limit(5000)
                ->get();

            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($staticPages as $page) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>{$page['loc']}</loc>\n";
                $xml .= "    <changefreq>{$page['freq']}</changefreq>\n";
                $xml .= "    <priority>{$page['priority']}</priority>\n";
                $xml .= "  </url>\n";
            }

            foreach ($comics as $comic) {
                $loc = htmlspecialchars($baseUrl.'/komik/'.$comic->slug, ENT_XML1, 'UTF-8');
                $lastmod = $comic->updated_at ? $comic->updated_at->toAtomString() : now()->toAtomString();

                $xml .= "  <url>\n";
                $xml .= "    <loc>{$loc}</loc>\n";
                $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
                $xml .= "    <changefreq>daily</changefreq>\n";
                $xml .= "    <priority>0.7</priority>\n";
                $xml .= "  </url>\n";
            }

            $xml .= '</urlset>';

            return $xml;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
