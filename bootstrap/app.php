<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        // Prepended so banned IPs are rejected before any other work runs.
        $middleware->web(prepend: [
            \App\Http\Middleware\BlockBannedIps::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\EnsureAppIsInstalled::class,
            // Before routing tries to resolve the path itself, so a
            // redirect rule wins even for a URL that would otherwise 404.
            \App\Http\Middleware\HandleRedirects::class,
            \App\Http\Middleware\TrackPageview::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        // RUM beacon: navigator.sendBeacon() cannot attach custom headers,
        // so a CSRF token can't ride along. Same threat model as the
        // pageview tracker (anonymous, no session state changes) — only
        // ever inserts a constrained numeric/enum analytics row.
        $middleware->validateCsrfTokens(except: ['rum']);
        // Cài đặt → Bảo trì toggle: /admin stays reachable while the site
        // is down so an admin can still manage it (and turn maintenance
        // back off) without shell access.
        $middleware->preventRequestsDuringMaintenance(['admin', 'admin/*', 'login']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Website Health's "Lỗi & 404" section — only public-facing issues
        // (skip /admin/* so an admin's own typos while managing content
        // don't get logged as if a visitor hit them).
        $shouldLog = function (Request $request): bool {
            return file_exists(storage_path('app/install.lock'))
                && ! $request->is('admin') && ! $request->is('admin/*');
        };

        // NotFoundHttpException is in Laravel's default "don't report" list
        // (treated as expected, not a bug) — reportable() below never even
        // fires for it. render() isn't filtered that way, so log there
        // instead and return null to fall through to the normal 404 page.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) use ($shouldLog) {
            if ($shouldLog($request)) {
                try {
                    \App\Models\ErrorLog::create([
                        'type' => 'not_found',
                        'message' => 'Trang không tồn tại',
                        'url' => mb_substr($request->fullUrl(), 0, 500),
                    ]);
                } catch (\Throwable) {
                    // Logging the error must never itself break error handling.
                }
            }

            return null;
        });

        $exceptions->reportable(function (\Throwable $e) use ($shouldLog) {
            $request = request();

            if (! $shouldLog($request) || $e instanceof \Illuminate\Validation\ValidationException) {
                return;
            }

            try {
                \App\Models\ErrorLog::create([
                    'type' => 'error',
                    'message' => mb_substr($e->getMessage() ?: get_class($e), 0, 500),
                    'file' => mb_substr($e->getFile(), 0, 500),
                    'line' => $e->getLine(),
                    'url' => mb_substr($request->fullUrl(), 0, 500),
                ]);
            } catch (\Throwable) {
                // Logging the error must never itself break error handling.
            }
        });
    })->create();
