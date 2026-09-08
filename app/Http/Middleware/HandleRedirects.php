<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Real 301/302 redirect manager — checked before routing even tries to
 * resolve `/{slug}`, so a redirect rule wins even when the old URL would
 * otherwise 404 (the common case: content moved/renamed). Public-facing
 * only — admin/login/install paths never go through this, so a bad rule
 * can't lock an admin out of the panel.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin') || $request->is('admin/*') || $request->is('login') || $request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        $path = $request->path();

        $redirect = Redirect::active()->get()->first(fn (Redirect $r) => $r->matches($path));

        if ($redirect) {
            $redirect->increment('hits');

            return redirect($redirect->target, $redirect->status_code);
        }

        return $next($request);
    }
}
