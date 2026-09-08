<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Term;

/**
 * Public listing pages for the non-blog CPTs (service/project/product) —
 * one thin method per type so route names stay clean (services.index etc.),
 * all sharing the same render() so adding a future CPT archive is a
 * two-line addition.
 */
class ArchiveController extends Controller
{
    public function services()
    {
        return $this->render('service');
    }

    public function projects()
    {
        return $this->render('project');
    }

    public function products()
    {
        return $this->render('product');
    }

    /**
     * One shared template for all three archives — each renders the
     * type-specific fields (price/CTA, client name...) conditionally based
     * on $postType['slug'].
     */
    private function render(string $postType)
    {
        $posts = Post::ofType($postType)
            ->published()
            ->with(['author', 'terms', 'meta'])
            ->latest('published_at')
            ->paginate(12);

        // Chip lọc theo taxonomy của chính loại nội dung này — chỉ term có
        // bài đã xuất bản. Trước đây trang Sản phẩm không có lối vào nào cho
        // "Loại sản phẩm" dù taxonomy đã đăng ký và biên tập viên đã gán.
        $taxonomySlugs = array_keys(get_taxonomies_for_post_type($postType));

        $terms = $taxonomySlugs === [] ? collect() : Term::whereIn('taxonomy', $taxonomySlugs)
            ->whereHas('posts', fn ($q) => $q->where('status', 'published')->where('post_type', $postType))
            ->withCount(['posts' => fn ($q) => $q->where('status', 'published')->where('post_type', $postType)])
            ->orderByDesc('posts_count')
            ->get()
            ->filter(fn (Term $term) => $term->url() !== null)
            ->groupBy('taxonomy');

        return view('theme::archive', [
            'posts' => $posts,
            'postType' => get_post_type($postType),
            'termGroups' => $terms,
        ]);
    }
}
