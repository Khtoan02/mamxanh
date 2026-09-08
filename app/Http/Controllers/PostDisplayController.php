<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Setting;
use App\Support\GoogleProductFeed;

class PostDisplayController extends Controller
{
    public function index()
    {
        $query = Post::ofType('post')
            ->published()
            ->with(['author', 'terms', 'meta'])
            ->latest('published_at');

        // Bài mới nhất được kéo ra làm bài nổi bật ở đầu trang, nên phải loại
        // khỏi danh sách bên dưới — nếu không nó xuất hiện 2 lần trên cùng
        // 1 màn hình. Chỉ làm vậy ở trang 1: từ trang 2 trở đi không còn khối
        // nổi bật nên loại tiếp sẽ làm mất hẳn 1 bài khỏi archive.
        $onFirstPage = (int) request()->query('page', 1) <= 1;
        $featured = $onFirstPage ? (clone $query)->first() : null;

        $posts = $query
            ->when($featured, fn ($q) => $q->whereKeyNot($featured->getKey()))
            ->paginate(9);

        // Chỉ danh mục/thẻ THẬT SỰ có bài đã xuất bản — link tới một archive
        // rỗng là ngõ cụt, và với Google là một trang mỏng vô ích.
        $terms = \App\Models\Term::whereIn('taxonomy', ['category', 'post_tag'])
            ->whereHas('posts', fn ($q) => $q->where('status', 'published')->where('post_type', 'post'))
            ->withCount(['posts' => fn ($q) => $q->where('status', 'published')->where('post_type', 'post')])
            ->orderByDesc('posts_count')
            ->get()
            ->groupBy('taxonomy');

        return view('theme::index', [
            'posts' => $posts,
            'featured' => $featured,
            'categories' => $terms->get('category') ?? collect(),
            'tags' => $terms->get('post_tag') ?? collect(),
        ]);
    }

    public function show(string $slug)
    {
        $post = Post::where('slug', $slug)->with(['author', 'terms'])->firstOrFail();

        // Draft/unpublished content is only viewable by whoever could have
        // written it — everyone else gets the same 404 as a missing slug.
        if ($post->status !== 'published' && ! auth()->user()?->hasCapability('edit_posts')) {
            abort(404);
        }

        // Theme Hierarchy: a per-type template (show-product, show-project...)
        // if the active theme has one, otherwise the generic show template —
        // same fallback mechanism as the theme:: namespace itself.
        $view = view()->exists("theme::show-{$post->post_type}") ? "theme::show-{$post->post_type}" : 'theme::show';

        return view($view, ['post' => $post]);
    }

    public function sitemap()
    {
        $excluded = json_decode(Setting::get('sitemap_excluded_types', '[]'), true) ?: [];

        $posts = Post::published()
            ->whereNotIn('post_type', $excluded)
            ->orderBy('slug')
            ->get(['slug', 'updated_at', 'post_type', 'featured_image', 'title']);

        // Trang danh sách cố định. Chỉ đưa vào những route thật sự tồn tại
        // và luôn trả 200 — /blog, /dich-vu, /du-an, /san-pham, /lien-he.
        $archives = [
            route('blog.index'),
            route('services.index'),
            route('projects.index'),
            route('products.index'),
            route('contact.show'),
        ];

        // Archive theo term: chỉ term có nội dung đã xuất bản VÀ có route
        // archive (Term::url() trả null cho taxonomy chưa đăng ký prefix).
        $terms = \App\Models\Term::whereHas('posts', fn ($q) => $q->where('status', 'published'))
            ->get()
            ->map(fn (\App\Models\Term $term) => ['url' => $term->url()])
            ->filter(fn (array $row) => $row['url'] !== null)
            ->values();

        $xml = view('public.sitemap', [
            'posts' => $posts,
            'archives' => $archives,
            'terms' => $terms,
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Google Merchant Center product feed (RSS 2.0 + the `g:` namespace) —
     * see `App\Support\GoogleProductFeed` for exactly which products
     * qualify and why. Registering this URL in Merchant Center is a manual
     * one-time step on Google's side, outside this app's reach.
     */
    public function productFeed()
    {
        $xml = view('public.product-feed', ['products' => GoogleProductFeed::eligibleProducts()])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function robotsTxt()
    {
        $default = app(\App\Http\Controllers\Admin\SeoController::class)->defaultRobotsTxt();

        return response(Setting::get('robots_txt', $default), 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Serves the IndexNow verification key back at /{key}.txt — the
     * protocol's own ownership-proof mechanism (see SettingsController's
     * seo() for where the key is generated/persisted).
     */
    public function indexNowKeyFile(string $key)
    {
        abort_unless($key === Setting::get('indexnow_key'), 404);

        return response($key, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Real, live-queried site summary in the emerging llms.txt convention —
     * Google explicitly ignores this file entirely (confirmed in their own
     * AI-optimization guide), so this exists only as a courtesy for OTHER
     * AI systems that do read it, never marketed as an SEO/Google feature.
     * Every line is real published content, nothing fabricated.
     */
    public function llmsTxt()
    {
        $byType = Post::published()
            ->with('author')
            ->latest('published_at')
            ->get(['id', 'post_type', 'title', 'slug', 'excerpt', 'content'])
            ->groupBy('post_type');

        $labels = ['service' => 'Dịch vụ', 'project' => 'Dự án', 'product' => 'Sản phẩm', 'post' => 'Bài viết', 'page' => 'Trang'];

        $lines = [];
        $lines[] = '# '.config('app.name');
        if ($tagline = env('SITE_TAGLINE')) {
            $lines[] = '';
            $lines[] = '> '.$tagline;
        }

        foreach (['service', 'product', 'project', 'post', 'page'] as $type) {
            $posts = $byType->get($type);
            if (! $posts || $posts->isEmpty()) {
                continue;
            }

            $lines[] = '';
            $lines[] = '## '.($labels[$type] ?? ucfirst($type));

            // Bài viết could grow without bound over time — cap it so this
            // file stays a reasonable size instead of listing every post
            // ever published.
            foreach ($posts->take(50) as $post) {
                $summary = $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content ?? ''), 140);
                $url = route('post.show', $post->slug);
                $lines[] = $summary
                    ? "- [{$post->title}]({$url}): {$summary}"
                    : "- [{$post->title}]({$url})";
            }
        }

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
