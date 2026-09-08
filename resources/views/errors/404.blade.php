<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Không tìm thấy trang — {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    {{-- Trang lỗi cố tình KHÔNG dùng theme::layout và KHÔNG truy vấn DB:
         nó phải tự hiển thị được ngay cả khi chính DB hoặc theme là thứ
         đang hỏng. Đó cũng là lý do dùng .theme-public thủ công ở <body>
         thay vì mượn layout. --}}
    @vite(['resources/css/app.css'])
</head>
<body class="theme-public min-h-screen bg-background text-foreground flex flex-col items-center justify-center px-6 py-20">
    <main class="w-full max-w-lg text-center">
        <p class="chapter-number leading-none">404</p>
        <div class="chapter-rule mx-auto mt-5 mb-7 w-20"></div>

        <h1 class="font-heading text-3xl sm:text-4xl font-semibold leading-tight tracking-tight text-balance">Không tìm thấy trang</h1>
        <p class="mt-5 text-muted-foreground leading-relaxed">Trang bạn tìm không tồn tại, hoặc đã được đổi địa chỉ. Bạn có thể thử tìm lại bằng từ khoá.</p>

        <div class="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-4">
            <a href="{{ url('/tim-kiem') }}" class="group inline-flex items-center gap-2.5 font-sans font-semibold">
                <span class="border-b-2 border-primary pb-1">Tìm nội dung khác</span>
                <span class="text-primary transition-transform group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
            </a>
            <a href="{{ url('/') }}" class="font-sans text-sm text-muted-foreground hover:text-foreground transition-colors">Về trang chủ</a>
            <a href="{{ url('/lien-he') }}" class="font-sans text-sm text-muted-foreground hover:text-foreground transition-colors">Liên hệ</a>
        </div>
    </main>
</body>
</html>
