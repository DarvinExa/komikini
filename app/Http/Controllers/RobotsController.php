<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Generate dynamic robots.txt based on environment.
     */
    public function __invoke(): Response
    {
        if (app()->environment('staging') || config('app.env') === 'staging') {
            $content = "User-agent: *\nDisallow: /\n";
        } else {
            $sitemapUrl = url('/sitemap.xml');
            $content = implode("\n", [
                'User-agent: *',
                'Disallow: /admin/',
                'Disallow: /admin',
                'Disallow: /profile',
                'Disallow: /pustaka',
                'Disallow: /pustaka/',
                'Disallow: /search',
                'Disallow: /comments',
                'Disallow: /comments/',
                'Disallow: /login',
                'Disallow: /register',
                'Disallow: /forgot-password',
                'Disallow: /reset-password',
                '',
                "Sitemap: {$sitemapUrl}",
                '',
            ]);
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
