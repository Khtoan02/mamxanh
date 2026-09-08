@props(['icon' => 'file-text', 'title', 'action' => null, 'actionLabel' => null])

{{-- Trạng thái trống dùng chung. Nói rõ vì sao trống VÀ lối đi tiếp — một
     dòng "Chưa có nội dung" trong khung xám là ngõ cụt cho người đọc. --}}
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <span class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
        <x-icon :name="$icon" class="h-5 w-5" />
    </span>

    <p class="font-heading text-xl font-medium">{{ $title }}</p>

    @if (trim($slot) !== '')
        <p class="mt-2.5 mx-auto max-w-md text-sm text-muted-foreground leading-relaxed">{{ $slot }}</p>
    @endif

    @if ($action)
        <a href="{{ $action }}" class="group mt-7 inline-flex items-center gap-2 font-sans text-sm font-semibold">
            <span class="border-b-2 border-primary pb-0.5">{{ $actionLabel ?? 'Về trang chủ' }}</span>
            <span class="text-primary transition-transform group-hover:translate-x-0.5" aria-hidden="true">→</span>
        </a>
    @endif
</div>
