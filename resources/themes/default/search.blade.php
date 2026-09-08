@extends('theme::layout')

@php
    $hasQuery = mb_strlen($query) >= 2;
    $typeLabels = collect($types)->mapWithKeys(fn ($t) => [$t => get_post_type($t)['label'] ?? $t])->all();
@endphp

@section('title', $hasQuery
    ? \App\Support\SeoTemplate::render('Tìm kiếm: %term% %sep% %sitename%', ['term' => $query])
    : \App\Support\SeoTemplate::render('Tìm kiếm %sep% %sitename%'))
@section('description', 'Tìm bài viết, dịch vụ, dự án và sản phẩm trên '.config('app.name'))
{{-- Trang kết quả tìm kiếm không nên vào chỉ mục: mỗi truy vấn sinh ra một
     URL mới, tạo ra vô số trang mỏng gần trùng nhau — đúng thứ Google gọi là
     "search results within search results" và khuyến nghị chặn. --}}
@section('robots', 'noindex, follow')

@section('content')
    <x-marketing.page-hero eyebrow="Tìm kiếm" subtitle="Gõ từ khoá để tìm trong toàn bộ nội dung đã xuất bản.">
        Bạn đang tìm điều gì?
    </x-marketing.page-hero>

    <div class="mt-10 pb-20 sm:pb-28">
        <form method="GET" action="{{ route('search') }}" role="search" class="max-w-2xl">
            <label for="site-search" class="sr-only">Từ khoá tìm kiếm</label>
            <div class="flex items-center gap-3 border-b-2 border-border focus-within:border-primary transition-colors pb-2">
                <x-icon name="search" class="h-5 w-5 shrink-0 text-muted-foreground" />
                <input id="site-search" type="search" name="q" value="{{ $query }}" autocomplete="off"
                       placeholder="Ví dụ: thiết kế website, SEO, thương hiệu…"
                       class="w-full bg-transparent font-heading text-xl sm:text-2xl placeholder:text-muted-foreground/60 focus:outline-none">
                <button type="submit" class="shrink-0 font-sans text-sm font-semibold text-primary">Tìm</button>
            </div>
            @if ($type)
                <input type="hidden" name="type" value="{{ $type }}">
            @endif
        </form>

        @if (! $hasQuery)
            <x-marketing.empty-state class="mt-16" icon="search" title="Nhập ít nhất 2 ký tự">
                Từ khoá quá ngắn sẽ khớp với gần như mọi nội dung, nên kết quả không còn ý nghĩa.
            </x-marketing.empty-state>
        @else
            {{-- Chip lọc theo loại nội dung, kèm SỐ THẬT đếm được từ chính
                 truy vấn — không hiện loại nào đang có 0 kết quả. --}}
            @if (array_sum($countsByType) > 0)
                <nav aria-label="Lọc theo loại nội dung" class="chip-row mt-10">
                    <a href="{{ route('search', ['q' => $query]) }}" class="filter-chip" @if (! $type) aria-current="true" @endif>
                        Tất cả <span class="tabular-nums opacity-70">{{ array_sum($countsByType) }}</span>
                    </a>
                    @foreach ($types as $t)
                        @if (($countsByType[$t] ?? 0) > 0)
                            <a href="{{ route('search', ['q' => $query, 'type' => $t]) }}" class="filter-chip"
                               @if ($type === $t) aria-current="true" @endif>
                                {{ $typeLabels[$t] }} <span class="tabular-nums opacity-70">{{ $countsByType[$t] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
            @endif

            @if ($results->isEmpty())
                <x-marketing.empty-state class="mt-16" icon="search" title="Không tìm thấy kết quả nào"
                    :action="route('blog.index')" actionLabel="Xem tất cả bài viết">
                    Không có nội dung nào khớp với "{{ $query }}". Thử từ khoá ngắn hơn hoặc chung hơn.
                </x-marketing.empty-state>
            @else
                <p class="eyebrow mt-12 mb-8">
                    {{ $results->total() }} kết quả cho "{{ $query }}"
                </p>

                <div class="reveal-stagger grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                    @foreach ($results as $post)
                        <x-marketing.content-card :post="$post" show-type />
                    @endforeach
                </div>

                <div class="mt-16">{{ $results->links() }}</div>
            @endif
        @endif
    </div>
@endsection
