<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Support\DeviceClassifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs one row per public pageview for the Dashboard's traffic stats.
 * Runs in terminate() (after the response is already sent) so it never
 * adds latency to the actual page load. Deliberately does not store IP
 * addresses or user agents — visitor_id is a random, non-PII cookie value.
 */
class TrackPageview
{
    private const BOT_MARKERS = [
        'bot', 'spider', 'crawl', 'slurp', 'curl', 'wget', 'python-requests',
        'headless', 'ahrefs', 'semrush', 'mj12bot', 'facebookexternalhit',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $visitorId = $request->cookie('mxd_vid');

        if (! $visitorId) {
            $visitorId = (string) Str::ulid();
            // Cookie::queue() is dispatched through the Facade's __callStatic,
            // which does not preserve PHP named arguments (they silently
            // collapse to positional order) — pass everything positionally.
            Cookie::queue('mxd_vid', $visitorId, 60 * 24 * 365, '/', null, $request->secure(), true, false, 'lax');
        }

        $request->attributes->set('mxd_vid', $visitorId);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->isMethod('GET')) {
            return;
        }

        if ($request->is('admin') || $request->is('admin/*') || $request->is('login') || $request->is('logout')
            || $request->is('install') || $request->is('install/*') || $request->is('up') || $request->is('sitemap.xml')) {
            return;
        }

        if ($response->getStatusCode() !== 200 || ! str_starts_with($response->headers->get('Content-Type', ''), 'text/html')) {
            return;
        }

        $userAgent = mb_strtolower($request->userAgent() ?? '');
        foreach (self::BOT_MARKERS as $marker) {
            if (str_contains($userAgent, $marker)) {
                return;
            }
        }

        $referrerHost = null;
        if ($referer = $request->headers->get('referer')) {
            $host = parse_url($referer, PHP_URL_HOST);
            if ($host && $host !== $request->getHost()) {
                $referrerHost = mb_substr($host, 0, 255);
            }
        }

        PageView::create([
            'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255),
            'visitor_id' => $request->attributes->get('mxd_vid'),
            'referrer_host' => $referrerHost,
            'device_type' => DeviceClassifier::classify($userAgent),
            'os' => $this->classifyOs($userAgent),
            'browser' => $this->classifyBrowser($userAgent),
            'language' => $this->primaryLanguage($request->header('Accept-Language')),
            'utm_source' => $this->utmParam($request, 'utm_source'),
            'utm_medium' => $this->utmParam($request, 'utm_medium'),
            'utm_campaign' => $this->utmParam($request, 'utm_campaign'),
            'utm_content' => $this->utmParam($request, 'utm_content'),
            'utm_term' => $this->utmParam($request, 'utm_term'),
        ]);
    }

    /**
     * Only ever set when the visited URL actually carries the param (an
     * ad/campaign link) — most pageviews have none, matching referrer_host.
     */
    private function utmParam(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && $value !== '' ? mb_substr($value, 0, 100) : null;
    }

    private function classifyOs(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'windows') => 'Windows',
            str_contains($userAgent, 'iphone'), str_contains($userAgent, 'ipad'), str_contains($userAgent, 'ios') => 'iOS',
            str_contains($userAgent, 'mac os') => 'macOS',
            str_contains($userAgent, 'android') => 'Android',
            str_contains($userAgent, 'linux') => 'Linux',
            default => 'Khác',
        };
    }

    private function classifyBrowser(string $userAgent): string
    {
        // Order matters: Edge/Chrome UAs also contain "safari"/"chrome"
        // tokens from their engine lineage, so the more specific match
        // must be checked first.
        return match (true) {
            str_contains($userAgent, 'edg/'), str_contains($userAgent, 'edga/'), str_contains($userAgent, 'edgios/') => 'Edge',
            str_contains($userAgent, 'opr/'), str_contains($userAgent, 'opera') => 'Opera',
            str_contains($userAgent, 'coc_coc') => 'Cốc Cốc',
            str_contains($userAgent, 'firefox'), str_contains($userAgent, 'fxios') => 'Firefox',
            str_contains($userAgent, 'chrome'), str_contains($userAgent, 'crios') => 'Chrome',
            str_contains($userAgent, 'safari') => 'Safari',
            default => 'Khác',
        };
    }

    private function primaryLanguage(?string $acceptLanguage): ?string
    {
        if (! $acceptLanguage) {
            return null;
        }

        // "vi-VN,vi;q=0.9,en;q=0.8" -> "vi"
        $first = trim(explode(',', $acceptLanguage)[0]);
        $primary = explode('-', $first)[0];

        return $primary !== '' ? mb_substr(mb_strtolower($primary), 0, 5) : null;
    }
}
