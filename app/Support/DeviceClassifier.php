<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shared coarse device classification from a User-Agent string — used by
 * both TrackPageview (public pageviews) and PerformanceMetricController
 * (RUM Core Web Vitals) so the two never drift into different device
 * buckets for what should be the same taxonomy.
 */
class DeviceClassifier
{
    public static function classify(string $userAgent): string
    {
        $ua = mb_strtolower($userAgent);

        if (preg_match('/ipad|tablet|kindle|playbook|silk/', $ua)) {
            return 'tablet';
        }

        if (preg_match('/mobi|android|iphone|ipod|phone/', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
