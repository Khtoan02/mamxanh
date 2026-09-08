<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the WooCommerce REST API v3 (`wc/v3` under
 * `/wp-json/`) — a per-install configurable integration (URL + Consumer
 * Key/Secret entered at Cài đặt → Tích hợp), not hardcoded to any single
 * store, since this CMS ships as an open-source core. Auth is HTTP Basic
 * over the store's Consumer Key/Secret, which is only safe over HTTPS —
 * the official WooCommerce docs' recommended method (the OAuth1.0a
 * query-param fallback for plain HTTP is deliberately not implemented,
 * since every real WooCommerce store is expected to run HTTPS today).
 */
class WooCommerceClient
{
    public function isConfigured(): bool
    {
        return filled(Setting::get('woocommerce_url'))
            && filled(Setting::get('woocommerce_consumer_key'))
            && filled(Setting::get('woocommerce_consumer_secret'));
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>|null
     */
    private function request(string $method, string $path, array $query = [], array $json = []): ?array
    {
        $url = rtrim((string) Setting::get('woocommerce_url'), '/').'/wp-json/wc/v3/'.ltrim($path, '/');

        try {
            $pending = Http::withBasicAuth(
                (string) Setting::get('woocommerce_consumer_key'),
                (string) Setting::get('woocommerce_consumer_secret'),
            )->timeout(10);

            // GET/DELETE carry their params as a real query string (WooCommerce's
            // own docs show `DELETE .../products/{id}?force=true`) — POST/PUT
            // carry the product payload as a JSON body.
            $response = match ($method) {
                'get' => $pending->get($url, $query),
                'delete' => $pending->delete($url.'?'.http_build_query($query)),
                default => $pending->{$method}($url, $json),
            };

            if (! $response->successful()) {
                Log::warning('WooCommerce API request failed', [
                    'method' => $method, 'path' => $path, 'status' => $response->status(), 'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('WooCommerce API request threw', ['method' => $method, 'path' => $path, 'message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function createProduct(array $payload): ?array
    {
        return $this->request('post', 'products', json: $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function updateProduct(int $wooId, array $payload): ?array
    {
        return $this->request('put', "products/{$wooId}", json: $payload);
    }

    /**
     * Trashes by default (`force=false`) — a permanent delete on a live
     * store is not something a local action here should ever trigger
     * without the store owner's own confirmation on the WooCommerce side.
     */
    public function deleteProduct(int $wooId, bool $force = false): ?array
    {
        return $this->request('delete', "products/{$wooId}", ['force' => $force]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProducts(int $page = 1, int $perPage = 50): array
    {
        return $this->request('get', 'products', ['page' => $page, 'per_page' => $perPage]) ?? [];
    }

    /**
     * Finds an existing WooCommerce product category by exact name, or
     * creates one — categories are matched by name (not a locally-stored
     * id mapping) since Term has no meta storage of its own, and this only
     * runs on the (infrequent) product save path.
     */
    public function findOrCreateCategory(string $name): ?int
    {
        $existing = $this->request('get', 'products/categories', ['search' => $name]) ?? [];
        $match = collect($existing)->first(fn ($cat) => strcasecmp($cat['name'] ?? '', $name) === 0);

        if ($match) {
            return (int) $match['id'];
        }

        $created = $this->request('post', 'products/categories', json: ['name' => $name]);

        return $created['id'] ?? null;
    }
}
