<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aquí puedes agregar tus middlewares si los necesitas en el futuro
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        // 1. Forzar respuesta JSON para TODAS las rutas de la API en caso de cualquier error
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        // 2. Personalizar el error específico cuando NO hay token o el token expiró
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'No estás autenticado o tu sesión expiró. Por favor, inicia sesión nuevamente.'
                ], 401);
            }
        });

    })->create();