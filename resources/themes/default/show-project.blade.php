@extends('theme::layout')

@php
    $metaTitle = $post->seoTitle();
    $metaDescription = $post->seoDescription();
    $clientName = $post->getMeta('client_name');
    $projectUrl = $post->getMeta('project_url');
    $adjacent = $post->adjacent();
    $related = $post->related(3);

    // Dịch vụ đã dùng trong dự án là lối đi tiếp tự nhiên nhất từ một case
    // study. Chỉ lấy dịch vụ đã xuất bản; không có thì khối này biến mất.
    $services = \App\Models\Post::ofType('service')->published()
        ->with(['author', 'terms', 'meta'])
        ->latest('published_at')->limit(3)->get();

    $breadcrumbs = [
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Dự án', 'url' => route('projects.index')],
        ['label' => $post->title],
    ];
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
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $post->title,
            'description' => $metaDescription,
            'url' => url()->current(),
            'dateCreated' => $post->published_at?->toIso8601String(),
            'creator' => ['@id' => url('/').'#organization'],
            ...($post->featured_image ? ['image' => $post->featured_image] : []),
            ...($clientName ? ['about' => ['@type' => 'Organization', 'name' => $clientName]] : []),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-marketing.page-hero
        :breadcrumbs="$breadcrumbs"
        :eyebrow="$clientName ? 'Dự án · '.$clientName : 'Dự án'"
        :subtitle="$post->excerpt ?: null"
        :image="$post->featured_image"
        :imageAlt="$post->title">
        {{ $post->title }}
    </x-marketing.page-hero>

    @if ($clientName || $projectUrl || $post->published_at)
        <div class="reveal mt-12 flex flex-wrap items-center gap-x-16 gap-y-6 border-y border-border py-7">
            @if ($clientName)
                <div>
                    <p class="eyebrow mb-2">Khách hàng</p>
                    <p class="font-heading text-xl font-medium">{{ $clientName }}</p>
                </div>
            @endif

            @if ($post->published_at)
                <div>
                    <p class="eyebrow mb-2">Thời điểm</p>
                    <p class="font-heading text-xl font-medium">{{ $post->published_at->format('m/Y') }}</p>
                </div>
            @endif

            @if ($projectUrl)
                <div class="ml-auto self-center">
                    <a href="{{ $projectUrl }}" target="_blank" rel="noopener noreferrer nofollow"
                       class="group inline-flex items-center gap-3 font-sans font-semibold">
                        <span class="border-b-2 border-primary pb-1">Xem dự án trực tiếp</span>
                        <span class="text-primary transition-transform group-hover:translate-x-1" aria-hidden="true">↗</span>
                    </a>
                </div>
            @endif
        </div>
    @endif

    <div class="py-14 sm:py-20">
        <div class="max-w-3xl">
            {{-- $post->content đã được sanitize phía server lúc lưu --}}
            <div class="prose-content">{!! $post->content !!}</div>

            @if ($post->faqItems())
                <x-faq-block :items="$post->faqItems()" />
            @endif

            <x-marketing.share :title="$post->title" class="mt-10 flex-wrap" />

            <x-marketing.post-nav :prev="$adjacent['prev']" :next="$adjacent['next']" />
        </div>

        <x-marketing.related :items="$related" eyebrow="Xem thêm" title="Dự án khác" />

        @if ($services->isNotEmpty())
            <x-marketing.related :items="$services" eyebrow="Chúng tôi đã dùng gì" title="Dịch vụ liên quan" />
        @endif
    </div>
@endsection
