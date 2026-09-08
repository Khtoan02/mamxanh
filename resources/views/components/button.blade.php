@props(['variant' => 'primary', 'type' => 'button'])

@php
    $variants = [
        'primary' => 'bg-primary text-primary-foreground hover:opacity-90',
        'secondary' => 'bg-secondary text-secondary-foreground hover:bg-muted',
        'outline' => 'border border-border bg-transparent text-foreground hover:bg-accent hover:text-accent-foreground',
        'ghost' => 'bg-transparent text-foreground hover:bg-accent hover:text-accent-foreground',
    ];
    $classes = $variants[$variant] ?? $variants['primary'];
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => "inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors disabled:opacity-50 disabled:pointer-events-none cursor-pointer $classes"]) }}>
    {{ $slot }}
</button>
