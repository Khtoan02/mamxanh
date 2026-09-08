@extends('theme::layout')

@php
    $seoVars = ['term' => $term->name];
    $breadcrumbs = array_values(array_filter([
        ['label' => 'Trang chủ', 'url' => route('home')],
        $postType ? ['label' => $postType['label'], 'url' => match ($postType['slug']) {
            'post' => route('blog.index'),
            'product' => route('products.index'),
            'service' => route('services.index'),
            'project' => route('projects.index'),
            default => null,
        }] : null,
        ['label' => $term->name],
    ]));
@endphp

@section('title', \App\Support\SeoTemplate::render(\App\Models\Setting::get('seo_title_archive', '%term% %sep% %sitename%'), $seoVars))
@section('description', $term->description ?: \App\Support\SeoTemplate::render(\App\Models\Setting::get('seo_desc_archive', '%term% %sep% %sitename%'), $seoVars))

@push('schema')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $term->name,
            'description' => $term->description ?: null,
            'url' => url()->current(),
            'isPartOf' => ['@id' => url('/').'#website'],
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-marketing.page-hero
        :breadcrumbs="$breadcrumbs"
        :eyebrow="$taxonomy['label']"
        :subtitle="$term->description ?: null">
        {{ $term->name }}
    </x-marketing.page-hero>

    {{-- Đường ngang giữa các term cùng nhóm: cho người đọc đi NGANG sang chủ
         đề gần kề thay vì phải quay lại. Chỉ hiện term thật sự có bài. --}}
    @if ($siblings->count() > 1)
        <nav aria-label="{{ $taxonomy['label'] }}" class="chip-row reveal mt-10">
            @foreach ($siblings as $sibling)
                @if ($sibling->url())
                    <a href="{{ $sibling->url() }}" class="filter-chip"
                       @if ($sibling->is($term)) aria-current="true" @endif>{{ $sibling->name }}</a>
                @endif
            @endforeach
        </nav>
    @endif

    <div class="mt-12 pb-20 sm:pb-28">
        <p class="eyebrow mb-8">{{ $posts->total() }} nội dung</p>

        @if ($posts->isEmpty())
            <x-marketing.empty-state
                icon="tag"
                title="Chưa có nội dung trong chủ đề này"
                :action="route('home')"
                actionLabel="Về trang chủ">
                Chủ đề "{{ $term->name }}" đã được tạo nhưng chưa có bài nào được xuất bản.
            </x-marketing.empty-state>
        @else
            <div class="reveal-stagger grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                @foreach ($posts as $post)
                    <x-marketing.content-card :post="$post" show-author />
                @endforeach
            </div>

            <div class="mt-16">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
