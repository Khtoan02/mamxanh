@props(['number' => null, 'label' => null, 'align' => 'left'])

{{-- Mốc mở đầu mỗi chương của mạch truyện: số chương lớn mờ + vạch kẻ +
     nhãn. Thay cho "eyebrow badge" kiểu marketing thường thấy. --}}
<div {{ $attributes->merge(['class' => 'reveal mb-8 '.($align === 'center' ? 'text-center' : '')]) }}>
    @if ($number)
        <p class="chapter-number">{{ $number }}</p>
    @endif
    <div class="chapter-rule mt-4 mb-5 {{ $align === 'center' ? 'mx-auto w-24' : 'w-20' }}"></div>
    @if ($label)
        <p class="font-sans text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-muted-foreground mb-3">{{ $label }}</p>
    @endif
    <h2 class="font-heading text-3xl sm:text-4xl lg:text-5xl font-semibold leading-[1.15] tracking-tight text-balance {{ $align === 'center' ? 'mx-auto max-w-3xl' : 'max-w-2xl' }}">
        {{ $slot }}
    </h2>
</div>
