@props(['eyebrow' => null, 'subtitle' => null])

{{-- Tiêu đề dùng cho các trang trong (Dịch vụ/Dự án/Sản phẩm/Liên hệ) —
     cùng ngôn ngữ thị giác với <x-marketing.chapter> của trang chủ nhưng
     rút gọn, không có số chương. --}}
<div {{ $attributes->merge(['class' => 'mb-12 max-w-2xl']) }}>
    @if ($eyebrow)
        <p class="font-sans text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-muted-foreground mb-3">{{ $eyebrow }}</p>
    @endif
    <h1 class="font-heading text-3xl sm:text-4xl lg:text-5xl font-semibold leading-[1.15] tracking-tight text-balance">{{ $slot }}</h1>
    <div class="chapter-rule mt-6 w-20"></div>
    @if ($subtitle)
        <p class="mt-6 text-muted-foreground leading-relaxed">{{ $subtitle }}</p>
    @endif
</div>
