@extends('theme::layout')

@php
    $metaTitle = $post->seoTitle();
    $metaDescription = $post->seoDescription();
    $priceFrom = $post->getMeta('price_from');
    $duration = $post->getMeta('duration');
    $related = $post->related(3);

    // Dự án là bằng chứng mạnh nhất cho một dịch vụ. Chỉ lấy dự án ĐÃ xuất
    // bản; nếu chưa có dự án nào thì khối này biến mất hoàn toàn thay vì
    // hiện khung rỗng hay ví dụ minh hoạ.
    $proof = \App\Models\Post::ofType('project')->published()
        ->with(['author', 'terms', 'meta'])
        ->latest('published_at')->limit(3)->get();

    $breadcrumbs = [
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Dịch vụ', 'url' => route('services.index')],
        ['label' => $post->title],
    ];
@endphp

@section('title', $metaTitle)
@section('description', $metaDescription)
@section('robots', $post->robotsContent())
@if ($post->getMeta('canonical_url'))
    @section('canonical', $post->getMeta('canonical_url'))
@endif
@if ($post->featured_image)
    @section('og_image', $post->featured_image)
@endif

@push('schema')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $metaTitle,
            'description' => $metaDescription,
            'provider' => ['@id' => url('/').'#organization'],
            'areaServed' => 'VN',
            'image' => $post->featured_image ?: null,
            // Chỉ phát Offer khi có giá thật đã nhập. Một Offer không giá
            // là dữ liệu rác với Google và có thể bị đánh dấu lỗi.
            'offers' => $post->priceAmount('price_from') ? [
                '@type' => 'Offer',
                'price' => $post->priceAmount('price_from'),
                'priceCurrency' => 'VND',
                'url' => url()->current(),
            ] : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-marketing.page-hero
        :breadcrumbs="$breadcrumbs"
        eyebrow="Dịch vụ"
        :subtitle="$post->excerpt ?: null"
        :image="$post->featured_image"
        :imageAlt="$post->title">
        {{ $post->title }}
    </x-marketing.page-hero>

    {{-- Dải thông tin thực tế: chỉ hiện ô nào biên tập viên đã điền. Không
         có giá thì KHÔNG bịa "Liên hệ để báo giá" thành một con số. --}}
    @if ($priceFrom || $duration)
        <div class="reveal mt-12 flex flex-wrap gap-x-16 gap-y-6 border-y border-border py-7">
            @if ($priceFrom)
                <div>
                    <p class="eyebrow mb-2">Giá từ</p>
                    <p class="font-heading text-2xl font-semibold text-primary">{{ $priceFrom }}</p>
                </div>
            @endif
            @if ($duration)
                <div>
                    <p class="eyebrow mb-2">Thời gian thực hiện</p>
                    <p class="font-heading text-2xl font-semibold">{{ $duration }}</p>
                </div>
            @endif
            <div class="ml-auto self-center">
                <a href="{{ route('contact.show') }}" class="group inline-flex items-center gap-3 font-sans font-semibold">
                    <span class="border-b-2 border-primary pb-1">Nhận tư vấn</span>
                    <span class="text-primary transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                </a>
            </div>
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
        </div>

        <x-marketing.related :items="$related" eyebrow="Dịch vụ khác" title="Những việc chúng tôi cũng làm" />

        @if ($proof->isNotEmpty())
            <x-marketing.related :items="$proof" eyebrow="Minh chứng" title="Dự án đã thực hiện" />
        @endif
    </div>

    <section class="section-bleed bg-muted py-16 sm:py-20">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-8 lg:px-12">
          <div class="reveal max-w-2xl">
            <p class="eyebrow mb-4">Bước tiếp theo</p>
            <p class="font-heading text-2xl sm:text-3xl font-semibold leading-snug tracking-tight text-balance">
                Việc này có phù hợp với bạn không?
            </p>
            <p class="mt-4 text-muted-foreground leading-relaxed">
                Cách nhanh nhất để biết là kể cho chúng tôi nghe tình trạng hiện tại của bạn. Không ràng buộc, không áp lực.
            </p>
            <a href="{{ route('contact.show') }}" class="group mt-7 inline-flex items-center gap-3 font-sans font-semibold">
                <span class="border-b-2 border-primary pb-1">Liên hệ với chúng tôi</span>
                <span class="text-primary transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
            </a>
          </div>
        </div>
    </section>
@endsection
