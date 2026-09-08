@extends('theme::layout')

@php
    $breadcrumbs = [
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Liên hệ'],
    ];

    $channels = array_values(array_filter([
        env('SITE_CONTACT_EMAIL') ? ['icon' => 'mail', 'label' => 'Email', 'value' => env('SITE_CONTACT_EMAIL'), 'href' => 'mailto:'.env('SITE_CONTACT_EMAIL')] : null,
        env('SITE_CONTACT_PHONE') ? ['icon' => 'message-circle', 'label' => 'Điện thoại', 'value' => env('SITE_CONTACT_PHONE'), 'href' => 'tel:'.preg_replace('/\s+/', '', (string) env('SITE_CONTACT_PHONE'))] : null,
        env('SITE_ADDRESS') ? ['icon' => 'map-pin', 'label' => 'Địa chỉ', 'value' => env('SITE_ADDRESS'), 'href' => null] : null,
    ]));
@endphp

@section('title', \App\Support\SeoTemplate::render('Liên hệ %sep% %sitename%'))
@section('description', \App\Support\SeoTemplate::render('Liên hệ với %sitename%'))

@push('schema')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'url' => url()->current(),
            'about' => ['@id' => url('/').'#organization'],
            // Chỉ phát ContactPoint khi thật sự có số điện thoại đã cấu hình.
            'mainEntity' => env('SITE_CONTACT_PHONE') ? array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'telephone' => env('SITE_CONTACT_PHONE'),
                'email' => env('SITE_CONTACT_EMAIL') ?: null,
                'areaServed' => 'VN',
                'availableLanguage' => 'Vietnamese',
            ]) : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-marketing.page-hero
        :breadcrumbs="$breadcrumbs"
        eyebrow="Liên hệ"
        subtitle="Không có kịch bản bán hàng. Bạn kể bài toán, chúng tôi nói thật là có làm được không.">
        Bắt đầu bằng một cuộc trò chuyện
    </x-marketing.page-hero>

    <div class="mt-14 pb-20 sm:pb-28 grid lg:grid-cols-12 gap-12 lg:gap-16">
        <div class="lg:col-span-7 reveal">
            @if (session('status'))
                <x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert>
            @endif
            @if ($errors->any())
                <x-alert variant="destructive" class="mb-6">{{ $errors->first() }}</x-alert>
            @endif

            <form id="contact-form" method="POST" action="{{ route('contact.store') }}" class="space-y-6">
                @csrf

                {{-- Bẫy bot: ẩn bằng cả CSS lẫn aria-hidden/tabindex để trình
                     đọc màn hình và bàn phím đều bỏ qua. KHÔNG dùng
                     type="hidden" vì bot cũng bỏ qua field ẩn kiểu đó. --}}
                <div class="absolute left-[-9999px] top-0 h-0 w-0 overflow-hidden" aria-hidden="true">
                    <label for="contact-website">Đừng điền ô này</label>
                    <input type="text" id="contact-website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <x-label for="name">Họ tên</x-label>
                    <x-input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name" />
                </div>

                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <x-label for="email">Email</x-label>
                        <x-input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" />
                    </div>
                    <div>
                        <x-label for="phone">Điện thoại <span class="font-normal text-muted-foreground">(không bắt buộc)</span></x-label>
                        <x-input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" />
                    </div>
                </div>

                <div>
                    <x-label for="message">Bạn đang cần gì?</x-label>
                    <textarea id="message" name="message" rows="6" required maxlength="5000"
                              placeholder="Ví dụ: mình cần làm lại website công ty, hiện đang chạy trên nền tảng cũ và tải rất chậm…"
                              class="w-full rounded-lg border border-input bg-background px-3.5 py-3 text-sm text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-ring">{{ old('message') }}</textarea>
                    <p class="mt-2 text-xs text-muted-foreground">Càng cụ thể, phản hồi của chúng tôi càng cụ thể.</p>
                </div>

                <button type="submit" class="group inline-flex items-center gap-3 font-sans font-semibold">
                    <span class="border-b-2 border-primary pb-1">Gửi liên hệ</span>
                    <span class="text-primary transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                </button>
            </form>
        </div>

        <aside class="lg:col-span-5 reveal">
            @if ($channels)
                <p class="eyebrow mb-6">Hoặc liên hệ trực tiếp</p>
                <ul class="space-y-7">
                    @foreach ($channels as $channel)
                        <li class="flex items-start gap-4">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <x-icon :name="$channel['icon']" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="eyebrow mb-1">{{ $channel['label'] }}</p>
                                @if ($channel['href'])
                                    <a href="{{ $channel['href'] }}" class="font-heading text-lg hover:text-primary transition-colors break-words">{{ $channel['value'] }}</a>
                                @else
                                    <p class="font-heading text-lg leading-snug">{{ $channel['value'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-10 pt-8 border-t border-border">
                <p class="eyebrow mb-3">Chúng tôi trả lời thế nào</p>
                <p class="text-sm text-muted-foreground leading-relaxed">
                    Mọi liên hệ đều được một người thật đọc. Nếu việc bạn cần nằm ngoài khả năng của chúng tôi,
                    chúng tôi sẽ nói rõ điều đó thay vì hẹn gặp để thuyết phục bạn.
                </p>
            </div>
        </aside>
    </div>
@endsection
