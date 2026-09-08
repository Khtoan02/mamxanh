@extends('theme::layout')

@section('title', \App\Support\SeoTemplate::render(\App\Models\Setting::get('seo_title_home', '%sitename% %sep% %tagline%')))
@section('description', \App\Support\SeoTemplate::render(\App\Models\Setting::get('seo_desc_home', '%tagline%')))

@section('content')
    @php
        // Trang chủ mặc định của bản phát hành. Nguyên tắc: KHÔNG có một chữ
        // nội dung marketing nào viết cứng ở đây. Mọi thứ hiển thị đều là dữ
        // liệu thật của người cài đặt — tên site, tagline, nội dung họ đã
        // đăng. Một theme mặc định mà kể sẵn câu chuyện của người khác thì
        // người dùng phải đi xoá từng dòng trước khi dùng được.
        $tagline = env('SITE_TAGLINE');
        $hasAnyContent = $services->isNotEmpty() || $projects->isNotEmpty()
            || $products->isNotEmpty() || $recentPosts->isNotEmpty();
        $canEdit = auth()->user()?->hasCapability('edit_posts') ?? false;
    @endphp

    {{-- Mở đầu — chỉ tên site + tagline. Không ảnh nền mặc định: một tấm ảnh
         stock không liên quan còn tệ hơn là khoảng trắng sạch sẽ. --}}
    <section class="py-20 sm:py-28 lg:py-36">
        <div class="max-w-3xl">
            <h1 class="reveal font-heading text-4xl sm:text-5xl lg:text-6xl font-semibold leading-[1.1] tracking-tight text-balance">
                {{ config('app.name') }}
            </h1>

            <div class="chapter-rule reveal mt-7 w-24"></div>

            @if ($tagline)
                <p class="reveal lede mt-7 text-muted-foreground max-w-2xl">{{ $tagline }}</p>
            @endif

            <div class="reveal mt-10 flex flex-wrap items-center gap-x-8 gap-y-4">
                <a href="{{ route('contact.show') }}" class="group inline-flex items-center gap-2.5 font-sans text-base font-semibold">
                    <span class="border-b-2 border-primary pb-1">Liên hệ với chúng tôi</span>
                    <span class="text-primary transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                </a>

                @if ($recentPosts->isNotEmpty())
                    <a href="{{ route('blog.index') }}" class="font-sans text-base text-muted-foreground hover:text-foreground transition-colors">
                        Đọc bài viết
                    </a>
                @endif
            </div>
        </div>
    </section>

    @if (! $hasAnyContent)
        {{-- Site vừa cài xong, chưa có gì. Không để trang chủ trắng trơn —
             người quản trị thì được chỉ đường vào khu soạn thảo, khách ghé
             qua thì thấy một câu trung tính thay vì khoảng trống khó hiểu. --}}
        <x-marketing.empty-state
            icon="sparkles"
            title="Website đã sẵn sàng"
            :action="$canEdit ? route('admin.content.create') : null"
            actionLabel="Viết nội dung đầu tiên"
            class="mb-24">
            @if ($canEdit)
                Chưa có nội dung nào được đăng. Bắt đầu bằng một bài viết, một dịch vụ
                hoặc một dự án — trang chủ sẽ tự hiện chúng ra ngay khi bạn xuất bản.
            @else
                Nội dung đang được chuẩn bị. Mời bạn quay lại sau, hoặc liên hệ trực tiếp với chúng tôi.
            @endif
        </x-marketing.empty-state>
    @endif

    {{-- Mỗi khối dưới đây tự ẩn khi chưa có dữ liệu, nên trang chủ lớn dần
         theo nội dung người dùng đăng thay vì hiện ra các mục rỗng. --}}
    @foreach ([
        ['items' => $services, 'label' => 'Dịch vụ', 'title' => 'Chúng tôi làm gì', 'url' => route('services.index'), 'cols' => 'sm:grid-cols-2 lg:grid-cols-3'],
        ['items' => $projects, 'label' => 'Dự án', 'title' => 'Việc đã làm', 'url' => route('projects.index'), 'cols' => 'sm:grid-cols-2 lg:grid-cols-3'],
        ['items' => $products, 'label' => 'Sản phẩm', 'title' => 'Sản phẩm', 'url' => route('products.index'), 'cols' => 'sm:grid-cols-2 lg:grid-cols-3'],
        ['items' => $recentPosts, 'label' => 'Bài viết', 'title' => 'Mới nhất', 'url' => route('blog.index'), 'cols' => 'sm:grid-cols-2 lg:grid-cols-3'],
    ] as $block)
        @continue ($block['items']->isEmpty())

        <section class="py-16 sm:py-20 border-t border-border">
            <div class="flex flex-wrap items-end justify-between gap-6 mb-12">
                <div class="reveal">
                    <p class="eyebrow mb-3">{{ $block['label'] }}</p>
                    <h2 class="font-heading text-2xl sm:text-3xl lg:text-4xl font-semibold leading-tight tracking-tight text-balance">
                        {{ $block['title'] }}
                    </h2>
                </div>

                <a href="{{ $block['url'] }}" class="group reveal inline-flex items-center gap-2 font-sans text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                    Xem tất cả
                    <span class="text-primary transition-transform group-hover:translate-x-0.5" aria-hidden="true">→</span>
                </a>
            </div>

            <div class="reveal-stagger grid {{ $block['cols'] }} gap-8">
                @foreach ($block['items'] as $item)
                    <x-marketing.content-card :post="$item" />
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- Khép lại bằng lời mời liên hệ. Thông tin liên hệ lấy từ Cài đặt, mục
         nào chưa điền thì tự bỏ qua chứ không hiện nhãn rỗng. --}}
    <section class="py-20 sm:py-28 border-t border-border">
        <div class="max-w-2xl">
            <h2 class="reveal font-heading text-3xl sm:text-4xl lg:text-5xl font-semibold leading-[1.15] tracking-tight text-balance">
                Bắt đầu một cuộc trò chuyện
            </h2>

            <div class="reveal mt-9">
                <a href="{{ route('contact.show') }}" class="group inline-flex items-center gap-2.5 font-sans text-base font-semibold">
                    <span class="border-b-2 border-primary pb-1">Gửi liên hệ</span>
                    <span class="text-primary transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                </a>
            </div>

            @php
                $contactItems = array_values(array_filter([
                    env('SITE_ADDRESS'), env('SITE_CONTACT_EMAIL'), env('SITE_CONTACT_PHONE'),
                ]));
            @endphp
            @if (! empty($contactItems))
                <div class="reveal mt-12 flex flex-wrap gap-x-10 gap-y-3 text-sm text-muted-foreground">
                    @foreach ($contactItems as $item)
                        <span>{{ $item }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
