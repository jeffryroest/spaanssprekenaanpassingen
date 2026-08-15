<?php

use App\ContentApi\PublicApiResponder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return app(PublicApiResponder::class)->respond($request, [
                'schema_version' => PublicApiResponder::API_VERSION,
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'De aanvraag bevat ongeldige waarden.',
                    'details' => $exception->errors(),
                ],
            ], status: 422);
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            $status = $exception->getStatusCode();
            [$code, $message] = match ($status) {
                404 => ['api_route_not_found', 'De gevraagde API-route is niet gevonden.'],
                429 => ['rate_limit_exceeded', 'Te veel aanvragen. Probeer het later opnieuw.'],
                default => ['http_error', 'De aanvraag kon niet worden verwerkt.'],
            };

            return app(PublicApiResponder::class)->respond($request, [
                'schema_version' => PublicApiResponder::API_VERSION,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ], status: $status, additionalHeaders: $exception->getHeaders());
        });
    })->create();
