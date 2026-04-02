<?php

use App\Exceptions\QuoteProviderException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(guests: static fn () => null);
        $middleware->api(prepend: \App\Http\Middleware\ForceJsonResponse::class);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QuoteProviderException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Quote provider is currently unavailable.',
                    'error' => [
                        'code' => 'QUOTE_PROVIDER_UNAVAILABLE',
                        'details' => $exception->getMessage(),
                    ],
                ], 503);
            }

            return null;
        });

        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, \Throwable $exception): bool => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
