<?php

namespace App\Http\Middleware;

use App\Services\CacheService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache successful GET JSON responses.
 *
 * Usage in routes: CacheResponse::class . ':module,ttl'
 * Example: CacheResponse::class . ':hr,120'
 *
 * Skips caching when:
 * - Request is not GET
 * - Request has ?format= param (file downloads)
 * - Response is not 2xx
 * - Response is not JSON
 * - User is not authenticated
 */
class CacheResponse
{
    public function handle(Request $request, Closure $next, string $module = 'general', int $ttl = 60): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Skip file downloads
        if ($request->filled('format')) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $cacheKey = CacheService::responseKey(
            $request->path(),
            $request->query(),
            $user->id
        );

        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($cached !== null) {
            return response()
                ->json($cached['body'], $cached['status'])
                ->header('X-Cache', 'HIT')
                ->header('X-Cache-TTL', $ttl);
        }

        $response = $next($request);

        // Only cache successful JSON responses
        if ($response->isSuccessful() && str_contains($response->headers->get('Content-Type', ''), 'json')) {
            $payload = [
                'body'   => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
            ];

            \Illuminate\Support\Facades\Cache::put($cacheKey, $payload, $ttl);
            CacheService::register("response:{$module}", $cacheKey);

            $response->headers->set('X-Cache', 'MISS');
            $response->headers->set('X-Cache-TTL', $ttl);
        }

        return $response;
    }
}
