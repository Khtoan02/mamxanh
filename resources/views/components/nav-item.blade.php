@props(['href' => null, 'active' => false, 'disabled' => false, 'title' => null])

@php
    $base = 'nav-item flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors [&>svg]:shrink-0';
    $state = $disabled
        ? 'text-muted-foreground/50 cursor-not-allowed'
        : ($active
            ? 'bg-primary/10 text-primary'
            : 'text-muted-foreground hover:bg-accent hover:text-foreground');
@endphp

@if ($disabled)
    <span {{ $attributes->merge(['class' => "$base $state"]) }}>
        {{ $slot }}
        <span class="sidebar-label ml-auto rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground">Sắp có</span>
    </span>
@else
    <a href="{{ $href }}" @if ($title) title="{{ $title }}" @endif {{ $attributes->merge(['class' => "$base $state"]) }}>
        {{ $slot }}
    </a>
@endif
