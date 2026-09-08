<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PerformanceMetric;
use App\Support\DeviceClassifier;
use Illuminate\Http\Request;

/**
 * Receives real Core Web Vitals reports from actual visitor browsers
 * (resources/js/rum.js, the `web-vitals` library) via navigator.sendBeacon
 * — this is Real User Monitoring, not synthetic/lab data. Deliberately
 * unauthenticated and CSRF-exempt (see bootstrap/app.php) since it's a
 * cross-origin-safe, no-cookie-read beacon endpoint hit by anonymous
 * visitors, same posture as the pageview tracker.
 */
class PerformanceMetricController extends Controller
{
    private const VALID_METRICS = ['LCP', 'INP', 'CLS', 'FCP', 'TTFB'];

    public function store(Request $request)
    {
        $data = $request->validate([
            'metric' => ['required', 'string', 'in:'.implode(',', self::VALID_METRICS)],
            'value' => ['required', 'numeric', 'min:0'],
            'rating' => ['nullable', 'string', 'max:20'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        PerformanceMetric::create([
            'metric' => $data['metric'],
            'value' => $data['value'],
            'rating' => $data['rating'] ?? null,
            'path' => $data['path'] ?? null,
            'device_type' => DeviceClassifier::classify((string) $request->userAgent()),
        ]);

        return response()->noContent();
    }
}
