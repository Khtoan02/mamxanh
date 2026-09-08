@extends('theme::layout')

@php
    $category = $post->primaryTerm();
    $tags = $post->terms->where('taxonomy', 'post_tag');
    $metaTitle = $post->seoTitle();
    $metaDescription = $post->seoDescription();
    $adjacent = $post->post_type === 'post' ? $post->adjacent() : ['prev' => null, 'next' => null];
    $related = $post->related(3);

    // 'page' không có archive cha tự nhiên — breadcrumb đi thẳng Trang chủ >
    // Tiêu đề, không bịa ra một mắt xích ở giữa.
    $breadcrumbParent = match ($post->post_type) {
        'post' => ['label' => 'Bài viết', 'url' => route('blog.index')],
        'service' => ['label' => 'Dịch vụ', 'url' => route('services.index')],
        default => null,
    };
    $breadcrumbs = array_values(array_filter([
        ['label' => 'Trang chủ', 'url' => route('home')],
        $breadcrumbParent,
        // Danh mục chỉ chen vào breadcrumb khi nó có archive thật để trỏ tới.
        $category && $category->url() ? ['label' => $category->name, 'url' => $category->url()] : null,
        ['label' => $post->title],
    ]));
@endphp

@section('title', $metaTitle)
@section('description', $metaDescription)
@section('robots', $post->robotsContent())
@if ($post->getMeta('canonical_url'))
    @section('canonical', $post->getMeta('canonical_url'))
@endif
@section('og_type', 'article')
@if ($post->featured_image)
    @section('og_image', $post->featured_image)
@endif
@if ($post->published_at)
    @section('article_published_time', $post->published_at->toIso8601String())
@endif
@section('article_modified_time', $post->updated_at->toIso8601String())

@push('schema')
    <script type="application/ld+json">
        {!! json_encode(
            $post->post_type === 'service'
                ? array_filter([
                    '@@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $metaTitle,
                    'description' => $metaDescription,
                    'provider' => ['@id' => url('/').'#organization'],
                    'image' => $post->featured_image ?: null,
                ])
                : [
                    '@@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $metaTitle,
                    'description' => $metaDescription,
                    'datePublished' => $post->published_at?->toIso8601String(),
                    'dateModified' => $post->updated_at->toIso8601String(),
                    'mainEntityOfPage' => url()->current(),
                    'publisher' => ['@id' => url('/').'#organization'],
                    'author' => array_filter([
                        '@type' => 'Person',
                        'name' => $post->author->name,
                        'url' => $post->author->url(),
                        'jobTitle' => $post->author->job_title,
                        'description' => $post->author->bio,
                    ]),
                    ...($post->featured_image ? ['image' => $post->featured_image] : []),
                ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) !!}
    </script>
@endpush

@section('content')
<div class="py-10 sm:py-14">
    {{-- Khung 1440px hợp cho lưới, nhưng dòng chữ dài quá 75 ký tự thì mắt
         khó bắt dòng — nên phần đọc dài giữ khung riêng hẹp hơn. --}}
    <div class="max-w-3xl mx-auto">
        <x-breadcrumbs :items="$breadcrumbs" />
    </div>

    <article class="max-w-3xl mx-auto">
        <header class="reveal">
            @if ($category)
                @if ($category->url())
                    <a href="{{ $category->url() }}" class="eyebrow hover:text-primary transition-colors">{{ $category->name }}</a>
                @else
                    <span class="eyebrow">{{ $category->name }}</span>
                @endif
            @endif

            <h1 class="mt-3 font-heading text-3xl sm:text-4xl lg:text-[2.75rem] font-semibold leading-[1.15] tracking-tight text-balance">
                {{ $post->title }}
            </h1>

            @if ($post->excerpt)
                <p class="lede mt-6 text-muted-foreground">{{ $post->excerpt }}</p>
            @endif

            <div class="mt-7 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-muted-foreground">
                <x-avatar :name="$post->author->name" />
                <a href="{{ $post->author->url() }}" class="font-medium text-foreground hover:text-primary transition-colors">{{ $post->author->name }}</a>
                @if ($post->author->job_title)
                    <span class="text-xs">({{ $post->author->job_title }})</span>
                @endif
                <span aria-hidden="true">·</span>
                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d/m/Y') }}</time>
                @if ($post->post_type === 'post')
                    <span aria-hidden="true">·</span>
                    <span class="tabular-nums">{{ $post->readingTime() }} phút đọc</span>
                @endif
            </div>
        </header>

        @if ($post->featured_image)
            <figure class="reveal-image mt-10 card-media aspect-[16/9]">
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" fetchpriority="high" decoding="async">
            </figure>
        @endif

        {{-- $post->content đã được sanitize phía server lúc lưu (config/purifier.php) --}}
        <div class="prose-content mt-10">{!! $post->content !!}</div>

        @if ($tags->isNotEmpty())
            <div class="mt-12 pt-7 border-t border-border flex flex-wrap items-center gap-2.5">
                <span class="eyebrow mr-1">Thẻ</span>
                @foreach ($tags as $tag)
                    @if ($tag->url())
                        <a href="{{ $tag->url() }}" class="filter-chip">#{{ $tag->name }}</a>
                    @else
                        <span class="filter-chip">#{{ $tag->name }}</span>
                    @endif
                @endforeach
            </div>
        @endif

        <x-marketing.share :title="$post->title" class="mt-8 flex-wrap" />

        @if ($post->faqItems())
            <x-faq-block :items="$post->faqItems()" />
        @endif

        <x-marketing.author-box :author="$post->author" />

        <x-marketing.post-nav :prev="$adjacent['prev']" :next="$adjacent['next']" />
    </article>

    <x-marketing.related
        :items="$related"
        :eyebrow="$post->post_type === 'service' ? 'Dịch vụ khác' : 'Đọc tiếp'"
        :title="$post->post_type === 'service' ? 'Những việc chúng tôi cũng làm' : 'Bài viết liên quan'" />
</div>
@endsection
