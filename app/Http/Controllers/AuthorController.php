<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;

/**
 * Public author page. Exists for a concrete reason, not decoration: Google's
 * own guidance on people-first content asks for clear authorship, and the
 * post detail page already prints an author name, job title and bio that
 * previously linked nowhere.
 *
 * Only shows content the author actually published — no draft counts, no
 * invented "X years of experience".
 */
class AuthorController extends Controller
{
    public function show(string $slug)
    {
        // Accept the numeric id too, so links generated before a slug was
        // backfilled keep resolving instead of 404ing.
        $author = User::where('slug', $slug)
            ->when(ctype_digit($slug), fn ($q) => $q->orWhere('id', (int) $slug))
            ->firstOrFail();

        $posts = Post::published()
            ->where('author_id', $author->getKey())
            ->whereIn('post_type', ['post', 'service', 'project', 'product'])
            ->with(['author', 'terms', 'meta'])
            ->latest('published_at')
            ->paginate(12);

        abort_if($posts->total() === 0, 404);

        return view('theme::author', [
            'author' => $author,
            'posts' => $posts,
        ]);
    }
}
