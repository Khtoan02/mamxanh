<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;

/**
 * RSS 2.0 feed of the blog. Still the one format every reader, newsletter
 * tool and content aggregator understands, and it costs one route — a site
 * that publishes articles without a feed simply cannot be subscribed to.
 */
class FeedController extends Controller
{
    public function __invoke()
    {
        $posts = Post::ofType('post')
            ->published()
            ->with('author')
            ->latest('published_at')
            ->limit(20)
            ->get();

        $xml = view('public.feed', ['posts' => $posts])->render();

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
