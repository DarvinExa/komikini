<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and apply hardened security headers.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Content-Security-Policy (CSP) baseline without unsafe-eval
        $cspPolicy = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://challenges.cloudflare.com https://static.cloudflareinsights.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://challenges.cloudflare.com https://cloudflareinsights.com",
            "frame-src 'self' https://challenges.cloudflare.com",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $cspPolicy);

        // 2. Anti-Clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // 3. Anti-MIME-Sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 4. Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Deprecate legacy buggy XSS filters in favor of modern CSP
        $response->headers->set('X-XSS-Protection', '0');

        // 6. Restrict Sensitive Device APIs
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // 7. Strict-Transport-Security (HSTS) on HTTPS connections or production
        if ($request->isSecure() || app()->isProduction() || $request->header('X-Forwarded-Proto') === 'https') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // 8. Staging Environment Crawl Protection
        if (app()->environment('staging') || config('app.env') === 'staging') {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
