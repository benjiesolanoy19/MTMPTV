<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'staff-access' => \App\Http\Middleware\StaffAccessMiddleware::class,
            'staff-onboarding' => \App\Http\Middleware\StaffOnboardingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render a readable page (with the real retry delay) instead of the
        // bare framework error screen when a rate limit is hit.
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => (int) ($e->getHeaders()['Retry-After'] ?? 0),
                ], 429, $e->getHeaders());
            }

            return response()->view('errors.429', [
                'seconds' => (int) ($e->getHeaders()['Retry-After'] ?? 0),
            ], 429, $e->getHeaders());
        });
    })->create();
