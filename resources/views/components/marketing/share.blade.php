@props(['title', 'url' => null])

@php
    $shareUrl = $url ?: url()->current();
    $encodedUrl = rawurlencode($shareUrl);
    $encodedTitle = rawurlencode($title);

    // Chỉ những mạng có endpoint chia sẻ công khai, không cần SDK/JS của
    // bên thứ ba — nhúng SDK Facebook/X chỉ để hiện 1 nút chia sẻ là đánh
    // đổi tốc độ tải và quyền riêng tư của mọi người đọc lấy một tiện ích
    // rất ít người dùng.
    $targets = [
        ['icon' => 'facebook', 'label' => 'Chia sẻ lên Facebook', 'href' => "https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}"],
        ['icon' => 'message-circle', 'label' => 'Chia sẻ lên X', 'href' => "https://twitter.com/intent/tweet?url={$encodedUrl}&text={$encodedTitle}"],
        ['icon' => 'link', 'label' => 'Chia sẻ lên LinkedIn', 'href' => "https://www.linkedin.com/sharing/share-offsite/?url={$encodedUrl}"],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <span class="eyebrow">Chia sẻ</span>

    @foreach ($targets as $target)
        <a href="{{ $target['href'] }}" target="_blank" rel="noopener noreferrer nofollow"
           aria-label="{{ $target['label'] }}" title="{{ $target['label'] }}"
           class="flex h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-primary hover:border-primary transition-colors">
            <x-icon :name="$target['icon']" class="h-4 w-4" />
        </a>
    @endforeach

    <button type="button" id="share-copy-link" data-url="{{ $shareUrl }}"
            aria-label="Sao chép liên kết" title="Sao chép liên kết"
            class="flex h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-primary hover:border-primary transition-colors">
        <x-icon name="copy" class="h-4 w-4" />
    </button>
    <span id="share-copy-status" role="status" class="text-xs text-primary opacity-0 transition-opacity">Đã sao chép</span>
</div>

@once
    @push('scripts')
        <script>
            // getElementById chứ không phải querySelector chung chung: trang
            // này có nhiều form/nút, một selector rộng từng khiến script bấm
            // nhầm nút khác (đã xảy ra thật trong dự án này).
            document.getElementById('share-copy-link')?.addEventListener('click', async function () {
                const status = document.getElementById('share-copy-status');
                try {
                    // navigator.clipboard chỉ tồn tại trên HTTPS/localhost;
                    // http trên IP nội bộ sẽ rơi xuống nhánh catch.
                    await navigator.clipboard.writeText(this.dataset.url);
                    if (status) {
                        status.textContent = 'Đã sao chép';
                        status.style.opacity = '1';
                        setTimeout(() => { status.style.opacity = '0'; }, 2000);
                    }
                } catch (e) {
                    if (status) {
                        status.textContent = 'Không sao chép được — hãy copy từ thanh địa chỉ';
                        status.style.opacity = '1';
                        setTimeout(() => { status.style.opacity = '0'; }, 3500);
                    }
                }
            });
        </script>
    @endpush
@endonce
