@props(['delta'])

@if ($delta === null)
    <span class="inline-flex items-center rounded-full bg-muted px-1.5 py-0.5 text-[11px] font-medium text-muted-foreground">Mới</span>
@else
    @php
        $up = $delta >= 0;
        $color = $up ? 'bg-brand-green/10 text-brand-green' : 'bg-destructive/10 text-destructive';
        $icon = $up ? 'trending-up' : 'trending-down';
        $sign = $up ? '+' : '';
    @endphp
    <span class="inline-flex items-center gap-1 rounded-full {{ $color }} px-1.5 py-0.5 text-[11px] font-medium">
        <x-icon :name="$icon" class="h-3 w-3" />{{ $sign }}{{ number_format($delta, 1) }}%
    </span>
@endif
