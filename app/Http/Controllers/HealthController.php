<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ComicProviderInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * Internal readiness endpoint to verify DB, cache, and storage connectivity.
     */
    public function ready(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $isHealthy = ! in_array(false, array_values($checks), true);
        $statusCode = $isHealthy ? 200 : 503;
        $timestamp = CarbonImmutable::now('UTC')->toIso8601String();

        $healthSecret = (string) config('services.health.secret');
        $isAuthorized = ($healthSecret !== '' && hash_equals($healthSecret, (string) $request->header('X-Health-Key')))
            || ($request->user() && method_exists($request->user(), 'hasRole') && $request->user()->hasRole('superadmin'));

        if ($isAuthorized) {
            return response()->json([
                'status' => $isHealthy ? 'ok' : 'degraded',
                'timestamp' => $timestamp,
                'environment' => config('app.env'),
                'checks' => [
                    'database' => $checks['database'] ? 'ok' : 'failed',
                    'cache' => $checks['cache'] ? 'ok' : 'failed',
                    'storage' => $checks['storage'] ? 'ok' : 'failed',
                ],
            ], $statusCode);
        }

        // Public or edge probe: minimal payload without internal detail disclosure
        return response()->json([
            'status' => $isHealthy ? 'ok' : 'degraded',
            'timestamp' => $timestamp,
        ], $statusCode);
    }

    /**
     * Isolated upstream provider health check.
     * Does not affect the application's readiness probe status.
     */
    public function upstream(Request $request, ComicProviderInterface $provider): JsonResponse
    {
        $isHealthy = false;
        $timestamp = CarbonImmutable::now('UTC')->toIso8601String();

        try {
            $latest = $provider->latest(1);
            $isHealthy = $latest !== null;
        } catch (Throwable $e) {
            Log::warning('Upstream health probe failed', [
                'error' => $e->getMessage(),
                'provider' => config('comic.provider'),
            ]);
        }

        $statusCode = $isHealthy ? 200 : 503;

        return response()->json([
            'status' => $isHealthy ? 'ok' : 'degraded',
            'provider' => config('comic.provider', 'komiku'),
            'timestamp' => $timestamp,
        ], $statusCode);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable $e) {
            Log::error('Health check database connection failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'health:ping:'.CarbonImmutable::now()->timestamp;
            Cache::put($key, true, 10);
            $retrieved = (bool) Cache::get($key);
            Cache::forget($key);

            return $retrieved;
        } catch (Throwable $e) {
            Log::error('Health check cache connection failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            $frameworkCache = storage_path('framework/cache');
            $logs = storage_path('logs');

            return is_writable($frameworkCache) && is_writable($logs);
        } catch (Throwable $e) {
            Log::error('Health check storage path permission failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
