@props(['items'])

{{-- Cùng một mảng $items sinh ra cả khối Q&A nhìn thấy được lẫn FAQPage
     JSON-LD bên dưới — không bao giờ phát schema cho câu hỏi mà khách
     không thật sự nhìn thấy trên trang.
     Dùng <x-marketing.faq-item> (thẻ <details> gốc) để giống hệt khối FAQ
     của trang chủ: cùng một ngôn ngữ thị giác, không JS, không layout shift. --}}
<section class="mt-16 pt-10 border-t border-border">
    <h2 class="font-heading text-2xl sm:text-3xl font-semibold tracking-tight mb-2">Câu hỏi thường gặp</h2>
    <div class="mt-8">
        @foreach ($items as $item)
            <x-marketing.faq-item :question="$item['q']">{{ $item['a'] }}</x-marketing.faq-item>
        @endforeach
        <div class="border-t border-border"></div>
    </div>
</section>

@push('schema')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($items)->map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ])->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
