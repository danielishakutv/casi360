<?php

/**
 * CASI360 API - Application Bootstrap
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sanctum stateful API middleware
        $middleware->statefulApi();
        
        // Trust proxies (important for cPanel/Contabo behind reverse proxy)
        $middleware->trustProxies(at: '*');
        
        // CORS is handled by config/cors.php
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // JSON responses for API exceptions
        $exceptions->shouldRenderJsonWhen(function () {
            return true;
        });
    })->create();
