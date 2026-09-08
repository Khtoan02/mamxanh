@props(['label', 'icon', 'active' => false])

<div class="nav-group">
    <button type="button" class="nav-group-toggle flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors [&>svg]:shrink-0 {{ $active ? 'text-primary' : 'text-muted-foreground hover:bg-accent hover:text-foreground' }}">
        <x-icon :name="$icon" />
        <span class="sidebar-label flex-1 truncate text-left">{{ $label }}</span>
        <x-icon name="chevron-down" class="nav-group-chevron sidebar-label h-3.5 w-3.5 shrink-0 transition-transform duration-150 {{ $active ? '' : '-rotate-90' }}" />
    </button>
    <div class="nav-group-children sidebar-label space-y-1 py-1 pl-[2.15rem] {{ $active ? '' : 'hidden' }}">
        {{ $slot }}
    </div>
</div>
