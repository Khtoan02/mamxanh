<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Custom Taxonomy registry (SRS section IV.1.b) — same code-registration
 * model as PostTypeRegistry. Actual terms (e.g. "Tin tức", "Sự kiện") are
 * stored in the `terms` table, tagged by `taxonomy`.
 */
class TaxonomyRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $taxonomies = [];

    /**
     * @param  array<int, string>  $postTypes
     * @param  array<string, mixed>  $args
     */
    public function register(string $slug, array $postTypes, array $args = []): void
    {
        $this->taxonomies[$slug] = array_merge([
            'label' => Str::headline($slug),
            'hierarchical' => false,
        ], $args, ['slug' => $slug, 'post_types' => $postTypes]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $slug): ?array
    {
        return $this->taxonomies[$slug] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->taxonomies;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function forPostType(string $postType): array
    {
        return array_filter(
            $this->taxonomies,
            fn (array $tax) => in_array($postType, $tax['post_types'], true)
        );
    }

    /**
     * Taxonomies that share at least one post type with $slug — e.g.
     * category/post_tag both attach to `post`, so they're "related" to
     * each other, but neither is related to product_category (attaches
     * only to `product`). Used to scope the taxonomy management page's
     * tab bar to the current content type instead of listing every
     * taxonomy registered anywhere in the system.
     *
     * @return array<string, array<string, mixed>>
     */
    public function relatedTo(string $slug): array
    {
        $target = $this->get($slug);

        if (! $target) {
            return [];
        }

        return array_filter(
            $this->taxonomies,
            fn (array $tax) => array_intersect($tax['post_types'], $target['post_types']) !== []
        );
    }
}
