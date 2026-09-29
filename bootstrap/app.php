<?php

use App\Http\Middleware\HandleCorrelationId;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogRequestTelemetry;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));

        $middleware->web(prepend: [
            HandleCorrelationId::class,
        ]);

        $middleware->web(append: [
            SecurityHeadersMiddleware::class,
            HandleInertiaRequests::class,
            LogRequestTelemetry::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e) {
            // Sentry error capture hook if configured and installed
            if (config('sentry.dsn') && class_exists(Integration::class)) {
                Integration::captureUnhandledException($e);
            }
        });
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $correlationId = $request->attributes->get('correlation_id');

            // Generate correlation ID if middleware didn't run (e.g., 404 on unmatched routes)
            if (! $correlationId) {
                $correlationId = (string) Str::uuid();
                $request->attributes->set('correlation_id', $correlationId);
            }

            if (! $response->headers->has('X-Correlation-ID')) {
                $response->headers->set('X-Correlation-ID', (string) $correlationId);
            }

            // Ensure security headers are set even on error responses
            if (! $response->headers->has('X-Content-Type-Options')) {
                $response->headers->set('X-Content-Type-Options', 'nosniff');
                $response->headers->set('X-Frame-Options', 'DENY');
                $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            }

            return $response;
        });
    })->create();
