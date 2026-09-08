@props(['chart', 'color' => 'var(--color-primary)', 'gradientId' => 'areaChartFill'])

<svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" class="w-full h-36" preserveAspectRatio="none">
    <defs>
        <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.25" />
            <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0" />
        </linearGradient>
    </defs>
    @foreach ([0.25, 0.5, 0.75] as $g)
        <line x1="0" x2="{{ $chart['width'] }}" y1="{{ 10 + $g * ($chart['baseline'] - 10) }}" y2="{{ 10 + $g * ($chart['baseline'] - 10) }}" stroke="currentColor" class="text-border" stroke-width="1" />
    @endforeach
    <path d="{{ $chart['areaPath'] }}" fill="url(#{{ $gradientId }})" />
    <polyline points="{{ $chart['polyline'] }}" fill="none" stroke="{{ $color }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
    @foreach ($chart['points'] as $p)
        <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="2.5" fill="{{ $color }}">
            <title>{{ $p['label'] }}: {{ $p['views'] }} lượt xem</title>
        </circle>
    @endforeach
</svg>
<div class="flex justify-between mt-1">
    @foreach ($chart['points'] as $p)
        <span class="text-[10px] text-muted-foreground" style="width: {{ 100 / count($chart['points']) }}%; text-align: center;">
            {{ $p['showLabel'] ? $p['label'] : '' }}
        </span>
    @endforeach
</div>
