<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class HandleCorrelationId
{
    /**
     * Handle an incoming request and ensure a Correlation ID is present.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-ID') ?? $request->header('X-Request-ID');

        if (! is_string($correlationId) || ! preg_match('/^[a-zA-Z0-9\-_]{8,64}$/', $correlationId)) {
            $correlationId = (string) Str::uuid();
        }

        $request->attributes->set('correlation_id', $correlationId);

        Log::withContext([
            'correlation_id' => $correlationId,
        ]);

        $response = $next($request);

        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
