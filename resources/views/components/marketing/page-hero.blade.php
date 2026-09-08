@props([
    'eyebrow' => null,
    'subtitle' => null,
    'image' => null,
    'imageAlt' => '',
    'breadcrumbs' => null,
])

{{-- Đầu trang dùng chung cho mọi trang trong, giữ đúng ngôn ngữ của trang
     chủ. Có ảnh thì thành "sân khấu" (ảnh phủ kín + chữ sáng), không có ảnh
     thì về bản chữ trên nền theme — KHÔNG chèn ảnh stock mặc định, vì một
     ảnh không liên quan còn tệ hơn là không có ảnh. --}}
@if ($image)
    <section class="section-bleed stage stage-center py-20 sm:py-24">
        <img src="{{ $image }}" alt="{{ $imageAlt }}" fetchpriority="high" decoding="async">

        <div class="stage-content max-w-[1440px] mx-auto px-4 sm:px-8 lg:px-12 text-center">
            @if ($breadcrumbs)
                <div class="flex justify-center [&_nav]:mb-5 [&_a]:text-white/60 [&_a:hover]:text-white [&_span]:text-white/90 [&_li]:text-white/40">
                    <x-breadcrumbs :items="$breadcrumbs" />
                </div>
            @endif

            @if ($eyebrow)
                <p class="eyebrow reveal !text-white/65 mb-4">{{ $eyebrow }}</p>
            @endif

            <h1 class="reveal font-heading text-3xl sm:text-4xl lg:text-5xl font-semibold leading-[1.15] tracking-tight text-balance max-w-3xl mx-auto">
                {{ $slot }}
            </h1>

            @if ($subtitle)
                <p class="reveal lede mt-6 max-w-2xl mx-auto text-white/80">{{ $subtitle }}</p>
            @endif

            {{ $footer ?? '' }}
        </div>
    </section>
@else
    <div class="pt-10 sm:pt-14 pb-2">
        @if ($breadcrumbs)
            <x-breadcrumbs :items="$breadcrumbs" />
        @endif

        @if ($eyebrow)
            <p class="eyebrow reveal mb-3">{{ $eyebrow }}</p>
        @endif

        <h1 class="reveal font-heading text-3xl sm:text-4xl lg:text-5xl font-semibold leading-[1.15] tracking-tight text-balance max-w-3xl">
            {{ $slot }}
        </h1>

        <div class="chapter-rule reveal mt-6 w-20"></div>

        @if ($subtitle)
            <p class="reveal lede mt-6 max-w-2xl text-muted-foreground">{{ $subtitle }}</p>
        @endif

        {{ $footer ?? '' }}
    </div>
@endif
