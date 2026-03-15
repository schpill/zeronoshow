<?php

use App\Http\Middleware\AllowIframeForWidget;
use App\Http\Middleware\AllowTelegramWebhookIps;
use App\Http\Middleware\EnsureAdminAbility;
use App\Http\Middleware\LeoAddonActiveMiddleware;
use App\Http\Middleware\RequireActiveSubscription;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'join/*',
            'api/v1/public/widget/*',
        ]);
        $middleware->alias([
            'admin.ability' => EnsureAdminAbility::class,
            'telegram.allowlist' => AllowTelegramWebhookIps::class,
            'leo.addon' => LeoAddonActiveMiddleware::class,
            'subscription' => RequireActiveSubscription::class,
            'widget.allowframe' => AllowIframeForWidget::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $shouldRenderJson = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();
        $jsonError = function (string $code, string $message, int $status, array $headers = []) {
            return response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ], $status, $headers);
        };

        $exceptions->dontReport([
            AuthenticationException::class,
            ValidationException::class,
            ThrottleRequestsException::class,
        ]);

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($jsonError, $shouldRenderJson) {
            if (! $shouldRenderJson($request)) {
                return null;
            }

            return $jsonError('NOT_FOUND', 'La ressource demandee est introuvable.', 404);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($jsonError, $shouldRenderJson) {
            if (! $shouldRenderJson($request)) {
                return null;
            }

            return $jsonError('FORBIDDEN', 'Vous n avez pas les droits pour effectuer cette action.', 403);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($jsonError, $shouldRenderJson) {
            if (! $shouldRenderJson($request)) {
                return null;
            }

            return $jsonError('UNAUTHENTICATED', 'Authentification requise.', 401);
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) use ($jsonError, $shouldRenderJson) {
            if (! $shouldRenderJson($request)) {
                return null;
            }

            $headers = $exception->getHeaders();

            return $jsonError(
                'RATE_LIMITED',
                'Trop de requetes. Veuillez patienter avant de reessayer.',
                429,
                is_array($headers) ? $headers : [],
            );
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($jsonError, $shouldRenderJson) {
            if (! $shouldRenderJson($request)) {
                return null;
            }

            $status = $exception->getStatusCode();

            return match ($status) {
                403 => $jsonError('FORBIDDEN', 'Vous n avez pas les droits pour effectuer cette action.', 403, $exception->getHeaders()),
                404 => $jsonError('NOT_FOUND', 'La ressource demandee est introuvable.', 404, $exception->getHeaders()),
                default => null,
            };
        });

        $exceptions->render(function (Throwable $exception, Request $request) use ($jsonError, $shouldRenderJson) {
            if (! $shouldRenderJson($request)) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return null;
            }

            return $jsonError('INTERNAL_SERVER_ERROR', 'Une erreur interne est survenue.', 500);
        });
    })->create();
