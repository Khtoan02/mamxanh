@php
    $tabs = [
        'titles' => ['label' => 'Tiêu đề & Meta', 'icon' => 'sliders', 'route' => 'admin.seo.titles'],
        'sitemap' => ['label' => 'Sơ đồ trang', 'icon' => 'globe', 'route' => 'admin.seo.sitemap'],
        'product-feed' => ['label' => 'Feed sản phẩm', 'icon' => 'tag', 'route' => 'admin.seo.product-feed'],
        'robots' => ['label' => 'robots.txt', 'icon' => 'file-text', 'route' => 'admin.seo.robots'],
        'indexnow' => ['label' => 'IndexNow', 'icon' => 'zap', 'route' => 'admin.seo.indexnow'],
    ];
@endphp

<div class="flex gap-1 overflow-x-auto rounded-lg border border-border bg-muted p-1 mb-6 w-fit max-w-full">
    @foreach ($tabs as $key => $tab)
        <a href="{{ route($tab['route']) }}"
           class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-colors {{ $active === $key ? 'bg-card shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground' }}">
            <x-icon :name="$tab['icon']" class="h-4 w-4" />
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>

<div class="flex flex-wrap gap-3 mb-6 text-xs text-muted-foreground">
    <span>Liên quan:</span>
    <a href="{{ route('admin.redirects.index') }}" class="text-primary hover:underline">Chuyển hướng →</a>
    <a href="{{ route('admin.health.index') }}" class="text-primary hover:underline">Giám sát lỗi 404 →</a>
</div>
