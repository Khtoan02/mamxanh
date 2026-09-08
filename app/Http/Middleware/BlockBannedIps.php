<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * App-level "firewall": rejects requests from IPs the admin has explicitly
 * blocked (see /admin/health, "Chặn IP"). Not a substitute for a real
 * network/infrastructure firewall — this only ever sees requests that
 * already reached PHP — but it's a real, working access-control layer,
 * not a decorative toggle.
 */
class BlockBannedIps
{
    public function handle(Request $request, Closure $next): Response
    {
        // Not installed yet (no DB/tables to query) or DB briefly
        // unreachable — degrade to "allow", same resilience posture as
        // AppServiceProvider::registerCapabilityGates().
        if (file_exists(storage_path('app/install.lock'))) {
            try {
                if (BlockedIp::where('ip_address', $request->ip())->exists()) {
                    abort(403, 'Truy cập bị từ chối.');
                }
            } catch (\Illuminate\Database\QueryException) {
                // table/DB not ready — fall through to allow
            }
        }

        return $next($request);
    }
}
