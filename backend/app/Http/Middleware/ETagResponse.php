<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add ETag headers to GET responses for conditional request support.
 *
 * When the client sends If-None-Match matching the current ETag,
 * returns 304 Not Modified (no body), saving bandwidth.
 *
 * Apply AFTER SecurityHeaders, BEFORE CacheResponse for best results.
 */
class ETagResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only process GET requests with successful JSON responses
        if ($request->method() !== 'GET' || !$response->isSuccessful()) {
            return $response;
        }

        // Skip file downloads (binary content)
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'json')) {
            return $response;
        }

        // Generate ETag from response content
        $content = $response->getContent();
        $etag = '"' . md5($content) . '"';

        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', 'private, must-revalidate');

        // Check if client sent matching ETag
        $clientETag = $request->header('If-None-Match');
        if ($clientETag === $etag) {
            // 304 Not Modified — no body transfer needed
            return response('', 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'private, must-revalidate');
        }

        return $response;
    }
}
