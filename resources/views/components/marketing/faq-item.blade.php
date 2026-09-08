@props(['question'])

{{-- Native <details>/<summary> — không JS, không layout shift, hỗ trợ bàn
     phím/trình đọc màn hình sẵn. Chỉ có đường kẻ phân cách, không khung. --}}
<details {{ $attributes->merge(['class' => 'group border-t border-border [&_summary::-webkit-details-marker]:hidden']) }}>
    <summary class="flex items-baseline justify-between gap-6 cursor-pointer list-none py-6 font-heading text-lg sm:text-xl font-medium leading-snug">
        {{ $question }}
        <span class="shrink-0 font-sans text-xl text-muted-foreground transition-transform group-open:rotate-45" aria-hidden="true">+</span>
    </summary>
    <div class="pb-7 -mt-1 max-w-2xl text-muted-foreground leading-relaxed">
        {{ $slot }}
    </div>
</details>
