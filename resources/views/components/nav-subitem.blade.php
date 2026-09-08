@props(['href', 'active' => false, 'icon' => null])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-2.5 truncate rounded-md px-2.5 py-1.5 text-sm transition-colors [&>svg]:shrink-0 ' . ($active ? 'font-medium text-primary' : 'text-muted-foreground hover:text-foreground')]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="h-3.5 w-3.5" />
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
