<?php

namespace App\Http\Middleware;

use App\Services\CacheService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatically invalidate response cache after successful write operations.
 *
 * Usage in routes: InvalidateCache::class . ':hr'
 * Usage for multiple modules: InvalidateCache::class . ':hr,reports'
 *
 * Only triggers on POST/PATCH/PUT/DELETE with 2xx responses.
 * Reports cache is always busted alongside the module cache,
 * since reports aggregate data from all modules.
 */
class InvalidateCache
{
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $response = $next($request);

        // Only invalidate on successful write operations
        if (
            in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE'])
            && $response->isSuccessful()
        ) {
            foreach ($modules as $module) {
                CacheService::invalidateResponses($module);
            }

            // Always bust reports cache since reports aggregate across modules
            if (!in_array('reports', $modules)) {
                CacheService::invalidateResponses('reports');
            }
        }

        return $response;
    }
}
