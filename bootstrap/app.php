<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'track.user.session' => \App\Http\Middleware\TrackUserSession::class,
        ]);
    })
    
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $previous = $exception->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'message' => class_basename($previous->getModel()) . ' not found',
                ], 404);
            }

            return response()->json([
                'success' => false,
                'message' => 'Route not found',
            ], 404);
        });

        // $exceptions->render(function (\Throwable $exception, Request $request) {
        //     if (! $request->is('api/*')) {
        //         return null;
        //     }

        //     return response()->json([
        //         'success' => false,
        //         'message' => app()->environment('production')
        //             ? 'Server error'
        //             : $exception->getMessage(),
        //     ], 500);
        // });

    })->create();