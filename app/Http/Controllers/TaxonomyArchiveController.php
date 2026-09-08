<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Term;
use App\Support\TaxonomyRegistry;
use Illuminate\Http\Request;

/**
 * Public archive for a single taxonomy term (/danh-muc/tin-tuc, /the/seo…).
 *
 * Before this existed the category/tag badges rendered on every post detail
 * page were dead ends — visible, styled, and linked nowhere. Anything the
 * editor can attach to content is now reachable and indexable.
 */
class TaxonomyArchiveController extends Controller
{
    /**
     * URL prefix → taxonomy slug. Deliberately a plain constant rather than
     * something derived from TaxonomyRegistry at route-registration time:
     * the registry is filled during AppServiceProvider::boot() via the
     * mxd_init hook, which is NOT guaranteed to have run when routes/web.php
     * is evaluated, and `route:cache` would freeze whatever state existed at
     * cache time. A constant is available immediately and caches correctly.
     * Adding an archive for a new taxonomy is one line here.
     */
    public const PREFIXES = [
        'danh-muc' => 'category',
        'the' => 'post_tag',
        'loai-san-pham' => 'product_category',
        'the-san-pham' => 'product_tag',
    ];

    /** URL prefix for a taxonomy slug, or null if that taxonomy has no archive. */
    public static function prefixFor(string $taxonomy): ?string
    {
        $prefix = array_search($taxonomy, self::PREFIXES, true);

        return $prefix === false ? null : $prefix;
    }

    public function __invoke(Request $request, string $prefix, string $slug, TaxonomyRegistry $registry)
    {
        $taxonomySlug = self::PREFIXES[$prefix] ?? abort(404);

        $term = Term::where('taxonomy', $taxonomySlug)->where('slug', $slug)->firstOrFail();

        $taxonomy = $registry->get($taxonomySlug) ?? abort(404);
        $postTypes = $taxonomy['post_types'];

        $posts = Post::published()
            ->whereIn('post_type', $postTypes)
            ->whereHas('terms', fn ($q) => $q->whereKey($term->getKey()))
            ->with(['author', 'terms', 'meta'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        // Sibling terms let a reader move sideways ("Tin tức" → "Sự kiện")
        // instead of having to go back up. Only terms that actually have
        // published content — an empty archive link is a dead end too.
        $siblings = Term::where('taxonomy', $taxonomySlug)
            ->whereHas('posts', fn ($q) => $q->where('status', 'published'))
            ->orderBy('name')
            ->get();

        return view('theme::taxonomy', [
            'term' => $term,
            'taxonomy' => $taxonomy,
            'prefix' => $prefix,
            'posts' => $posts,
            'siblings' => $siblings,
            'postType' => count($postTypes) === 1 ? get_post_type($postTypes[0]) : null,
        ]);
    }
}
