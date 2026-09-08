@props(['data', 'labelKey' => 'label', 'valueKey' => 'views', 'colors' => null, 'size' => 128])

@php
    $palette = $colors ?? ['var(--color-primary)', 'var(--color-brand-green)', '#f59e0b', '#0ea5e9', '#a855f7', '#64748b'];
    $rows = collect($data)->values();
    $total = (int) $rows->sum($valueKey);
    $holeInset = round($size * 0.28);
@endphp

<div class="flex items-center gap-5">
    <div class="relative shrink-0" style="width: {{ $size }}px; height: {{ $size }}px;">
        @if ($total <= 0)
            <div class="absolute inset-0 rounded-full bg-muted"></div>
        @else
            @php
                $cursor = 0;
                $stops = [];
                foreach ($rows as $i => $row) {
                    $value = (float) ($row[$valueKey] ?? 0);
                    $start = round($cursor / $total * 360, 1);
                    $cursor += $value;
                    $end = round($cursor / $total * 360, 1);
                    if ($end > $start) {
                        $stops[] = ($palette[$i % count($palette)]) . " {$start}deg {$end}deg";
                    }
                }
            @endphp
            <div class="absolute inset-0 rounded-full" style="background: conic-gradient({{ implode(', ', $stops) }});"></div>
        @endif
        <div class="absolute rounded-full bg-card flex items-center justify-center" style="inset: {{ $holeInset }}px;">
            <span class="text-xs font-semibold text-foreground">{{ number_format($total) }}</span>
        </div>
    </div>
    <div class="flex-1 space-y-2 min-w-0">
        @forelse ($rows as $i => $row)
            <div class="flex items-center justify-between gap-3 text-xs">
                <span class="flex items-center gap-1.5 min-w-0">
                    <span class="h-2 w-2 rounded-full shrink-0" style="background: {{ $palette[$i % count($palette)] }}"></span>
                    <span class="truncate text-foreground">{{ $row[$labelKey] }}</span>
                </span>
                <span class="shrink-0 text-muted-foreground">{{ $row['pct'] ?? 0 }}%</span>
            </div>
        @empty
            <p class="text-xs text-muted-foreground">Chưa có dữ liệu.</p>
        @endforelse
    </div>
</div>
