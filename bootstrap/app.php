<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [\App\Http\Middleware\JsonUnicode::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Toda respuesta de /api es JSON (RQNF-03).
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Datos inválidos: 400 en lugar del 422 por defecto de Laravel (RQNF-03, RQF-08).
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Los datos enviados no son válidos.',
                    'code' => 'DATOS_INVALIDOS',
                    'errors' => $e->errors(),
                ], 400);
            }
        });

        // Recurso inexistente: 404 (RQNF-03).
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $modelo = $e->getPrevious() instanceof ModelNotFoundException;

                return response()->json([
                    'message' => $modelo ? 'El recurso solicitado no existe.' : 'La ruta solicitada no existe.',
                    'code' => 'NO_ENCONTRADO',
                ], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Método HTTP no permitido para esta ruta.',
                    'code' => 'METODO_NO_PERMITIDO',
                ], 405);
            }
        });
    })->create();
