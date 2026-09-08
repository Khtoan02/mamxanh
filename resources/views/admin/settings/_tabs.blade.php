@php
    $tabs = [
        'general' => ['label' => 'Chung', 'icon' => 'settings', 'route' => 'admin.settings.general'],
        'contact' => ['label' => 'Liên hệ', 'icon' => 'map-pin', 'route' => 'admin.settings.contact'],
        'social' => ['label' => 'Mạng xã hội', 'icon' => 'share-2', 'route' => 'admin.settings.social'],
        'integrations' => ['label' => 'Tích hợp', 'icon' => 'plug', 'route' => 'admin.settings.integrations'],
        'email' => ['label' => 'Email', 'icon' => 'mail', 'route' => 'admin.settings.email'],
        'maintenance' => ['label' => 'Bảo trì', 'icon' => 'wrench', 'route' => 'admin.settings.maintenance'],
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
