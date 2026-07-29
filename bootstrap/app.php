<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        
        $middleware->alias([
            'admin.api' => \App\Http\Middleware\EnsureAdminApi::class,
            'set.language' => \App\Http\Middleware\SetLanguage::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            // Handle API exceptions
            if ($request->is('api/*') || $request->expectsJson()) {
                return app(\App\Exceptions\ApiExceptionHandler::class)->handle($e);
            }
        });
    })->create();
