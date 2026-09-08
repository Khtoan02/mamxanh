<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates every "web" request on whether storage/app/install.lock exists —
 * mirrors the SRS's "Lock & Completion" step (docs/openapi.yaml / SRS
 * Section III.1.4). Not-yet-installed requests are sent to the wizard;
 * once installed, the wizard routes are closed off.
 */
class EnsureAppIsInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = file_exists(storage_path('app/install.lock'));
        $onInstaller = $request->is('install') || $request->is('install/*');

        if (! $installed && ! $onInstaller) {
            return redirect('/install');
        }

        if ($installed && $onInstaller) {
            return redirect('/');
        }

        return $next($request);
    }
}
