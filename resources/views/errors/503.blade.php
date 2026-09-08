<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website đang bảo trì — {{ config('app.name') }}</title>
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
        <p class="chapter-number leading-none">503</p>
        <div class="chapter-rule mx-auto mt-5 mb-7 w-20"></div>

        <h1 class="font-heading text-3xl sm:text-4xl font-semibold leading-tight tracking-tight text-balance">Website đang bảo trì</h1>
        <p class="mt-5 text-muted-foreground leading-relaxed">Chúng tôi đang nâng cấp và sẽ quay lại trong thời gian ngắn nhất. Cảm ơn bạn đã kiên nhẫn.</p>

        <div class="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-4">
        </div>
    </main>
</body>
</html>
