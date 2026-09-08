@props(['items'])

{{-- Real breadcrumb trail — same $items array drives both the visible nav
     and the BreadcrumbList JSON-LD below, so the two can never drift apart. --}}
<nav aria-label="Breadcrumb" class="mb-6 text-sm text-muted-foreground">
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $i => $item)
            @if ($i > 0)
                <li aria-hidden="true" class="text-muted-foreground/50">/</li>
            @endif
            <li>
                @if (! empty($item['url']) && $i < count($items) - 1)
                    <a href="{{ $item['url'] }}" class="hover:text-foreground transition-colors">{{ $item['label'] }}</a>
                @else
                    <span class="text-foreground font-medium">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

@push('schema')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['label'],
                'item' => $item['url'] ?? null,
            ]))->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
