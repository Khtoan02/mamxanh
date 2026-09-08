<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * Single rendering engine for every configurable title/meta template in the
 * admin SEO Hub (`/admin/seo/titles`) — replaces the hardcoded
 * "{title} — {sitename}" strings that used to be duplicated across every
 * theme view. Callers pass context-specific vars (%title%, %term%...); the
 * site-wide vars (%sitename%, %tagline%, %sep%) are always available.
 */
class SeoTemplate
{
    /**
     * @param  array<string, string|null>  $vars
     */
    public static function render(string $template, array $vars = []): string
    {
        $vars = array_merge([
            'sitename' => (string) config('app.name'),
            'tagline' => env('SITE_TAGLINE', '') ?: (string) config('app.name'),
            'sep' => Setting::get('seo_sep', ' — '),
            'currentyear' => date('Y'),
        ], array_filter($vars, fn ($v) => $v !== null));

        $result = preg_replace_callback(
            '/%([a-z_]+)%/',
            fn (array $m) => $vars[$m[1]] ?? '',
            $template
        );

        return trim(preg_replace('/\s+/', ' ', $result ?? ''));
    }
}
