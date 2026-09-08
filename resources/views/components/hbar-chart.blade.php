@props(['data', 'labelKey' => 'label', 'valueKey' => 'views', 'pctKey' => null, 'color' => 'var(--color-primary)', 'suffix' => '', 'sublabelKey' => null])

@php
    $rows = collect($data)->values();
    $max = max(1, (float) $rows->max($pctKey ?? $valueKey));
@endphp

<div class="space-y-3">
    @forelse ($rows as $row)
        @php
            $value = $row[$valueKey] ?? 0;
            $width = $pctKey ? (float) ($row[$pctKey] ?? 0) : round($value / $max * 100);
            $display = is_float($value) ? number_format($value, 1) : number_format($value);
        @endphp
        <div>
            <div class="flex items-center justify-between text-xs mb-1 gap-2">
                <span class="min-w-0">
                    <span class="truncate text-foreground">{{ $row[$labelKey] }}</span>
                    @if ($sublabelKey && ! empty($row[$sublabelKey]))
                        <span class="block text-[11px] text-muted-foreground truncate">{{ $row[$sublabelKey] }}</span>
                    @endif
                </span>
                <span class="shrink-0 text-muted-foreground">{{ $display }}{{ $suffix }}</span>
            </div>
            <div class="h-2 rounded-full bg-muted overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ max(2, $width) }}%; background: {{ $color }};"></div>
            </div>
        </div>
    @empty
        <p class="text-xs text-muted-foreground">Chưa có dữ liệu.</p>
    @endforelse
</div>
