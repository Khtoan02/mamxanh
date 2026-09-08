<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Post;
use App\Models\PostMeta;
use App\Models\Term;
use Illuminate\Support\Str;

/**
 * Maps a `post_type=product` Post to/from a WooCommerce REST API product
 * payload, and orchestrates push/import against `WooCommerceClient`. This
 * CMS is treated as the source of truth going forward — `push()` runs
 * after every product create/update (see `PostController`) — while
 * `import()` is a one-time/on-demand pull for stores that already have
 * products in WooCommerce before adopting this CMS.
 */
class WooCommerceSyncService
{
    public function __construct(private readonly WooCommerceClient $client) {}

    /**
     * Creates or updates the WooCommerce product for $post, keyed by the
     * `woocommerce_product_id` meta this method itself maintains. Never
     * throws — a sync failure is recorded in `woocommerce_sync_error`
     * meta for the admin to see, not allowed to block the local save.
     */
    public function push(Post $post): void
    {
        if (! $this->client->isConfigured()) {
            return;
        }

        try {
            $payload = $this->buildPayload($post);
            $wooId = $post->getMeta('woocommerce_product_id');

            $result = $wooId
                ? $this->client->updateProduct((int) $wooId, $payload)
                : $this->client->createProduct($payload);

            if (! $result || empty($result['id'])) {
                $post->setMeta('woocommerce_sync_error', 'Không nhận được phản hồi hợp lệ từ WooCommerce — kiểm tra lại URL/API key ở Cài đặt → Tích hợp.');

                return;
            }

            $post->setMeta('woocommerce_product_id', (string) $result['id']);
            $post->setMeta('woocommerce_synced_at', now()->toIso8601String());
            $post->setMeta('woocommerce_sync_error', $this->priceWarning($post));
        } catch (\Throwable $e) {
            $post->setMeta('woocommerce_sync_error', 'Lỗi đồng bộ: '.$e->getMessage());
        }
    }

    /**
     * Deliberately does NOT delete the product on WooCommerce — removing a
     * local record shouldn't silently remove a product on a live store
     * that may be shared/serving real traffic. Only the local mapping
     * disappears (via post_meta cascading with the post itself).
     */
    public function delete(Post $post): void {}

    /**
     * @return array{imported: int, skipped: int}
     */
    public function import(): array
    {
        $imported = 0;
        $skipped = 0;

        if (! $this->client->isConfigured()) {
            return compact('imported', 'skipped');
        }

        $page = 1;

        do {
            $products = $this->client->listProducts($page);

            foreach ($products as $wooProduct) {
                $wooId = (string) ($wooProduct['id'] ?? '');

                if ($wooId === '' || $this->alreadyLinked($wooId)) {
                    $skipped++;

                    continue;
                }

                $this->importOne($wooProduct);
                $imported++;
            }

            $page++;
        } while (count($products) > 0);

        return compact('imported', 'skipped');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Post $post): array
    {
        $externalUrl = $post->getMeta('external_url');

        $payload = [
            'name' => $post->title,
            'description' => (string) $post->content,
            'short_description' => (string) $post->excerpt,
            'status' => $post->status === 'published' ? 'publish' : 'draft',
            'type' => $externalUrl ? 'external' : 'simple',
            'meta_data' => [
                ['key' => '_mxd_post_id', 'value' => (string) $post->id],
            ],
        ];

        if ($externalUrl) {
            $payload['external_url'] = $externalUrl;
            $payload['button_text'] = $post->getMeta('cta_label') ?: 'Mua ngay';
        }

        $amount = $post->priceAmount();
        if ($amount !== null) {
            $payload['regular_price'] = (string) $amount;
        }

        if ($post->featured_image) {
            $payload['images'] = [['src' => $post->featured_image]];
        }

        $categoryIds = $post->terms
            ->where('taxonomy', 'product_category')
            ->map(fn (Term $term) => $this->client->findOrCreateCategory($term->name))
            ->filter()
            ->values();

        if ($categoryIds->isNotEmpty()) {
            $payload['categories'] = $categoryIds->map(fn ($id) => ['id' => $id])->all();
        }

        return $payload;
    }

    private function priceWarning(Post $post): string
    {
        $price = $post->getMeta('price');

        if ($price && $post->priceAmount() === null) {
            return 'Đã đồng bộ, nhưng giá "'.$price.'" không tách được thành số — nhập tay giá bên WooCommerce.';
        }

        return '';
    }

    private function alreadyLinked(string $wooId): bool
    {
        return PostMeta::query()
            ->where('meta_key', 'woocommerce_product_id')
            ->where('meta_value', $wooId)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $wooProduct
     */
    private function importOne(array $wooProduct): void
    {
        $post = new Post([
            'post_type' => 'product',
            'title' => $wooProduct['name'] ?? 'Sản phẩm nhập từ WooCommerce',
            'slug' => Str::slug($wooProduct['slug'] ?? $wooProduct['name'] ?? Str::random(8)),
            'content' => $wooProduct['description'] ?? '',
            'excerpt' => $wooProduct['short_description'] ?? '',
            'status' => ($wooProduct['status'] ?? '') === 'publish' ? 'published' : 'draft',
            'featured_image' => $wooProduct['images'][0]['src'] ?? null,
            'author_id' => auth()->id(),
            'published_at' => now(),
        ]);
        $post->save();

        $post->setMeta('woocommerce_product_id', (string) $wooProduct['id']);
        $post->setMeta('woocommerce_synced_at', now()->toIso8601String());

        if (! empty($wooProduct['regular_price'])) {
            $formatted = number_format((float) $wooProduct['regular_price'], 0, ',', '.').'đ';
            $post->setMeta('price', $formatted);
        }

        if (($wooProduct['type'] ?? '') === 'external') {
            $post->setMeta('external_url', $wooProduct['external_url'] ?? null);
            $post->setMeta('cta_label', $wooProduct['button_text'] ?? null);
        }

        foreach ($wooProduct['categories'] ?? [] as $wooCategory) {
            $name = $wooCategory['name'] ?? null;
            if (! $name) {
                continue;
            }

            $term = Term::firstOrCreate(
                ['taxonomy' => 'product_category', 'slug' => Str::slug($name)],
                ['name' => $name]
            );
            $post->terms()->syncWithoutDetaching([$term->id]);
        }
    }
}
