<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\Term;
use App\Support\IndexNowPinger;
use App\Support\WooCommerceSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'post');
        $postType = get_post_type($type);
        abort_unless($postType, 404);

        $search = trim((string) $request->query('q', ''));

        $posts = Post::ofType($type)
            ->with(['author', 'meta', 'terms'])
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $primaryTaxonomy = $this->primaryHierarchicalTaxonomy($type);

        return view('admin.content.index', [
            'posts' => $posts,
            'type' => $type,
            'postType' => $postType,
            'listColumn' => $this->listColumnFor($type),
            'search' => $search,
            'primaryTaxonomy' => $primaryTaxonomy,
            'primaryTaxonomyTerms' => $primaryTaxonomy ? Term::ofTaxonomy($primaryTaxonomy['slug'])->orderBy('name')->get() : collect(),
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $type = Post::whereIn('id', $data['ids'])->value('post_type');

        $count = Post::whereIn('id', $data['ids'])->get()->each(function (Post $post) {
            $post->meta()->delete();
            $post->terms()->detach();
            $post->delete();
        })->count();

        return back()->with('status', "Đã xoá {$count} mục.");
    }

    /**
     * Only touches fields the admin actually picked ("giữ nguyên" = field
     * omitted entirely from the request, not sent as empty) — a bulk action
     * that silently blanks out everything not explicitly chosen would be a
     * much worse failure mode than doing nothing for that field.
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['nullable', 'in:draft,published'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
        ]);

        $posts = Post::whereIn('id', $data['ids'])->get();

        foreach ($posts as $post) {
            if (! empty($data['status'])) {
                $post->status = $data['status'];
                $post->published_at = $data['status'] === 'published' ? ($post->published_at ?? now()) : null;
                $post->save();
            }

            if (! empty($data['term_id'])) {
                $taxonomy = $this->primaryHierarchicalTaxonomy($post->post_type);
                if ($taxonomy) {
                    // Replaces only this post type's hierarchical taxonomy
                    // assignment — any tag-style (non-hierarchical) terms
                    // it also carries are untouched.
                    $otherTermIds = $post->terms()->where('taxonomy', '!=', $taxonomy['slug'])->pluck('terms.id');
                    $post->terms()->sync($otherTermIds->push((int) $data['term_id']));
                }
            }
        }

        return back()->with('status', "Đã cập nhật {$posts->count()} mục.");
    }

    /**
     * The first hierarchical (category-style) taxonomy registered for this
     * post type — used both for the list page's "Danh mục" column and for
     * what the bulk-edit "đổi danh mục" action reassigns. A post type can
     * only have 0 or 1 of these in practice today (post→category,
     * product→product_category), so "first" is unambiguous.
     *
     * @return array{slug: string, label: string}|null
     */
    private function primaryHierarchicalTaxonomy(string $postTypeSlug): ?array
    {
        foreach (get_taxonomies_for_post_type($postTypeSlug) as $slug => $taxonomy) {
            if ($taxonomy['hierarchical']) {
                return ['slug' => $slug, 'label' => $taxonomy['label']];
            }
        }

        return null;
    }

    /**
     * The one custom field (if any) worth showing as its own list column
     * for quick scanning — e.g. Sản phẩm's price, Dự án's client — rather
     * than dumping every registered custom field into the table.
     *
     * @return array{key: string, label: string}|null
     */
    private function listColumnFor(string $postTypeSlug): ?array
    {
        return match ($postTypeSlug) {
            'product' => ['key' => 'price', 'label' => 'Giá'],
            'project' => ['key' => 'client_name', 'label' => 'Khách hàng'],
            default => null,
        };
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'post');
        $postType = get_post_type($type);
        abort_unless($postType, 404);

        return view('admin.content.form', [
            'post' => new Post(['post_type' => $type]),
            'postType' => $postType,
            'taxonomies' => get_taxonomies_for_post_type($type),
            'terms' => $this->termsFor($type),
            'selectedTermIds' => [],
            'customFields' => $this->customFieldsFor($type),
        ]);
    }

    public function store(Request $request, WooCommerceSyncService $wooCommerce)
    {
        $type = (string) $request->input('post_type');
        $postType = get_post_type($type);
        abort_unless($postType, 404);

        $data = $this->validated($request, $postType);

        $post = new Post(['post_type' => $type, 'author_id' => $request->user()->id]);
        $this->fillAndSave($post, $data, $type);

        do_action('mxd_after_post_created', $post);

        if ($post->status === 'published') {
            IndexNowPinger::ping(route('post.show', $post->slug));
        }

        if ($type === 'product' && Setting::get('woocommerce_sync_enabled')) {
            $wooCommerce->push($post);
        }

        return redirect()->route('admin.content.index', ['type' => $type])->with('status', 'Đã lưu.');
    }

    public function edit(Post $post)
    {
        $postType = get_post_type($post->post_type);
        abort_unless($postType, 404);

        return view('admin.content.form', [
            'post' => $post,
            'postType' => $postType,
            'taxonomies' => get_taxonomies_for_post_type($post->post_type),
            'terms' => $this->termsFor($post->post_type),
            'selectedTermIds' => $post->terms->pluck('id')->all(),
            'customFields' => $this->customFieldsFor($post->post_type),
        ]);
    }

    public function update(Request $request, Post $post, WooCommerceSyncService $wooCommerce)
    {
        $postType = get_post_type($post->post_type);
        abort_unless($postType, 404);

        $oldSlug = $post->slug;
        $wasPublished = $post->status === 'published';

        $data = $this->validated($request, $postType, $post->id);
        $this->fillAndSave($post, $data, $post->post_type);

        // A published post's slug changing means whatever indexed/shared the
        // old URL now 404s — auto-create the redirect rather than relying on
        // the admin to remember. Drafts never went live, so no old URL to
        // preserve. `firstOrCreate` avoids piling up duplicate rules if the
        // post is saved again without the slug changing again.
        if ($wasPublished && $oldSlug !== $post->slug) {
            Redirect::firstOrCreate(
                ['source' => $oldSlug, 'match_type' => 'exact'],
                ['target' => '/'.$post->slug, 'status_code' => 301, 'is_active' => true]
            );
        }

        if ($post->status === 'published') {
            IndexNowPinger::ping(route('post.show', $post->slug));
        }

        if ($post->post_type === 'product' && Setting::get('woocommerce_sync_enabled')) {
            $wooCommerce->push($post);
        }

        return redirect()->route('admin.content.index', ['type' => $post->post_type])->with('status', 'Đã cập nhật.');
    }

    public function destroy(Post $post, WooCommerceSyncService $wooCommerce)
    {
        $type = $post->post_type;

        if ($type === 'product') {
            $wooCommerce->delete($post);
        }

        $post->delete();

        return redirect()->route('admin.content.index', ['type' => $type])->with('status', 'Đã xoá.');
    }

    /**
     * @param  array<string, mixed>  $postType
     * @return array<string, mixed>
     */
    private function validated(Request $request, array $postType, ?int $ignoreId = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'robots_directive' => ['nullable', 'in:noindex_follow,index_nofollow,noindex_nofollow'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'faq_items' => ['nullable', 'json'],
        ];

        foreach (get_taxonomies_for_post_type($postType['slug']) as $slug => $taxonomy) {
            $rules["taxonomy_{$slug}"] = $taxonomy['hierarchical'] ? ['nullable', 'integer'] : ['nullable', 'string'];
        }

        foreach (array_keys($this->customFieldsFor($postType['slug'])) as $field) {
            $rules[$field] = ['nullable', 'string', 'max:2048'];
        }

        if (in_array('editor', $postType['supports'], true)) {
            $rules['content'] = ['nullable', 'string'];
        }

        if (in_array('excerpt', $postType['supports'], true)) {
            $rules['excerpt'] = ['nullable', 'string', 'max:500'];
        }

        if (in_array('featured_image', $postType['supports'], true)) {
            $rules['featured_image'] = ['nullable', 'string', 'max:2048'];
        }

        $data = $request->validate($rules);

        // Defense in depth: the create/edit routes already require
        // edit_posts, but only publish_posts holders may ship a post live.
        if (! $request->user()->hasCapability('publish_posts')) {
            $data['status'] = 'draft';
        }

        $data['ignore_id'] = $ignoreId;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillAndSave(Post $post, array $data, string $postTypeSlug): void
    {
        $post->title = $data['title'];
        $post->slug = $this->uniqueSlug($data['slug'] ?: $data['title'], $data['ignore_id']);
        // Sanitized server-side (config/purifier.php) — the TinyMCE editor is
        // not a trust boundary, a raw POST could bypass it entirely.
        $post->content = isset($data['content']) ? clean($data['content']) : null;
        $post->excerpt = $data['excerpt'] ?? null;
        $post->featured_image = $data['featured_image'] ?? null;
        $post->status = $data['status'];
        $post->published_at = $data['status'] === 'published' ? ($post->published_at ?? now()) : null;
        $post->save();

        $post->terms()->sync($this->resolveTermIds($data, $postTypeSlug));

        foreach (['meta_title', 'meta_description', 'focus_keyword', 'robots_directive', 'canonical_url', 'faq_items'] as $seoField) {
            $post->setMeta($seoField, $data[$seoField] ?? null);
        }

        foreach (array_keys($this->customFieldsFor($postTypeSlug)) as $field) {
            $post->setMeta($field, $data[$field] ?? null);
        }
    }

    /**
     * Only attaches terms for taxonomies actually registered against this
     * post type (e.g. `page` has none) — even if the raw request smuggled a
     * `taxonomy_*` value in anyway. Hierarchical taxonomies (category-like)
     * submit a single term id; non-hierarchical ones (tag-like) submit
     * comma-separated names and get new Term rows created on the fly.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    private function resolveTermIds(array $data, string $postTypeSlug): array
    {
        $termIds = [];

        foreach (get_taxonomies_for_post_type($postTypeSlug) as $slug => $taxonomy) {
            $value = $data["taxonomy_{$slug}"] ?? null;

            if ($taxonomy['hierarchical']) {
                if (! empty($value)) {
                    $termIds[] = (int) $value;
                }

                continue;
            }

            foreach (explode(',', (string) $value) as $termName) {
                $termName = trim($termName);
                if ($termName === '') {
                    continue;
                }

                $term = Term::firstOrCreate(
                    ['taxonomy' => $slug, 'slug' => Str::slug($termName)],
                    ['name' => $termName]
                );
                $termIds[] = $term->id;
            }
        }

        return $termIds;
    }

    /**
     * Per-post-type custom fields, stored via Post::setMeta()/getMeta()
     * (the Custom Fields Engine) — key => admin-facing label.
     *
     * @return array<string, string>
     */
    private function customFieldsFor(string $postTypeSlug): array
    {
        return match ($postTypeSlug) {
            'product' => [
                'price' => 'Giá hiển thị (vd: 299.000đ, hoặc "Miễn phí")',
                'external_url' => 'Link mua / affiliate',
                'cta_label' => 'Nhãn nút (mặc định: Mua ngay)',
            ],
            'project' => [
                'client_name' => 'Tên khách hàng',
                'project_url' => 'Link xem dự án (nếu có)',
            ],
            'service' => [
                'price_from' => 'Giá từ (vd: 15.000.000đ — để trống nếu chỉ báo giá theo yêu cầu)',
                'duration' => 'Thời gian thực hiện (vd: 3–4 tuần)',
            ],
            default => [],
        };
    }

    private function uniqueSlug(string $source, ?int $ignoreId): string
    {
        $base = Str::slug($source) ?: 'untitled';
        $slug = $base;
        $i = 2;

        while (
            Post::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, Term>>
     */
    private function termsFor(string $postType)
    {
        $taxonomySlugs = array_keys(get_taxonomies_for_post_type($postType));

        return Term::whereIn('taxonomy', $taxonomySlugs)->orderBy('name')->get()->groupBy('taxonomy');
    }
}
