@props(['items', 'title' => 'Nội dung liên quan', 'eyebrow' => null, 'ratio' => 'aspect-[4/3]'])

@if ($items->isNotEmpty())
    <section class="mt-20 pt-12 border-t border-border">
        @if ($eyebrow)
            <p class="eyebrow mb-3">{{ $eyebrow }}</p>
        @endif
        <h2 class="font-heading text-2xl sm:text-3xl font-semibold tracking-tight mb-10">{{ $title }}</h2>

        <div class="reveal-stagger grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
            @foreach ($items as $item)
                <x-marketing.content-card :post="$item" :ratio="$ratio" />
            @endforeach
        </div>
    </section>
@endif
