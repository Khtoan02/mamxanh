<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Site-wide search across every published content type.
 *
 * Deliberately plain SQL LIKE rather than a search engine: the whole point
 * of this CMS is that it installs on ordinary shared hosting with nothing
 * but PHP + MySQL. A Meilisearch/Elasticsearch dependency would make search
 * "work on my machine" and silently 500 on a real customer's host.
 */
class SearchController extends Controller
{
    /** Types a visitor can filter by, in the order shown as chips. */
    private const TYPES = ['post', 'service', 'project', 'product', 'page'];

    public function __invoke(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $type = $request->query('type');

        if (! in_array($type, self::TYPES, true)) {
            $type = null;
        }

        $results = null;
        $countsByType = [];

        // Below 2 characters every row matches and the page becomes a slow
        // full-table dump, so we show the empty state instead of "results".
        if (mb_strlen($query) >= 2) {
            // LIKE wildcards inside user input would otherwise change the
            // meaning of the query ("100%" matching everything).
            $escaped = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%';

            $base = fn () => Post::published()
                ->whereIn('post_type', self::TYPES)
                ->where(fn ($q) => $q
                    ->where('title', 'like', $escaped)
                    ->orWhere('excerpt', 'like', $escaped)
                    ->orWhere('content', 'like', $escaped));

            $countsByType = $base()
                ->selectRaw('post_type, count(*) as total')
                ->groupBy('post_type')
                ->pluck('total', 'post_type')
                ->all();

            $results = $base()
                ->when($type, fn ($q) => $q->where('post_type', $type))
                ->with(['author', 'terms', 'meta'])
                // Title matches are what people mean 90% of the time; a
                // body-text mention should not outrank them just because
                // it is newer.
                ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$escaped])
                ->latest('published_at')
                ->paginate(12)
                ->withQueryString();
        }

        return view('theme::search', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'countsByType' => $countsByType,
            'types' => self::TYPES,
        ]);
    }
}
