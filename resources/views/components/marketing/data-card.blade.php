@props(['label' => null, 'icon' => null, 'value' => null, 'caption' => null, 'chip' => null])

{{-- Thẻ dữ liệu kính mờ nổi trên nền ảnh (.stage). Mọi con số truyền vào
     đây phải là dữ liệu thật của hệ thống — không phải số minh hoạ. --}}
<div {{ $attributes->merge(['class' => 'data-card px-5 py-4']) }}>
    @if ($label)
        <div class="flex items-center gap-2 mb-3">
            @if ($icon)
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/15">
                    <x-icon :name="$icon" class="h-3.5 w-3.5" />
                </span>
            @endif
            <span class="data-card-label font-sans font-medium">{{ $label }}</span>
        </div>
    @endif

    @if ($value)
        <p class="data-card-value text-3xl sm:text-4xl">{{ $value }}</p>
    @endif

    @if ($caption)
        <p class="mt-1.5 text-[0.8rem] leading-snug text-white/70">{{ $caption }}</p>
    @endif

    {{ $slot }}

    @if ($chip)
        <span class="data-chip mt-3">{{ $chip }}</span>
    @endif
</div>
