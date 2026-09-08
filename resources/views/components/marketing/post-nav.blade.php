@props(['prev' => null, 'next' => null])

@if ($prev || $next)
    <nav aria-label="Bài trước / bài sau" class="mt-14 pt-8 border-t border-border grid sm:grid-cols-2 gap-8">
        <div>
            @if ($prev)
                <a href="{{ $prev->url() }}" rel="prev" class="group block">
                    <span class="eyebrow flex items-center gap-1.5">
                        <span class="transition-transform group-hover:-translate-x-0.5" aria-hidden="true">←</span> Bài trước
                    </span>
                    <span class="mt-2.5 block font-heading text-lg leading-snug group-hover:text-primary transition-colors">{{ $prev->title }}</span>
                </a>
            @endif
        </div>

        <div class="sm:text-right">
            @if ($next)
                <a href="{{ $next->url() }}" rel="next" class="group block">
                    <span class="eyebrow flex items-center gap-1.5 sm:justify-end">
                        Bài sau <span class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">→</span>
                    </span>
                    <span class="mt-2.5 block font-heading text-lg leading-snug group-hover:text-primary transition-colors">{{ $next->title }}</span>
                </a>
            @endif
        </div>
    </nav>
@endif
