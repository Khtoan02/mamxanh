@extends('theme::layout')

{{-- Theme con "Tối giản": chỉ ghi đè trang danh sách bài viết. Khác bản của
     theme cha ở đúng một điểm có chủ đích — không có khối "bài nổi bật" cỡ
     lớn, mọi bài trình bày ngang hàng trong một lưới đều. Mọi thứ khác
     (layout, đầu trang, chip danh mục, phân trang) thừa kế nguyên vẹn. --}}

@php
    $breadcrumbs = [
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Bài viết'],
    ];

    // Theme cha tách bài mới nhất ra làm bài nổi bật; ở đây không có khối đó
    // nên phải ghép nó trở lại đầu danh sách, nếu không bài mới nhất sẽ
    // biến mất hoàn toàn khỏi trang 1.
    $entries = $featured ? collect([$featured])->concat($posts->items()) : collect($posts->items());
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

    <div class="mt-14 pb-20 sm:pb-28">
        @if ($entries->isEmpty())
            <x-marketing.empty-state icon="file-text" title="Chưa có bài viết nào"
                :action="route('contact.show')" actionLabel="Liên hệ với chúng tôi">
                Chúng tôi đang chuẩn bị những bài đầu tiên. Trong lúc chờ, bạn có thể trò chuyện trực tiếp với đội ngũ.
            </x-marketing.empty-state>
        @else
            <div class="reveal-stagger grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                @foreach ($entries as $post)
                    <x-marketing.content-card :post="$post" show-author />
                @endforeach
            </div>

            <div class="mt-16">{{ $posts->links() }}</div>
        @endif
    </div>

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
