<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Post;
use Illuminate\Support\Collection;

/**
 * Eligibility + stats for the public Google Merchant Center feed
 * (`/product-feed.xml`, see `PostDisplayController::productFeed()`) and its
 * admin-facing counterpart (`SeoController::productFeed()`, `/admin/seo/product-feed`)
 * — kept in one place so the two never drift on what counts as "in the feed".
 *
 * A product qualifies only when it's genuinely sold by this business
 * (no `external_url` — an affiliate link points at someone else's product
 * page, which Merchant Center's own policies don't allow advertising here),
 * has a real numeric price (Google's feed spec has no "liên hệ" concept —
 * price is a hard requirement, not a design choice), and has a real image
 * (`image_link` is effectively required for any listing to actually show).
 */
class GoogleProductFeed
{
    /**
     * @return Collection<int, Post>
     */
    public static function candidates(): Collection
    {
        return Post::ofType('product')
            ->published()
            ->with('meta')
            ->get()
            ->filter(fn (Post $post) => ! $post->getMeta('external_url'));
    }

    /**
     * @return Collection<int, Post>
     */
    public static function eligibleProducts(): Collection
    {
        return static::candidates()->filter(
            fn (Post $post) => $post->priceAmount() !== null && filled($post->featured_image)
        )->values();
    }

    /**
     * @return array{total: int, eligible: int, missingPrice: int, missingImage: int, affiliateExcluded: int}
     */
    public static function stats(): array
    {
        $affiliateExcluded = Post::ofType('product')->published()->get()->count() - static::candidates()->count();
        $candidates = static::candidates();

        return [
            'total' => Post::ofType('product')->published()->count(),
            'eligible' => static::eligibleProducts()->count(),
            'missingPrice' => $candidates->filter(fn (Post $post) => $post->priceAmount() === null)->count(),
            'missingImage' => $candidates->filter(fn (Post $post) => $post->priceAmount() !== null && blank($post->featured_image))->count(),
            'affiliateExcluded' => $affiliateExcluded,
        ];
    }
}
