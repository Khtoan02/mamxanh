@props([
    'post',
    'ratio' => 'aspect-[4/3]',
    'showType' => false,
    'showAuthor' => false,
])

@php
    $term = $post->primaryTerm();
    $termUrl = $term?->url();
    $typeLabel = get_post_type($post->post_type)['label'] ?? null;
    $price = $post->post_type === 'product' ? $post->getMeta('price') : null;
    $client = $post->post_type === 'project' ? $post->getMeta('client_name') : null;
@endphp

{{-- Thẻ nội dung dùng chung cho blog, dịch vụ, dự án, sản phẩm, danh mục,
     tìm kiếm, tác giả. Một component thay vì 6 bản sao chép — thêm 1 trường
     là sửa 1 file. Các nhánh theo post_type ở dưới chỉ hiện khi có dữ liệu
     thật, không bịa placeholder. --}}
<article {{ $attributes->merge(['class' => 'entry-card group']) }}>
    <a href="{{ $post->url() }}" class="card-media {{ $ratio }} block" tabindex="-1" aria-hidden="true">
        @if ($post->featured_image)
            <img src="{{ $post->featured_image }}" alt="" loading="lazy" decoding="async">
        @else
            <span class="card-media-empty h-full w-full">
                <x-icon name="image" class="h-7 w-7" />
            </span>
        @endif
    </a>

    <div class="pt-5 flex-1 flex flex-col">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mb-2.5">
            @if ($showType && $typeLabel)
                <span class="eyebrow">{{ $typeLabel }}</span>
            @endif

            @if ($term)
                @if ($termUrl)
                    <a href="{{ $termUrl }}" class="eyebrow hover:text-primary transition-colors">{{ $term->name }}</a>
                @else
                    <span class="eyebrow">{{ $term->name }}</span>
                @endif
            @endif

            @if ($client)
                <span class="eyebrow">{{ $client }}</span>
            @endif
        </div>

        <h3 class="font-heading text-lg sm:text-xl font-medium leading-snug text-balance">
            <a href="{{ $post->url() }}">{{ $post->title }}</a>
        </h3>

        @if ($post->excerpt)
            <p class="mt-2.5 text-sm text-muted-foreground leading-relaxed line-clamp-3">{{ $post->excerpt }}</p>
        @endif

        <div class="mt-4 pt-3.5 flex items-center justify-between gap-3 border-t border-border/70 text-xs text-muted-foreground">
            <span class="truncate">
                @if ($showAuthor && $post->relationLoaded('author') && $post->author)
                    {{ $post->author->name }} ·
                @endif
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d/m/Y') }}</time>
                @endif
            </span>

            @if ($price)
                <span class="font-sans font-semibold text-primary shrink-0">{{ $price }}</span>
            @elseif ($post->post_type === 'post')
                <span class="shrink-0 tabular-nums">{{ $post->readingTime() }} phút đọc</span>
            @endif
        </div>
    </div>
</article>
