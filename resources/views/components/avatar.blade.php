@props(['name'])

@php
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));
@endphp

<span {{ $attributes->merge(['class' => 'flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary']) }}>{{ $initial }}</span>
