{{-- Pure visual grouping — not interactive, not collapsible. Just a small
     label to help scan clusters of related nav items apart, per the user's
     explicit "tách nhóm phân biệt nhìn cho dễ, không cần gộp vào 1 tab cha"
     request (visually separate, no accordion wrapper). --}}
<p class="sidebar-label px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground/70 first:pt-1">
    {{ $slot }}
</p>
