<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Custom Post Type registry (SRS section IV.1.b). Post types are registered
 * in code (typically on the `mxd_init` action — see
 * AppServiceProvider::registerCoreContentTypes()), not stored in the DB —
 * only the posts themselves live in the `posts` table, tagged by
 * `post_type`. This mirrors WordPress's register_post_type() model.
 */
class PostTypeRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $types = [];

    /**
     * @param  array<string, mixed>  $args
     */
    public function register(string $slug, array $args = []): void
    {
        $this->types[$slug] = array_merge([
            'label' => Str::headline($slug),
            'label_singular' => Str::headline(Str::singular($slug)),
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor'],
        ], $args, ['slug' => $slug]);
    }

    public function exists(string $slug): bool
    {
        return isset($this->types[$slug]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $slug): ?array
    {
        return $this->types[$slug] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->types;
    }
}
