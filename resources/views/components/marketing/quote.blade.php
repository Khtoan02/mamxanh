@props(['author', 'source' => null])

{{-- Trích dẫn thật, đã xác minh đúng người/đúng chữ trước khi dùng (xem
     ARCHITECTURE.md). Không khung, không nền — chỉ chữ lớn nghiêng và
     khoảng trắng, đúng tinh thần trang in. --}}
<figure {{ $attributes->merge(['class' => 'reveal max-w-3xl mx-auto text-center py-4']) }}>
    <blockquote class="font-heading text-2xl sm:text-3xl lg:text-4xl font-normal italic leading-[1.35] text-balance">
        {{ $slot }}
    </blockquote>
    <figcaption class="mt-6 font-sans text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
        {{ $author }}@if ($source) <span class="normal-case tracking-normal font-normal italic">— {{ $source }}</span>@endif
    </figcaption>
</figure>
