<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogRequestTelemetry
{
    /**
     * Handle an incoming request and record start time.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('telemetry_start_time', microtime(true));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        $startTime = $request->attributes->get('telemetry_start_time');
        $durationMs = is_float($startTime)
            ? round((microtime(true) - $startTime) * 1000, 2)
            : 0.0;

        $path = $request->path();
        $isHealthProbe = $path === 'up' || str_starts_with($path, 'health');

        $user = $request->user();
        $role = null;
        if ($user && method_exists($user, 'getRoleNames')) {
            $role = $user->getRoleNames()->first();
        }

        $context = [
            'correlation_id' => $request->attributes->get('correlation_id', (string) $request->header('X-Correlation-ID')),
            'route' => $request->route()?->getName() ?? $path,
            'method' => $request->method(),
            'path' => '/'.ltrim($path, '/'),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'ip' => $request->ip(),
            'user_id' => $user?->id,
            'role' => $role,
            'user_agent' => Str::limit((string) $request->userAgent(), 100),
        ];

        // Sanitize: strictly prevent logging passwords, tokens, auth headers, cookies, or comment bodies
        if ($isHealthProbe) {
            Log::debug('HTTP Health Probe', $context);
        } elseif ($response->getStatusCode() >= 500) {
            Log::error('HTTP Request 5xx Error', $context);
        } elseif ($response->getStatusCode() >= 400) {
            Log::warning('HTTP Request 4xx Client Error', $context);
        } else {
            Log::info('HTTP Request Handled', $context);
        }
    }
}
