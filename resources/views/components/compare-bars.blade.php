@props(['current', 'previous', 'currentLabel' => 'Kỳ này', 'previousLabel' => 'Kỳ trước', 'color' => 'var(--color-primary)'])

@php
    $max = max(1, $current, $previous);
@endphp

<div class="space-y-1.5 mt-3">
    <div class="flex items-center gap-2">
        <span class="w-16 shrink-0 text-[10px] text-muted-foreground">{{ $currentLabel }}</span>
        <div class="flex-1 h-2 rounded-full bg-muted overflow-hidden">
            <div class="h-full rounded-full" style="width: {{ max(2, round($current / $max * 100)) }}%; background: {{ $color }};"></div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <span class="w-16 shrink-0 text-[10px] text-muted-foreground">{{ $previousLabel }}</span>
        <div class="flex-1 h-2 rounded-full bg-muted overflow-hidden">
            <div class="h-full rounded-full bg-foreground/25" style="width: {{ max(2, round($previous / $max * 100)) }}%;"></div>
        </div>
    </div>
</div>
