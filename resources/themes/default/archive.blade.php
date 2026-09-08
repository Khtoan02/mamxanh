@extends('theme::layout')

@php
    $archiveVars = ['term' => $postType['label']];
    $breadcrumbs = [
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => $postType['label']],
    ];

    // Lời dẫn riêng cho từng loại nội dung. Viết tay, không sinh tự động từ
    // tên loại — một câu chung chung ("Danh sách Dự án của chúng tôi") không
    // nói thêm được gì cho người đọc.
    $intro = [
        'service' => 'Mỗi dịch vụ ở đây là một việc chúng tôi làm thường xuyên, đủ nhiều để biết chỗ nào dễ sai và chỗ nào tạo ra khác biệt.',
        'project' => 'Những gì đã làm xong và bàn giao. Không phải bộ sưu tập ảnh đẹp — mỗi dự án là một bài toán cụ thể của một khách hàng cụ thể.',
        'product' => 'Các sản phẩm và gói dịch vụ có thể bắt đầu ngay. Giá hiển thị là giá thật; phần nào cần khảo sát trước thì ghi rõ "Liên hệ".',
    ][$postType['slug']] ?? null;

    $emptyCopy = [
        'service' => 'Danh sách dịch vụ đang được hoàn thiện. Bạn có thể mô tả nhu cầu của mình, chúng tôi sẽ trả lời cụ thể.',
        'project' => 'Chúng tôi chưa công bố dự án nào ở đây. Một số dự án cần sự đồng ý của khách hàng trước khi đưa lên.',
        'product' => 'Chưa có sản phẩm nào được đăng. Hãy cho chúng tôi biết bạn đang cần gì.',
    ][$postType['slug']] ?? 'Nội dung đang được chuẩn bị.';

    $emptyIcon = ['service' => 'briefcase', 'project' => 'grid', 'product' => 'tag'][$postType['slug']] ?? 'file-text';
    $ratio = $postType['slug'] === 'product' ? 'aspect-square' : 'aspect-[4/3]';
@endphp

@section('title', \App\Support\SeoTemplate::render(\App\Models\Setting::get('seo_title_archive', '%term% %sep% %sitename%'), $archiveVars))
@section('description', \App\Support\SeoTemplate::render(\App\Models\Setting::get('seo_desc_archive', '%term% %sep% %sitename%'), $archiveVars))

@push('schema')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $postType['label'].' — '.config('app.name'),
            'url' => url()->current(),
            'isPartOf' => ['@id' => url('/').'#website'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-marketing.page-hero
        :breadcrumbs="$breadcrumbs"
        :eyebrow="config('app.name')"
        :subtitle="$intro">
        {{ $postType['label'] }}
    </x-marketing.page-hero>

    @foreach ($termGroups as $taxonomySlug => $terms)
        @php $taxonomyLabel = app(\App\Support\TaxonomyRegistry::class)->get($taxonomySlug)['label'] ?? null; @endphp
        <nav aria-label="{{ $taxonomyLabel }}" class="chip-row reveal mt-8 first:mt-10">
            @foreach ($terms as $term)
                <a href="{{ $term->url() }}" class="filter-chip">
                    {{ $term->name }} <span class="tabular-nums opacity-70">{{ $term->posts_count }}</span>
                </a>
            @endforeach
        </nav>
    @endforeach

    <div class="mt-14 pb-20 sm:pb-28">
        @if ($posts->isEmpty())
            <x-marketing.empty-state
                :icon="$emptyIcon"
                title="Chưa có {{ mb_strtolower($postType['label']) }} nào"
                :action="route('contact.show')"
                actionLabel="Trò chuyện với đội ngũ">
                {{ $emptyCopy }}
            </x-marketing.empty-state>
        @else
            <p class="eyebrow mb-8">{{ $posts->total() }} {{ mb_strtolower($postType['label']) }}</p>

            <div class="reveal-stagger grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                @foreach ($posts as $post)
                    <x-marketing.content-card :post="$post" :ratio="$ratio" />
                @endforeach
            </div>

            <div class="mt-16">{{ $posts->links() }}</div>
        @endif
    </div>

    {{-- CTA khép trang: mọi trang danh sách đều phải có lối đi tiếp, không
         kết thúc bằng khoảng trắng sau phân trang. --}}
    <section class="section-bleed bg-muted py-16 sm:py-20">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-8 lg:px-12 reveal">
            <div class="max-w-2xl">
                <p class="eyebrow mb-4">Bước tiếp theo</p>
                <p class="font-heading text-2xl sm:text-3xl font-semibold leading-snug tracking-tight text-balance">
                    Chưa thấy đúng thứ bạn cần?
                </p>
                <p class="mt-4 text-muted-foreground leading-relaxed">
                    Kể cho chúng tôi nghe bài toán của bạn. Nếu chúng tôi không phải là bên phù hợp, chúng tôi sẽ nói thẳng.
                </p>
                <a href="{{ route('contact.show') }}" class="group mt-7 inline-flex items-center gap-3 font-sans font-semibold">
                    <span class="border-b-2 border-primary pb-1">Bắt đầu một cuộc trò chuyện</span>
                    <span class="text-primary transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>
@endsection
