@props(['variant' => 'default'])

@php
    $variants = [
        'default' => 'bg-primary/10 text-primary',
        'green' => 'bg-brand-green/10 text-brand-green',
        'secondary' => 'bg-secondary text-secondary-foreground',
    ];
    $classes = $variants[$variant] ?? $variants['default'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold $classes"]) }}>{{ $slot }}</span>
