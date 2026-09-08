@extends('theme::layout')

@php
    $category = $post->primaryTerm();
    $tags = $post->terms->where('taxonomy', 'product_tag');
    $metaTitle = $post->seoTitle();
    $metaDescription = $post->seoDescription();
    $price = $post->getMeta('price');
    $externalUrl = $post->getMeta('external_url');
    $ctaLabel = $post->getMeta('cta_label') ?: 'Mua ngay';
    $related = $post->related(3);

    $breadcrumbs = array_values(array_filter([
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Sản phẩm', 'url' => route('products.index')],
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
@section('og_type', 'product')
@if ($post->featured_image)
    @section('og_image', $post->featured_image)
@endif

@push('schema')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $post->title,
            'description' => $metaDescription,
            'url' => url()->current(),
            ...($category ? ['category' => $category->name] : []),
            ...($post->featured_image ? ['image' => $post->featured_image] : []),
            // price PHẢI là số, không phải chuỗi hiển thị ("299.000đ"), và
            // chỉ phát Offer khi có giá thật — "Liên hệ" không phải là giá.
            ...($post->priceAmount() ? ['offers' => [
                '@type' => 'Offer',
                'price' => $post->priceAmount(),
                'priceCurrency' => 'VND',
                'availability' => 'https://schema.org/InStock',
                'url' => url()->current(),
            ]] : []),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
<div class="py-10 sm:py-14">
    <x-breadcrumbs :items="$breadcrumbs" />

    <article class="mt-4 grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
        <div class="lg:col-span-7 reveal-image">
            <div class="card-media aspect-[4/3]">
                @if ($post->featured_image)
                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" fetchpriority="high" decoding="async">
                @else
                    <span class="card-media-empty h-full w-full"><x-icon name="image" class="h-10 w-10" /></span>
                @endif
            </div>
        </div>

        <div class="lg:col-span-5 reveal lg:sticky lg:top-24">
            @if ($category)
                @if ($category->url())
                    <a href="{{ $category->url() }}" class="eyebrow hover:text-primary transition-colors">{{ $category->name }}</a>
                @else
                    <span class="eyebrow">{{ $category->name }}</span>
                @endif
            @endif

            <h1 class="mt-3 font-heading text-3xl sm:text-4xl font-semibold leading-tight tracking-tight text-balance">{{ $post->title }}</h1>

            @if ($post->excerpt)
                <p class="mt-5 text-muted-foreground leading-relaxed">{{ $post->excerpt }}</p>
            @endif

            <div class="mt-8 pt-7 border-t border-border">
                @if ($price)
                    <p class="eyebrow mb-2">Giá</p>
                    <p class="font-heading text-3xl font-semibold text-primary">{{ $price }}</p>
                @else
                    {{-- Không có giá thì nói thẳng là chưa niêm yết, chứ không
                         để trống làm người đọc tưởng trang bị lỗi. --}}
                    <p class="eyebrow mb-2">Giá</p>
                    <p class="font-heading text-2xl font-semibold">Liên hệ để báo giá</p>
                @endif

                <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3">
                    @if ($externalUrl)
                        <a href="{{ $externalUrl }}" target="_blank" rel="noopener noreferrer nofollow"
                           class="inline-flex items-center justify-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold bg-primary text-primary-foreground hover:opacity-90 transition-opacity">
                            {{ $ctaLabel }}
                        </a>
                    @endif

                    <a href="{{ route('contact.show') }}" class="group inline-flex items-center gap-2 font-sans text-sm font-semibold">
                        <span class="border-b-2 border-primary pb-0.5">Hỏi thêm về sản phẩm này</span>
                        <span class="text-primary transition-transform group-hover:translate-x-0.5" aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

            @if ($tags->isNotEmpty())
                <div class="mt-8 flex flex-wrap gap-2.5">
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
        </div>
    </article>

    @if (trim(strip_tags((string) $post->content)) !== '')
        <div class="mt-16 max-w-3xl">
            <h2 class="font-heading text-2xl font-semibold tracking-tight mb-6">Chi tiết sản phẩm</h2>
            {{-- $post->content đã được sanitize phía server lúc lưu --}}
            <div class="prose-content">{!! $post->content !!}</div>
        </div>
    @endif

    @if ($post->faqItems())
        <div class="max-w-3xl">
            <x-faq-block :items="$post->faqItems()" />
        </div>
    @endif

    <x-marketing.related :items="$related" eyebrow="Có thể bạn quan tâm" title="Sản phẩm liên quan" ratio="aspect-square" />
</div>
@endsection
