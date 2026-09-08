<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Powers "Admin đang online" on /admin/analytics — just a timestamp bump
 * on every authenticated admin request, no session/Redis introspection.
 */
class TrackAdminActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $request->user()->forceFill(['last_active_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
