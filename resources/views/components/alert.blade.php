@props(['variant' => 'destructive'])

@php
    $variants = [
        'destructive' => 'bg-destructive/10 text-destructive border border-destructive/20',
        'success' => 'bg-brand-green/10 text-brand-green border border-brand-green/20',
        'default' => 'bg-muted text-muted-foreground border border-border',
    ];
    $classes = $variants[$variant] ?? $variants['destructive'];
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg px-3.5 py-2.5 text-sm $classes"]) }}>{{ $slot }}</div>
