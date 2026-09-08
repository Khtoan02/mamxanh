<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\SeoTemplate;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'post_type', 'title', 'slug', 'content', 'excerpt',
    'status', 'author_id', 'featured_image', 'published_at',
])]
class Post extends Model
{
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(Term::class);
    }

    public function meta(): HasMany
    {
        return $this->hasMany(PostMeta::class);
    }

    public function scopeOfType($query, string $postType)
    {
        return $query->where('post_type', $postType);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta->firstWhere('meta_key', $key)?->meta_value ?? $default;
    }

    public function setMeta(string $key, ?string $value): void
    {
        $this->meta()->updateOrCreate(['meta_key' => $key], ['meta_value' => $value]);
    }

    private const ROBOTS_DIRECTIVES = [
        'noindex_follow' => 'noindex, follow',
        'index_nofollow' => 'index, nofollow',
        'noindex_nofollow' => 'noindex, nofollow',
    ];

    /**
     * The real `<meta name="robots">` content for this post — stored as a
     * short code (`robots_directive` meta) rather than the literal
     * "noindex, follow" string, since that string contains a comma and
     * can't be validated with Laravel's `in:` rule as-is.
     */
    public function robotsContent(): string
    {
        return self::ROBOTS_DIRECTIVES[$this->getMeta('robots_directive')] ?? 'index, follow';
    }

    /**
     * Rendered <title> for this post's public page — the manual "Tiêu đề
     * SEO" override (if set) still gets wrapped by the site's title
     * template (see /admin/seo/titles), it never bypasses it entirely.
     */
    public function seoTitle(): string
    {
        $template = Setting::get('seo_title_'.$this->post_type, '%title% %sep% %sitename%');

        return SeoTemplate::render($template, ['title' => $this->getMeta('meta_title') ?: $this->title]);
    }

    public function seoDescription(): string
    {
        return $this->getMeta('meta_description')
            ?: ($this->excerpt ?: Str::limit(strip_tags($this->content ?? ''), 160));
    }

    /**
     * Best-effort digit extraction from the free-text "Giá" custom field
     * (e.g. "299.000đ" → 299000). Returns null (not 0) when nothing
     * parseable is found (e.g. "Miễn phí", "Liên hệ") — every consumer of
     * this (WooCommerce sync, Product schema.org, the Google Merchant feed)
     * needs to treat "no real price" as an explicit absence, not a guess.
     */
    public function priceAmount(string $metaKey = 'price'): ?int
    {
        $digits = preg_replace('/\D/', '', (string) $this->getMeta($metaKey));

        return $digits !== '' ? (int) $digits : null;
    }

    /**
     * Canonical public URL — every template linked to a post by hand-writing
     * route('post.show', $post->slug); centralising it means a future URL
     * scheme change (e.g. /blog/{slug}) is one edit, not thirty.
     */
    public function url(): string
    {
        return route('post.show', $this->slug);
    }

    /**
     * Reading time in minutes from the REAL rendered text (tags stripped),
     * at 200 wpm — a widely used average for adult reading of prose. Never
     * returns 0, because "0 phút đọc" reads as broken rather than short.
     */
    public function readingTime(): int
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $this->content)));

        if ($text === '') {
            return 1;
        }

        return max(1, (int) ceil(count(preg_split('/\s+/u', $text)) / 200));
    }

    /**
     * The term used as the post's "home" taxonomy in breadcrumbs and cards —
     * first term of the hierarchical taxonomy registered for this post type
     * (category for posts, product_category for products). Returns null
     * rather than inventing a fallback when the author picked nothing.
     */
    public function primaryTerm(): ?Term
    {
        foreach (get_taxonomies_for_post_type($this->post_type) as $taxonomy) {
            if (($taxonomy['hierarchical'] ?? false) === true) {
                $term = $this->terms->firstWhere('taxonomy', $taxonomy['slug']);

                if ($term) {
                    return $term;
                }
            }
        }

        return null;
    }

    /**
     * Related content, best-effort and honest about it: posts sharing at
     * least one term first (ordered by how many terms they share), topped up
     * with recent same-type posts only if that yields fewer than $limit.
     * Never pads with unrelated content and then calls it "related" — the
     * caller can see how many came back.
     *
     * @return \Illuminate\Support\Collection<int, Post>
     */
    public function related(int $limit = 3): \Illuminate\Support\Collection
    {
        $termIds = $this->terms->pluck('id');

        $byTerm = collect();

        if ($termIds->isNotEmpty()) {
            $byTerm = static::query()
                ->published()
                ->ofType($this->post_type)
                // <x-marketing.content-card> gọi primaryTerm() và getMeta()
                // cho từng thẻ; không nạp sẵn thì mỗi thẻ liên quan tốn thêm
                // 3 truy vấn (terms, meta, author).
                ->with(['author', 'terms', 'meta'])
                ->whereKeyNot($this->getKey())
                ->whereHas('terms', fn ($q) => $q->whereIn('terms.id', $termIds))
                ->withCount(['terms' => fn ($q) => $q->whereIn('terms.id', $termIds)])
                ->orderByDesc('terms_count')
                ->latest('published_at')
                ->limit($limit)
                ->get();
        }

        if ($byTerm->count() >= $limit) {
            return $byTerm;
        }

        $filler = static::query()
            ->published()
            ->ofType($this->post_type)
            ->with(['author', 'terms', 'meta'])
            ->whereKeyNot($this->getKey())
            ->whereNotIn('id', $byTerm->pluck('id'))
            ->latest('published_at')
            ->limit($limit - $byTerm->count())
            ->get();

        return $byTerm->concat($filler);
    }

    /**
     * Previous/next in the same post type by publish date — a real reading
     * path, not a random "you might also like". Either side can be null at
     * the ends of the archive; templates must handle that.
     *
     * @return array{prev: ?Post, next: ?Post}
     */
    public function adjacent(): array
    {
        $base = fn () => static::query()->published()->ofType($this->post_type)->whereKeyNot($this->getKey());

        return [
            'prev' => $base()->where('published_at', '<', $this->published_at)->latest('published_at')->first(),
            'next' => $base()->where('published_at', '>', $this->published_at)->oldest('published_at')->first(),
        ];
    }

    /**
     * Real FAQ pairs the author actually typed in — never fabricated. Drives
     * both the visible Q&A block and the FAQPage schema from one source, so
     * the two can't drift. Rows with a blank question or answer are dropped
     * rather than shown/schema'd empty.
     *
     * @return array<int, array{q: string, a: string}>
     */
    public function faqItems(): array
    {
        $raw = $this->getMeta('faq_items');

        if (! $raw) {
            return [];
        }

        $items = json_decode($raw, true) ?: [];

        return array_values(array_filter(
            $items,
            fn ($item) => filled($item['q'] ?? null) && filled($item['a'] ?? null)
        ));
    }
}
