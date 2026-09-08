<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Real IndexNow ping (Bing/Yandex/etc — Google doesn't use this protocol)
 * on publish/update, gated behind the Cài đặt → SEO kỹ thuật toggle. A
 * single best-effort outbound HTTP call — never blocks or breaks the save
 * it's attached to if the endpoint is slow/unreachable.
 */
class IndexNowPinger
{
    public static function ping(string $url): void
    {
        if (! Setting::get('indexnow_enabled')) {
            return;
        }

        $key = Setting::get('indexnow_key');

        if (! $key) {
            return;
        }

        try {
            Http::timeout(5)->get('https://api.indexnow.org/indexnow', [
                'url' => $url,
                'key' => $key,
                'keyLocation' => url("/{$key}.txt"),
            ]);
        } catch (\Throwable) {
            // Best-effort — indexing speed, not correctness.
        }
    }
}
