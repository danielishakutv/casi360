<?php

/**
 * CASI360 API - Application Bootstrap
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sanctum stateful API middleware
        $middleware->statefulApi();
        
        // Trust only the local reverse proxy (nginx on same server)
        // If behind a load balancer, add its IP(s) here instead of '*'
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        
        // CORS is handled by config/cors.php

        // For API-only apps: return 401 JSON instead of redirecting to a "login" route
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // JSON responses for API exceptions
        $exceptions->shouldRenderJsonWhen(function () {
            return true;
        });

        // Return proper 401 JSON when unauthenticated (instead of "Route [login] not defined")
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in.',
            ], 401);
        });

        // Global catch-all: never leak stack traces in production
        $exceptions->render(function (\Throwable $e, $request) {
            if (!$request->expectsJson()) {
                return null; // Let Laravel handle non-JSON (shouldn't happen for API)
            }

            // Let validation & auth exceptions pass through (already handled by Laravel/above)
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return null;
            }

            $status = $e instanceof HttpException ? $e->getStatusCode() : 500;

            return response()->json([
                'success' => false,
                'message' => $status === 500
                    ? 'An unexpected error occurred. Please try again later.'
                    : $e->getMessage(),
            ], $status);
        });
    })->create();
