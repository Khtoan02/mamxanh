@extends('theme::layout')

@php
    $breadcrumbs = [
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Bài viết'],
    ];
@endphp

@section('title', \App\Support\SeoTemplate::render(\App\Models\Setting::get('seo_title_archive', '%term% %sep% %sitename%'), ['term' => 'Bài viết']))
@section('description', \App\Support\SeoTemplate::render(\App\Models\Setting::get('seo_desc_archive', '%term% %sep% %sitename%'), ['term' => 'Bài viết']))

@push('schema')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => 'Bài viết — '.config('app.name'),
            'url' => route('blog.index'),
            'publisher' => ['@id' => url('/').'#organization'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-marketing.page-hero
        :breadcrumbs="$breadcrumbs"
        eyebrow="Ghi chép"
        subtitle="Những gì chúng tôi học được khi làm nghề — viết ra để bạn không phải trả học phí lần nữa.">
        Bài viết
    </x-marketing.page-hero>

    @if ($categories->isNotEmpty())
        <nav aria-label="Danh mục bài viết" class="chip-row reveal mt-10">
            @foreach ($categories as $category)
                @if ($category->url())
                    <a href="{{ $category->url() }}" class="filter-chip">
                        {{ $category->name }} <span class="tabular-nums opacity-70">{{ $category->posts_count }}</span>
                    </a>
                @endif
            @endforeach
        </nav>
    @endif

    @if (! $featured && $posts->isEmpty())
        <div class="pb-20 sm:pb-28">
            <x-marketing.empty-state class="mt-12" icon="file-text" title="Chưa có bài viết nào"
                :action="route('contact.show')" actionLabel="Liên hệ với chúng tôi">
                Chúng tôi đang chuẩn bị những bài đầu tiên. Trong lúc chờ, bạn có thể trò chuyện trực tiếp với đội ngũ.
            </x-marketing.empty-state>
        </div>
    @else
        {{-- Bài nổi bật: bài mới nhất, trình bày lớn hơn hẳn phần còn lại để
             trang có điểm vào rõ ràng thay vì một lưới phẳng đều nhau. --}}
        @if ($featured)
            <article class="entry-card group mt-14 grid lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                <a href="{{ $featured->url() }}" class="card-media aspect-[16/10] lg:aspect-[4/3] block lg:col-span-7 reveal-image"
                   tabindex="-1" aria-hidden="true">
                    @if ($featured->featured_image)
                        <img src="{{ $featured->featured_image }}" alt="" fetchpriority="high" decoding="async">
                    @else
                        <span class="card-media-empty h-full w-full"><x-icon name="image" class="h-9 w-9" /></span>
                    @endif
                </a>

                <div class="lg:col-span-5 reveal">
                    @php $featuredTerm = $featured->primaryTerm(); @endphp
                    {{-- Ghép chuỗi trong PHP thay vì viết @if dính liền chữ:
                         Blade dùng \B trước @ khi dò directive, nên một @if viết dính ngay sau chữ
                         KHÔNG được biên dịch trong khi "@endif" (đứng sau dấu
                         đóng ngoặc) thì có → view vỡ với "unexpected endif".
                         Lỗi này chỉ lộ ra khi theme cha được kích hoạt. --}}
                    <p class="eyebrow mb-3">
                        Mới nhất{{ $featuredTerm ? ' · '.$featuredTerm->name : '' }}
                    </p>

                    <h2 class="font-heading text-2xl sm:text-3xl lg:text-4xl font-semibold leading-tight tracking-tight text-balance">
                        <a href="{{ $featured->url() }}">{{ $featured->title }}</a>
                    </h2>

                    @if ($featured->excerpt)
                        <p class="mt-5 text-muted-foreground leading-relaxed">{{ $featured->excerpt }}</p>
                    @endif

                    <div class="mt-6 flex items-center gap-2.5 text-sm text-muted-foreground">
                        <x-avatar :name="$featured->author->name" />
                        <a href="{{ $featured->author->url() }}" class="hover:text-foreground transition-colors">{{ $featured->author->name }}</a>
                        <span aria-hidden="true">·</span>
                        <time datetime="{{ $featured->published_at->toDateString() }}">{{ $featured->published_at->format('d/m/Y') }}</time>
                        <span aria-hidden="true">·</span>
                        <span class="tabular-nums">{{ $featured->readingTime() }} phút đọc</span>
                    </div>
                </div>
            </article>
        @endif

        @if ($posts->isNotEmpty())
            <div class="mt-20 pb-20 sm:pb-28">
                @if ($featured)
                    <div class="chapter-rule reveal mb-10 w-20"></div>
                @endif

                <div class="reveal-stagger grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                    @foreach ($posts as $post)
                        <x-marketing.content-card :post="$post" show-author />
                    @endforeach
                </div>

                <div class="mt-16">{{ $posts->links() }}</div>
            </div>
        @else
            <div class="pb-20 sm:pb-28"></div>
        @endif
    @endif

    @if ($tags->isNotEmpty())
        <section class="section-bleed bg-muted py-16">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-8 lg:px-12">
                <p class="eyebrow mb-6">Thẻ nội dung</p>
                <div class="flex flex-wrap gap-2.5">
                    @foreach ($tags as $tag)
                        @if ($tag->url())
                            <a href="{{ $tag->url() }}" class="filter-chip bg-background">#{{ $tag->name }}</a>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
